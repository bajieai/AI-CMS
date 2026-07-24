<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.39 SYS-ROBUST-4: 健康检查服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\system;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Log;

/**
 * 健康检查服务 - V2.9.39 SYS-ROBUST-4
 * MySQL/Redis/磁盘/CPU/队列/外部服务检查
 */
class HealthCheckService
{
    protected const CACHE_TAG = 'health_check';
    protected const CACHE_TTL = 30;

    // 检查状态
    public const STATUS_HEALTHY  = 'healthy';
    public const STATUS_DEGRADED = 'degraded';
    public const STATUS_UNHEALTHY = 'unhealthy';

    /**
     * 全面健康检查
     */
    public function checkAll(): array
    {
        $cacheKey = 'health_check_all';

        return Cache::remember($cacheKey, function () {
            $checks = [];

            // 1. MySQL检查
            $checks['mysql'] = $this->checkMysql();

            // 2. Redis检查
            $checks['redis'] = $this->checkRedis();

            // 3. 磁盘空间检查
            $checks['disk'] = $this->checkDisk();

            // 4. CPU/内存检查
            $checks['system'] = $this->checkSystem();

            // 5. 队列检查
            $checks['queue'] = $this->checkQueue();

            // 6. 文件目录检查
            $checks['storage'] = $this->checkStorage();

            // 7. 应用配置检查
            $checks['app'] = $this->checkApp();

            // 计算整体状态
            $overallStatus = $this->calculateOverallStatus($checks);

            return [
                'status'     => $overallStatus,
                'checks'     => $checks,
                'checked_at' => date('Y-m-d H:i:s'),
                'hostname'  => gethostname(),
                'php_version'=> PHP_VERSION,
            ];
        }, self::CACHE_TTL);
    }

    /**
     * 就绪检查（Readiness Probe）
     * 检查应用是否准备好接受流量
     */
    public function readiness(): array
    {
        $checks = [];

        // MySQL必须可用
        $checks['mysql'] = $this->checkMysql();

        // Redis必须可用（如果配置了）
        $checks['redis'] = $this->checkRedis();

        // 存储目录必须可写
        $checks['storage'] = $this->checkStorageWritable();

        $allHealthy = true;
        foreach ($checks as $check) {
            if ($check['status'] !== self::STATUS_HEALTHY) {
                $allHealthy = false;
                break;
            }
        }

        return [
            'status'  => $allHealthy ? self::STATUS_HEALTHY : self::STATUS_UNHEALTHY,
            'checks'  => $checks,
            'time'    => date('c'),
        ];
    }

    /**
     * 存活检查（Liveness Probe）
     * 检查应用进程是否存活
     */
    public function liveness(): array
    {
        return [
            'status' => self::STATUS_HEALTHY,
            'time'   => date('c'),
            'uptime' => $this->getUptime(),
        ];
    }

