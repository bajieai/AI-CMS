<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Cache;
use think\facade\Config;

/**
 * V2.9.35 PERF-1: 缓存优化服务
 * 穿透/雪崩/击穿三重防护 + 降级策略
 * 复用现有MultiLevelCacheService(V2.9.32 三级缓存)
 */
class CacheOptimizeService
{
    /**
     * 穿透防护：空值缓存时间（秒）
     */
    protected int $nullTtl = 60;

    /**
     * 雪崩防护：随机抖动范围（秒）
     */
    protected int $jitterRange = 300;

    /**
     * 击穿防护：锁超时时间（秒）
     */
    protected int $lockTimeout = 10;

    /**
     * 安全获取缓存（三重防护）
     * @param string $key 缓存键
     * @param callable $callback 缓存未命中时的回调
     * @param int $ttl 缓存时间（秒）
     * @return mixed
     */
    public function safeRemember(string $key, callable $callback, int $ttl = 300): mixed
    {
        // 1. 尝试读取缓存
        $value = Cache::get($key);
        if ($value !== null) {
            // 空值标记（穿透防护）
            if ($value === '__NULL__') {
                return null;
            }
            return $value;
        }

        // 2. 击穿防护：获取互斥锁
        $lockKey = $key . '_lock';
        $locked = Cache::lock($lockKey, $this->lockTimeout);

        if (!$locked) {
            // 未获取到锁，等待短暂时间后重试缓存
            usleep(100000); // 100ms
            $value = Cache::get($key);
            if ($value !== null) {
                return $value === '__NULL__' ? null : $value;
            }
            // 仍未获取到数据，直接执行回调（降级：不等待锁释放）
            return $callback();
        }

        try {
            // 3. 再次检查缓存（双重检查，防止锁等待期间其他进程已写入）
            $value = Cache::get($key);
            if ($value !== null) {
                return $value === '__NULL__' ? null : $value;
            }

            // 4. 执行回调获取数据
            $value = $callback();

            // 5. 穿透防护：空值缓存
            if ($value === null || $value === []) {
                Cache::set($key, '__NULL__', $this->nullTtl);
                return null;
            }

            // 6. 雪崩防护：TTL加随机抖动
            $actualTtl = $ttl + random_int(0, $this->jitterRange);
            Cache::set($key, $value, $actualTtl);

            return $value;
        } finally {
            // 释放锁
            try {
                Cache::unlock($lockKey);
            } catch (\Throwable) {
                // 忽略锁释放失败
            }
        }
    }

    /**
     * 降级获取：Redis不可用时降级到文件缓存
     */
    public function degradedGet(string $key): mixed
    {
        try {
            return Cache::store('redis')->get($key);
        } catch (\Throwable) {
            try {
                return Cache::store('file')->get($key);
            } catch (\Throwable) {
                return null;
            }
        }
    }

    /**
     * 降级写入
     */
    public function degradedSet(string $key, mixed $value, int $ttl = 300): bool
    {
        try {
            Cache::store('redis')->set($key, $value, $ttl);
            return true;
        } catch (\Throwable) {
            try {
                Cache::store('file')->set($key, $value, $ttl);
                return true;
            } catch (\Throwable) {
                return false;
            }
        }
    }
}
