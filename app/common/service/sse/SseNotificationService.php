<?php
declare(strict_types=1);
namespace app\common\service\sse;

use app\common\service\sse\SseEngine;
use app\common\model\SseMessageQueue;

/**
 * V2.9.27 T-2: SSE通知推送服务
 */
class SseNotificationService
{
    public static function pushNotification(int $userId, string $title, string $content, string $type = 'info', array $extra = []): int
    {
        return SseEngine::push(SseMessageQueue::CHANNEL_NOTIFICATION, array_merge([
            'title' => $title, 'content' => $content, 'type' => $type, 'time' => date('Y-m-d H:i:s'),
        ], $extra), $userId, 'notification');
    }

    public static function pushUnreadCount(int $userId, int $unreadCount, int $systemUnread = 0): int
    {
        return SseEngine::push(SseMessageQueue::CHANNEL_NOTIFICATION, [
            'type' => 'unread_count', 'unread' => $unreadCount,
            'system_unread' => $systemUnread, 'total' => $unreadCount + $systemUnread,
        ], $userId, 'unread_update');
    }

    public static function pushSystemNotice(string $title, string $content): void
    {
        SseEngine::push(SseMessageQueue::CHANNEL_SYSTEM, [
            'title' => $title, 'content' => $content, 'time' => date('Y-m-d H:i:s'),
        ], 0, 'system_notice');
    }
}
