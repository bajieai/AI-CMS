<?php
declare(strict_types=1);
namespace app\common\service\template;

use app\common\model\TemplateUserAction;
use app\common\model\TemplateRecommendQueue;
use app\common\model\TemplateStore;
use think\facade\Cache;
use think\facade\Db;

/**
 * 模板推荐引擎V2 (V2.9.29 T-2)
 * 协同过滤+热度推荐+个性化
 */
class TemplateRecommendV2Service
{
    private const CACHE_TAG = 'template_recommend_v2';
    private const CACHE_TTL = 300;

    /**
     * 获取用户推荐（"猜你喜欢"）
     */
    public function getRecommendations(int $userId, int $limit = 6): array
    {
        if ($userId <= 0) {
            return $this->getHotRecommendations($limit);
        }

        // 从预计算队列获取
        $queued = TemplateRecommendQueue::where('user_id', $userId)
            ->where('expire_time', '>', time())
            ->order('score', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();

        if (count($queued) >= $limit) {
            $templateIds = array_column($queued, 'template_id');
            return TemplateStore::whereIn('id', $templateIds)
                ->where('status', 1)
                ->select()
                ->toArray();
        }

        // 队列不足，回退热度推荐
        return $this->getHotRecommendations($limit);
    }

    /**
     * 热度推荐
     */
    public function getHotRecommendations(int $limit = 6): array
    {
        return Cache::remember(
            'hot_templates_' . $limit,
            function () use ($limit) {
                return TemplateStore::where('status', 1)
                    ->order('download_count', 'desc')
                    ->order('rating_avg', 'desc')
                    ->limit($limit)
                    ->select()
                    ->toArray();
            },
            self::CACHE_TTL
        );
    }

    /**
     * 协同过滤：浏览了该模板的用户也浏览了
     */
    public function getCollaborativeRecommendations(int $templateId, int $limit = 6): array
    {
        // 找到浏览过该模板的用户
        $users = TemplateUserAction::where('template_id', $templateId)
            ->where('action', 'view')
            ->column('user_id');

        if (empty($users)) {
            return $this->getHotRecommendations($limit);
        }

        // 找到这些用户还浏览了哪些模板
        $relatedTemplateIds = TemplateUserAction::whereIn('user_id', $users)
            ->where('template_id', '<>', $templateId)
            ->where('action', 'view')
            ->group('template_id')
            ->orderRaw('COUNT(*) DESC')
            ->limit($limit)
            ->column('template_id');

        if (empty($relatedTemplateIds)) {
            return $this->getHotRecommendations($limit);
        }

        return TemplateStore::whereIn('id', $relatedTemplateIds)
            ->where('status', 1)
            ->select()
            ->toArray();
    }

    /**
     * 预计算推荐队列（定时任务调用）
     */
    public function computeRecommendations(int $batchSize = 100): int
    {
        // 获取有行为的活跃用户
        $activeUsers = TemplateUserAction::group('user_id')
            ->orderRaw('COUNT(*) DESC')
            ->limit($batchSize)
            ->column('user_id');

        $computed = 0;
        foreach ($activeUsers as $userId) {
            $this->computeForUser($userId);
            $computed++;
        }
        return $computed;
    }

    /**
     * 为单个用户计算推荐
     */
    private function computeForUser(int $userId): void
    {
        // 获取用户浏览过的模板分类
        $viewedTemplateIds = TemplateUserAction::where('user_id', $userId)
            ->where('action', 'view')
            ->order('create_time', 'desc')
            ->limit(20)
            ->column('template_id');

        if (empty($viewedTemplateIds)) return;

        // 获取同分类的热门模板
        $categories = TemplateStore::whereIn('id', $viewedTemplateIds)
            ->column('category_id');

        $recommendations = TemplateStore::whereIn('category_id', $categories)
            ->where('id', 'not in', $viewedTemplateIds)
            ->where('status', 1)
            ->order('download_count', 'desc')
            ->limit(10)
            ->select();

        // 清除旧推荐
        TemplateRecommendQueue::where('user_id', $userId)->delete();

        // 写入新推荐
        $now = time();
        $expireTime = $now + 86400; // 24小时过期
        foreach ($recommendations as $idx => $tpl) {
            TemplateRecommendQueue::create([
                'user_id' => $userId,
                'template_id' => $tpl->id,
                'score' => 1.0 - ($idx * 0.1),
                'reason' => 'category',
                'expire_time' => $expireTime,
                'create_time' => $now,
            ]);
        }
    }
}
