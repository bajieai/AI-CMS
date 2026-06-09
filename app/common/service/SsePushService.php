<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service;

use think\facade\Cache;

/**
 * V2.9.20 C-1: SSE 推送服务
 * 
 * 3通道推送：audit(审计)/comment(评论)/system(系统)
 * 基于缓存的消息队列，适配 PHP-FPM 环境
 */
class SsePushService
{
    // 通道常量
    const CHANNEL_AUDIT  = 'audit';
    const CHANNEL_COMMENT = 'comment';
    const CHANNEL_SYSTEM  = 'system';

    /**
     * 推送消息到指定通道
     */
    public static function push(string $channel, array $payload, int $ttl = 3600): bool
    {
        $validChannels = [self::CHANNEL_AUDIT, self::CHANNEL_COMMENT, self::CHANNEL_SYSTEM];
        if (!in_array($channel, $validChannels, true)) {
            return false;
        }

        $message = [
            'id'        => uniqid('sse_', true),
            'channel'   => $channel,
            'payload'   => $payload,
            'time'      => time(),
        ];

        $key = self::queueKey($channel);
        $queue = Cache::get($key, []);
        $queue[] = $message;

        // 限制队列长度，防止内存膨胀
        if (count($queue) > 100) {
            $queue = array_slice($queue, -50);
        }

        Cache::set($key, $queue, $ttl);
        return true;
    }

    /**
     * 获取通道消息（消费模式）
     */
    public static function pull(string $channel, int $lastTime = 0): array
    {
        $key = self::queueKey($channel);
        $queue = Cache::get($key, []);

        $messages = [];
        foreach ($queue as $msg) {
            if ($msg['time'] > $lastTime) {
                $messages[] = $msg;
            }
        }

        return $messages;
    }

    /**
     * 广播到所有通道
     */
    public static function broadcast(array $payload, int $ttl = 3600): void
    {
        foreach ([self::CHANNEL_AUDIT, self::CHANNEL_COMMENT, self::CHANNEL_SYSTEM] as $ch) {
            self::push($ch, $payload, $ttl);
        }
    }

    /**
     * 清理过期消息
     */
    public static function clear(string $channel): void
    {
        Cache::delete(self::queueKey($channel));
    }

    private static function queueKey(string $channel): string
    {
        return 'sse_queue:' . $channel;
    }
}
