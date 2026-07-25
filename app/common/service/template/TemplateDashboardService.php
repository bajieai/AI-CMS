<?php
declare(strict_types=1);
namespace app\common\service\template;

use think\facade\Db;
use think\facade\Cache;

/**
 * 模板运营数据看板Service - V2.9.32 T4-5
 */
class TemplateDashboardService
{
    private const CACHE_TAG = 'template_dashboard';

    public function getOverview(): array
    {
        $cacheKey = 'dashboard_overview';
        $cached = Cache::get($cacheKey);
        if ($cached !== null) return $cached;

        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        $totalInstall = (int) Db::table($prefix . 'template_install_log')->where('action', 'install')->where('result', 1)->count();
        $totalUninstall = (int) Db::table($prefix . 'template_install_log')->where('action', 'uninstall')->where('result', 1)->count();
        $todayStart = strtotime(date('Y-m-d'));
        $todayInstall = (int) Db::table($prefix . 'template_install_log')->where('action', 'install')->where('result', 1)->where('create_time', '>=', $todayStart)->count();
        $todayUninstall = (int) Db::table($prefix . 'template_install_log')->where('action', 'uninstall')->where('result', 1)->where('create_time', '>=', $todayStart)->count();
        $activeTemplates = (int) Db::table($prefix . 'template_install')->where('is_active', 1)->count();

        $result = ['total_install' => $totalInstall, 'total_uninstall' => $totalUninstall, 'active_templates' => $activeTemplates, 'today_install' => $todayInstall, 'today_uninstall' => $todayUninstall];
        Cache::set($cacheKey, $result, 300);
        return $result;
    }

    public function getTrend(int $days = 30): array
    {
        $cacheKey = "dashboard_trend_{$days}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) return $cached;

        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', time() - $i * 86400);
            $start = strtotime($date);
            $end = $start + 86400;
            $install = (int) Db::table($prefix . 'template_install_log')->where('action', 'install')->where('result', 1)->whereBetweenTime('create_time', $start, $end)->count();
            $uninstall = (int) Db::table($prefix . 'template_install_log')->where('action', 'uninstall')->where('result', 1)->whereBetweenTime('create_time', $start, $end)->count();
            $trend[] = ['date' => $date, 'install' => $install, 'uninstall' => $uninstall, 'net' => $install - $uninstall];
        }

        $result = ['days' => $days, 'trend' => $trend];
        Cache::set($cacheKey, $result, 600);
        return $result;
    }

    public function getTopTemplates(int $limit = 10): array
    {
        return \app\common\model\TemplateStore::where('status', 1)->order('install_count DESC')->limit($limit)->field('id, name, slug, install_count, avg_rating')->select()->toArray();
    }

    public function getUninstallWarnings(): array
    {
        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        $templates = \app\common\model\TemplateStore::where('status', 1)->where('install_count', '>', 10)->field('id, name, install_count')->select()->toArray();
        $warnings = [];
        foreach ($templates as $t) {
            $uninstallCount = (int) Db::table($prefix . 'template_install_log')->where('store_id', $t['id'])->where('action', 'uninstall')->where('result', 1)->count();
            $rate = $t['install_count'] > 0 ? $uninstallCount / $t['install_count'] : 0;
            if ($rate > 0.3) $warnings[] = ['id' => $t['id'], 'name' => $t['name'], 'install_count' => $t['install_count'], 'uninstall_count' => $uninstallCount, 'uninstall_rate' => round($rate * 100, 1)];
        }
        return $warnings;
    }

    public function exportData(string $startDate, string $endDate): array
    {
        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        $start = strtotime($startDate);
        $end = strtotime($endDate) + 86400;
        return Db::table($prefix . 'template_install_log')
            ->whereBetweenTime('create_time', $start, $end)
            ->order('create_time', 'desc')
            ->select()
            ->toArray();
    }
}
