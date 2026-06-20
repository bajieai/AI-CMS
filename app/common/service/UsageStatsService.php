<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\model\TemplateUsageLog;
use app\common\model\TemplateDailyStats;
use app\common\model\TemplateInstallLog;
use think\facade\Cache;

/**
 * V2.9.25 N-2: 模板使用统计服务
 */
class UsageStatsService
{
    private string $cacheTag = 'template_usage_stats';

    /**
     * 记录埋点事件
     */
    public function trackEvent(int $templateId, int $memberId, string $eventType, array $extra = []): void
    {
        TemplateUsageLog::record($templateId, $memberId, $eventType, $extra);

        // 实时增量更新日统计
        $date = date('Y-m-d');
        $fieldMap = [
            TemplateUsageLog::EVENT_VIEW => 'view_count',
            TemplateUsageLog::EVENT_INSTALL => 'install_count',
            TemplateUsageLog::EVENT_ACTIVATE => 'activate_count',
        ];

        if (isset($fieldMap[$eventType])) {
            TemplateDailyStats::increment($templateId, $date, $fieldMap[$eventType]);
        }

        Cache::tag($this->cacheTag)->clear();
    }

    /**
     * 批量记录埋点事件
     */
    public function batchTrack(array $events): int
    {
        $count = 0;
        foreach ($events as $event) {
            $templateId = (int)($event['template_id'] ?? 0);
            $memberId = (int)($event['member_id'] ?? 0);
            $eventType = $event['event_type'] ?? '';
            $extra = $event['extra'] ?? [];

            if ($templateId > 0 && $eventType) {
                $this->trackEvent($templateId, $memberId, $eventType, $extra);
                $count++;
            }
        }
        return $count;
    }

    /**
     * 获取使用统计概览
     */
    public function getOverview(string $startDate = '', string $endDate = ''): array
    {
        $start = $startDate ?: date('Y-m-d', strtotime('-30 days'));
        $end = $endDate ?: date('Y-m-d');

        $cacheKey = 'usage_overview_' . md5($start . $end);
        return Cache::tag($this->cacheTag)->remember($cacheKey, function () use ($start, $end) {
            // 总浏览量
            $totalViews = TemplateUsageLog::whereBetween('create_date', [$start, $end])
                ->where('event_type', TemplateUsageLog::EVENT_VIEW)
                ->count();

            // 独立访客
            $uniqueVisitors = TemplateUsageLog::whereBetween('create_date', [$start, $end])
                ->where('event_type', TemplateUsageLog::EVENT_VIEW)
                ->distinct('member_id')
                ->count('member_id');

            // 安装次数
            $installs = TemplateInstallLog::where('action', TemplateInstallLog::ACTION_INSTALL)
                ->whereBetweenTime('create_time', strtotime($start), strtotime($end . ' 23:59:59'))
                ->count();

            // 卸载次数
            $uninstalls = TemplateInstallLog::where('action', TemplateInstallLog::ACTION_UNINSTALL)
                ->whereBetweenTime('create_time', strtotime($start), strtotime($end . ' 23:59:59'))
                ->count();

            // DAU（最近7天平均）
            $dau7 = TemplateDailyStats::where('template_id', 0)
                ->whereBetween('stats_date', [date('Y-m-d', strtotime('-7 days')), date('Y-m-d')])
                ->avg('dau');

            // MAU
            $mau = TemplateDailyStats::where('template_id', 0)
                ->where('stats_date', date('Y-m-01'))
                ->value('mau') ?? 0;

            // 设备分布
            $deviceDist = TemplateUsageLog::whereBetween('create_date', [$start, $end])
                ->field('device, COUNT(*) as count')
                ->group('device')
                ->select()
                ->toArray();

            return [
                'date_range' => ['start' => $start, 'end' => $end],
                'total_views' => $totalViews,
                'unique_visitors' => $uniqueVisitors,
                'installs' => $installs,
                'uninstalls' => $uninstalls,
                'dau_7day_avg' => round((float)$dau7, 0),
                'mau' => (int)$mau,
                'device_distribution' => $deviceDist,
            ];
        }, 600);
    }

    /**
     * 获取模板使用趋势
     */
    public function getUsageTrend(string $startDate = '', string $endDate = '', int $templateId = 0): array
    {
        $start = $startDate ?: date('Y-m-d', strtotime('-30 days'));
        $end = $endDate ?: date('Y-m-d');

        $query = TemplateDailyStats::whereBetween('stats_date', [$start, $end]);
        if ($templateId > 0) {
            $query->where('template_id', $templateId);
        }

        $trend = $query->field("stats_date, SUM(view_count) as views, SUM(unique_visitors) as visitors, SUM(install_count) as installs, SUM(uninstall_count) as uninstalls")
            ->group('stats_date')
            ->order('stats_date', 'asc')
            ->select()
            ->toArray();

        return $trend;
    }

    /**
     * 获取热门模板（按浏览量）
     */
    public function getHotTemplatesByViews(int $limit = 10, string $startDate = ''): array
    {
        $start = $startDate ?: date('Y-m-d', strtotime('-30 days'));

        return TemplateUsageLog::where('event_type', TemplateUsageLog::EVENT_VIEW)
            ->where('create_date', '>=', $start)
            ->field('template_id, COUNT(*) as view_count')
            ->group('template_id')
            ->order('view_count', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 汇总每日统计（定时任务调用）
     */
    public function aggregateDailyStats(string $date = ''): array
    {
        $date = $date ?: date('Y-m-d', strtotime('-1 day'));
        $startTs = strtotime($date . ' 00:00:00');
        $endTs = strtotime($date . ' 23:59:59');

        // 全站汇总（template_id=0）
        $views = TemplateUsageLog::where('create_date', $date)->where('event_type', 'view')->count();
        $visitors = TemplateUsageLog::where('create_date', $date)->where('event_type', 'view')->distinct('member_id')->count('member_id');
        $installs = TemplateInstallLog::where('action', 1)->whereBetweenTime('create_time', $startTs, $endTs)->count();
        $uninstalls = TemplateInstallLog::where('action', 2)->whereBetweenTime('create_time', $startTs, $endTs)->count();

        $stats = TemplateDailyStats::getOrCreate(0, $date);
        $stats->view_count = $views;
        $stats->unique_visitors = $visitors;
        $stats->install_count = $installs;
        $stats->uninstall_count = $uninstalls;
        $stats->dau = $visitors;
        $stats->save();

        // MAU（每月1日计算）
        if (date('j') === '1') {
            $mau = TemplateUsageLog::where('event_type', 'view')
                ->whereBetween('create_date', [date('Y-m-01', strtotime('last month')), date('Y-m-t', strtotime('last month'))])
                ->distinct('member_id')
                ->count('member_id');
            $stats->mau = $mau;
            $stats->save();
        }

        // 按模板汇总
        $templateStats = TemplateUsageLog::where('create_date', $date)
            ->field('template_id, SUM(IF(event_type="view",1,0)) as views, COUNT(DISTINCT member_id) as visitors')
            ->group('template_id')
            ->select();

        foreach ($templateStats as $ts) {
            $record = TemplateDailyStats::getOrCreate((int)$ts['template_id'], $date);
            $record->view_count = (int)$ts['views'];
            $record->unique_visitors = (int)$ts['visitors'];
            $record->save();
        }

        Cache::tag($this->cacheTag)->clear();

        return [
            'date' => $date,
            'total_views' => $views,
            'total_visitors' => $visitors,
            'total_installs' => $installs,
            'total_uninstalls' => $uninstalls,
            'template_count' => count($templateStats),
        ];
    }
}
