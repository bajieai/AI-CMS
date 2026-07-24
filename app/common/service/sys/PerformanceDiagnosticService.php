<?php

declare(strict_types=1);

namespace app\common\service\sys;

use think\facade\Db;

/**
 * 性能诊断服务
 */
class PerformanceDiagnosticService
{
    /**
     * 获取慢查询
     */
    public static function getSlowQueries(int $limit = 20): array
    {
        try {
            return Db::query("
                SELECT * FROM information_schema.processlist
                WHERE TIME > 2
                ORDER BY TIME DESC
                LIMIT ?
            ", [$limit]);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 内存使用
     */
    public static function getMemoryUsage(): array
    {
        return [
            'php_usage'    => memory_get_usage(true),
            'php_peak'     => memory_get_peak_usage(true),
            'php_limit'    => ini_get('memory_limit'),
            'system'       => SystemMonitorService::getMemoryUsage(),
        ];
    }

    /**
     * CPU使用
     */
    public static function getCpuUsage(): array
    {
        return SystemMonitorService::getCpuUsage();
    }

    /**
     * 性能基线
     */
    public static function getPerformanceBaseline(): array
    {
        return [
            'avg_response_time' => self::getAvgResponseTime(),
            'avg_query_time'    => self::getAvgQueryTime(),
            'cache_hit_rate'    => self::getCacheHitRate(),
            'opcache_hit_rate'  => self::getOpcacheHitRate(),
        ];
    }

    /**
     * 生成诊断报告
     */
    public static function generateReport(): array
    {
        $server = SystemMonitorService::getServerStatus();
        $db = SystemMonitorService::getDatabaseStatus();
        $queue = SystemMonitorService::getQueueStatus();
        $baseline = self::getPerformanceBaseline();

        $issues = [];
        $suggestions = [];

        // CPU检查
        if (($server['cpu']['usage'] ?? 0) > 80) {
            $issues[] = ['type' => 'cpu', 'level' => 'critical', 'message' => 'CPU使用率超过80%'];
            $suggestions[] = '考虑增加CPU核心数或优化计算密集型任务';
        }

        // 内存检查
        $memUsage = $server['memory']['usage_percent'] ?? 0;
        if ($memUsage > 80) {
            $issues[] = ['type' => 'memory', 'level' => 'critical', 'message' => '内存使用率超过80%'];
            $suggestions[] = '增加内存或优化内存使用';
        }

        // 磁盘检查
        foreach ($server['disk'] ?? [] as $disk) {
            if ($disk['usage_percent'] > 90) {
                $issues[] = ['type' => 'disk', 'level' => 'critical', 'message' => "磁盘 {$disk['path']} 使用率超过90%"];
                $suggestions[] = "清理 {$disk['path']} 目录或扩容磁盘";
            }
        }

        // 数据库检查
        if (($db['slow_queries'] ?? 0) > 10) {
            $issues[] = ['type' => 'database', 'level' => 'warning', 'message' => '慢查询数量较多'];
            $suggestions[] = '检查并优化慢查询SQL';
        }

        // 队列检查
        if (($queue['pending'] ?? 0) > 100) {
            $issues[] = ['type' => 'queue', 'level' => 'warning', 'message' => '队列积压较多'];
            $suggestions[] = '增加消费者数量或优化任务处理速度';
        }

        $criticalCount = count(array_filter($issues, fn($i) => $i['level'] === 'critical'));

        return [
            'time'         => date('Y-m-d H:i:s'),
            'overall'      => $criticalCount > 0 ? 'critical' : (count($issues) > 0 ? 'warning' : 'healthy'),
            'server'       => $server,
            'database'     => $db,
            'queue'        => $queue,
            'baseline'     => $baseline,
            'issues'       => $issues,
            'suggestions'  => $suggestions,
        ];
    }

    /**
     * 获取优化建议
     */
    public static function getSuggestions(): array
    {
        $report = self::generateReport();
        return $report['suggestions'];
    }

    private static function getAvgResponseTime(): float
    {
        // 从缓存或日志中获取平均响应时间
        return 0.0;
    }

    private static function getAvgQueryTime(): float
    {
        return 0.0;
    }

    private static function getCacheHitRate(): float
    {
        return 0.0;
    }

    private static function getOpcacheHitRate(): float
    {
        if (!function_exists('opcache_get_status')) return 0.0;
        $status = opcache_get_status(false);
        if (!$status) return 0.0;
        $hits = $status['opcache_statistics']['hits'] ?? 0;
        $misses = $status['opcache_statistics']['misses'] ?? 0;
        $total = $hits + $misses;
        return $total > 0 ? round($hits / $total * 100, 2) : 0.0;
    }
}
