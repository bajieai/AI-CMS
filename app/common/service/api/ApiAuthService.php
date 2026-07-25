<?php
declare(strict_types=1);
namespace app\common\service\api;

use app\common\model\ApiKey;
use app\common\model\ApiLog;
use think\facade\Cache;
use think\facade\Db;

/**
 * API认证服务 (V2.9.29 D-5)
 * 双认证(api_key+api_secret) + IP白名单 + 频率限制
 */
class ApiAuthService
{
    private const RATE_LIMIT_TAG = 'api_rate_limit';

    /**
     * 验证请求
     * @return array|false [key_id, scopes] 或 false
     */
    public function authenticate(string $apiKey, string $apiSecret, string $ip, string $endpoint): array|false
    {
        $key = ApiKey::where('api_key', $apiKey)
            ->where('is_active', 1)
            ->find();

        if (!$key) return false;
        if ($key->api_secret !== $apiSecret) return false;

        // IP白名单检查
        if (!empty($key->ip_whitelist)) {
            $allowedIps = explode(',', $key->ip_whitelist);
            $allowedIps = array_map('trim', $allowedIps);
            if (!in_array($ip, $allowedIps, true)) return false;
        }

        // 频率限制检查
        if (!$this->checkRateLimit($key->id, $key->rate_limit)) return false;

        $scopes = json_decode($key->scopes, true) ?: [];

        // 记录调用日志
        ApiLog::create([
            'api_key_id' => $key->id,
            'endpoint' => $endpoint,
            'method' => request()->method(),
            'ip_address' => $ip,
            'status_code' => 200,
            'duration_ms' => 0,
            'user_agent' => substr(request()->header('user-agent', ''), 0, 500),
            'create_time' => time(),
        ]);

        return ['key_id' => $key->id, 'scopes' => $scopes, 'rate_limit' => $key->rate_limit];
    }

    /**
     * 检查频率限制
     */
    public function checkRateLimit(int $keyId, int $limit): bool
    {
        $cacheKey = self::RATE_LIMIT_TAG . '_' . $keyId . '_' . date('YmdHi');
        $count = (int) Cache::get($cacheKey, 0);
        if ($count >= $limit) return false;
        Cache::set($cacheKey, $count + 1, 120);
        return true;
    }

    /**
     * 检查scope权限
     */
    public function checkScope(array $authResult, string $requiredScope): bool
    {
        $scopes = $authResult['scopes'] ?? [];
        // 默认拒绝：未明确授权的接口禁止访问
        if (empty($scopes)) return false;
        if (in_array('*', $scopes)) return true;
        return in_array($requiredScope, $scopes, true);
    }
}
