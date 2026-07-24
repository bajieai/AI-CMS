<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\traits;

use think\facade\Cache;
use think\facade\Log;

/**
 * Redis队列Trait - V2.6
 * 提供基于Redis List的原子队列操作，Redis不可用时降级为Cache
 */
trait RedisQueueTrait
{
    /**
     * 队列键名前缀
     */
    protected static string $queuePrefix = 'cms_queue:';

    /**
     * 检测Redis是否可用
     */
    protected static function isRedisAvailable(): bool
    {
        try {
            $handler = Cache::store('redis')->handler();
            return $handler instanceof \Redis && $handler->ping() !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取Redis实例
     */
    protected static function getRedis(): ?\Redis
    {
        try {
            $handler = Cache::store('redis')->handler();
            return $handler instanceof \Redis ? $handler : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 从队列右侧入队 (rPush)
     */
    protected static function queuePush(string $queueName, array $data): bool
    {
        $redis = self::getRedis();
        if ($redis) {
            try {
                $redis->rPush(self::$queuePrefix . $queueName, json_encode($data, JSON_UNESCAPED_UNICODE));
                return true;
            } catch (\Exception $e) {
                Log::warning("Redis队列入队失败，降级到Cache: " . $e->getMessage());
            }
        }

        // Cache降级
        $key = self::$queuePrefix . $queueName;
        $queue = Cache::get($key, []);
        $queue[] = $data;
        return Cache::set($key, $queue, 86400 * 7);
    }

    /**
     * 从队列左侧出队 (lPop)
     * @return array|null 返回数据或null(队列为空)
     */
    protected static function queuePop(string $queueName): ?array
    {
        $redis = self::getRedis();
        if ($redis) {
            try {
                $item = $redis->lPop(self::$queuePrefix . $queueName);
                if ($item !== false) {
                    $decoded = json_decode($item, true);
                    return is_array($decoded) ? $decoded : null;
                }
                return null;
            } catch (\Exception $e) {
                Log::warning("Redis队列出队失败，降级到Cache: " . $e->getMessage());
            }
        }

        // Cache降级
        $key = self::$queuePrefix . $queueName;
        $queue = Cache::get($key, []);
        if (empty($queue)) {
            return null;
        }
        $item = array_shift($queue);
        Cache::set($key, $queue, 86400 * 7);
        return is_array($item) ? $item : null;
    }

    /**
     * 获取队列长度
     */
    protected static function queueLen(string $queueName): int
    {
        $redis = self::getRedis();
        if ($redis) {
            try {
                return (int) $redis->lLen(self::$queuePrefix . $queueName);
            } catch (\Exception $e) {
                // 降级
            }
        }

        $queue = Cache::get(self::$queuePrefix . $queueName, []);
        return count($queue);
    }

    /**
     * 清空队列
     */
    protected static function queueClear(string $queueName): bool
    {
        $redis = self::getRedis();
        if ($redis) {
            try {
                $redis->del(self::$queuePrefix . $queueName);
                return true;
            } catch (\Exception $e) {
                // 降级
            }
        }

        return Cache::delete(self::$queuePrefix . $queueName);
    }
}
