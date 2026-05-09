<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Cache;
use think\facade\Db;

/**
 * 系统性能监控服务 - V2.9.2 M24
 */
class MonitorService
{
    /**
     * 获取系统指标
     */
    public function getSystemMetrics(): array
    {
        $metrics = [
            'cpu'    => ['status' => 'unknown', 'value' => null],
            'memory' => ['status' => 'unknown', 'value' => null],
            'disk'   => ['status' => 'unknown', 'value' => null],
            'load'   => ['status' => 'unknown', 'value' => null],
        ];

        // CPU
        try {
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                $metrics['load'] = ['status' => 'ok', 'value' => $load];
            }
        } catch (\Throwable) {
            $metrics['load'] = ['status' => 'unavailable', 'message' => '当前环境不支持负载查询'];
        }

        // 内存
        try {
            if (function_exists('memory_get_usage') && function_exists('memory_get_peak_usage')) {
                $metrics['memory'] = [
                    'status' => 'ok',
                    'usage'  => memory_get_usage(true),
                    'peak'   => memory_get_peak_usage(true),
                    'limit'  => $this->getMemoryLimit(),
                ];
            }
        } catch (\Throwable) {
            $metrics['memory'] = ['status' => 'unavailable', 'message' => '当前环境不支持内存查询'];
        }

        // 磁盘
        try {
            if (function_exists('disk_free_space') && function_exists('disk_total_space')) {
                $free = disk_free_space(root_path());
                $total = disk_total_space(root_path());
                $metrics['disk'] = [
                    'status' => 'ok',
                    'free'   => $free,
                    'total'  => $total,
                    'used_percent' => $total > 0 ? round((1 - $free / $total) * 100, 2) : 0,
                ];
            }
        } catch (\Throwable) {
            $metrics['disk'] = ['status' => 'unavailable', 'message' => '当前环境不支持磁盘查询'];
        }

        return $metrics;
    }

    /**
     * PHP指标
     */
    public function getPhpMetrics(): array
    {
        return [
            'version'       => PHP_VERSION,
            'sapi'          => PHP_SAPI,
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit'  => $this->getMemoryLimit(),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status(false) !== false,
        ];
    }

    /**
     * MySQL指标
     */
    public function getMysqlMetrics(): array
    {
        $metrics = [
            'version'      => 'unknown',
            'connections'  => ['status' => 'unknown'],
            'tables'       => [],
            'slow_queries' => ['status' => 'unavailable', 'message' => '当前环境不支持慢查询分析'],
        ];

        try {
            $version = Db::query('SELECT VERSION() as v');
            $metrics['version'] = $version[0]['v'] ?? 'unknown';

            $status = Db::query("SHOW STATUS LIKE 'Threads_connected'");
            $metrics['connections'] = [
                'status' => 'ok',
                'threads_connected' => $status[0]['Value'] ?? 0,
            ];

            $tables = Db::query("SELECT table_name, round(((data_length + index_length) / 1024 / 1024), 2) AS size_mb FROM information_schema.TABLES WHERE table_schema = DATABASE() ORDER BY size_mb DESC LIMIT 20");
            $metrics['tables'] = $tables;

            // 慢查询（含降级）
            try {
                $slowLog = Db::query("SHOW VARIABLES LIKE 'slow_query_log'");
                $isEnabled = ($slowLog[0]['Value'] ?? '') === 'ON';
                if ($isEnabled) {
                    $slowCount = Db::query("SHOW GLOBAL STATUS LIKE 'Slow_queries'");
                    $metrics['slow_queries'] = [
                        'status' => 'ok',
                        'count'  => $slowCount[0]['Value'] ?? 0,
                    ];
                } else {
                    $metrics['slow_queries'] = ['status' => 'disabled', 'message' => 'slow_query_log 未开启'];
                }
            } catch (\Throwable $e) {
                $metrics['slow_queries'] = ['status' => 'unavailable', 'message' => '当前环境不支持慢查询分析'];
            }
        } catch (\Throwable $e) {
            $metrics['error'] = $e->getMessage();
        }

        return $metrics;
    }

    /**
     * 缓存指标
     */
    public function getCacheMetrics(): array
    {
        $metrics = ['driver' => config('cache.default', 'file'), 'status' => 'unknown'];

        if ($metrics['driver'] === 'redis') {
            try {
                $info = Cache::handler()->info();
                $metrics['status'] = 'ok';
                $metrics['used_memory'] = $info['used_memory'] ?? 0;
                $metrics['hits'] = $info['keyspace_hits'] ?? 0;
                $metrics['misses'] = $info['keyspace_misses'] ?? 0;
                $total = $metrics['hits'] + $metrics['misses'];
                $metrics['hit_rate'] = $total > 0 ? round($metrics['hits'] / $total * 100, 2) : 0;
            } catch (\Throwable $e) {
                $metrics['status'] = 'error';
                $metrics['error'] = $e->getMessage();
            }
        } else {
            $metrics['status'] = 'ok';
            $metrics['message'] = '文件缓存模式下命中率统计不可用';
        }

        return $metrics;
    }

    /**
     * 运行时日志分析
     */
    public function getRuntimeLogStats(): array
    {
        $logPath = runtime_path() . 'log/' . date('Ym') . '/';
        $stats = ['total_files' => 0, 'total_size' => 0, 'recent_errors' => []];

        if (!is_dir($logPath)) {
            return $stats;
        }

        $files = glob($logPath . '*.log');
        $stats['total_files'] = count($files);
        foreach ($files as $file) {
            $stats['total_size'] += filesize($file);
        }

        // 读取最近错误
        $errorFile = $logPath . date('d') . '_error.log';
        if (file_exists($errorFile)) {
            $lines = array_filter(array_map('trim', file($errorFile)));
            $stats['recent_errors'] = array_slice($lines, -20);
        }

        return $stats;
    }

    protected function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1') return -1;
        $unit = strtolower(substr($limit, -1));
        $value = (int) $limit;
        switch ($unit) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        return $value;
    }
}
