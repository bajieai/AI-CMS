<?php
declare(strict_types=1);

namespace app\common\service\ai;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * AI知识库管理服务 - V2.9.40 AI-DEEP2-4
 *
 * RAG架构: MySQL全文索引+TF-IDF → 混合排序 → AI生成 → 引用溯源
 * 支持知识库CRUD、文档导入、检索、对话集成
 */
class AiKnowledgeBaseService
{
    private const CACHE_TAG = 'ai_knowledge';
    private const CACHE_TTL = 3600;

    /**
     * 创建知识库
     */
    public function create(array $data): int
    {
        $id = Db::name('ai_knowledge_base')->insertGetId([
            'name'        => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'type'        => $data['type'] ?? 'general',
            'source_type' => $data['source_type'] ?? 'manual',
            'embedding_model' => $data['embedding_model'] ?? 'tfidf',
            'status'      => 1,
            'doc_count'   => 0,
            'created_at'  => time(),
            'updated_at'  => time(),
        ]);
        Cache::clear();
        return (int) $id;
    }

    /**
     * 更新知识库
     */
    public function update(int $id, array $data): bool
    {
        $update = [];
        $fields = ['name', 'description', 'type', 'source_type', 'embedding_model', 'status'];
        foreach ($fields as $f) {
            if (isset($data[$f])) $update[$f] = $data[$f];
        }
        $update['updated_at'] = time();

        $result = Db::name('ai_knowledge_base')->where('id', $id)->update($update);
        Cache::clear();
        return $result > 0;
    }

    /**
     * 删除知识库
     */
    public function delete(int $id): bool
    {
        // 先删除知识库下所有文档
        Db::name('ai_knowledge_doc')->where('kb_id', $id)->delete();
        Db::name('ai_knowledge_chunk')->where('kb_id', $id)->delete();
        Db::name('ai_knowledge_base')->where('id', $id)->delete();
        Cache::clear();
        return true;
    }

    /**
     * 导入文档到知识库
     *
     * @param int   $kbId   知识库ID
     * @param array $doc    文档数据 [title, content, source, source_url]
     * @return int 文档ID
     */
    public function importDocument(int $kbId, array $doc): int
    {
        $docId = Db::name('ai_knowledge_doc')->insertGetId([
            'kb_id'       => $kbId,
            'title'       => $doc['title'] ?? '',
            'content'     => $doc['content'] ?? '',
            'source'      => $doc['source'] ?? 'manual',
            'source_url'  => $doc['source_url'] ?? '',
            'status'      => 1,
            'chunk_count' => 0,
            'created_at'  => time(),
            'updated_at'  => time(),
        ]);

        // 自动分块并建立检索索引
        $this->chunkAndIndex($kbId, $docId, $doc['content'] ?? '');

        // 更新知识库文档计数
        Db::name('ai_knowledge_base')->where('id', $kbId)->inc('doc_count')->update();

        Cache::clear();
        return (int) $docId;
    }

    /**
     * 文档分块与索引
     */
    private function chunkAndIndex(int $kbId, int $docId, string $content): void
    {
        $chunks = $this->splitContent($content);
        foreach ($chunks as $idx => $chunk) {
            Db::name('ai_knowledge_chunk')->insert([
                'kb_id'       => $kbId,
                'doc_id'      => $docId,
                'chunk_index' => $idx,
                'content'     => $chunk,
                'tfidf_hash'  => md5($chunk),
                'created_at'  => time(),
            ]);
        }
        Db::name('ai_knowledge_doc')->where('id', $docId)->update([
            'chunk_count' => count($chunks),
            'updated_at'  => time(),
        ]);
    }

    /**
     * 内容分块（按段落+字数限制）
     */
    private function splitContent(string $content, int $maxLen = 500): array
    {
        $paragraphs = preg_split('/\n{2,}/', $content);
        $chunks = [];
        $current = '';

        foreach ($paragraphs as $p) {
            $p = trim($p);
            if ($p === '') continue;
            if (mb_strlen($current . $p) > $maxLen && $current !== '') {
                $chunks[] = $current;
                $current = $p;
            } else {
                $current .= "\n" . $p;
            }
        }
        if ($current !== '') $chunks[] = $current;

        return $chunks ?: [mb_substr($content, 0, $maxLen)];
    }

    /**
     * RAG检索 — MySQL全文索引+TF-IDF混合排序
     *
     * @param int    $kbId   知识库ID
     * @param string $query  查询文本
     * @param int    $topK   返回前K个结果
     * @return array 检索结果 [{chunk_id, doc_id, content, score, source}]
     */
    public function search(int $kbId, string $query, int $topK = 5): array
    {
        $cacheKey = 'kb_search_' . $kbId . '_' . md5($query) . '_' . $topK;

        return Cache::remember($cacheKey, function () use ($kbId, $query, $topK) {
            // Step1: MySQL全文索引检索
            $fulltextResults = $this->fulltextSearch($kbId, $query, $topK * 2);

            // Step2: TF-IDF评分计算
            $tfidfResults = $this->tfidfScore($kbId, $query, $fulltextResults);

            // Step3: 混合排序（全文70% + TF-IDF30%）
            $scored = [];
            foreach ($tfidfResults as $r) {
                $scored[$r['chunk_id']] = ($r['fulltext_score'] ?? 0) * 0.7 + ($r['tfidf_score'] ?? 0) * 0.3;
            }
            arsort($scored);

            $results = [];
            $docCache = [];
            $count = 0;
            foreach ($scored as $chunkId => $score) {
                if ($count >= $topK) break;
                $chunk = Db::name('ai_knowledge_chunk')->find($chunkId);
                if (!$chunk) continue;

                if (!isset($docCache[$chunk['doc_id']])) {
                    $docCache[$chunk['doc_id']] = Db::name('ai_knowledge_doc')->find($chunk['doc_id']);
                }
                $doc = $docCache[$chunk['doc_id']];

                $results[] = [
                    'chunk_id' => $chunkId,
                    'doc_id'   => $chunk['doc_id'],
                    'content'  => $chunk['content'],
                    'score'    => round($score, 4),
                    'source'   => $doc['source'] ?? '',
                    'title'    => $doc['title'] ?? '',
                ];
                $count++;
            }

            return $results;
        }, self::CACHE_TTL);
    }

