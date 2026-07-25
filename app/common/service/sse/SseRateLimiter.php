<?php
declare(strict_types=1);
namespace app\common\service\sse;

use app\common\model\Config;
use app\common\model\SseClient;
use think\facade\Cache;

/**
 * V2.9.27 T-6: SSE安全限流器
 */
class SseRateLimiter
{
    public static function checkIpLimit(string $ip): bool
    {
        $max = (int)Config::getValue('sse_max_connections_per_ip', '5');
        $count = SseClient::where('ip_address', $ip)->where('status', SseClient::STATUS_ONLINE)->count();
        return $count < $max;
    }

    public static function checkUserLimit(int $userId): bool
    {
        if ($userId <= 0) return true;
        $max = (int)Config::getValue('sse_max_connections_per_user', '3');
        $count = SseClient::where('user_id', $userId)->where('status', SseClient::STATUS_ONLINE)->count();
        return $count < $max;
    }

    public static function checkPushRate(int $userId, string $channel): bool
    {
        $key = 'sse_push_rate_' . $userId . '_' . $channel;
        $count = (int)Cache::get($key, 0);
        if ($count >= 50) return false;
        Cache::set($key, $count + 1, 60);
        return true;
    }

    public static function validateOrigin(string $origin): bool
    {
        if (empty($origin)) return true;
        $allowedDomains = Config::getValue('sse_allowed_domains', '');
        if (empty($allowedDomains)) return true;
        $domains = explode(',', $allowedDomains);
        foreach ($domains as $domain) {
            if (trim($domain) && strpos($origin, trim($domain)) !== false) return true;
        }
        return false;
    }
}
