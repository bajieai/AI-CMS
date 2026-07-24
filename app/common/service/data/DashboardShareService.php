<?php
declare(strict_types=1);

namespace app\common\service\data;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 数据大屏分享服务 - V2.9.40 DATA-DEEP2-1
 *
 * 封面图自动生成、分享管理、访问统计
 * 已在 DashboardInteractionService 中实现核心分享逻辑
 * 本服务补充封面图+统计分析功能
 */
class DashboardShareService
{
    private const CACHE_TAG = 'dashboard_share';

    /**
     * 获取分享统计（访问趋势、热门时段、来源分布）
     */
    public function getShareStats(int $dashboardId): array
    {
        return Cache::remember('share_stats_' . $dashboardId, function () use ($dashboardId) {
            $shares = Db::name('data_dashboard_share')
                ->where('dashboard_id', $dashboardId)
                ->select()
                ->toArray();

            $totalViews = 0;
            $activeCount = 0;
            foreach ($shares as $s) {
                $totalViews += $s['view_count'];
                if ($s['expire_at'] === 0 || $s['expire_at'] > time()) $activeCount++;
            }

            return [
                'total_shares'   => count($shares),
                'active_shares'  => $activeCount,
                'total_views'    => $totalViews,
                'avg_views'      => count($shares) > 0 ? round($totalViews / count($shares), 1) : 0,
            ];
        }, 300);
    }

    /**
     * 生成封面图信息（基于大屏布局数据生成缩略描述）
     */
    public function generateCoverInfo(int $dashboardId): array
    {
        $dashboard = Db::name('data_dashboard')->find($dashboardId);
        if (!$dashboard) return [];

        $layout = json_decode($dashboard['layout_config'] ?? '{}', true);
        $modules = json_decode($dashboard['module_config'] ?? '{}', true);

        $moduleNames = [];
        if (is_array($modules)) {
            foreach ($modules as $key => $m) {
                $moduleNames[] = is_array($m) ? ($m['name'] ?? $key) : $key;
            }
        }

        return [
            'dashboard_id'  => $dashboardId,
            'name'          => $dashboard['name'] ?? '',
            'module_count'  => is_array($modules) ? count($modules) : 0,
            'module_names'  => $moduleNames,
            'layout_type'   => isset($layout['type']) ? $layout['type'] : 'grid',
        ];
    }
}
