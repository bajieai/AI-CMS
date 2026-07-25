<?php
declare(strict_types=1);

namespace app\common\service\sys;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Config;
use think\facade\Log;

/**
 * 系统监控服务
 * V2.9.40 SYS-ROBUST2-1
 *
 * 提供 CPU/内存/磁盘/网络/负载/应用/数据库/缓存/队列 全维度监控
 */
class SystemMonitorService
{
    private const CACHE_TAG = 'system_monitor';
    private const CACHE_TTL = 30;

    /**
     * 获取服务器状态: CPU/内存/磁盘/网络/负载
     */
    public function getServerStatus(): array
    {
        return Cache::remember('server_status', function () {
            return [
                'cpu'       => $this->getCpuStatus(),
                'memory'    => $this->getMemoryStatus(),
                'disk'      => $this->getDiskStatus(),
                'network'   => $this->getNetworkStatus(),
                'load'      => $this->getLoadAvg(),
                'uptime'    => $this->getUptime(),
                'timestamp' => time(),
            ];
        }, self::CACHE_TTL);
    }

    /**
     * 获取应用状态: PHP版本/OPcache/进程数
     */
    public function getApplicationStatus(): array
    {
        return Cache::remember('app_status', function () {
            return [
                'php_version'    => PHP_VERSION,
                'sapi'           => PHP_SAPI,
                'opcache'        => $this->getOpcacheStatus(),
                'max_memory'     => ini_get('memory_limit'),
                'processes'      => $this->getProcessCount(),
                'framework'      => 'ThinkPHP 8.1',
                'timestamp'      => time(),
            ];
        }, self::CACHE_TTL);
    }

    /**
     * 获取数据库状态: MySQL连接数/慢查询/表大小
     */
    public function getDatabaseStatus(): array
    {
        return Cache::remember('db_status', function () {
            $status = [
                'connections'  => 0,
                'max_connections' => 0,
                'slow_queries'  => 0,
                'table_count'   => 0,
                'db_size'       => '0 MB',
                'tables'        => [],
                'timestamp'     => time(),
            ];

            try {
                // 连接数
                $connResult = Db::query("SHOW STATUS WHERE Variable_name IN ('Threads_connected','Max_used_connections','Slow_queries')");
                foreach ($connResult as $row) {
                    if ($row['Variable_name'] === 'Threads_connected') {
                        $status['connections'] = (int)$row['Value'];
                    } elseif ($row['Variable_name'] === 'Max_used_connections') {
                        $status['max_connections'] = (int)$row['Value'];
                    } elseif ($row['Variable_name'] === 'Slow_queries') {
                        $status['slow_queries'] = (int)$row['Value'];
                    }
                }

                // 最大连接数配置
                $maxConn = Db::query("SHOW VARIABLES LIKE 'max_connections'");
                if (!empty($maxConn)) {
                    $status['max_connections'] = max($status['max_connections'], (int)$maxConn[0]['Value']);
                }

                // 表大小和数量
                $dbConfig = Config::get('database.connections.mysql.database', '');
                $tables = Db::query(
                    "SELECT table_name AS `name`, data_length, index_length, table_rows 
                     FROM information_schema.tables 
                     WHERE table_schema = ? AND table_type = 'BASE TABLE' 
                     ORDER BY (data_length + index_length) DESC 
                     LIMIT 10",
                    [$dbConfig]
                );
                $status['tables'] = array_map(function ($t) {
                    return [
                        'name'  => $t['name'],
                        'size'  => $this->formatBytes((int)$t['data_length'] + (int)$t['index_length']),
                        'rows'  => (int)$t['table_rows'],
                    ];
                }, $tables);

                // 数据库总大小
                $totalSize = Db::query(
                    "SELECT SUM(data_length + index_length) AS total 
                     FROM information_schema.tables 
                     WHERE table_schema = ?",
                    [$dbConfig]
                );
                if (!empty($totalSize) && $totalSize[0]['total']) {
                    $status['db_size'] = $this->formatBytes((int)$totalSize[0]['total']);
                }
                $status['table_count'] = count(Db::query("SHOW TABLES"));
            } catch (\Throwable $e) {
                Log::warning('SystemMonitorService getDatabaseStatus failed: ' . $e->getMessage());
            }

            return $status;
        }, self::CACHE_TTL);
    }

