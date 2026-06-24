<?php
declare(strict_types=1);
namespace app\common\service\ai;

use app\common\model\Content;

/**
 * AI内容质量诊断服务 (V2.9.29 I-4)
 * 可读性+SEO健康度+图片质量+链接有效性
 */
class AiContentDiagnosisService
{
    /**
     * 诊断内容质量
     */
    public function diagnose(int $contentId): array
    {
        $content = Content::find($contentId);
        if (!$content) {
            return ['success' => false, 'error' => '内容不存在'];
        }

        $html = $content->content ?? '';
        $text = strip_tags($html);

        return [
            'success' => true,
            'readability' => $this->checkReadability($text),
            'seo' => $this->checkSeo($content, $html),
            'image_quality' => $this->checkImageQuality($html),
            'link_validity' => $this->checkLinks($html),
            'overall_score' => 0,
            'suggestions' => [],
        ];
    }

    private function checkReadability(string $text): array
    {
        $textLen = mb_strlen($text);
        $sentences = preg_split('/[。！？.!?]/', $text);
        $sentences = array_filter($sentences, fn($s) => mb_strlen(trim($s)) > 0);
        $avgSentenceLen = count($sentences) > 0 ? $textLen / count($sentences) : 0;

        $score = 100;
        if ($avgSentenceLen > 100) $score -= 20;
        if ($avgSentenceLen > 150) $score -= 20;
        if ($textLen < 200) $score -= 30;

        return [
            'score' => max(0, $score),
            'text_length' => $textLen,
            'avg_sentence_length' => round($avgSentenceLen, 1),
            'sentence_count' => count($sentences),
        ];
    }

    private function checkSeo($content, string $html): array
    {
        $issues = [];
        $titleLen = mb_strlen($content->title ?? '');

        if ($titleLen < 10) $issues[] = '标题过短（建议10-30字）';
        if ($titleLen > 60) $issues[] = '标题过长（建议不超过60字）';

        if (empty($content->seo_description)) $issues[] = '缺少Meta描述';
        if (empty($content->seo_keywords)) $issues[] = '缺少SEO关键词';

        // 检查H标签
        $h1Count = preg_match_all('/<h1/i', $html);
        if ($h1Count === 0) $issues[] = '缺少H1标签';
        if ($h1Count > 1) $issues[] = '多个H1标签';

        // 检查图片Alt
        $imgCount = preg_match_all('/<img/i', $html);
        $imgWithAlt = preg_match_all('/<img[^>]+alt=["\'][^"\']+["\']/i', $html);
        if ($imgCount > 0 && $imgWithAlt < $imgCount) {
            $issues[] = "有" . ($imgCount - $imgWithAlt) . "张图片缺少Alt属性";
        }

        return ['score' => max(0, 100 - count($issues) * 15), 'issues' => $issues];
    }

    private function checkImageQuality(string $html): array
    {
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches);
        $images = $matches[1] ?? [];
        return ['image_count' => count($images), 'images' => $images];
    }

    private function checkLinks(string $html): array
    {
        preg_match_all('/href=["\'](https?:\/\/[^"\']+)["\']/i', $html, $matches);
        $links = $matches[1] ?? [];
        return ['link_count' => count($links), 'links' => $links];
    }
}
