<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateStore;
use app\common\model\TemplateInstall;
use app\common\model\TemplateRecommendLog;
use think\facade\Cache;

/**
 * 模板推荐算法 — V2.9.33 T5-1
 * 5种策略：协同过滤/内容推荐/热门/新品/混合
 * 推荐场景缓存策略：
 *   - 首页推荐：5分钟缓存
 *   - 详情页相关推荐：5分钟缓存
 *   - 安装成功页推荐：30秒短缓存（确保实时性）
 *   - 卸载后推荐替代：30秒短缓存
 *   - 新用户注册推荐：10分钟缓存
 */
class TemplateRecommendService
{
    private const CACHE_TAG = 'template_recommend';

    /** 各场景缓存时间（秒） */
    private const SCENE_CACHE_TTL = [
        'home'            => 300,   // 5分钟
        'detail'          => 300,   // 5分钟
        'install_success' => 30,    // 30秒（短缓存确保实时性）
        'uninstall'       => 30,    // 30秒
        'register'        => 600,  // 10分钟
    ];

    /** 混合推荐权重 */
    private const HYBRID_WEIGHTS = [
        'collaborative' => 0.40,
        'content_based' => 0.30,
        'popular'       => 0.20,
        'new'           => 0.10,
    ];

    /**
     * 推荐
     */
    public function recommend(int $userId, string $scene, int $count = 6): array
    {
        $cacheKey = "rec_{$userId}_{$scene}_{$count}";
        $ttl = self::SCENE_CACHE_TTL[$scene] ?? 300;

        return Cache::remember($cacheKey, function () use ($userId, $scene, $count) {
            $strategy = $this->selectStrategy($userId, $scene);
            $results = $this->executeStrategy($strategy, $userId, $count);

            // 记录推荐日志
            $this->logRecommend($userId, $scene, $strategy, $results);

            return $results;
        }, $ttl);
    }

    /**
     * 策略选择
     */
    private function selectStrategy(int $userId, string $scene): string
    {
        // 新用户(无安装历史)→popular
        $installCount = TemplateInstall::where('member_id', $userId)->count();

        if ($scene === 'register') return 'popular';
        if ($scene === 'uninstall') return 'content_based';
        if ($installCount === 0) return 'popular';
        return 'hybrid';
    }

    /**
     * 执行推荐策略
     */
    private function executeStrategy(string $strategy, int $userId, int $count): array
    {
        switch ($strategy) {
            case 'collaborative':
                return $this->collaborativeFiltering($userId, $count);
            case 'content_based':
                return $this->contentBased($userId, $count);
            case 'popular':
                return $this->popularRecommend($count);
            case 'new':
                return $this->newRecommend($count);
            case 'hybrid':
            default:
                return $this->hybridRecommend($userId, $count);
        }
    }

    /**
     * 协同过滤：安装过类似模板的用户也安装了XX
     */
    private function collaborativeFiltering(int $userId, int $count): array
    {
        // 获取用户已安装的模板ID
        $installedIds = TemplateInstall::where('member_id', $userId)
            ->column('store_id');

        if (empty($installedIds)) {
            return $this->popularRecommend($count);
        }

        // 找到安装过相同模板的其他用户
        $similarUserIds = TemplateInstall::whereIn('store_id', $installedIds)
            ->where('member_id', '<>', $userId)
            ->group('member_id')
            ->column('member_id');

        if (empty($similarUserIds)) {
            return $this->popularRecommend($count);
        }

        // 获取这些用户安装的其他模板
        $recommended = TemplateInstall::whereIn('member_id', $similarUserIds)
            ->whereNotIn('store_id', $installedIds)
            ->group('store_id')
            ->orderRaw('COUNT(*) DESC')
            ->limit($count)
            ->column('store_id');

        return $this->formatResults($recommended, 'collaborative', '用户也在用');
    }

    /**
     * 基于内容推荐：根据用户当前模板行业/风格推荐同类
     */
    private function contentBased(int $userId, int $count): array
    {
        $currentInstall = TemplateInstall::where('member_id', $userId)
            ->order('id', 'desc')
            ->find();

        if (!$currentInstall) {
            return $this->popularRecommend($count);
        }

        $currentTemplate = TemplateStore::find($currentInstall->store_id);
        if (!$currentTemplate) {
            return $this->popularRecommend($count);
        }

        // 推荐同分类的模板
        $recommended = TemplateStore::where('status', 1)
            ->where('id', '<>', $currentInstall->store_id)
            ->where('category_id', $currentTemplate->category_id)
            ->order('install_count', 'desc')
            ->limit($count)
            ->column('id');

        return $this->formatResults($recommended, 'content_based', '同类推荐');
    }

    /**
     * 热门推荐
     */
    private function popularRecommend(int $count): array
    {
        $recommended = TemplateStore::where('status', 1)
            ->order('install_count', 'desc')
            ->order('avg_rating', 'desc')
            ->limit($count)
            ->column('id');

        return $this->formatResults($recommended, 'popular', '热门');
    }

