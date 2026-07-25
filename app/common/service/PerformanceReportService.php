<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;

/**
 * V2.9.35 PERF-5: 性能报告服务
 */
class PerformanceReportService
{
    /**
     * 获取性能概览
     */
    public function getOverview(): array
    {
        $today = date('Y-m-d');

        $todayStats = Db::name('performance_log')
            ->where('created_at_date', $today)
            ->field('COUNT(*) as total, AVG(response_time) as avg_time, MAX(response_time) as max_time, SUM(is_slow) as slow_count, AVG(db_query_count) as avg_queries')
            ->find();

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $yesterdayStats = Db::name('performance_log')
            ->where('created_at_date', $yesterday)
            ->field('AVG(response_time) as avg_time')
            ->find();

        $avgTimeToday = round($todayStats['avg_time'] ?? 0, 1);
        $avgTimeYesterday = round($yesterdayStats['avg_time'] ?? 0, 1);
        $trend = $avgTimeYesterday > 0 ? round(($avgTimeToday - $avgTimeYesterday) / $avgTimeYesterday * 100, 1) : 0;

        return [
            'today_total'     => $todayStats['total'] ?? 0,
            'today_avg_time'  => $avgTimeToday,
            'today_max_time'  => $todayStats['max_time'] ?? 0,
            'today_slow'      => $todayStats['slow_count'] ?? 0,
            'today_avg_queries' => round($todayStats['avg_queries'] ?? 0, 1),
            'trend'           => $trend,
        ];
    }

    /**
     * 获取慢请求TOP10
     */
    public function getTopSlowRequests(int $limit = 10): array
    {
        return Db::name('performance_log')
            ->where('is_slow', 1)
            ->order('response_time', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取性能趋势（近7天）
     */
    public function getTrend(int $days = 7): array
    {
        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $stats = Db::name('performance_log')
                ->where('created_at_date', $date)
                ->field('COUNT(*) as total, AVG(response_time) as avg_time, SUM(is_slow) as slow_count')
                ->find();

            $trend[] = [
                'date'      => $date,
                'total'     => $stats['total'] ?? 0,
                'avg_time'  => round($stats['avg_time'] ?? 0, 1),
                'slow_count' => $stats['slow_count'] ?? 0,
            ];
        }

        return $trend;
    }

    /**
     * 获取URL性能分布
     */
    public function getUrlPerformance(int $limit = 20): array
    {
        return Db::name('performance_log')
            ->field('url, COUNT(*) as count, AVG(response_time) as avg_time, MAX(response_time) as max_time')
            ->group('url')
            ->order('avg_time', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
}
