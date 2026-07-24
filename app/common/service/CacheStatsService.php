<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;
use think\facade\Cache;

/**
 * V2.9.35 PERF-1: 缓存统计服务
 * 命中率统计 + 大小监控 + 预热状态
 */
class CacheStatsService
{
    /**
     * 记录缓存命中/未命中
     */
    public function recordHit(string $key, bool $hit, string $level = 'L1'): void
    {
        $today = date('Y-m-d');
        $statKey = 'cache_stat_' . md5($key) . '_' . $today;

        // 使用缓存计数器（每5分钟批量写入DB）
        if ($hit) {
            Cache::inc($statKey . '_hit');
        } else {
            Cache::inc($statKey . '_miss');
        }
    }

    /**
     * 批量聚合统计数据到数据库（定时任务调用）
     */
    public function aggregate(): void
    {
        $today = date('Y-m-d');
        $pattern = 'cache_stat_*_' . $today;

        // 遍历缓存中的统计计数器
        // 由于ThinkPHP Cache不支持通配符遍历，使用Redis原生操作
        try {
            $redis = Cache::store('redis')->handler();
            $keys = $redis->keys('cache_stat_*_' . $today);
            $aggregated = [];

            foreach ($keys as $statKey) {
                $hitCount = (int) $redis->get($statKey . '_hit');
                $missCount = (int) $redis->get($statKey . '_miss');
                $total = $hitCount + $missCount;
                $hitRate = $total > 0 ? round(($hitCount / $total) * 100, 2) : 0;

                // 提取原始key
                $originalKey = str_replace(['cache_stat_', '_' . $today, '_hit', '_miss'], '', $statKey);

                $aggregated[$originalKey] = [
                    'stat_date'   => $today,
                    'cache_key'   => $originalKey,
                    'hit_count'   => $hitCount,
                    'miss_count'  => $missCount,
                    'hit_rate'    => $hitRate,
                    'level'       => 'L1',
                    'updated_at'  => date('Y-m-d H:i:s'),
                ];
            }

            // 批量写入DB
            foreach ($aggregated as $data) {
                Db::name('cache_stats')->replace($data);
            }
        } catch (\Throwable) {
            // Redis不可用时跳过
        }
    }

    /**
     * 获取缓存统计
     */
    public function getStats(string $date = ''): array
    {
        $date = $date ?: date('Y-m-d');

        $stats = Db::name('cache_stats')
            ->where('stat_date', $date)
            ->order('hit_count', 'desc')
            ->limit(50)
            ->select()
            ->toArray();

        $totalHit = 0;
        $totalMiss = 0;
        foreach ($stats as $s) {
            $totalHit += $s['hit_count'];
            $totalMiss += $s['miss_count'];
        }

        $totalAll = $totalHit + $totalMiss;
        $overallRate = $totalAll > 0 ? round(($totalHit / $totalAll) * 100, 2) : 0;

        return [
            'date'        => $date,
            'stats'       => $stats,
            'total_hit'   => $totalHit,
            'total_miss'  => $totalMiss,
            'hit_rate'    => $overallRate,
        ];
    }
}
