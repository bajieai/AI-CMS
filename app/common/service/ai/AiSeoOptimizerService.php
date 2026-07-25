<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\Content;
use think\facade\Cache;

/**
 * AI SEO优化服务 — V2.9.30 AI2-2
 */
class AiSeoOptimizerService
{
    /**
     * AI生成SEO标题
     */
    public function generateTitle(int $contentId, int $maxLength = 60): string
    {
        $content = Content::find($contentId);
        if (!$content) return '';
        $title = $content->seo_title ?: $content->title;
        if (mb_strlen($title) > $maxLength) {
            $title = mb_substr($title, 0, $maxLength);
        }
        return $title;
    }

    /**
     * AI生成SEO描述
     */
    public function generateDescription(int $contentId, int $maxLength = 160): string
    {
        $content = Content::find($contentId);
        if (!$content) return '';
        $desc = $content->description ?: '';
        if (empty($desc)) {
            $desc = mb_substr(strip_tags($content->content ?? ''), 0, $maxLength);
        }
        if (mb_strlen($desc) > $maxLength) {
            $desc = mb_substr($desc, 0, $maxLength);
        }
        return $desc;
    }

    /**
     * AI提取关键词（Top 5）
     */
    public function extractKeywords(int $contentId): array
    {
        $content = Content::find($contentId);
        if (!$content) return [];
        $text = $content->title . ' ' . $content->description . ' ' . mb_substr(strip_tags($content->content ?? ''), 0, 2000);
        $keywords = $this->extractKeywordsFromText($text);
        return array_slice($keywords, 0, 5);
    }

    /**
     * SEO评分
     */
    public function score(int $contentId): array
    {
        $cached = Cache::get('seo:score:' . $contentId);
        if ($cached !== null) return $cached;

        $content = Content::find($contentId);
        if (!$content) {
            return ['score' => 0, 'suggestions' => ['内容不存在']];
        }

        // 1. 标题评分 (30%)
        $titleLen = mb_strlen($content->seo_title ?: $content->title);
        $titleScore = ($titleLen >= 30 && $titleLen <= 60) ? 100 : ($titleLen >= 20 ? 70 : 40);

        // 2. 描述评分 (30%)
        $descLen = mb_strlen($content->description ?? '');
        $descScore = ($descLen >= 80 && $descLen <= 160) ? 100 : ($descLen >= 50 ? 70 : ($descLen > 0 ? 50 : 0));

        // 3. 关键词评分 (20%)
        $keywords = $content->seo_keywords ? explode(',', $content->seo_keywords) : [];
        $kwCount = count(array_filter($keywords, fn($k) => trim($k) !== ''));
        $keywordScore = ($kwCount >= 3 && $kwCount <= 5) ? 100 : ($kwCount > 0 ? 50 : 0);

        // 4. 内容质量评分 (20%)
        $contentLen = mb_strlen(strip_tags($content->content ?? ''));
        $qualityScore = $contentLen > 500 ? 100 : ($contentLen > 200 ? 60 : 30);

        $total = (int)round($titleScore * 0.30 + $descScore * 0.30 + $keywordScore * 0.20 + $qualityScore * 0.20);

        $suggestions = [];
        if ($titleScore < 100) $suggestions[] = '标题长度建议在30-60字符之间';
        if ($descScore < 100) $suggestions[] = '描述长度建议在80-160字符之间';
        if ($keywordScore < 100) $suggestions[] = '建议设置3-5个SEO关键词';
        if ($qualityScore < 100) $suggestions[] = '内容长度建议超过500字符';

        $result = [
            'score' => $total,
            'title_score' => $titleScore,
            'desc_score' => $descScore,
            'keyword_score' => $keywordScore,
            'quality_score' => $qualityScore,
            'suggestions' => $suggestions,
        ];

        Cache::set('seo:score:' . $contentId, $result, 3600);
        return $result;
    }

    /**
     * 批量SEO优化
     */
    public function batchOptimize(array $contentIds): array
    {
        $success = 0;
        $failed = 0;
        foreach ($contentIds as $id) {
            $content = Content::find((int)$id);
            if (!$content) { $failed++; continue; }
            $title = $this->generateTitle((int)$id);
            $desc = $this->generateDescription((int)$id);
            $keywords = implode(',', $this->extractKeywords((int)$id));
            $content->seo_title = $title;
            $content->seo_description = $desc;
            $content->seo_keywords = $keywords;
            $content->save();
            Cache::delete('seo:score:' . $id);
            $success++;
        }
        return ['total' => count($contentIds), 'success' => $success, 'failed' => $failed];
    }

    /**
     * 从文本提取关键词（简易分词）
     */
    private function extractKeywordsFromText(string $text): array
    {
        $text = preg_replace('/[^\p{L}\p{N}\s,，、]/u', ' ', $text);
        $words = preg_split('/[\s,，、]+/u', $text);
        $words = array_filter($words, fn($w) => mb_strlen($w) >= 2);
        $freq = array_count_values($words);
        arsort($freq);
        return array_keys($freq);
    }
}
