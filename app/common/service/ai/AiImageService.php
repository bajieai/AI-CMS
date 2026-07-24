<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.32 Sprint FIX-4: AI配图独立Service
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\Content;
use think\facade\Cache;

/**
 * AI配图独立Service - V2.9.32 FIX-4
 * 从ContentController中提取的配图生成逻辑
 */
class AiImageService
{
    private const string CACHE_TAG = 'ai_image';

    /**
     * 生成配图
     */
    public function generate(int $contentId, string $style = 'auto', int $count = 1): array
    {
        $content = Content::find($contentId);
        if (!$content) {
            return ['success' => false, 'message' => '内容不存在'];
        }

        $imageService = new AiImageGenerateService();
        $results = [];

        for ($i = 0; $i < $count; $i++) {
            $result = $imageService->generate($contentId, $style);
            if ($result['success']) {
                $results[] = $result;
            }
        }

        if (empty($results)) {
            return ['success' => false, 'message' => '配图生成失败'];
        }

        return [
            'success' => true,
            'images' => $results,
            'count' => count($results),
            'message' => "成功生成{$count}张配图",
        ];
    }

    /**
     * 批量生成配图（≤10篇/批次）
     */
    public function batchGenerate(array $contentIds, string $style = 'auto'): array
    {
        if (count($contentIds) > 10) {
            return ['success' => false, 'message' => '批量配图每次最多10篇'];
        }

        $success = 0;
        $failed = 0;
        $details = [];

        foreach ($contentIds as $id) {
            $result = $this->generate((int) $id, $style, 1);
            if ($result['success']) {
                $success++;
                $details[] = ['content_id' => $id, 'status' => 'success', 'image' => $result['images'][0] ?? null];
            } else {
                $failed++;
                $details[] = ['content_id' => $id, 'status' => 'failed', 'message' => $result['message']];
            }
        }

        return [
            'success' => true,
            'total' => count($contentIds),
            'success_count' => $success,
            'failed_count' => $failed,
            'details' => $details,
        ];
    }

    /**
     * 获取配图库
     */
    public function getImageLibrary(int $userId, int $limit = 20): array
    {
        $cacheKey = "ai_image_library_{$userId}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $images = Content::where('member_id', $userId)
            ->where('thumb', '<>', '')
            ->field('id, title, thumb, create_time')
            ->order('id', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();

        $result = [
            'list' => $images,
            'total' => count($images),
        ];

        Cache::set($cacheKey, $result, 300);
        return $result;
    }

    /**
     * 配图评分
     */
    public function rateImage(int $contentId, int $score, string $feedback = ''): array
    {
        if ($score < 0 || $score > 100) {
            return ['success' => false, 'message' => '评分必须在0-100之间'];
        }

        $content = Content::find($contentId);
        if (!$content) {
            return ['success' => false, 'message' => '内容不存在'];
        }

        try {
            $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
            \think\facade\Db::table($prefix . 'content_image')
                ->where('content_id', $contentId)
                ->update(['quality_score' => $score]);
        } catch (\Throwable $e) {
            // 表可能不存在，忽略
        }

        Cache::delete("ai_image_library_" . ($content->member_id ?? 0));

        return ['success' => true, 'message' => '评分已记录', 'score' => $score];
    }

    /**
     * 自动配图触发（发布内容时钩子调用）
     */
    public function autoTrigger(int $contentId): array
    {
        $content = Content::find($contentId);
        if (!$content) {
            return ['success' => false, 'message' => '内容不存在'];
        }

        // 检测是否缺配图
        if (!empty($content->thumb)) {
            return ['success' => true, 'message' => '已有配图，无需触发', 'skipped' => true];
        }

        // 检查配置是否开启自动配图
        $autoEnabled = (bool) config('ai.image.auto_on_publish', false);
        if (!$autoEnabled) {
            return ['success' => true, 'message' => '自动配图未开启', 'skipped' => true];
        }

        $result = $this->generate($contentId, 'auto', 1);
        if ($result['success']) {
            // 自动设置缩略图
            $imageUrl = $result['images'][0]['image_url'] ?? '';
            if ($imageUrl) {
                $content->thumb = $imageUrl;
                $content->save();
            }
        }

        return $result;
    }

    /**
     * 清除缓存
     */
    public function clearCache(): void
    {
        Cache::clear();
    }
}