    /**
     * 获取缓存状态: Redis/File
     */
    public function getCacheStatus(): array
    {
        return Cache::remember('cache_status', function () {
            $driver = Config::get('cache.default', 'file');
            $status = [
                'driver'    => $driver,
                'redis'     => null,
                'file'      => null,
                'timestamp' => time(),
            ];

            // Redis状态
            if ($driver === 'redis' || class_exists('redis')) {
                try {
                    $redis = Cache::store('redis')->handler();
                    if ($redis instanceof \Redis) {
                        $info = $redis->info();
                        $status['redis'] = [
                            'version'       => $info['redis_version'] ?? 'unknown',
                            'connected_clients' => $info['connected_clients'] ?? 0,
                            'used_memory'   => $this->formatBytes((int)($info['used_memory'] ?? 0)),
                            'used_memory_peak' => $this->formatBytes((int)($info['used_memory_peak'] ?? 0)),
                            'uptime_days'   => (int)($info['uptime_in_days'] ?? 0),
                            'hit_rate'      => $this->calcHitRate($info),
                        ];
                    }
                } catch (\Throwable $e) {
                    $status['redis'] = ['error' => $e->getMessage()];
                }
            }

            // File缓存大小
            $cachePath = runtime_path() . 'cache';
            $status['file'] = [
                'path'  => $cachePath,
                'size'  => $this->getDirSize($cachePath),
                'files' => $this->getDirFileCount($cachePath),
            ];

            return $status;
        }, self::CACHE_TTL);
    }

    /**
     * 获取队列状态: 积压情况
     */
    public function getQueueStatus(): array
    {
        return Cache::remember('queue_status', function () {
            $status = [
                'queues'    => [],
                'total_pending' => 0,
                'timestamp' => time(),
            ];

            $queues = ['default', 'ai', 'email', 'push', 'export', 'cleanup'];
            foreach ($queues as $queue) {
                try {
                    $count = Db::name('queue_jobs')
                        ->where('queue', $queue)
                        ->where('reserved_at', 0)
                        ->count();
                } catch (\Throwable) {
                    $count = 0;
                }
                $status['queues'][] = ['name' => $queue, 'pending' => $count];
                $status['total_pending'] += $count;
            }

            return $status;
        }, self::CACHE_TTL);
    }

    /**
     * 综合健康检查
     */
    public function getHealthCheck(): array
    {
        return Cache::remember('health_check', function () {
            $checks = [];

            // 数据库检查
            try {
                Db::query('SELECT 1');
                $checks['database'] = ['status' => 'ok', 'message' => '数据库连接正常'];
            } catch (\Throwable $e) {
                $checks['database'] = ['status' => 'error', 'message' => $e->getMessage()];
            }

            // 缓存检查
            try {
                Cache::set('health_check_test', 1, 5);
                $val = Cache::get('health_check_test');
                $checks['cache'] = ['status' => $val ? 'ok' : 'error', 'message' => $val ? '缓存读写正常' : '缓存读取失败'];
            } catch (\Throwable $e) {
                $checks['cache'] = ['status' => 'error', 'message' => $e->getMessage()];
            }

            // 磁盘空间检查
            $disk = $this->getDiskStatus();
            $diskPercent = $disk['percent'] ?? 0;
            if ($diskPercent > 90) {
                $checks['disk'] = ['status' => 'critical', 'message' => "磁盘使用率过高: {$diskPercent}%"];
            } elseif ($diskPercent > 80) {
                $checks['disk'] = ['status' => 'warning', 'message' => "磁盘使用率偏高: {$diskPercent}%"];
            } else {
                $checks['disk'] = ['status' => 'ok', 'message' => "磁盘空间充足: {$diskPercent}%"];
            }

            // 内存检查
            $mem = $this->getMemoryStatus();
            $memPercent = $mem['percent'] ?? 0;
            if ($memPercent > 90) {
                $checks['memory'] = ['status' => 'critical', 'message' => "内存使用率过高: {$memPercent}%"];
            } elseif ($memPercent > 80) {
                $checks['memory'] = ['status' => 'warning', 'message' => "内存使用率偏高: {$memPercent}%"];
            } else {
                $checks['memory'] = ['status' => 'ok', 'message' => "内存使用正常: {$memPercent}%"];
            }

            // CPU检查
            $cpu = $this->getCpuStatus();
            $cpuPercent = $cpu['percent'] ?? 0;
            if ($cpuPercent > 90) {
                $checks['cpu'] = ['status' => 'critical', 'message' => "CPU使用率过高: {$cpuPercent}%"];
            } elseif ($cpuPercent > 80) {
                $checks['cpu'] = ['status' => 'warning', 'message' => "CPU使用率偏高: {$cpuPercent}%"];
            } else {
                $checks['cpu'] = ['status' => 'ok', 'message' => "CPU使用正常: {$cpuPercent}%"];
            }

            // 整体评分
            $criticalCount = count(array_filter($checks, fn($c) => $c['status'] === 'critical'));
            $warningCount = count(array_filter($checks, fn($c) => $c['status'] === 'warning'));
            $okCount = count(array_filter($checks, fn($c) => $c['status'] === 'ok'));

            $overall = 'healthy';
            if ($criticalCount > 0) {
                $overall = 'critical';
            } elseif ($warningCount > 0) {
                $overall = 'warning';
            }

            return [
                'checks'   => $checks,
                'overall'  => $overall,
                'score'    => $okCount * 100 / count($checks),
                'summary'  => "{$okCount} ok / {$warningCount} warning / {$criticalCount} critical",
                'timestamp' => time(),
            ];
        }, self::CACHE_TTL);
    }