    /**
     * MySQL检查
     */
    public function checkMysql(): array
    {
        $start = microtime(true);

        try {
            Db::query('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);

            // 获取连接数
            $connections = Db::query('SHOW STATUS LIKE "Threads_connected"');
            $connCount = (int) ($connections[0]['Value'] ?? 0);

            // 获取最大连接数
            $maxConn = Db::query('SHOW VARIABLES LIKE "max_connections"');
            $maxConnCount = (int) ($maxConn[0]['Value'] ?? 0);

            $connUsage = $maxConnCount > 0 ? round($connCount / $maxConnCount * 100, 2) : 0;

            $status = self::STATUS_HEALTHY;
            if ($latency > 1000) {
                $status = self::STATUS_DEGRADED;
            }
            if ($connUsage > 80) {
                $status = self::STATUS_DEGRADED;
            }

            return [
                'status'        => $status,
                'latency_ms'    => $latency,
                'connections'   => $connCount,
                'max_connections' => $maxConnCount,
                'conn_usage'    => $connUsage,
                'message'       => 'MySQL连接正常',
            ];
        } catch (\Throwable $e) {
            return [
                'status'    => self::STATUS_UNHEALTHY,
                'message'   => 'MySQL连接失败: ' . $e->getMessage(),
                'error'     => $e->getMessage(),
            ];
        }
    }

    /**
     * Redis检查
     */
    public function checkRedis(): array
    {
        $start = microtime(true);

        try {
            $redis = Cache::store('redis');
            $testKey = '_health_check_' . uniqid();
            $redis->set($testKey, 'ok', 5);
            $value = $redis->get($testKey);
            $redis->delete($testKey);

            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($value !== 'ok') {
                return [
                    'status'  => self::STATUS_UNHEALTHY,
                    'message' => 'Redis读写验证失败',
                ];
            }

            $status = $latency > 100 ? self::STATUS_DEGRADED : self::STATUS_HEALTHY;

            return [
                'status'     => $status,
                'latency_ms' => $latency,
                'message'    => 'Redis连接正常',
            ];
        } catch (\Throwable $e) {
            // Redis不可用不影响核心功能（降级到文件缓存）
            return [
                'status'  => self::STATUS_DEGRADED,
                'message' => 'Redis不可用，降级到文件缓存: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 磁盘空间检查
     */
    public function checkDisk(): array
    {
        $path = runtime_path();
        $free = disk_free_space($path);
        $total = disk_total_space($path);

        if ($free === false || $total === false) {
            return [
                'status'  => self::STATUS_DEGRADED,
                'message' => '无法获取磁盘信息',
            ];
        }

        $used = $total - $free;
        $usagePercent = round($used / $total * 100, 2);

        $status = self::STATUS_HEALTHY;
        if ($usagePercent > 90) {
            $status = self::STATUS_UNHEALTHY;
        } elseif ($usagePercent > 80) {
            $status = self::STATUS_DEGRADED;
        }

        return [
            'status'        => $status,
            'total_bytes'   => (int) $total,
            'free_bytes'    => (int) $free,
            'used_bytes'    => (int) $used,
            'usage_percent' => $usagePercent,
            'message'       => "磁盘使用率: {$usagePercent}%",
        ];
    }

    /**
     * 系统资源检查（CPU/内存）
     */
    public function checkSystem(): array
    {
        $loadAvg = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
        $memUsage = memory_get_usage(true);
        $memLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

        $memUsagePercent = $memLimit > 0 ? round($memUsage / $memLimit * 100, 2) : 0;

        $status = self::STATUS_HEALTHY;
        if ($loadAvg[0] > 10 || $memUsagePercent > 90) {
            $status = self::STATUS_UNHEALTHY;
        } elseif ($loadAvg[0] > 5 || $memUsagePercent > 75) {
            $status = self::STATUS_DEGRADED;
        }

        return [
            'status'            => $status,
            'load_avg'         => $loadAvg,
            'memory_usage'     => (int) $memUsage,
            'memory_limit'     => (int) $memLimit,
            'memory_usage_percent' => $memUsagePercent,
            'message'          => "负载: {$loadAvg[0]} | 内存: {$memUsagePercent}%",
        ];
    }

    /**
     * 队列检查
     */
    public function checkQueue(): array
    {
        try {
            // 检查队列是否有积压
            $pendingJobs = 0;
            $failedJobs = 0;

            try {
                $pendingJobs = Db::name('queue_jobs')->where('reserved_at', null)->count();
                $failedJobs = Db::name('queue_failed_jobs')->count();
            } catch (\Throwable) {
                // 队列表可能不存在
            }

            $status = self::STATUS_HEALTHY;
            if ($failedJobs > 100) {
                $status = self::STATUS_UNHEALTHY;
            } elseif ($pendingJobs > 1000 || $failedJobs > 10) {
                $status = self::STATUS_DEGRADED;
            }

            return [
                'status'        => $status,
                'pending_jobs'  => $pendingJobs,
                'failed_jobs'   => $failedJobs,
                'message'       => "待处理: {$pendingJobs} | 失败: {$failedJobs}",
            ];
        } catch (\Throwable $e) {
            return [
                'status'  => self::STATUS_DEGRADED,
                'message'=> '队列检查失败: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 文件存储目录检查
     */
    public function checkStorage(): array
    {
        $dirs = [
            'runtime'       => runtime_path(),
            'runtime/cache'=> runtime_path() . 'cache',
            'runtime/log'   => runtime_path() . 'log',
            'uploads'       => public_path() . 'uploads',
        ];

        $results = [];
        $allOk = true;

        foreach ($dirs as $name => $path) {
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            $results[$name] = [
                'path'     => $path,
                'exists'   => $exists,
                'writable' => $writable,
            ];
            if (!$writable) {
                $allOk = false;
            }
        }

        return [
            'status'  => $allOk ? self::STATUS_HEALTHY : self::STATUS_DEGRADED,
            'dirs'    => $results,
            'message' => $allOk ? '存储目录正常' : '部分目录不可写',
        ];
    }

    /**
     * 仅检查存储可写性（用于Readiness）
     */
    public function checkStorageWritable(): array
    {
        $result = $this->checkStorage();
        return [
            'status'  => $result['status'] === self::STATUS_HEALTHY ? self::STATUS_HEALTHY : self::STATUS_UNHEALTHY,
            'message' => $result['message'],
        ];
    }

    /**
     * 应用配置检查
     */
    public function checkApp(): array
    {
        $checks = [];

        // 检查调试模式
        $checks['debug_mode'] = Config::get('app.debug') ? 'warning' : 'ok';

        // 检查时区
        $checks['timezone'] = date_default_timezone_get();

        // 检查OPcache
        $checks['opcache_enabled'] = function_exists('opcache_get_status') && opcache_get_status() !== false;

        $hasWarnings = $checks['debug_mode'] === 'warning';

        return [
            'status'  => $hasWarnings ? self::STATUS_DEGRADED : self::STATUS_HEALTHY,
            'checks'  => $checks,
            'message' => $hasWarnings ? '生产环境建议关闭调试模式' : '应用配置正常',
        ];
    }

    /**
     * 计算整体状态
     */
    protected function calculateOverallStatus(array $checks): string
    {
        $hasUnhealthy = false;
        $hasDegraded = false;

        foreach ($checks as $check) {
            $status = $check['status'] ?? self::STATUS_HEALTHY;
            if ($status === self::STATUS_UNHEALTHY) {
                $hasUnhealthy = true;
            } elseif ($status === self::STATUS_DEGRADED) {
                $hasDegraded = true;
            }
        }

        if ($hasUnhealthy) {
            return self::STATUS_UNHEALTHY;
        }
        if ($hasDegraded) {
            return self::STATUS_DEGRADED;
        }
        return self::STATUS_HEALTHY;
    }

    /**
     * 解析内存限制
     */
    protected function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return 0;
        }
        $unit = strtolower(substr($limit, -1));
        $value = (int) $limit;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    /**
     * 获取运行时间
     */
    protected function getUptime(): string
    {
        $startTime = Cache::get('app_start_time');
        if (!$startTime) {
            $startTime = time();
            Cache::set('app_start_time', $startTime);
        }

        $seconds = time() - $startTime;
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return "{$days}d {$hours}h {$minutes}m";
    }
}
