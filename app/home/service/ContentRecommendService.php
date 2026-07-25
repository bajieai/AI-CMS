<?php
declare(strict_types=1);

namespace app\home\service;

use app\common\model\Content;
use app\common\model\ContentRecommendLog;
use app\common\service\content\ContentRecommendEngine;
use think\facade\Cache;

/**
 * 前台内容推荐服务 - V2.9.29 Sprint I-2
 */
class ContentRecommendService
{
    private ContentRecommendEngine $engine;

    public function __construct()
    {
        $this->engine = new ContentRecommendEngine();
    }

    public function getRecommendForDetail(int $contentId, int $limit = 5): array
    {
        return Cache::remember('rec_detail_' . $contentId . '_' . $limit, function () use ($contentId, $limit) {
            return $this->engine->recommend($contentId, $limit);
        }, 300);
    }

    public function getHotRecommend(int $cateId = 0, int $limit = 5): array
    {
        $cacheKey = 'rec_hot_' . $cateId . '_' . $limit;
        return Cache::remember($cacheKey, function () use ($cateId, $limit) {
            $query = Content::where('status', 2)->order('views', 'desc');
            if ($cateId > 0) $query->where('cate_id', $cateId);
            return $query->limit($limit)->select()->toArray();
        }, 600);
    }

    public function logImpression(int $contentId, int $recommendedId): void
    {
        ContentRecommendLog::create([
            'content_id' => $contentId,
            'recommended_id' => $recommendedId,
            'action' => 'impression',
        ]);
    }

    public function logClick(int $contentId, int $recommendedId): void
    {
        ContentRecommendLog::create([
            'content_id' => $contentId,
            'recommended_id' => $recommendedId,
            'action' => 'click',
        ]);
    }
}
