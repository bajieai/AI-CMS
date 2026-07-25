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
 * V2.9.27 T-1: 升级为DB持久化队列兼容层
 * 
 * 3通道推送：audit(审计)/comment(评论)/system(系统)
 * V2.9.27新增：notification(通知)通道
 * 
 * @deprecated V2.9.27 请直接使用 app\common\service\sse\SseEngine
 */
class SsePushService
{
    // 通道常量
    const CHANNEL_AUDIT  = 'audit';
    const CHANNEL_COMMENT = 'comment';
    const CHANNEL_SYSTEM  = 'system';

    /**
     * 推送消息到指定通道
     * V2.9.27: 转发到SseEngine(DB持久化队列)
     */
    public static function push(string $channel, array $payload, int $ttl = 3600): bool
    {
        $validChannels = [self::CHANNEL_AUDIT, self::CHANNEL_COMMENT, self::CHANNEL_SYSTEM];
        if (!in_array($channel, $validChannels, true)) {
            return false;
        }

        // V2.9.27 T-1: 转发到DB持久化队列
        try {
            $msgId = \app\common\service\sse\SseEngine::push($channel, $payload, 0, 'message', $ttl);
            return $msgId > 0;
        } catch (\Throwable $e) {
            // 降级到缓存队列
            $message = [
                'id'        => uniqid('sse_', true),
                'channel'   => $channel,
                'payload'   => $payload,
                'time'      => time(),
            ];
            $key = self::queueKey($channel);
            $queue = Cache::get($key, []);
            $queue[] = $message;
            if (count($queue) > 100) $queue = array_slice($queue, -50);
            Cache::set($key, $queue, $ttl);
            return true;
        }
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
