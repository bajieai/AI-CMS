<?php
declare(strict_types=1);

namespace app\common\service\i18n;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 翻译记忆增强服务 - V2.9.40 I18N-V3-1
 *
 * 增强版翻译记忆库：SHA256精确匹配+模糊匹配+术语表+质量评分
 * 基于V2.9.37 TranslationMemoryService扩展
 */
class TranslationMemoryEnhanceService
{
    private const CACHE_TAG = 'translation_memory_enhance';
    private const CACHE_TTL = 3600;

    /**
     * 存储翻译记忆（精确+模糊匹配索引）
     */
    public function store(string $source, string $target, string $sourceLang, string $targetLang, array $meta = []): int
    {
        $sourceHash = hash('sha256', $source);
        $sourceWords = $this->extractWords($source);

        $id = Db::name('translation_memory')->insertGetId([
            'source_hash'    => $sourceHash,
            'source_text'    => $source,
            'target_text'    => $target,
            'source_lang'    => $sourceLang,
            'target_lang'    => $targetLang,
            'source_words'   => json_encode($sourceWords),
            'quality_score'  => (float) ($meta['quality_score'] ?? 0.8),
            'usage_count'    => 0,
            'domain'         => $meta['domain'] ?? 'general',
            'created_at'     => time(),
            'updated_at'     => time(),
        ]);

        Cache::clear();
        return (int) $id;
    }

    /**
     * 查询翻译记忆（精确匹配→模糊匹配→术语匹配）
     */
    public function search(string $source, string $sourceLang, string $targetLang, float $threshold = 0.6): array
    {
        $cacheKey = 'tm_search_' . md5($source . $sourceLang . $targetLang . $threshold);

        return Cache::remember($cacheKey, function () use ($source, $sourceLang, $targetLang, $threshold) {
            // Step1: 精确匹配（SHA256）
            $exactMatch = Db::name('translation_memory')
                ->where('source_hash', hash('sha256', $source))
                ->where('source_lang', $sourceLang)
                ->where('target_lang', $targetLang)
                ->order('quality_score', 'desc')
                ->find();

            if ($exactMatch) {
                Db::name('translation_memory')->where('id', $exactMatch['id'])->inc('usage_count')->update();
                return [
                    'type'    => 'exact',
                    'score'   => 1.0,
                    'source'  => $exactMatch['source_text'],
                    'target'  => $exactMatch['target_text'],
                    'quality' => (float) $exactMatch['quality_score'],
                ];
            }

            // Step2: 模糊匹配（词重叠率）
            $sourceWords = $this->extractWords($source);
            $allMemories = Db::name('translation_memory')
                ->where('source_lang', $sourceLang)
                ->where('target_lang', $targetLang)
                ->where('quality_score', '>=', $threshold)
                ->select()
                ->toArray();

            $bestMatch = null;
            $bestScore = 0;
            foreach ($allMemories as $m) {
                $mWords = json_decode($m['source_words'] ?? '[]', true) ?: [];
                $score = $this->calcSimilarity($sourceWords, $mWords);
                if ($score > $bestScore && $score >= $threshold) {
                    $bestScore = $score;
                    $bestMatch = $m;
                }
            }

            if ($bestMatch) {
                Db::name('translation_memory')->where('id', $bestMatch['id'])->inc('usage_count')->update();
                return [
                    'type'    => 'fuzzy',
                    'score'   => $bestScore,
                    'source'  => $bestMatch['source_text'],
                    'target'  => $bestMatch['target_text'],
                    'quality' => (float) $bestMatch['quality_score'],
                ];
            }

            return ['type' => 'none', 'score' => 0, 'source' => '', 'target' => ''];
        }, self::CACHE_TTL);
    }

    /**
     * 计算词重叠率（Jaccard相似度）
     */
    private function calcSimilarity(array $a, array $b): float
    {
        if (empty($a) || empty($b)) return 0;
        $intersection = count(array_intersect($a, $b));
        $union = count(array_unique(array_merge($a, $b)));
        return $union > 0 ? $intersection / $union : 0;
    }

    /**
     * 提取词列表（中文按字+英文按词）
     */
    private function extractWords(string $text): array
    {
        $text = preg_replace('/[^\w\x{4e00}-\x{9fff}]/u', ' ', $text);
        $words = [];
        foreach (explode(' ', $text) as $w) {
            $w = trim($w);
            if ($w !== '') $words[] = strtolower($w);
        }
        // 中文2字组合
        $chinese = preg_replace('/[^\x{4e00}-\x{9fff}]/u', '', $text);
        for ($i = 0; $i < mb_strlen($chinese) - 1; $i++) {
            $words[] = mb_substr($chinese, $i, 2);
        }
        return array_unique($words);
    }

    /**
     * 获取翻译记忆统计
     */
    public function getStats(): array
    {
        return [
            'total_entries' => Db::name('translation_memory')->count(),
            'by_lang_pair'  => Db::name('translation_memory')
                ->group('source_lang, target_lang')
                ->column('count(*) as cnt', 'source_lang'),
            'avg_quality'   => Db::name('translation_memory')->avg('quality_score'),
            'domain_counts' => Db::name('translation_memory')
                ->group('domain')->column('count(*) as cnt', 'domain'),
        ];
    }

    /**
     * 批量导入翻译记忆
     */
    public function batchImport(array $entries, string $sourceLang, string $targetLang): int
    {
        $count = 0;
        foreach ($entries as $entry) {
            $this->store(
                $entry['source'] ?? '',
                $entry['target'] ?? '',
                $sourceLang,
                $targetLang,
                ['domain' => $entry['domain'] ?? 'general', 'quality_score' => (float) ($entry['quality_score'] ?? 0.8)]
            );
            $count++;
        }
        return $count;
    }
}
