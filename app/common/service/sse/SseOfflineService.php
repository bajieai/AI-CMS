<?php
declare(strict_types=1);

namespace app\common\service\sse;

use app\common\model\SseMessageQueue;

/**
 * V2.9.27 T-5: SSE离线消息补推服务
 * 当客户端断线重连时，通过Last-Event-Id补推离线期间的消息
 */
class SseOfflineService
{
    /**
     * 获取离线消息（用于断线重连补推）
     * @param string $channel 通道
     * @param int $lastEventId 客户端最后接收的消息ID
     * @param int $userId 用户ID（0=广播消息）
     * @param int $limit 最大补推条数
     * @return array
     */
    public static function getOfflineMessages(string $channel, int $lastEventId, int $userId = 0, int $limit = 50): array
    {
        if ($lastEventId <= 0) {
            return [];
        }

        $query = SseMessageQueue::where('channel', $channel)
            ->where('id', '>', $lastEventId)
            ->where('status', SseMessageQueue::STATUS_PENDING)
            ->where('expires_at', '>', time())
            ->order('id', 'asc')
            ->limit($limit);

        // 用户专属消息或广播消息
        $query->where(function ($q) use ($userId) {
            $q->where('target_user_id', 0)->whereOr('target_user_id', $userId);
        });

        return $query->select()->toArray();
    }

    /**
     * 计算离线消息数量
     */
    public static function countOfflineMessages(string $channel, int $lastEventId, int $userId = 0): int
    {
        if ($lastEventId <= 0) return 0;

        return SseMessageQueue::where('channel', $channel)
            ->where('id', '>', $lastEventId)
            ->where('status', SseMessageQueue::STATUS_PENDING)
            ->where(function ($q) use ($userId) {
                $q->where('target_user_id', 0)->whereOr('target_user_id', $userId);
            })
            ->count();
    }
}