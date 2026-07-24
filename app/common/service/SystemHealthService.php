<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Cache;
use think\facade\Db;

/**
 * 系统健康监控 — V2.9.33 OPS-3
 *
 * 容器化环境兼容方案：
 * - CPU使用率：优先读取cgroup v2(/sys/fs/cgroup/cpu.stat)，降级到/proc/stat
 * - 内存使用率：优先读取cgroup v2(/sys/fs/cgroup/memory.current)，降级到/proc/meminfo
 * - 磁盘使用率：disk_free_space/disk_total_space（容器内有效）
 * - 缓存命中率：从MultiLevelCacheService获取
 */
class SystemHealthService
{
    private const CACHE_TTL = 30; // 30秒缓存

    /**
     * 获取完整健康状态
     */
    public function getHealthStatus(): array
    {
        return Cache::remember('system_health', function () {
            return [
                'overall'     => $this->getOverallStatus(),
                'services'    => $this->getServicesStatus(),
                'performance' => $this->getPerformanceMetrics(),
                'resources'   => $this->getResourceUsage(),
                'errors'      => $this->getRecentErrors(),
                'alerts'      => $this->getActiveAlerts(),
            ];
        }, self::CACHE_TTL);
    }

    /**
     * 整体状态
     */
    private function getOverallStatus(): array
    {
        $resources = $this->getResourceUsage();
        $status = 'healthy';

        if ($resources['cpu_percent'] > 90 || $resources['memory_percent'] > 85 || $resources['disk_percent'] > 90) {
            $status = 'danger';
        } elseif ($resources['cpu_percent'] > 70 || $resources['memory_percent'] > 70 || $resources['disk_percent'] > 75) {
            $status = 'warning';
        }

        return ['status' => $status, 'uptime' => time() - (intval(filemtime(runtime_path()) ?: time()))];
    }

    /**
     * 各服务运行状态
     */
    private function getServicesStatus(): array
    {
        $services = [];

        // Web Server (PHP-FPM)
        $services['web'] = ['status' => 'running', 'latency_ms' => 0];

        // 数据库
        try {
            $start = microtime(true);
            Db::query('SELECT 1');
            $services['database'] = ['status' => 'running', 'latency_ms' => round((microtime(true) - $start) * 1000, 1)];
        } catch (\Throwable $e) {
            $services['database'] = ['status' => 'error', 'latency_ms' => 0, 'error' => $e->getMessage()];
        }

        // 缓存
        try {
            Cache::set('health_check', 1, 10);
            $services['cache'] = ['status' => 'running', 'latency_ms' => 0];
        } catch (\Throwable $e) {
            $services['cache'] = ['status' => 'error', 'latency_ms' => 0];
        }

        // AI服务（检查配置是否可用）
        $aiConfig = config('ai.default_api_key');
        $services['ai'] = ['status' => !empty($aiConfig) ? 'running' : 'unknown', 'latency_ms' => 0];

        return $services;
    }

    /**
     * 性能指标
     */
    private function getPerformanceMetrics(): array
    {
        $cacheHitRate = 0;
        if (class_exists(\app\common\service\MultiLevelCacheService::class)) {
            $stats = (new \app\common\service\MultiLevelCacheService())->getStats();
            $cacheHitRate = $stats['hit_rate'] ?? 0;
        }

        return [
            'avg_page_load_ms'  => $this->getAvgPageLoadTime(),
            'avg_api_response_ms' => $this->getAvgApiResponseTime(),
            'cache_hit_rate'    => $cacheHitRate,
            'db_query_avg_ms'   => $this->getDbQueryTime(),
        ];
    }

    /**
     * 资源使用率（容器化兼容）
     */
    public function getResourceUsage(): array
    {
        return [
            'cpu_percent'    => $this->getCpuUsage(),
            'memory_percent' => $this->getMemoryUsage(),
            'memory_used_mb' => $this->getMemoryUsedMB(),
            'disk_percent'   => $this->getDiskUsage(),
            'disk_free_gb'   => round(@disk_free_space('/') / 1073741824, 2),
            'php_processes'  => $this->getPhpProcessCount(),
        ];
    }

