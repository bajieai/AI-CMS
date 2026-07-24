<?php
declare(strict_types=1);

namespace app\common\service\perf;

use think\facade\Log;
use think\facade\Redis as RedisFacade;

/**
 * Redis高级服务
 * V2.9.38 PERF-II-3
 * 分布式锁/计数器/排行榜/限流器/HyperLogLog/Bitmap
 */
class RedisAdvancedService
{
    protected $redis;

    public function __construct()
    {
        try {
            $this->redis = RedisFacade::connection();
        } catch (\Throwable $e) {
            Log::warning('Redis connection failed: ' . $e->getMessage());
            $this->redis = null;
        }
    }

    /**
     * 分布式锁(token防止误释放)
     */
    public function lock(string $key, int $ttl = 30): ?string
    {
        if (!$this->redis) return null;
        $token = bin2hex(random_bytes(16));
        $result = $this->redis->set('lock:' . $key, $token, ['NX', 'EX' => $ttl]);
        return $result ? $token : null;
    }

    public function unlock(string $key, string $token): bool
    {
        if (!$this->redis) return false;
        // Lua脚本确保原子性: 只有token匹配才删除
        $script = "if redis.call('get', KEYS[1]) == ARGV[1] then return redis.call('del', KEYS[1]) else return 0 end";
        return (bool) $this->redis->eval($script, ['lock:' . $key, $token], 1);
    }

    /**
     * 计数器
     */
    public function incr(string $key, int $amount = 1): int
    {
        if (!$this->redis) return 0;
        return $this->redis->incrby('counter:' . $key, $amount);
    }

    public function decr(string $key, int $amount = 1): int
    {
        if (!$this->redis) return 0;
        return $this->redis->decrby('counter:' . $key, $amount);
    }

    public function getCount(string $key): int
    {
        if (!$this->redis) return 0;
        return (int) $this->redis->get('counter:' . $key);
    }

    /**
     * 排行榜
     */
    public function zadd(string $key, float $score, string $member): int
    {
        if (!$this->redis) return 0;
        return $this->redis->zadd('rank:' . $key, $score, $member);
    }

    public function zrange(string $key, int $start, int $end, bool $withScores = true): array
    {
        if (!$this->redis) return [];
        $end = $end < 0 ? $end : ($end - 1);
        return $this->redis->zrevrange('rank:' . $key, $start, $end, $withScores);
    }

    /**
     * 限流器
     */
    public function rateLimit(string $key, int $max, int $ttl): bool
    {
        if (!$this->redis) return true;
        $current = $this->redis->incr('ratelimit:' . $key);
        if ($current === 1) {
            $this->redis->expire('ratelimit:' . $key, $ttl);
        }
        return $current <= $max;
    }

    /**
     * HyperLogLog (独立访客UV)
     */
    public function pfadd(string $key, string $member): bool
    {
        if (!$this->redis) return false;
        return (bool) $this->redis->pfadd('hll:' . $key, [$member]);
    }

    public function pfcount(string $key): int
    {
        if (!$this->redis) return 0;
        return (int) $this->redis->pfcount('hll:' . $key);
    }

    /**
     * Bitmap (签到/活跃用户)
     */
    public function setBit(string $key, int $offset, bool $value): int
    {
        if (!$this->redis) return 0;
        return $this->redis->setbit('bitmap:' . $key, $offset, $value ? 1 : 0);
    }

    public function getBit(string $key, int $offset): bool
    {
        if (!$this->redis) return false;
        return (bool) $this->redis->getbit('bitmap:' . $key, $offset);
    }

    /**
     * 获取监控数据
     */
    public function getMonitorData(): array
    {
        if (!$this->redis) return ['status' => 'disconnected'];
        
        try {
            $info = $this->redis->info();
            return [
                'status' => 'connected',
                'used_memory' => $info['used_memory'] ?? 0,
                'used_memory_human' => $info['used_memory_human'] ?? '',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => isset($info['keyspace_hits']) && isset($info['keyspace_misses']) 
                    ? round($info['keyspace_hits'] / max($info['keyspace_hits'] + $info['keyspace_misses'], 1) * 100, 1) 
                    : 0,
                'uptime_in_seconds' => $info['uptime_in_seconds'] ?? 0,
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