    /**
     * 新品推荐
     */
    private function newRecommend(int $count): array
    {
        $recommended = TemplateStore::where('status', 1)
            ->order('id', 'desc')
            ->limit($count)
            ->column('id');

        return $this->formatResults($recommended, 'new', '新品');
    }

    /**
     * 混合推荐
     */
    private function hybridRecommend(int $userId, int $count): array
    {
        $collab = $this->collaborativeFiltering($userId, $count * 2);
        $content = $this->contentBased($userId, $count * 2);
        $popular = $this->popularRecommend($count);
        $new = $this->newRecommend($count);

        // 加权合并去重
        $merged = [];
        foreach ($collab as $item) {
            $id = $item['id'];
            if (!isset($merged[$id])) $merged[$id] = ['id' => $id, 'score' => 0, 'reason' => $item['reason']];
            $merged[$id]['score'] += self::HYBRID_WEIGHTS['collaborative'] * 100;
        }
        foreach ($content as $item) {
            $id = $item['id'];
            if (!isset($merged[$id])) $merged[$id] = ['id' => $id, 'score' => 0, 'reason' => $item['reason']];
            $merged[$id]['score'] += self::HYBRID_WEIGHTS['content_based'] * 100;
        }
        foreach ($popular as $item) {
            $id = $item['id'];
            if (!isset($merged[$id])) $merged[$id] = ['id' => $id, 'score' => 0, 'reason' => $item['reason']];
            $merged[$id]['score'] += self::HYBRID_WEIGHTS['popular'] * 100;
        }
        foreach ($new as $item) {
            $id = $item['id'];
            if (!isset($merged[$id])) $merged[$id] = ['id' => $id, 'score' => 0, 'reason' => $item['reason']];
            $merged[$id]['score'] += self::HYBRID_WEIGHTS['new'] * 100;
        }

        uasort($merged, fn($a, $b) => $b['score'] <=> $a['score']);
        $topIds = array_slice(array_keys($merged), 0, $count);

        return $this->formatResults($topIds, 'hybrid', '为您推荐');
    }

    /**
     * 格式化结果
     */
    private function formatResults(array $templateIds, string $strategy, string $reason): array
    {
        if (empty($templateIds)) return [];

        $templates = TemplateStore::whereIn('id', $templateIds)
            ->field('id,name,slug,price,install_count,avg_rating,screenshots')
            ->select()
            ->toArray();

        $idOrder = array_flip($templateIds);
        usort($templates, fn($a, $b) => ($idOrder[$a['id']] ?? 999) <=> ($idOrder[$b['id']] ?? 999));

        foreach ($templates as &$t) {
            $t['recommend_reason'] = $reason;
            $t['recommend_strategy'] = $strategy;
        }

        return $templates;
    }

    /**
     * 记录推荐日志
     */
    private function logRecommend(int $userId, string $scene, string $strategy, array $results): void
    {
        $now = time();
        $logs = [];
        foreach ($results as $r) {
            $logs[] = [
                'user_id'            => $userId,
                'template_id'        => $r['id'],
                'recommend_strategy' => $strategy,
                'recommend_scene'    => $scene,
                'impression'         => 1,
                'click'              => 0,
                'install'            => 0,
                'create_time'        => $now,
            ];
        }
        if (!empty($logs)) {
            (new TemplateRecommendLog())->insertAll($logs);
        }
    }

    /**
     * 记录推荐点击
     */
    public function logClick(int $userId, int $templateId): void
    {
        TemplateRecommendLog::where('user_id', $userId)
            ->where('template_id', $templateId)
            ->where('click', 0)
            ->order('id', 'desc')
            ->limit(1)
            ->update(['click' => 1, 'click_time' => time()]);
    }

    /**
     * 记录推荐安装
     */
    public function logInstall(int $userId, int $templateId): void
    {
        TemplateRecommendLog::where('user_id', $userId)
            ->where('template_id', $templateId)
            ->where('install', 0)
            ->order('id', 'desc')
            ->limit(1)
            ->update(['install' => 1, 'install_time' => time()]);
    }

    /**
     * 推荐效果统计
     */
    public function getStats(int $days = 7): array
    {
        $startTime = time() - $days * 86400;

        $totalImpression = TemplateRecommendLog::where('create_time', '>=', $startTime)->sum('impression');
        $totalClick = TemplateRecommendLog::where('create_time', '>=', $startTime)->sum('click');
        $totalInstall = TemplateRecommendLog::where('create_time', '>=', $startTime)->sum('install');

        return [
            'impression'   => (int) $totalImpression,
            'click'        => (int) $totalClick,
            'install'      => (int) $totalInstall,
            'click_rate'   => $totalImpression > 0 ? round($totalClick / $totalImpression * 100, 2) : 0,
            'install_rate' => $totalClick > 0 ? round($totalInstall / $totalClick * 100, 2) : 0,
        ];
    }
}
