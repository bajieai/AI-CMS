<?php
declare(strict_types=1);

namespace app\common\service\content;

use app\common\model\Content;

/**
 * 内容质量评估服务 - V2.9.29 Sprint I-4
 */
class ContentQualityService
{
    /**
     * 计算内容质量评分
     */
    public function calculateScore(int $contentId): array
    {
        $content = Content::find($contentId);
        if (!$content) return ['score' => 0, 'issues' => ['内容不存在']];

        $scores = [];
        $issues = [];

        // 标题长度评分
        $titleLen = mb_strlen($content->title);
        if ($titleLen < 5) {
            $scores['title'] = 2;
            $issues[] = '标题过短';
        } elseif ($titleLen > 60) {
            $scores['title'] = 3;
            $issues[] = '标题过长';
        } else {
            $scores['title'] = 5;
        }

        // 内容长度评分
        $contentLen = mb_strlen(strip_tags($content->content));
        if ($contentLen < 100) {
            $scores['content'] = 2;
            $issues[] = '内容过短';
        } elseif ($contentLen < 300) {
            $scores['content'] = 3;
        } else {
            $scores['content'] = 5;
        }

        // SEO评分
        if (empty($content->seo_title)) {
            $scores['seo'] = 2;
            $issues[] = '缺少SEO标题';
        } elseif (empty($content->seo_description)) {
            $scores['seo'] = 3;
            $issues[] = '缺少SEO描述';
        } else {
            $scores['seo'] = 5;
        }

        // 图片评分
        $hasImage = !empty($content->cover) || strpos($content->content, '<img') !== false;
        $scores['image'] = $hasImage ? 5 : 3;
        if (!$hasImage) $issues[] = '缺少图片';

        $avgScore = round(array_sum($scores) / count($scores), 1);
        return ['score' => $avgScore, 'details' => $scores, 'issues' => $issues];
    }
}
