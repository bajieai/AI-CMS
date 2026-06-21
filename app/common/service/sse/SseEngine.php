<?php
declare(strict_types=1);
namespace app\common\service\sse;

use app\common\model\SseMessageQueue;
use app\common\model\SseClient;
use app\common\model\Config;
use think\facade\Log;

/**
 * V2.9.27 T-1: SSE引擎 — DB持久化队列
 */
class SseEngine
{
    public static function push(string $channel, array $payload, int $userId = 0, string $eventType = 'message', int $ttl = 3600): int
    {
        try {
            $msg = SseMessageQueue::create([
                'channel' => $channel, 'user_id' => $userId, 'event_type' => $eventType,
                'payload' => $payload, 'is_delivered' => 0, 'delivered_at' => 0,
                'expires_at' => $ttl > 0 ? time() + $ttl : 0, 'create_time' => time(),
            ]);
            return (int)$msg->id;
        } catch (\Throwable $e) {
            Log::error('SSE push failed: ' . $e->getMessage());
            return 0;
        }
    }

    public static function pull(string $channel, int $userId = 0, int $lastEventId = 0, int $limit = 50): array
    {
        $query = SseMessageQueue::where('channel', $channel)->where('is_delivered', 0)
            ->where(function ($q) { $q->where('expires_at', 0)->whereOr('expires_at', '>', time()); });
        if ($lastEventId > 0) $query->where('id', '>', $lastEventId);
        $query->where(function ($q) use ($userId) {
            $q->where('user_id', 0);
            if ($userId > 0) $q->whereOr('user_id', $userId);
        });
        return $query->order('id', 'asc')->limit($limit)->select()->toArray();
    }

    public static function markDelivered(array $messageIds): void
    {
        if (empty($messageIds)) return;
        SseMessageQueue::whereIn('id', $messageIds)->update(['is_delivered' => 1, 'delivered_at' => time()]);
    }

    public static function broadcast(array $payload, string $eventType = 'message', int $ttl = 3600): void
    {
        foreach ([SseMessageQueue::CHANNEL_AUDIT, SseMessageQueue::CHANNEL_COMMENT, SseMessageQueue::CHANNEL_SYSTEM, SseMessageQueue::CHANNEL_NOTIFICATION] as $ch) {
            self::push($ch, $payload, 0, $eventType, $ttl);
        }
    }

    public static function cleanup(): int
    {
        $expired = SseMessageQueue::where('expires_at', '>', 0)->where('expires_at', '<', time())->delete();
        $old = SseMessageQueue::where('is_delivered', 1)->where('delivered_at', '<', time() - 86400 * 7)->delete();
        return $expired + $old;
    }

    public static function getOfflineMessages(int $userId, int $lastEventId = 0, int $limit = 100): array
    {
        $limit = min($limit, (int)Config::getValue('sse_offline_message_limit', '100'));
        $messages = [];
        foreach ([SseMessageQueue::CHANNEL_NOTIFICATION, SseMessageQueue::CHANNEL_SYSTEM, SseMessageQueue::CHANNEL_AUDIT] as $ch) {
            $messages = array_merge($messages, self::pull($ch, $userId, $lastEventId, $limit));
        }
        usort($messages, fn($a, $b) => $a['id'] <=> $b['id']);
        return array_slice($messages, 0, $limit);
    }

    public static function registerClient(string $clientId, int $userId, string $ip, string $userAgent, string $channels): SseClient
    {
        $now = time();
        $client = SseClient::where('client_id', $clientId)->find();
        if ($client) {
            $client->user_id = $userId; $client->ip_address = $ip; $client->user_agent = $userAgent;
            $client->channels = $channels; $client->last_active = $now; $client->connect_time = $now;
            $client->status = SseClient::STATUS_ONLINE; $client->save();
        } else {
            $client = SseClient::create([
                'client_id' => $clientId, 'user_id' => $userId, 'ip_address' => $ip,
                'user_agent' => $userAgent, 'channels' => $channels, 'last_event_id' => 0,
                'last_active' => $now, 'connect_time' => $now, 'status' => SseClient::STATUS_ONLINE,
            ]);
        }
        return $client;
    }

    public static function heartbeat(string $clientId, int $lastEventId = 0): void
    {
        $client = SseClient::where('client_id', $clientId)->find();
        if ($client) {
            $client->last_active = time();
            if ($lastEventId > 0) $client->last_event_id = $lastEventId;
            $client->save();
        }
    }

    public static function unregisterClient(string $clientId): void
    {
        $client = SseClient::where('client_id', $clientId)->find();
        if ($client) { $client->status = SseClient::STATUS_OFFLINE; $client->last_active = time(); $client->save(); }
    }

    public static function cleanStaleClients(): int
    {
        $timeout = (int)Config::getValue('sse_connection_timeout', '1800');
        return SseClient::where('status', SseClient::STATUS_ONLINE)
            ->where('last_active', '<', time() - $timeout)
            ->update(['status' => SseClient::STATUS_OFFLINE]);
    }

    public static function getStats(): array
    {
        return [
            'online_clients' => SseClient::where('status', SseClient::STATUS_ONLINE)->count(),
            'total_clients' => SseClient::count(),
            'pending_messages' => SseMessageQueue::where('is_delivered', 0)->count(),
            'total_messages' => SseMessageQueue::count(),
            'channels' => [
                'audit' => SseMessageQueue::where('channel', 'audit')->where('is_delivered', 0)->count(),
                'comment' => SseMessageQueue::where('channel', 'comment')->where('is_delivered', 0)->count(),
                'system' => SseMessageQueue::where('channel', 'system')->where('is_delivered', 0)->count(),
                'notification' => SseMessageQueue::where('channel', 'notification')->where('is_delivered', 0)->count(),
            ],
        ];
    }
}
