<?php
declare(strict_types=1);

namespace app\common\service\ml;

use app\common\model\TranslationMemory;
use think\facade\Cache;

/**
 * 翻译记忆库服务
 * V2.9.37 I18N-5
 */
class TranslationMemoryService
{
    private const CACHE_TAG = 'translation_memory';

    /**
     * 存储翻译记忆
     */
    public function store(string $sourceText, string $targetText, string $srcLang, string $tgtLang, array $context = []): int
    {
        $hash = hash('sha256', $sourceText);
        $existing = TranslationMemory::where('hash_value', $hash)
            ->where('source_lang', $srcLang)
            ->where('target_lang', $tgtLang)
            ->find();
        if ($existing) {
            // 更新已有记忆
            $existing->target_text = $targetText;
            $existing->use_count = $existing->use_count + 1;
            $existing->save();
            return (int) $existing->id;
        }
        $model = TranslationMemory::create([
            'source_text'   => $sourceText,
            'target_text'   => $targetText,
            'source_lang'   => $srcLang,
            'target_lang'   => $tgtLang,
            'context_type'  => $context['context_type'] ?? '',
            'context_id'    => $context['context_id'] ?? 0,
            'quality_score' => $context['quality_score'] ?? 0,
            'hash_value'    => $hash,
        ]);
        return (int) $model->id;
    }

    /**
     * 匹配翻译(精确+模糊)
     */
    public function match(string $sourceText, string $srcLang, string $tgtLang): ?array
    {
        // 精确匹配
        $hash = hash('sha256', $sourceText);
        $exact = TranslationMemory::where('hash_value', $hash)
            ->where('source_lang', $srcLang)
            ->where('target_lang', $tgtLang)
            ->find();
        if ($exact) {
            // 更新使用次数
            $exact->use_count = $exact->use_count + 1;
            $exact->save();
            return [
                'target_text' => $exact->target_text,
                'similarity'  => 100,
                'type'        => 'exact',
                'id'          => $exact->id,
            ];
        }
        // 模糊匹配
        return $this->fuzzyMatch($sourceText, $srcLang, $tgtLang, 80);
    }

    /**
     * 模糊匹配
     */
    public function fuzzyMatch(string $sourceText, string $srcLang, string $tgtLang, float $threshold = 80): ?array
    {
        // 获取同语言对的所有记忆(限制数量)
        $memories = TranslationMemory::where('source_lang', $srcLang)
            ->where('target_lang', $tgtLang)
            ->order('use_count', 'desc')
            ->limit(500)
            ->select()
            ->toArray();
        $bestMatch = null;
        $bestScore = 0;
        foreach ($memories as $memory) {
            similar_text($sourceText, $memory['source_text'], $percent);
            if ($percent >= $threshold && $percent > $bestScore) {
                $bestScore = $percent;
                $bestMatch = $memory;
            }
        }
        if ($bestMatch) {
            return [
                'target_text' => $bestMatch['target_text'],
                'similarity'  => round($bestScore, 2),
                'type'        => 'fuzzy',
                'id'          => $bestMatch['id'],
            ];
        }
        return null;
    }

    /**
     * 获取统计信息
     */
    public function getStats(): array
    {
        $total = TranslationMemory::count();
        $confirmed = TranslationMemory::where('is_confirmed', 1)->count();
        $totalUses = TranslationMemory::sum('use_count');
        $byLangPair = TranslationMemory::field('source_lang, target_lang, COUNT(*) as cnt, SUM(use_count) as uses')
            ->group('source_lang, target_lang')
            ->select()
            ->toArray();
        return [
            'total_memories'   => $total,
            'confirmed'        => $confirmed,
            'total_uses'       => $totalUses,
            'avg_quality'      => TranslationMemory::avg('quality_score'),
            'by_lang_pair'     => $byLangPair,
            'hit_rate'         => $totalUses > 0 ? round(($totalUses - $total) / $totalUses * 100, 2) : 0,
        ];
    }

    /**
     * 清理低质量/长期未使用的记忆
     */
    public function cleanup(int $days = 180): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return TranslationMemory::where('quality_score', '<', 2)
            ->where('use_count', '<', 2)
            ->where('update_time', '<', $cutoff)
            ->delete();
    }

    /**
     * 导入翻译记忆
     */
    public function import(string $json): int
    {
        $data = json_decode($json, true);
        if (!is_array($data)) return 0;
        $count = 0;
        foreach ($data as $item) {
            if ($this->store($item['source_text'], $item['target_text'], $item['source_lang'], $item['target_lang'], $item)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * 导出翻译记忆
     */
    public function export(string $srcLang = '', string $tgtLang = ''): string
    {
        $query = TranslationMemory::order('use_count', 'desc')->limit(1000);
        if ($srcLang) $query->where('source_lang', $srcLang);
        if ($tgtLang) $query->where('target_lang', $tgtLang);
        $data = $query->select()->toArray();
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
