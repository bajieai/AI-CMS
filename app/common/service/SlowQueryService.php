<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;

/**
 * V2.9.35 PERF-2: 慢查询分析服务
 */
class SlowQueryService
{
    /**
     * 获取慢查询TOP10
     */
    public function getTopSlowQueries(int $limit = 10): array
    {
        // 从performance_log中获取慢请求
        $slowRequests = Db::name('performance_log')
            ->where('is_slow', 1)
            ->where('db_query_count', '>', 0)
            ->order('db_query_time', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();

        return $slowRequests;
    }

    /**
     * 获取DB查询统计
     */
    public function getDbStats(): array
    {
        $today = date('Y-m-d');

        $avgQueryCount = Db::name('performance_log')
            ->where('created_at_date', $today)
            ->avg('db_query_count');

        $avgQueryTime = Db::name('performance_log')
            ->where('created_at_date', $today)
            ->avg('db_query_time');

        $maxQueryCount = Db::name('performance_log')
            ->where('created_at_date', $today)
            ->max('db_query_count');

        $slowQueryCount = Db::name('performance_log')
            ->where('created_at_date', $today)
            ->where('is_slow', 1)
            ->count();

        return [
            'today_avg_query_count' => round($avgQueryCount, 1),
            'today_avg_query_time'  => round($avgQueryTime, 1),
            'today_max_query_count' => $maxQueryCount,
            'today_slow_count'      => $slowQueryCount,
        ];
    }
}
