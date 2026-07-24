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

namespace app\common\service;

use app\common\model\Message;
use app\common\model\MessageConversation;
use app\common\model\MessageSystem;
use app\common\model\MessageSystemRead;
use think\facade\Cache;
use think\facade\Db;

/**
 * 私信服务 - V2.6
 * 支持一对一私信 + 系统通知
 */
class PrivateMessageService
{
    /**
     * 发送私信
     */
    public static function send(int $fromUserId, int $toUserId, string $content): array
    {
        if ($fromUserId === $toUserId) {
            throw new \Exception('不能给自己发送私信');
        }
        if (empty(trim($content))) {
            throw new \Exception('消息内容不能为空');
        }

        // 确保user_id_1 < user_id_2
        $uid1 = min($fromUserId, $toUserId);
        $uid2 = max($fromUserId, $toUserId);

        Db::startTrans();
        try {
            // 获取或创建会话
            $conversation = MessageConversation::where('user_id_1', $uid1)
                ->where('user_id_2', $uid2)
                ->find();

            if (!$conversation) {
                $conversation = MessageConversation::create([
                    'user_id_1' => $uid1,
                    'user_id_2' => $uid2,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            }

            // 创建消息
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'content' => trim($content),
                'create_time' => time(),
            ]);

            // 更新会话
            $conversation->last_message_id = $message->id;
            $conversation->last_message_time = $message->create_time;
            if ($fromUserId === $uid1) {
                $conversation->unread_count_2 += 1;
            } else {
                $conversation->unread_count_1 += 1;
            }
            $conversation->update_time = time();
            $conversation->save();

            // 清除缓存
            Cache::clear();

            Db::commit();
            return ['success' => true, 'message_id' => $message->id];
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 获取用户的会话列表
     */
    public static function getConversations(int $userId, int $page = 1, int $limit = 20): array
    {
        $list = MessageConversation::where(function ($query) use ($userId) {
                $query->where('user_id_1', $userId)->whereOr('user_id_2', $userId);
            })
            ->order('last_message_time', 'desc')
            ->page($page, $limit)
            ->select();

        $result = [];
        foreach ($list as $item) {
            $unread = ($item->user_id_1 == $userId) ? $item->unread_count_1 : $item->unread_count_2;
            $otherId = ($item->user_id_1 == $userId) ? $item->user_id_2 : $item->user_id_1;
            $result[] = [
                'id' => $item->id,
                'other_user_id' => $otherId,
                'unread_count' => $unread,
                'last_message_time' => $item->last_message_time,
                'create_time' => $item->create_time,
            ];
        }
        return $result;
    }

    /**
     * 获取会话消息列表
     */
    public static function getMessages(int $conversationId, int $userId, int $page = 1, int $limit = 20): array
    {
        $conversation = MessageConversation::find($conversationId);
        if (!$conversation || ($conversation->user_id_1 != $userId && $conversation->user_id_2 != $userId)) {
            throw new \Exception('会话不存在');
        }

        $list = Message::where('conversation_id', $conversationId)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();

        return array_reverse($list->toArray());
    }

    /**
     * 标记会话消息为已读
     */
    public static function markRead(int $conversationId, int $userId): bool
    {
        $conversation = MessageConversation::find($conversationId);
        if (!$conversation || ($conversation->user_id_1 != $userId && $conversation->user_id_2 != $userId)) {
            return false;
        }

        Db::startTrans();
        try {
            // 标记消息已读
            Message::where('conversation_id', $conversationId)
                ->where('to_user_id', $userId)
                ->where('is_read', 0)
                ->update(['is_read' => 1]);

            // 重置会话未读数
            if ($conversation->user_id_1 == $userId) {
                $conversation->unread_count_1 = 0;
            } else {
                $conversation->unread_count_2 = 0;
            }
            $conversation->save();

            Cache::clear();

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    /**
     * 获取用户总未读私信数
     */
    public static function getUnreadCount(int $userId): int
    {
        return Message::where('to_user_id', $userId)
            ->where('is_read', 0)
            ->count();
    }

    /**
     * 发送系统通知
     */
    public static function sendSystem(string $title, string $content, string $type = 'system', string $targetUrl = '', int $expireDays = 0): int
    {
        $msg = MessageSystem::create([
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'target_url' => $targetUrl,
            'send_time' => time(),
            'expire_time' => $expireDays > 0 ? time() + ($expireDays * 86400) : 0,
            'create_time' => time(),
        ]);

        Cache::clear();
        return $msg->id;
    }

    /**
     * 获取用户的系统通知列表
     */
    public static function getSystemMessages(int $userId, int $page = 1, int $limit = 20): array
    {
        $now = time();
        $list = MessageSystem::where(function ($query) use ($now) {
                $query->where('expire_time', 0)->whereOr('expire_time', '>', $now);
            })
            ->where('send_time', '<=', $now)
            ->order('send_time', 'desc')
            ->page($page, $limit)
            ->select();

        $messageIds = array_column($list->toArray(), 'id');
        $readIds = [];
        if (!empty($messageIds)) {
            $readRecords = MessageSystemRead::where('user_id', $userId)
                ->whereIn('message_id', $messageIds)
                ->column('message_id');
            $readIds = array_flip($readRecords);
        }

        $result = [];
        foreach ($list as $item) {
            $row = $item->toArray();
            $row['is_read'] = isset($readIds[$item->id]);
            $result[] = $row;
        }
        return $result;
    }

    /**
     * 标记系统通知为已读
     */
    public static function markSystemRead(int $messageId, int $userId): bool
    {
        try {
            MessageSystemRead::create([
                'message_id' => $messageId,
                'user_id' => $userId,
                'read_time' => time(),
            ]);
            return true;
        } catch (\Exception $e) {
            // 已读记录唯一键冲突，忽略
            return true;
        }
    }

    /**
     * 获取用户未读系统通知数
     */
    public static function getSystemUnreadCount(int $userId): int
    {
        $now = time();
        $total = MessageSystem::where(function ($query) use ($now) {
                $query->where('expire_time', 0)->whereOr('expire_time', '>', $now);
            })
            ->where('send_time', '<=', $now)
            ->count();

        $readCount = MessageSystemRead::where('user_id', $userId)->count();
        return max(0, $total - $readCount);
    }
}
