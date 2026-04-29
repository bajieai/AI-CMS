<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Notification as NotificationModel;
use think\facade\Cache;

/**
 * 通知服务
 */
class NotificationService
{
    /**
     * 发送通知
     */
    public function send(string $receiverType, int $receiverId, string $type, string $title, string $content, string $link = ''): void
    {
        if (!config('notification.notification_enabled')) {
            return;
        }

        NotificationModel::create([
            'receiver_type' => $receiverType,
            'receiver_id'   => $receiverId,
            'type'          => $type,
            'title'         => $title,
            'content'       => $content,
            'link'          => $link,
            'is_read'       => 0,
        ]);
    }

    /**
     * 批量发送
     */
    public function sendBatch(array $receivers, string $type, string $title, string $content, string $link = ''): void
    {
        if (!config('notification.notification_enabled')) {
            return;
        }

        $data = [];
        foreach ($receivers as $r) {
            $data[] = [
                'receiver_type' => $r['type'],
                'receiver_id'   => $r['id'],
                'type'          => $type,
                'title'         => $title,
                'content'       => $content,
                'link'          => $link,
                'is_read'       => 0,
                'create_time'   => time(),
            ];
        }

        if (!empty($data)) {
            NotificationModel::insertAll($data);
        }
    }

    /**
     * 标记已读
     */
    public function markRead(int $id, string $receiverType, int $receiverId): bool
    {
        return NotificationModel::where('id', $id)
            ->where('receiver_type', $receiverType)
            ->where('receiver_id', $receiverId)
            ->update(['is_read' => 1]) > 0;
    }

    /**
     * 标记全部已读
     */
    public function markAllRead(string $receiverType, int $receiverId): void
    {
        NotificationModel::where('receiver_type', $receiverType)
            ->where('receiver_id', $receiverId)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);
    }

    /**
     * 获取未读数量
     */
    public function getUnreadCount(string $receiverType, int $receiverId): int
    {
        $cacheKey = "notification_unread_{$receiverType}_{$receiverId}";
        return Cache::remember($cacheKey, function () use ($receiverType, $receiverId) {
            return NotificationModel::where('receiver_type', $receiverType)
                ->where('receiver_id', $receiverId)
                ->where('is_read', 0)
                ->count();
        });
    }

    /**
     * 获取通知列表
     */
    public function getList(string $receiverType, int $receiverId, int $isRead = null, int $page = 1, int $limit = 10): array
    {
        $query = NotificationModel::where('receiver_type', $receiverType)
            ->where('receiver_id', $receiverId)
            ->order('create_time', 'desc');

        if ($isRead !== null) {
            $query->where('is_read', $isRead);
        }

        return $query->page($page, $limit)->select()->toArray();
    }
}