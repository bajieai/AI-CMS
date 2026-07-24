<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;
use think\facade\Cache;

/**
 * V2.9.35 PERF-2: 数据库查询优化服务
 * 增强V2.9.31版本，增加N+1查询检测、连接池监控
 */
class DbQueryOptimizeService
{
    /**
     * 检测N+1查询（通过Db事件监听收集）
     */
    public function detectNPlusOne(string $table = '', int $threshold = 5): array
    {
        $cacheKey = 'db_nplusone_' . ($table ?: 'all') . '_' . date('Ymd');
        $issues = Cache::get($cacheKey, []);

        if (empty($issues)) {
            // 从慢查询日志分析N+1模式
            $issues = $this->analyzeNPlusOneFromLog($table, $threshold);
            Cache::set($cacheKey, $issues, 3600);
        }

        return $issues;
    }

    /**
     * 分析查询日志中的N+1模式
     */
    protected function analyzeNPlusOneFromLog(string $table, int $threshold): array
    {
        $issues = [];

        // 获取今日查询日志（从performance_log表）
        $logs = Db::name('performance_log')
            ->where('created_at', '>=', date('Y-m-d 00:00:00'))
            ->where('db_query_count', '>', $threshold)
            ->order('db_query_count', 'desc')
            ->limit(50)
            ->column('url', 'db_query_count');

        foreach ($logs as $count => $url) {
            $issues[] = [
                'url' => $url,
                'query_count' => $count,
                'suggestion' => '检查是否使用 with() 预加载关联数据',
                'severity' => $count > 20 ? 'high' : ($count > 10 ? 'medium' : 'low'),
            ];
        }

        return $issues;
    }

    /**
     * 连接池状态监控
     */
    public function getConnectionPoolStatus(): array
    {
        try {
            $result = Db::query("SHOW STATUS LIKE 'Threads_%'");
            $status = [];
            foreach ($result as $row) {
                $status[$row['Variable_name']] = $row['Value'];
            }

            return [
                'threads_connected' => (int) ($status['Threads_connected'] ?? 0),
                'threads_running' => (int) ($status['Threads_running'] ?? 0),
                'threads_cached' => (int) ($status['Threads_cached'] ?? 0),
                'threads_created' => (int) ($status['Threads_created'] ?? 0),
                'status' => ((int) ($status['Threads_running'] ?? 0) > 50) ? 'warning' : 'normal',
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 查询缓存建议
     */
    public function suggestQueryCache(string $table): array
    {
        $suggestions = [];

        // 检查高频查询表
        $prefix = Db::getConfig('prefix');
        $highFreqTables = [$prefix . 'content', $prefix . 'cate', $prefix . 'config', $prefix . 'member'];
        if (in_array($table, $highFreqTables, true)) {
            $suggestions[] = [
                'table' => $table,
                'suggestion' => '建议添加查询缓存，TTL 300-3600秒',
                'priority' => 'high',
            ];
        }

        // 检查大表
        try {
            $count = Db::table($table)->count();
            if ($count > 100000) {
                $suggestions[] = [
                    'table' => $table,
                    'suggestion' => "表数据量超过10万({$count})，建议添加索引或分区",
                    'priority' => 'high',
                ];
            }
        } catch (\Throwable $e) {
            // 忽略错误
        }

        return $suggestions;
    }

    /**
     * 批量查询优化：使用IN代替多个OR
     */
    public function optimizeBatchQuery(array $ids, string $table, string $pk = 'id'): array
    {
        if (count($ids) <= 1) {
            return ['optimized' => false, 'reason' => '单条查询无需优化'];
        }

        $chunks = array_chunk($ids, 1000);
        $results = [];

        foreach ($chunks as $chunk) {
            $chunkResults = Db::table($table)->whereIn($pk, $chunk)->select()->toArray();
            $results = array_merge($results, $chunkResults);
        }

        return [
            'optimized' => true,
            'original_queries' => count($ids),
            'optimized_queries' => count($chunks),
            'results_count' => count($results),
        ];
    }
}
