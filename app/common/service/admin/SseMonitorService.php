<?php
declare(strict_types=1);
namespace app\common\service\admin;

use app\common\service\sse\SseEngine;
use app\common\model\SseClient;
use app\common\model\SseMessageQueue;
use think\facade\Cache;

class SseMonitorService
{
    public static function getDashboard(): array
    {
        $cacheKey = 'sse_dashboard';
        $cached = Cache::get($cacheKey);
        if ($cached !== null) return $cached;
        $stats = SseEngine::getStats();
        $stats['recent_clients'] = SseClient::order('last_active', 'desc')->limit(20)->select()->toArray();
        $stats['recent_messages'] = SseMessageQueue::order('id', 'desc')->limit(20)->select()->toArray();
        $stats['channel_stats'] = self::getChannelStats();
        $stats['ip_stats'] = self::getIpStats();
        Cache::set($cacheKey, $stats, 10);
        return $stats;
    }

    public static function getChannelStats(): array
    {
        $channels = ['audit', 'comment', 'system', 'notification'];
        $result = [];
        foreach ($channels as $ch) {
            $result[$ch] = [
                'total' => SseMessageQueue::where('channel', $ch)->count(),
                'pending' => SseMessageQueue::where('channel', $ch)->where('is_delivered', 0)->count(),
                'delivered' => SseMessageQueue::where('channel', $ch)->where('is_delivered', 1)->count(),
            ];
        }
        return $result;
    }

    public static function getIpStats(): array
    {
        return SseClient::where('status', SseClient::STATUS_ONLINE)
            ->field('ip_address, COUNT(*) as count')
            ->group('ip_address')->order('count', 'desc')->limit(10)->select()->toArray();
    }

    public static function doCleanup(): array
    {
        $cleanedMsgs = SseEngine::cleanup();
        $cleanedClients = SseEngine::cleanStaleClients();
        Cache::delete('sse_dashboard');
        return ['cleaned_messages' => $cleanedMsgs, 'cleaned_clients' => $cleanedClients];
    }
}
