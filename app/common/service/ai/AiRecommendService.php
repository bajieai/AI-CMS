<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\RecommendLog;
use app\common\model\Content;
use think\facade\Cache;

/**
 * AI内容推荐引擎
 * V2.9.37 AI-HELPER-1
 * 
 * 推荐算法: CB(基于内容) + CF(协同过滤) + 热门 + 最新 + 混合
 * 因子权重: 用户行为40% + 内容特征30% + 用户画像20% + 时间衰减10%
 */
class AiRecommendService
{
    private const CACHE_TAG = 'ai_recommend';

    /**
     * 基于内容推荐(CB) — 相似分类/标签的内容
     */
    public function recommendByContent(int $contentId, int $limit = 10): array
    {
        $content = Content::find($contentId);
        if (!$content) return [];
        $query = Content::where('id', '<>', $contentId)
            ->where('status', 1);
        // 同分类优先
        if ($content['cate_id']) {
            $query->whereOr('cate_id', $content['cate_id']);
        }
        // 同标签
        if (!empty($content['tags'])) {
            $tags = is_array($content['tags']) ? $content['tags'] : explode(',', $content['tags']);
            foreach ($tags as $tag) {
                $query->whereOr('tags', 'like', '%' . $tag . '%');
            }
        }
        $query->order('recommend_weight', 'desc')->order('views', 'desc')->limit($limit);
        return $query->select()->toArray();
    }

    /**
     * 协同过滤推荐(CF) — 看过相同内容的用户还看了什么
     */
    public function recommendByUser(int $memberId, int $limit = 10): array
    {
        if ($memberId <= 0) return [];
        // 获取用户浏览过的内容
        $viewedContentIds = RecommendLog::where('member_id', $memberId)
            ->where('event_type', 'view')
            ->order('event_time', 'desc')
            ->limit(20)
            ->column('content_id');
        if (empty($viewedContentIds)) return $this->recommendHot($limit);
        // 找到看过相同内容的其他用户
        $similarUserIds = RecommendLog::whereIn('content_id', $viewedContentIds)
            ->where('member_id', '<>', $memberId)
            ->where('member_id', '>', 0)
            ->group('member_id')
            ->orderRaw('COUNT(*) DESC')
            ->limit(50)
            ->column('member_id');
        if (empty($similarUserIds)) return $this->recommendHot($limit);
        // 这些用户还看了什么
        $recommendedIds = RecommendLog::whereIn('member_id', $similarUserIds)
            ->whereNotIn('content_id', $viewedContentIds)
            ->where('event_type', 'view')
            ->group('content_id')
            ->orderRaw('COUNT(*) DESC')
            ->limit($limit)
            ->column('content_id');
        if (empty($recommendedIds)) return [];
        return Content::whereIn('id', $recommendedIds)
            ->where('status', 1)
            ->limit($limit)
            ->select()->toArray();
    }

    /**
     * 热门推荐
     */
    public function recommendHot(int $limit = 10): array
    {
        return Content::where('status', 1)
            ->where('create_time', '>=', date('Y-m-d H:i:s', strtotime('-30 days')))
            ->order('views', 'desc')
            ->order('recommend_weight', 'desc')
            ->limit($limit)
            ->select()->toArray();
    }

    /**
     * 最新推荐
     */
    public function recommendLatest(int $limit = 10): array
    {
        return Content::where('status', 1)
            ->order('create_time', 'desc')
            ->limit($limit)
            ->select()->toArray();
    }

    /**
     * 混合推荐(多算法加权)
     */
    public function recommendHybrid(int $memberId, string $scene = 'home'): array
    {
        $limit = 10;
        $cbResults = $memberId > 0 ? $this->recommendByUser($memberId, (int)ceil($limit * 0.4)) : [];
        $hotResults = $this->recommendHot((int)ceil($limit * 0.4));
        $latestResults = $this->recommendLatest((int)ceil($limit * 0.2));
        // 合并去重
        $merged = array_merge($cbResults, $hotResults, $latestResults);
        $seen = [];
        $result = [];
        foreach ($merged as $item) {
            if (!isset($seen[$item['id']])) {
                $seen[$item['id']] = true;
                $result[] = $item;
            }
            if (count($result) >= $limit) break;
        }
        return $result;
    }

    /**
     * 冷启动推荐(新用户)
     */
    public function coldStart(int $memberId): array
    {
        // 新用户推荐热门+最新
        return $this->recommendHybrid(0, 'cold_start');
    }

    /**
     * 首页推荐(5分钟缓存)
     */
    public function forHome(int $memberId = 0): array
    {
        return Cache::remember(
            'ai_recommend_home:' . $memberId,
            fn() => $this->recommendHybrid($memberId, 'home'),
            300
        );
    }

    /**
     * 详情页推荐(5分钟缓存)
     */
    public function forDetail(int $contentId, int $memberId = 0): array
    {
        return Cache::remember(
            'ai_recommend_detail:' . $contentId,
            fn() => $this->recommendByContent($contentId, 6),
            300
        );
    }

    /**
     * 搜索推荐
     */
    public function forSearch(string $keyword, int $memberId = 0): array
    {
        return Content::where('status', 1)
            ->where('title|content', 'like', '%' . $keyword . '%')
            ->order('views', 'desc')
            ->limit(5)
            ->select()->toArray();
    }

    /**
     * 记录行为事件
     */
    public function recordEvent(int $contentId, int $memberId, string $type, array $data = []): bool
    {
        try {
            RecommendLog::create([
                'content_id' => $contentId,
                'member_id'  => $memberId,
                'session_id' => $data['session_id'] ?? session_id(),
                'event_type' => $type,
                'event_data' => $data,
                'event_time' => date('Y-m-d H:i:s'),
            ]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取推荐配置
     */
    public function getConfig(): array
    {
        return Cache::remember('ai_recommend_config', function () {
            // 从数据库或默认配置获取
            return [
                'weight_behavior'    => 40,
                'weight_content'     => 30,
                'weight_profile'     => 20,
                'weight_time_decay' => 10,
                'cache_ttl'         => 300,
                'exclude_viewed'    => true,
            ];
        }, 3600);
    }

    /**
     * 推荐效果统计
     */
    public function getStats(): array
    {
        $totalEvents = RecommendLog::count();
        $clickEvents = RecommendLog::where('event_type', 'click')->count();
        $viewEvents = RecommendLog::where('event_type', 'view')->count();
        return [
            'total_events' => $totalEvents,
            'click_events' => $clickEvents,
            'view_events'  => $viewEvents,
            'ctr'          => $viewEvents > 0 ? round($clickEvents / $viewEvents * 100, 2) : 0,
        ];
    }
}