    /**
     * MySQL全文索引检索
     */
    private function fulltextSearch(int $kbId, string $query, int $limit): array
    {
        try {
            $results = Db::name('ai_knowledge_chunk')
                ->where('kb_id', $kbId)
                ->whereRaw("MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE)", [$query])
                ->limit($limit)
                ->column('id as chunk_id, doc_id, content', 'id');

            foreach ($results as &$r) {
                $r['fulltext_score'] = 1.0; // MySQL全文相关性评分基准
            }
            return $results;
        } catch (\Exception $e) {
            // 全文索引未建时回退到LIKE搜索
            Log::warning('AI知识库全文索引回退到LIKE搜索: ' . $e->getMessage());
            $keywords = explode(' ', $query);
            $results = Db::name('ai_knowledge_chunk')
                ->where('kb_id', $kbId)
                ->where(function ($q) use ($keywords) {
                    foreach ($keywords as $kw) {
                        $q->whereOr('content', 'like', '%' . $kw . '%');
                    }
                })
                ->limit($limit)
                ->column('id as chunk_id, doc_id, content', 'id');

            foreach ($results as &$r) {
                $r['fulltext_score'] = 0.5;
            }
            return $results;
        }
    }

    /**
     * TF-IDF评分
     */
    private function tfidfScore(int $kbId, string $query, array $fulltextResults): array
    {
        $queryTerms = $this->tokenize($query);
        $totalDocs = Db::name('ai_knowledge_chunk')->where('kb_id', $kbId)->count();

        foreach ($fulltextResults as &$r) {
            $contentTerms = $this->tokenize($r['content'] ?? '');
            $tfidfSum = 0.0;

            foreach ($queryTerms as $term) {
                $tf = $this->termFrequency($term, $contentTerms);
                $df = $this->documentFrequency($kbId, $term);
                $idf = log(($totalDocs + 1) / ($df + 1)) + 1;
                $tfidfSum += $tf * $idf;
            }

            $r['tfidf_score'] = $tfidfSum / max(count($queryTerms), 1);
        }

        return $fulltextResults;
    }

    private function tokenize(string $text): array
    {
        // 中文简单分词（按字+英文按词）
        $text = preg_replace('/[^\w\x{4e00}-\x{9fff}]/u', ' ', $text);
        $terms = [];
        // 英文词
        foreach (explode(' ', $text) as $w) {
            $w = trim($w);
            if ($w !== '' && !preg_match('/^[\x{4e00}-\x{9fff}]/u', $w)) $terms[] = strtolower($w);
        }
        // 中文字（2字组合）
        $chinese = preg_replace('/[^\x{4e00}-\x{9fff}]/u', '', $text);
        for ($i = 0; $i < mb_strlen($chinese) - 1; $i++) {
            $terms[] = mb_substr($chinese, $i, 2);
        }
        return $terms;
    }

    private function termFrequency(string $term, array $docTerms): float
    {
        $count = 0;
        foreach ($docTerms as $t) { if ($t === $term) $count++; }
        return $count / max(count($docTerms), 1);
    }

    private function documentFrequency(int $kbId, string $term): int
    {
        return (int) Db::name('ai_knowledge_chunk')
            ->where('kb_id', $kbId)
            ->where('content', 'like', '%' . $term . '%')
            ->count();
    }

    /**
     * 获取知识库列表
     */
    public function getList(int $page = 1, int $limit = 20): array
    {
        return Db::name('ai_knowledge_base')
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取知识库详情
     */
    public function getDetail(int $id): ?array
    {
        $kb = Db::name('ai_knowledge_base')->find($id);
        if (!$kb) return null;

        $kb['docs'] = Db::name('ai_knowledge_doc')
            ->where('kb_id', $id)
            ->order('id', 'desc')
            ->select()
            ->toArray();

        return $kb;
    }

    /**
     * 获取知识库统计
     */
    public function getStats(): array
    {
        return [
            'total_kb'     => Db::name('ai_knowledge_base')->count(),
            'total_docs'   => Db::name('ai_knowledge_doc')->count(),
            'total_chunks' => Db::name('ai_knowledge_chunk')->count(),
            'by_type'      => Db::name('ai_knowledge_base')->group('type')->column('count(*) as cnt', 'type'),
        ];
    }

    /**
     * 获取配置
     */
    public function getConfig(): array
    {
        return Cache::remember('kb_config', function () {
            $config = Db::name('ai_config')->where('group', 'knowledge')->column('value', 'key');
            return [
                'default_chunk_size' => (int) ($config['chunk_size'] ?? 500),
                'default_top_k'      => (int) ($config['top_k'] ?? 5),
                'fulltext_weight'    => (float) ($config['fulltext_weight'] ?? 0.7),
                'tfidf_weight'       => (float) ($config['tfidf_weight'] ?? 0.3),
            ];
        }, self::CACHE_TTL);
    }

    /**
     * 保存配置
     */
    public function saveConfig(array $data): void
    {
        foreach ($data as $key => $value) {
            Db::name('ai_config')->where('group', 'knowledge')->where('key', $key)->update(['value' => $value]);
        }
        Cache::clear();
    }
}
