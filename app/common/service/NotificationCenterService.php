<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint UX2: 通知中心服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use think\facade\Cache;
use think\facade\Db;

/**
 * 通知中心服务 - V2.9.31 UX2-3
 * 提供系统通知的聚合、推送、管理
 */
class NotificationCenterService
{
    private const string CACHE_TAG = 'notification';

    /**
     * 发送通知
     */
    public function send(int $userId, string $type, string $title, string $content, array $extra = []): bool
    {
        try {
            $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
            Db::table($prefix . 'notification')->insert([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'content' => $content,
                'extra' => json_encode($extra, JSON_UNESCAPED_UNICODE),
                'is_read' => 0,
                'create_time' => time(),
            ]);

            // 清除用户通知缓存
            Cache::delete("user_notifications_{$userId}");
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取用户通知列表
     */
    public function getUserNotifications(int $userId, int $limit = 20): array
    {
        $cacheKey = "user_notifications_{$userId}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
            $list = Db::table($prefix . 'notification')
                ->where('user_id', $userId)
                ->order('id', 'desc')
                ->limit($limit)
                ->select()
                ->toArray();

            Cache::set($cacheKey, $list, 60);
            return $list;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 获取未读通知数
     */
    public function getUnreadCount(int $userId): int
    {
        try {
            $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
            return (int) Db::table($prefix . 'notification')
                ->where('user_id', $userId)
                ->where('is_read', 0)
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 标记已读
     */
    public function markAsRead(int $notificationId): bool
    {
        try {
            $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
            Db::table($prefix . 'notification')
                ->where('id', $notificationId)
                ->update(['is_read' => 1, 'read_time' => time()]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 标记全部已读
     */
    public function markAllAsRead(int $userId): bool
    {
        try {
            $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
            Db::table($prefix . 'notification')
                ->where('user_id', $userId)
                ->where('is_read', 0)
                ->update(['is_read' => 1, 'read_time' => time()]);
            Cache::delete("user_notifications_{$userId}");
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 删除通知
     */
    public function delete(int $notificationId): bool
    {
        try {
            $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
            Db::table($prefix . 'notification')->where('id', $notificationId)->delete();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 批量发送系统通知
     */
    public function broadcast(string $type, string $title, string $content, array $userIds = []): int
    {
        if (empty($userIds)) {
            return 0;
        }

        $count = 0;
        foreach ($userIds as $uid) {
            if ($this->send((int) $uid, $type, $title, $content)) {
                $count++;
            }
        }
        return $count;
    }
}