    /**
     * CPU使用率（容器化兼容）
     * 优先使用cgroup v2，降级到/proc/stat
     */
    private function getCpuUsage(): float
    {
        // 方案1: cgroup v2 (Docker容器内)
        if (file_exists('/sys/fs/cgroup/cpu.stat')) {
            return $this->getCpuFromCgroupV2();
        }

        // 方案2: cgroup v1 (旧版Docker)
        if (file_exists('/sys/fs/cgroup/cpuacct/cpuacct.usage')) {
            return $this->getCpuFromCgroupV1();
        }

        // 方案3: /proc/stat (宿主机或非容器环境)
        return $this->getCpuFromProcStat();
    }

    /**
     * cgroup v2 CPU使用率
     */
    private function getCpuFromCgroupV2(): float
    {
        $cacheKey = 'cgroup_cpu_usage';
        $prev = Cache::get($cacheKey);
        $stat = file_get_contents('/sys/fs/cgroup/cpu.stat');

        $usageUsec = 0;
        if (preg_match('/usage_usec\s+(\d+)/', $stat, $m)) {
            $usageUsec = (int) $m[1];
        }

        // CPU配额
        $quota = -1;
        $period = 100000;
        if (file_exists('/sys/fs/cgroup/cpu.max')) {
            $cpuMax = file_get_contents('/sys/fs/cgroup/cpu.max');
            $parts = explode(' ', trim($cpuMax));
            if ($parts[0] !== 'max') {
                $quota = (int) $parts[0];
                $period = (int) $parts[1];
            }
        }

        if ($quota <= 0) return 0.0; // 无限制

        $now = microtime(true);
        if ($prev) {
            $timeDiff = ($now - $prev['time']) * 1000000; // 微秒
            $usageDiff = $usageUsec - $prev['usage'];
            $cpuPercent = ($usageDiff / $timeDiff) * ($period / $quota) * 100;
            Cache::set($cacheKey, ['usage' => $usageUsec, 'time' => $now], 10);
            return min(100, max(0, round($cpuPercent, 1)));
        }

        Cache::set($cacheKey, ['usage' => $usageUsec, 'time' => $now], 10);
        return 0.0;
    }

    /**
     * cgroup v1 CPU使用率
     */
    private function getCpuFromCgroupV1(): float
    {
        $cacheKey = 'cgroup_v1_cpu';
        $prev = Cache::get($cacheKey);
        $usage = (int) file_get_contents('/sys/fs/cgroup/cpuacct/cpuacct.usage');

        $now = microtime(true);
        if ($prev) {
            $timeDiff = ($now - $prev['time']) * 1000000000; // 纳秒
            $usageDiff = $usage - $prev['usage'];
            $percent = ($usageDiff / $timeDiff) * 100;
            Cache::set($cacheKey, ['usage' => $usage, 'time' => $now], 10);
            return min(100, max(0, round($percent, 1)));
        }

        Cache::set($cacheKey, ['usage' => $usage, 'time' => $now], 10);
        return 0.0;
    }

    /**
     * /proc/stat CPU使用率（非容器环境）
     */
    private function getCpuFromProcStat(): float
    {
        if (!file_exists('/proc/stat')) {
            return 0.0;
        }
        $cacheKey = 'proc_stat_cpu';
        $prev = Cache::get($cacheKey);
        $stat = @file_get_contents('/proc/stat');
        if ($stat === false) {
            return 0.0;
        }
        $lines = explode("\n", $stat);
        $cpuLine = $lines[0] ?? '';
        $parts = preg_split('/\s+/', $cpuLine);
        $total = array_sum(array_slice($parts, 1, 4));
        $idle = (int) ($parts[4] ?? 0);

        if ($prev) {
            $totalDiff = $total - $prev['total'];
            $idleDiff = $idle - $prev['idle'];
            if ($totalDiff > 0) {
                $percent = (1 - $idleDiff / $totalDiff) * 100;
                Cache::set($cacheKey, ['total' => $total, 'idle' => $idle], 10);
                return min(100, max(0, round($percent, 1)));
            }
        }

        Cache::set($cacheKey, ['total' => $total, 'idle' => $idle], 10);
        return 0.0;
    }