    // ===== 内部辅助方法 =====

    private function getCpuStatus(): array
    {
        $percent = 0;
        $cores = 1;
        $loadAvg = [0, 0, 0];

        if (function_exists('sys_getloadavg')) {
            $loadAvg = sys_getloadavg();
            $cores = $this->getCpuCores();
            $percent = $cores > 0 ? round(($loadAvg[0] / $cores) * 100, 1) : 0;
        }

        // Docker环境: 尝试读取cgroup
        if ($percent === 0) {
            $cgroupUsage = $this->readCgroupCpuUsage();
            if ($cgroupUsage !== null) {
                $percent = $cgroupUsage;
            }
        }

        return [
            'percent' => min(100, $percent),
            'cores'   => $cores,
            'load_1'  => round($loadAvg[0], 2),
            'load_5'  => round($loadAvg[1], 2),
            'load_15' => round($loadAvg[2], 2),
        ];
    }

    private function getMemoryStatus(): array
    {
        $total = 0;
        $free = 0;
        $used = 0;

        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: 使用wmic
            $output = @shell_exec('wmic OS get TotalVisibleMemorySize,FreePhysicalMemory /Value 2>nul');
            if ($output) {
                if (preg_match('/TotalVisibleMemorySize=(\d+)/', $output, $m)) {
                    $total = (int)$m[1] * 1024;
                }
                if (preg_match('/FreePhysicalMemory=(\d+)/', $output, $m)) {
                    $free = (int)$m[1] * 1024;
                }
                $used = $total - $free;
            }
        } else {
            // Linux: 读取/proc/meminfo
            $meminfo = @file_get_contents('/proc/meminfo');
            if ($meminfo) {
                if (preg_match('/MemTotal:\s+(\d+)/', $meminfo, $m)) {
                    $total = (int)$m[1] * 1024;
                }
                if (preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $m)) {
                    $free = (int)$m[1] * 1024;
                } elseif (preg_match('/MemFree:\s+(\d+)/', $meminfo, $m)) {
                    $free = (int)$m[1] * 1024;
                }
                $used = $total - $free;
            }
        }

        // Fallback: 使用PHP memory_get_usage
        if ($total === 0) {
            $used = memory_get_usage(true);
            $total = $this->parseMemoryLimit(ini_get('memory_limit'));
            $free = max(0, $total - $used);
        }

        $percent = $total > 0 ? round(($used / $total) * 100, 1) : 0;

        return [
            'total'    => $this->formatBytes($total),
            'used'     => $this->formatBytes($used),
            'free'     => $this->formatBytes($free),
            'percent'  => $percent,
            'php_usage' => $this->formatBytes(memory_get_usage(true)),
            'php_peak' => $this->formatBytes(memory_get_peak_usage(true)),
        ];
    }

    private function getDiskStatus(): array
    {
        $path = '/';
        if (PHP_OS_FAMILY === 'Windows') {
            $path = 'C:';
        }

        $total = @disk_total_space($path);
        $free = @disk_free_space($path);
        $used = $total - $free;
        $percent = $total > 0 ? round(($used / $total) * 100, 1) : 0;

        return [
            'path'     => $path,
            'total'    => $this->formatBytes((int)$total),
            'used'     => $this->formatBytes((int)$used),
            'free'     => $this->formatBytes((int)$free),
            'percent'  => $percent,
        ];
    }

    private function getNetworkStatus(): array
    {
        $status = [
            'hostname'  => gethostname() ?: 'unknown',
            'ip'        => $_SERVER['SERVER_ADDR'] ?? '0.0.0.0',
            'rx_bytes'  => '0 B',
            'tx_bytes'  => '0 B',
        ];

        if (PHP_OS_FAMILY !== 'Windows') {
            $netdev = @file_get_contents('/proc/net/dev');
            if ($netdev) {
                $lines = explode("\n", $netdev);
                $rxTotal = 0;
                $txTotal = 0;
                foreach ($lines as $line) {
                    if (preg_match('/:\s*(\d+)\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+(\d+)/', $line, $m)) {
                        $rxTotal += (int)$m[1];
                        $txTotal += (int)$m[2];
                    }
                }
                $status['rx_bytes'] = $this->formatBytes($rxTotal);
                $status['tx_bytes'] = $this->formatBytes($txTotal);
            }
        }

        return $status;
    }

    private function getLoadAvg(): array
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                'load_1'  => round($load[0], 2),
                'load_5'  => round($load[1], 2),
                'load_15' => round($load[2], 2),
            ];
        }
        return ['load_1' => 0, 'load_5' => 0, 'load_15' => 0];
    }

    private function getUptime(): string
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $uptime = @file_get_contents('/proc/uptime');
            if ($uptime) {
                $seconds = (float)explode(' ', $uptime)[0];
                $days = floor($seconds / 86400);
                $hours = floor(($seconds % 86400) / 3600);
                $mins = floor(($seconds % 3600) / 60);
                return "{$days}天 {$hours}小时 {$mins}分钟";
            }
        }
        return 'unknown';
    }

    private function getOpcacheStatus(): array
    {
        if (function_exists('opcache_get_status')) {
            $status = opcache_get_status(false);
            if ($status) {
                return [
                    'enabled'      => $status['opcache_enabled'] ?? false,
                    'memory_used'  => $this->formatBytes((int)($status['memory_usage']['used_memory'] ?? 0)),
                    'memory_free'  => $this->formatBytes((int)($status['memory_usage']['free_memory'] ?? 0)),
                    'hit_rate'     => round(($status['opcache_statistics']['opcache_hit_rate'] ?? 0) * 100, 2),
                    'num_scripts'  => $status['opcache_statistics']['num_cached_scripts'] ?? 0,
                ];
            }
        }
        return ['enabled' => false];
    }

    private function getProcessCount(): int
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $output = @shell_exec('ps aux | wc -l');
            if ($output) {
                return max(0, (int)trim($output) - 1);
            }
        }
        return 0;
    }

    private function getCpuCores(): int
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            // cgroup v2
            if (is_file('/sys/fs/cgroup/cpu.max')) {
                $quota = @file_get_contents('/sys/fs/cgroup/cpu.max');
                if ($quota && str_contains($quota, ' ')) {
                    [$quota, $period] = explode(' ', trim($quota));
                    if ($quota !== 'max' && $period > 0) {
                        return max(1, (int)ceil($quota / $period));
                    }
                }
            }
            if (is_file('/proc/cpuinfo')) {
                $cpuinfo = @file_get_contents('/proc/cpuinfo');
                if ($cpuinfo) {
                    return substr_count($cpuinfo, 'processor');
                }
            }
        }
        return 1;
    }

    private function readCgroupCpuUsage(): ?int
    {
        // cgroup v2: /sys/fs/cgroup/cpu.stat
        if (is_file('/sys/fs/cgroup/cpu.stat')) {
            $stat = @file_get_contents('/sys/fs/cgroup/cpu.stat');
            if ($stat && preg_match('/usage_usec\s+(\d+)/', $stat, $m)) {
                return min(100, (int)round($m[1] / 1000000));
            }
        }
        return null;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return 0;
        }
        $value = (int)$limit;
        $unit = strtolower(substr($limit, -1));
        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    private function calcHitRate(array $info): float
    {
        $hits = (int)($info['keyspace_hits'] ?? 0);
        $misses = (int)($info['keyspace_misses'] ?? 0);
        $total = $hits + $misses;
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;
    }

    private function getDirSize(string $path): string
    {
        if (!is_dir($path)) {
            return '0 B';
        }
        $size = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                $size += $file->getSize();
            }
        } catch (\Throwable) {
            // ignore
        }
        return $this->formatBytes($size);
    }

    private function getDirFileCount(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }
        $count = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $count++;
                }
            }
        } catch (\Throwable) {
            // ignore
        }
        return $count;
    }
}
