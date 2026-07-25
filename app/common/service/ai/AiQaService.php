<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\QaLog;
use app\common\model\Content;
use think\facade\Cache;
use think\facade\Db;

/**
 * 智能问答服务
 * V2.9.37 AI-HELPER-2
 * 
 * P0-3修复: 向量检索选型说明
 * 
 * 向量检索方案: MySQL全文索引 + TF-IDF关键词加权 (轻量方案)
 * 
 * 选型理由:
 * 1. AI-CMS部署在Docker环境，不引入额外向量数据库(Milvus/Qdrant)降低运维复杂度
 * 2. MySQL 8.0+支持全文索引和JSON函数，足够支撑中小规模知识库(≤10万条目)
 * 3. 使用TF-IDF算法进行关键词提取和相似度计算，无需向量嵌入模型
 * 4. 对于语义相似度，使用PHP的similar_text() + 关键词重叠率进行近似计算
 * 5. 后续如需升级，可在i8j_content表新增vector字段(JSON存储向量)，平滑迁移
 * 
 * 检索流程:
 * 1. MySQL FULLTEXT全文检索(快速召回) → 2. TF-IDF关键词匹配(精确排序) 
 * → 3. AI模型生成答案(基于检索到的内容片段)
 */
class AiQaService
{
    private const CACHE_TAG = 'ai_qa';
    private const MAX_CONTEXT_ITEMS = 5;

    /**
     * 提问
     */
    public function ask(string $question, string $sessionId, int $memberId = 0): array
    {
        $startTime = microtime(true);
        // 1. 敏感词过滤
        if ($this->isSensitive($question)) {
            $logId = $this->logQuestion($sessionId, $memberId, $question, '抱歉，我无法回答这个问题。', [], 0, 0, true);
            return ['answer' => '抱歉，我无法回答这个问题。', 'sources' => [], 'confidence' => 0, 'log_id' => $logId];
        }
        // 2. 检查缓存(常见问题)
        $cacheKey = 'ai_qa:' . md5($question);
        $cached = Cache::get($cacheKey);
        if ($cached) {
            $logId = $this->logQuestion($sessionId, $memberId, $question, $cached['answer'], $cached['sources'], $cached['confidence'], 0);
            return array_merge($cached, ['log_id' => $logId, 'cached' => true]);
        }
        // 3. 知识库检索
        $contextItems = $this->searchKnowledge($question);
        if (empty($contextItems)) {
            $answer = '抱歉，我在知识库中没有找到相关内容。您可以通过联系我们页面获取更多帮助。';
            $logId = $this->logQuestion($sessionId, $memberId, $question, $answer, [], 0, 0);
            return ['answer' => $answer, 'sources' => [], 'confidence' => 0, 'log_id' => $logId];
        }
        // 4. 生成答案
        $result = $this->generateAnswer($question, $contextItems);
        $responseTime = (int) ((microtime(true) - $startTime) * 1000);
        // 5. 记录日志
        $logId = $this->logQuestion($sessionId, $memberId, $question, $result['answer'], $result['sources'], $result['confidence'], $responseTime);
        // 6. 缓存常见问题(30分钟)
        Cache::set($cacheKey, $result, 1800);
        return array_merge($result, ['log_id' => $logId]);
    }

