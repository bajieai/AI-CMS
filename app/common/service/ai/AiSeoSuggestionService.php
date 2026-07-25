<?php
declare(strict_types=1);

namespace app\common\service\ai;

/**
 * AI SEO建议服务 — V2.9.26 R-5
 *
 * 基于已有 AiSeoOptimizerService 增强，提供：
 * - 关键词建议
 * - Meta标签优化
 * - 内容结构建议
 * - 可读性评分
 */
class AiSeoSuggestionService
{
    protected AiProviderFactory $providerFactory;

    public function __construct()
    {
        $this->providerFactory = new AiProviderFactory();
    }

    /**
     * 生成SEO优化建议
     */
    public function analyze(string $title, string $content, string $targetKeyword = ''): array
    {
        $startTime = microtime(true);

        try {
            $provider = $this->providerFactory->getDefaultProvider();
            $prompt = $this->buildAnalysisPrompt($title, $content, $targetKeyword);

            $result = $provider->chat([
                ['role' => 'system', 'content' => 'You are an SEO expert. Analyze the content and provide suggestions in JSON format.'],
                ['role' => 'user', 'content' => $prompt],
            ]);

            $suggestions = json_decode($result['content'] ?? '{}', true);
            $elapsed = (int)((microtime(true) - $startTime) * 1000);

            return [
                'success'    => true,
                'suggestions' => $suggestions ?: [],
                'elapsed_ms' => $elapsed,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 生成关键词建议
     */
    public function suggestKeywords(string $content, int $limit = 10): array
    {
        try {
            $provider = $this->providerFactory->getDefaultProvider();
            $result = $provider->chat([
                ['role' => 'system', 'content' => 'Extract key phrases from the content. Output as JSON array.'],
                ['role' => 'user', 'content' => "Extract {$limit} key phrases from: " . mb_substr($content, 0, 1000)],
            ]);
            $keywords = json_decode($result['content'] ?? '[]', true);
            return ['success' => true, 'keywords' => $keywords ?: []];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 优化Meta标签
     */
    public function optimizeMeta(string $title, string $content): array
    {
        try {
            $provider = $this->providerFactory->getDefaultProvider();
            $result = $provider->chat([
                ['role' => 'system', 'content' => 'Generate optimized SEO meta tags. Output JSON with fields: title, description, keywords.'],
                ['role' => 'user', 'content' => "Title: {$title}\nContent: " . mb_substr($content, 0, 500)],
            ]);
            $meta = json_decode($result['content'] ?? '{}', true);
            return ['success' => true, 'meta' => $meta ?: []];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 可读性评分
     */
    public function readabilityScore(string $content): array
    {
        $charCount = mb_strlen($content);
        $sentenceCount = max(1, preg_match_all('/[.!?。！？]/', $content));
        $avgSentenceLength = round($charCount / $sentenceCount);

        $score = 100;
        if ($avgSentenceLength > 50) $score -= 20;
        if ($avgSentenceLength > 80) $score -= 20;
        if ($charCount < 300) $score -= 15;
        if ($charCount > 5000) $score -= 10;

        $level = '优秀';
        if ($score < 60) $level = '需改进';
        elseif ($score < 80) $level = '良好';

        return [
            'score'             => max(0, $score),
            'level'             => $level,
            'char_count'        => $charCount,
            'sentence_count'    => $sentenceCount,
            'avg_sentence_length' => $avgSentenceLength,
        ];
    }

    protected function buildAnalysisPrompt(string $title, string $content, string $targetKeyword): string
    {
        $keywordPart = $targetKeyword ? "Target keyword: {$targetKeyword}\n" : '';
        return "Analyze the following content for SEO optimization. Provide suggestions in JSON format with fields: "
            . "score (0-100), title_suggestion, meta_description_suggestion, content_suggestions (array), "
            . "keyword_density (object).\n\n"
            . $keywordPart
            . "Title: {$title}\n"
            . "Content: " . mb_substr($content, 0, 2000);
    }
}
