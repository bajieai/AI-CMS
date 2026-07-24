<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\AiTranslationCache;
use app\common\model\AiTranslationGlossary;

/**
 * AI翻译增强服务 — V2.9.26 R-2
 *
 * 增强功能：翻译记忆(SHA256唯一键) + 术语库预处理 + 批量异步翻译
 */
class AiTranslateEnhanceService
{
    protected AiProviderFactory $providerFactory;

    public function __construct()
    {
        $this->providerFactory = new AiProviderFactory();
    }

    /**
     * 增强翻译（含翻译记忆+术语库）
     */
    public function translateWithMemory(
        string $text,
        string $sourceLang = 'zh-CN',
        string $targetLang = 'en',
        int $batchId = 0
    ): array {
        $startTime = microtime(true);

        // 1. 查翻译记忆
        $cached = AiTranslationCache::findTranslation($text, $sourceLang, $targetLang);
        if ($cached) {
            return [
                'success'   => true,
                'text'      => $cached['translated_text'],
                'from_cache' => true,
                'elapsed_ms' => (int)((microtime(true) - $startTime) * 1000),
            ];
        }

        // 2. 术语库预处理
        $preprocessed = AiTranslationGlossary::preprocessText($text, $sourceLang, $targetLang);

        // 3. AI翻译
        try {
            $provider = $this->providerFactory->getDefaultProvider();
            $systemPrompt = "You are a professional translator. Translate from {$sourceLang} to {$targetLang}. "
                . "Keep placeholders like [G0], [G1] unchanged. Output only the translation.";

            $result = $provider->chat([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $preprocessed['processed_text']],
            ]);

            $translatedText = $result['content'] ?? '';

            // 4. 术语库后处理
            $translatedText = AiTranslationGlossary::postprocessText($translatedText, $preprocessed['placeholders']);

            $elapsed = (int)((microtime(true) - $startTime) * 1000);

            // 5. 保存翻译记忆
            AiTranslationCache::saveTranslation(
                $text, $sourceLang, $targetLang, $translatedText,
                $result['provider'] ?? 'unknown',
                8.0
            );

            return ['success' => true, 'text' => $translatedText, 'from_cache' => false, 'elapsed_ms' => $elapsed];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 批量翻译
     */
    public function batchTranslate(array $texts, string $sourceLang = 'zh-CN', string $targetLang = 'en'): array
    {
        $results = [];
        foreach ($texts as $i => $text) {
            $results[$i] = $this->translateWithMemory($text, $sourceLang, $targetLang);
        }
        return ['success' => true, 'results' => $results];
    }

    /**
     * 获取翻译记忆统计
     */
    public function getMemoryStats(): array
    {
        $totalEntries = AiTranslationCache::count();
        $totalHits = AiTranslationCache::sum('hit_count');
        $avgQuality = AiTranslationCache::avg('quality_score');
        return [
            'total_entries' => $totalEntries,
            'total_hits'    => $totalHits,
            'avg_quality'   => round((float)$avgQuality, 1),
        ];
    }

    /**
     * 获取术语库统计
     */
    public function getGlossaryStats(): array
    {
        return [
            'total_terms' => AiTranslationGlossary::where('status', 1)->count(),
            'categories'  => AiTranslationGlossary::where('status', 1)
                ->field(['category', 'COUNT(*) as count'])
                ->group('category')
                ->select()
                ->toArray(),
        ];
    }
}
