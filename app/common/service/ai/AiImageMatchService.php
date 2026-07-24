<?php
declare(strict_types=1);
namespace app\common\service\ai;

use app\common\model\Content;
use think\facade\Cache;

/**
 * AI配图风格匹配增强 - V2.9.32 AI4-1
 */
class AiImageMatchService
{
    private const CONTENT_STYLE_MAP = [
        'product' => 'business', 'case' => 'business', 'news' => 'tech',
        'download' => 'tech', 'job' => 'business', 'recruit' => 'business',
    ];

    public function matchStyle(string $contentType, string $industry = ''): string
    {
        if (!empty($industry)) {
            $map = ['tech' => 'tech', 'finance' => 'business', 'medical' => 'nature', 'education' => 'creative', 'food' => 'creative', 'art' => 'creative'];
            if (isset($map[$industry])) return $map[$industry];
        }
        return self::CONTENT_STYLE_MAP[$contentType] ?? 'business';
    }

    public function autoTrigger(int $contentId): array
    {
        $content = Content::find($contentId);
        if (!$content) return ['success' => false, 'message' => '内容不存在'];
        if (!empty($content->thumb)) return ['success' => true, 'message' => '已有配图', 'skipped' => true];

        try { $content->auto_image_status = 1; $content->save(); } catch (\Throwable $e) {}

        $contentType = $this->detectContentType($content);
        $style = $this->matchStyle($contentType);
        $imageService = new AiImageService();
        $result = $imageService->generate($contentId, $style, 1);

        if ($result['success']) {
            $imageUrl = $result['images'][0]['image_url'] ?? '';
            if ($imageUrl) {
                $content->thumb = $imageUrl;
                try { $content->auto_image_status = 2; } catch (\Throwable $e) {}
                $content->save();
            }
            $qualityScore = $this->scoreQuality($imageUrl, $contentId);
            if ($qualityScore < 60) {
                for ($i = 0; $i < 3 && $qualityScore < 60; $i++) {
                    $retry = $imageService->generate($contentId, $style, 1);
                    if ($retry['success']) {
                        $imageUrl = $retry['images'][0]['image_url'] ?? '';
                        $qualityScore = $this->scoreQuality($imageUrl, $contentId);
                    }
                }
            }
            return ['success' => true, 'image_url' => $imageUrl, 'style' => $style, 'quality_score' => $qualityScore];
        }

        try { $content->auto_image_status = 3; $content->save(); } catch (\Throwable $e) {}
        return ['success' => false, 'message' => '自动配图失败'];
    }

    public function batchGenerateImages(array $contentIds): array
    {
        if (count($contentIds) > 10) return ['success' => false, 'message' => '批量配图每次最多10篇'];
        $success = 0; $failed = 0; $details = [];
        foreach ($contentIds as $id) {
            $result = $this->autoTrigger((int) $id);
            $details[] = ['content_id' => $id, 'status' => $result['success'] ? 'success' : 'failed', 'result' => $result];
            $result['success'] ? $success++ : $failed++;
        }
        return ['success' => true, 'total' => count($contentIds), 'success_count' => $success, 'failed_count' => $failed, 'details' => $details];
    }

    public function scoreQuality(string $imageUrl, int $contentId): int
    {
        // 简易质量评分：基于URL来源和图片类型
        $score = 70;
        if (str_contains($imageUrl, 'unsplash')) $score = 85;
        if (str_contains($imageUrl, '1024')) $score += 5;
        return min(100, $score);
    }

    private function detectContentType(Content $content): string
    {
        $cateId = (int) ($content->cate_id ?? 0);
        $modelId = (int) ($content->model_id ?? 0);
        $typeMap = [1 => 'news', 2 => 'product', 3 => 'case', 4 => 'download', 5 => 'job', 6 => 'recruit'];
        return $typeMap[$modelId] ?? 'news';
    }

    private function recordImage(int $contentId, string $imageUrl, string $style, bool $aiGenerated, bool $autoTriggered): void
    {
        try {
            $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
            \think\facade\Db::table($prefix . 'content_image')->insert([
                'content_id' => $contentId, 'image_url' => $imageUrl, 'image_type' => 'thumbnail',
                'style' => $style, 'quality_score' => 0,
                'ai_generated' => $aiGenerated ? 1 : 0, 'auto_triggered' => $autoTriggered ? 1 : 0,
                'create_time' => time(),
            ]);
        } catch (\Throwable $e) {}
    }
}