    /**
     * 内存使用率（容器化兼容）
     */
    private function getMemoryUsage(): float
    {
        // cgroup v2
        if (file_exists('/sys/fs/cgroup/memory.current')) {
            $current = (int) file_get_contents('/sys/fs/cgroup/memory.current');
            $max = file_exists('/sys/fs/cgroup/memory.max') ? (int) file_get_contents('/sys/fs/cgroup/memory.max') : 0;
            if ($max > 0) {
                return round($current / $max * 100, 1);
            }
        }

        // cgroup v1
        if (file_exists('/sys/fs/cgroup/memory/memory.usage_in_bytes')) {
            $usage = (int) file_get_contents('/sys/fs/cgroup/memory/memory.usage_in_bytes');
            $limit = (int) file_get_contents('/sys/fs/cgroup/memory/memory.limit_in_bytes');
            if ($limit > 0 && $limit < 9223372036854771712) {
                return round($usage / $limit * 100, 1);
            }
        }

        // /proc/meminfo
        if (!file_exists('/proc/meminfo')) {
            return 0.0;
        }
        $meminfo = @file_get_contents('/proc/meminfo');
        if ($meminfo === false) {
            return 0.0;
        }
        $total = 0;
        $available = 0;
        if (preg_match('/MemTotal:\s+(\d+)/', $meminfo, $m)) $total = (int) $m[1];
        if (preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $m)) $available = (int) $m[1];
        if ($total > 0) {
            return round(($total - $available) / $total * 100, 1);
        }

        return 0.0;
    }

    private function getMemoryUsedMB(): float
    {
        if (file_exists('/sys/fs/cgroup/memory.current')) {
            return round((int) file_get_contents('/sys/fs/cgroup/memory.current') / 1048576, 1);
        }
        if (!file_exists('/proc/meminfo')) {
            return 0.0;
        }
        $meminfo = @file_get_contents('/proc/meminfo');
        if ($meminfo === false) {
            return 0.0;
        }
        $total = 0;
        $available = 0;
        if (preg_match('/MemTotal:\s+(\d+)/', $meminfo, $m)) $total = (int) $m[1];
        if (preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $m)) $available = (int) $m[1];
        return round(($total - $available) / 1024, 1);
    }

    private function getDiskUsage(): float
    {
        $total = @disk_total_space('/') ?: 0;
        $free = @disk_free_space('/') ?: 0;
        if ($total > 0) {
            return round(($total - $free) / $total * 100, 1);
        }
        return 0.0;
    }

    private function getPhpProcessCount(): int
    {
        $output = @shell_exec('ps aux | grep php-fpm | grep -v grep | wc -l');
        return (int) trim($output ?: '0');
    }

    private function getAvgPageLoadTime(): float
    {
        return 0.0; // 需要中间件采集
    }

    private function getAvgApiResponseTime(): float
    {
        return 0.0;
    }

    private function getDbQueryTime(): float
    {
        try {
            $start = microtime(true);
            Db::query('SELECT 1');
            return round((microtime(true) - $start) * 1000, 1);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    /**
     * 最近错误
     */
    private function getRecentErrors(): array
    {
        $logPath = runtime_path() . 'log/';
        $today = date('Ym/d');
        $logFile = $logPath . $today . '.log';
        $errors = [];

        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            preg_match_all('/\[(\d{4}-\d{2}-\d{2}.*?\d{2}:\d{2}:\d{2})\]\s+(error|critical|warning)\s+(.+)/i', $content, $matches, PREG_SET_ORDER);
            foreach (array_slice($matches, -20) as $m) {
                $errors[] = ['time' => $m[1], 'level' => $m[2], 'message' => mb_substr($m[3], 0, 200)];
            }
        }

        return array_reverse($errors);
    }

    /**
     * 活跃告警
     */
    private function getActiveAlerts(): array
    {
        $alerts = [];
        $resources = $this->getResourceUsage();

        if ($resources['cpu_percent'] > 90) {
            $alerts[] = ['level' => 'danger', 'message' => "CPU使用率过高: {$resources['cpu_percent']}%"];
        }
        if ($resources['memory_percent'] > 85) {
            $alerts[] = ['level' => 'danger', 'message' => "内存使用率过高: {$resources['memory_percent']}%"];
        }
        if ($resources['disk_percent'] > 90) {
            $alerts[] = ['level' => 'danger', 'message' => "磁盘使用率过高: {$resources['disk_percent']}%"];
        }

        return $alerts;
    }
}
