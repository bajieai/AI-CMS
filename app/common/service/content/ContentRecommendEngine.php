<?php
declare(strict_types=1);
namespace app\common\service\content;

use app\common\model\Content;
use app\common\model\ContentTag;
use app\common\model\ContentRecommendLog;
use think\facade\Cache;

/**
 * 内容推荐引擎 (V2.9.29 I-2)
 * 基于标签相似度+同栏目+同分类的混合推荐算法
 */
class ContentRecommendEngine
{
    /**
     * 获取推荐内容
     */
    public function recommend(int $contentId, int $limit = 5): array
    {
        $cacheKey = 'content_recommend_' . $contentId . '_' . $limit;
        return Cache::remember($cacheKey, function () use ($contentId, $limit) {
            $content = Content::find($contentId);
            if (!$content) return [];

            $scores = [];

            // 1. 同栏目内容（权重0.3）
            $sameCate = Content::where('cate_id', $content->cate_id)
                ->where('id', '<>', $contentId)
                ->where('status', 1)
                ->order('views', 'desc')
                ->limit(20)
                ->select();
            foreach ($sameCate as $item) {
                $scores[$item->id] = ($scores[$item->id] ?? 0) + 0.3;
            }

            // 2. 同标签内容（权重0.5）
            $tagIds = ContentTag::where('content_id', $contentId)->column('tag_id');
            if (!empty($tagIds)) {
                $sameTagContentIds = ContentTag::whereIn('tag_id', $tagIds)
                    ->where('content_id', '<>', $contentId)
                    ->group('content_id')
                    ->limit(30)
                    ->column('content_id');
                $sameTag = Content::whereIn('id', $sameTagContentIds)
                    ->where('status', 1)
                    ->select();
                foreach ($sameTag as $item) {
                    $scores[$item->id] = ($scores[$item->id] ?? 0) + 0.5;
                }
            }

            // 3. 热门内容补充（权重0.1）
            if (count($scores) < $limit) {
                $hot = Content::where('id', '<>', $contentId)
                    ->where('status', 1)
                    ->where('id', 'not in', array_keys($scores))
                    ->order('views', 'desc')
                    ->limit($limit * 2)
                    ->select();
                foreach ($hot as $item) {
                    $scores[$item->id] = ($scores[$item->id] ?? 0) + 0.1;
                }
            }

            // 排序取Top N
            arsort($scores);
            $topIds = array_slice(array_keys($scores), 0, $limit);

            if (empty($topIds)) return [];

            return Content::whereIn('id', $topIds)
                ->select()
                ->toArray();
        }, 600);
    }

    /**
     * 记录推荐曝光
     */
    public function recordImpression(int $contentId, int $recommendedId, int $userId = 0, string $source = 'tag'): void
    {
        ContentRecommendLog::create([
            'content_id' => $contentId,
            'recommended_content_id' => $recommendedId,
            'user_id' => $userId,
            'source' => $source,
            'impressed' => 1,
            'clicked' => 0,
            'create_time' => time(),
        ]);
    }

    /**
     * 记录推荐点击
     */
    public function recordClick(int $contentId, int $recommendedId, int $userId = 0): void
    {
        ContentRecommendLog::where('content_id', $contentId)
            ->where('recommended_content_id', $recommendedId)
            ->where('user_id', $userId)
            ->order('id', 'desc')
            ->limit(1)
            ->update(['clicked' => 1]);
    }
}