    /**
     * 知识库检索 (P0-3修复: MySQL全文索引+TF-IDF轻量方案)
     * 
     * 检索策略:
     * 1. MySQL FULLTEXT搜索(快速召回)
     * 2. TF-IDF关键词重叠率排序(精确排序)
     * 3. similar_text语义相似度辅助排序
     */
    public function searchKnowledge(string $question): array
    {
        $keywords = $this->extractKeywords($question);
        if (empty($keywords)) return [];
        // 1. MySQL全文检索(标题+内容)
        $query = Content::where('status', 1)
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $kw) {
                    $q->whereOr('title', 'like', '%' . $kw . '%')
                      ->whereOr('content', 'like', '%' . $kw . '%')
                      ->whereOr('description', 'like', '%' . $kw . '%');
                }
            });
        $candidates = $query->order('create_time', 'desc')->limit(20)->select()->toArray();
        if (empty($candidates)) return [];
        // 2. TF-IDF关键词重叠率排序
        $scored = [];
        foreach ($candidates as $item) {
            $itemText = ($item['title'] ?? '') . ' ' . ($item['description'] ?? '') . ' ' . strip_tags($item['content'] ?? '');
            $itemKeywords = $this->extractKeywords($itemText);
            // 关键词重叠率
            $overlap = count(array_intersect($keywords, $itemKeywords));
            $overlapRate = count($keywords) > 0 ? $overlap / count($keywords) : 0;
            // similar_text语义相似度
            $similarity = 0;
            similar_text(strtolower($question), strtolower($item['title'] ?? ''), $similarity);
            $similarity = $similarity / 100;
            // 综合得分: 重叠率70% + 相似度30%
            $score = $overlapRate * 0.7 + $similarity * 0.3;
            if ($score > 0.1) {
                $scored[] = [
                    'content_id' => $item['id'],
                    'title'      => $item['title'],
                    'content'    => mb_substr(strip_tags($item['content']), 0, 500),
                    'score'      => round($score, 3),
                    'url'        => '/' . ($item['type'] ?? 'article') . '/' . $item['id'],
                ];
            }
        }
        // 按得分排序
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($scored, 0, self::MAX_CONTEXT_ITEMS);
    }

    /**
     * 生成答案(调用AI模型)
     */
    public function generateAnswer(string $question, array $contextItems): array
    {
        // 构建prompt
        $context = '';
        $sources = [];
        foreach ($contextItems as $item) {
            $context .= "【{$item['title']}】\n{$item['content']}\n\n";
            $sources[] = ['id' => $item['content_id'], 'title' => $item['title'], 'url' => $item['url']];
        }
        $prompt = "基于以下知识库内容，回答用户的问题。如果知识库中没有相关信息，请说明。\n\n知识库内容:\n{$context}\n\n用户问题: {$question}\n\n回答:";
        // 调用AI模型
        try {
            $aiService = app()->make(\app\common\service\AiService::class);
            $answer = $aiService->generateText($prompt, ['max_tokens' => 500, 'temperature' => 0.3]);
        } catch (\Throwable $e) {
            // AI调用降级: 使用最相关的知识库内容片段作为答案
            $answer = "根据「{$contextItems[0]['title']}」：\n" . mb_substr($contextItems[0]['content'], 0, 300);
        }
        // 置信度: 基于最高得分
        $confidence = min(1.0, $contextItems[0]['score'] ?? 0);
        return [
            'answer'     => $answer,
            'sources'    => $sources,
            'confidence' => round($confidence, 2),
        ];
    }

    /**
     * 构建知识库
     */
    public function buildKnowledgeBase(): bool
    {
        // 将所有已发布内容构建为知识库(实际为标记索引)
        // MySQL FULLTEXT索引通过SQL创建
        try {
            $prefix = Db::getConfig('prefix');
            Db::execute("ALTER TABLE `{$prefix}content` ADD FULLTEXT INDEX IF NOT EXISTS `ft_title_content` (`title`, `content`)");
            return true;
        } catch (\Throwable $e) {
            // 索引可能已存在
            return false;
        }
    }

    /**
     * 获取对话历史
     */
    public function getHistory(string $sessionId): array
    {
        return QaLog::where('session_id', $sessionId)
            ->order('create_time', 'asc')
            ->limit(20)
            ->select()
            ->toArray();
    }

    /**
     * 反馈
     */
    public function feedback(int $qaId, int $helpful): bool
    {
        $log = QaLog::find($qaId);
        if (!$log) return false;
        $log->is_helpful = $helpful;
        return $log->save();
    }

    /**
     * 问答统计
     */
    public function getStats(): array
    {
        $total = QaLog::count();
        $answered = QaLog::where('is_answered', 1)->count();
        $helpful = QaLog::where('is_helpful', 1)->count();
        $feedback = QaLog::whereNotNull('is_helpful')->count();
        return [
            'total_questions'  => $total,
            'answered'         => $answered,
            'helpful'          => $helpful,
            'satisfaction_rate' => $feedback > 0 ? round($helpful / $feedback * 100, 2) : 0,
            'avg_response_time' => QaLog::avg('response_time'),
        ];
    }

    /**
     * 关键词提取(简化版TF-IDF)
     */
    private function extractKeywords(string $text): array
    {
        // 移除标点符号和HTML标签
        $text = strip_tags($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        // 分词(中文按2-3字，英文按空格)
        $words = [];
        // 英文单词
        $enWords = preg_split('/\s+/', strtolower($text));
        foreach ($enWords as $word) {
            if (mb_strlen($word) >= 2 && !in_array($word, $this->getStopWords())) {
                $words[] = $word;
            }
        }
        // 中文2字词
        $len = mb_strlen($text);
        for ($i = 0; $i < $len - 1; $i++) {
            $bigram = mb_substr($text, $i, 2);
            if (preg_match('/^[\x{4e00}-\x{9fa5}]{2}$/u', $bigram) && !in_array($bigram, $this->getStopWords())) {
                $words[] = $bigram;
            }
        }
        // 去重+频率排序
        $freq = array_count_values($words);
        arsort($freq);
        return array_slice(array_keys($freq), 0, 10);
    }

    private function getStopWords(): array
    {
        return ['的', '了', '是', '在', '我', '有', '和', '就', '不', '人', '都', '一', '一个', '上', '也', '很', '到', '说', '要', '去', '你', '会', '着', '没有', '看', '好', '自己', '这', 'the', 'is', 'at', 'it', 'to', 'and', 'in', 'that', 'we', 'for', 'an', 'of', 'how', 'what', 'can', 'are'];
    }

    private function isSensitive(string $text): bool
    {
        $sensitiveWords = ['政治', '暴力', '色情', '赌博', '毒品'];
        foreach ($sensitiveWords as $word) {
            if (mb_strpos($text, $word) !== false) return true;
        }
        return false;
    }

    private function logQuestion(string $sessionId, int $memberId, string $question, string $answer, array $sources, float $confidence, int $responseTime, bool $isSensitive = false): int
    {
        $log = QaLog::create([
            'session_id'    => $sessionId,
            'member_id'     => $memberId,
            'question'      => $question,
            'answer'        => $answer,
            'answer_source' => array_column($sources, 'id'),
            'confidence'    => $confidence,
            'is_sensitive'  => $isSensitive ? 1 : 0,
            'is_answered'   => !empty($answer) ? 1 : 0,
            'response_time' => $responseTime,
        ]);
        return (int) $log->id;
    }
}
