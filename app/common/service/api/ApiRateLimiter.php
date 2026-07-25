<?php
declare(strict_types=1);
namespace app\common\service\api;

/**
 * API频率限制服务 (V2.9.29 D-5)
 */
class ApiRateLimiter
{
    /**
     * 获取剩余次数
     */
    public function getRemaining(int $keyId, int $limit): int
    {
        $cacheKey = 'api_rate_limit_' . $keyId . '_' . date('YmdHi');
        $used = (int) \think\facade\Cache::get($cacheKey, 0);
        return max(0, $limit - $used);
    }

    /**
     * 是否超限
     */
    public function isLimited(int $keyId, int $limit): bool
    {
        return $this->getRemaining($keyId, $limit) <= 0;
    }
}
