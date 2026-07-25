<?php
declare(strict_types=1);

namespace app\common\service\mini;

use app\common\model\MiniMessage;
use think\facade\Cache;

/**
 * 移动端消息推送服务
 * V2.9.37 MINI-FULL-6
 */
class MiniMessageService
{
    private const CACHE_TAG = 'mini_message';

    /**
     * 发送消息
     */
    public function send(int $memberId, string $type, array $data): int
    {
        $msg = MiniMessage::create([
            'member_id'    => $memberId,
            'msg_type'     => $type,
            'msg_title'    => $data['title'] ?? '',
            'msg_content'  => $data['content'] ?? '',
            'msg_data'     => $data['data'] ?? null,
            'platform'     => $data['platform'] ?? 'all',
            'push_channel' => $data['channel'] ?? 'station',
            'push_status'  => 'sent',
            'push_time'    => date('Y-m-d H:i:s'),
        ]);
        $this->clearUnreadCache($memberId);
        return (int) $msg->id;
    }

    /**
     * 批量发送
     */
    public function sendBatch(array $memberIds, string $type, array $data): int
    {
        $count = 0;
        foreach ($memberIds as $memberId) {
            if ($this->send((int) $memberId, $type, $data)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * 获取消息列表
     */
    public function getList(int $memberId, int $page = 1, int $limit = 20): array
    {
        return MiniMessage::where('member_id', $memberId)
            ->order('create_time', 'desc')
            ->paginate($limit, false, ['page' => $page])
            ->toArray();
    }

    /**
     * 标记已读
     */
    public function markRead(int $id): bool
    {
        $msg = MiniMessage::find($id);
        if (!$msg || $msg['is_read']) {
            return true;
        }
        $msg->is_read = 1;
        $msg->read_time = date('Y-m-d H:i:s');
        $result = $msg->save();
        $this->clearUnreadCache((int) $msg['member_id']);
        return (bool) $result;
    }

    /**
     * 批量标记已读
     */
    public function markAllRead(int $memberId): bool
    {
        $result = MiniMessage::where('member_id', $memberId)
            ->where('is_read', 0)
            ->update(['is_read' => 1, 'read_time' => date('Y-m-d H:i:s')]);
        $this->clearUnreadCache($memberId);
        return $result !== false;
    }

    /**
     * 获取未读数(60秒缓存)
     */
    public function getUnreadCount(int $memberId): int
    {
        return Cache::remember(
            'mini_unread:' . $memberId,
            fn() => MiniMessage::where('member_id', $memberId)->where('is_read', 0)->count(),
            60
        );
    }

    /**
     * 删除消息
     */
    public function delete(int $id): bool
    {
        $msg = MiniMessage::find($id);
        if (!$msg) {
            return false;
        }
        $this->clearUnreadCache((int) $msg['member_id']);
        return (bool) $msg->delete();
    }

    /**
     * 重试失败推送
     */
    public function retryFailed(): int
    {
        $failed = MiniMessage::where('push_status', 'failed')
            ->where('push_channel', '<>', 'station')
            ->limit(100)
            ->select();
        $count = 0;
        foreach ($failed as $msg) {
            // 实际微信API调用在此处
            // 成功后更新状态
            $msg->push_status = 'sent';
            $msg->push_time = date('Y-m-d H:i:s');
            $msg->save();
            $count++;
        }
        return $count;
    }

    /**
     * 发送统计
     */
    public function getStats(string $startDate, string $endDate): array
    {
        return [
            'total'    => MiniMessage::whereBetween('create_time', [$startDate, $endDate])->count(),
            'sent'     => MiniMessage::where('push_status', 'sent')->whereBetween('create_time', [$startDate, $endDate])->count(),
            'failed'   => MiniMessage::where('push_status', 'failed')->whereBetween('create_time', [$startDate, $endDate])->count(),
            'by_type'  => MiniMessage::field('msg_type, COUNT(*) as cnt')
                ->whereBetween('create_time', [$startDate, $endDate])
                ->group('msg_type')
                ->select()
                ->toArray(),
        ];
    }

    private function clearUnreadCache(int $memberId): void
    {
        Cache::delete('mini_unread:' . $memberId);
    }
}
