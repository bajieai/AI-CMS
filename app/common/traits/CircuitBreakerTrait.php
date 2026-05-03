<?php
declare(strict_types=1);

namespace app\common\traits;

use think\facade\Cache;

/**
 * AI熔断器Trait - V2.5新增
 * Redis计数 + 半开探测模式
 * 连续失败N次后熔断，熔断期间拒绝请求，半开期允许探测
 */
trait CircuitBreakerTrait
{
    /**
     * 熔断器配置（子类可覆盖）
     */
    protected int $breakerFailureThreshold = 3;  // 连续失败次数阈值
    protected int $breakerResetTime = 60;        // 熔断恢复时间(秒)
    protected int $breakerHalfOpenMax = 1;        // 半开期最大探测次数

    /**
     * 获取熔断器缓存Key
     */
    protected function getBreakerKey(string $provider): string
    {
        return "ai_breaker_{$provider}";
    }

    /**
     * 获取半开探测Key
     */
    protected function getHalfOpenKey(string $provider): string
    {
        return "ai_breaker_half_{$provider}";
    }

    /**
     * 检查是否熔断中
     * @return bool true=熔断中(拒绝请求), false=正常
     */
    protected function isBreakerOpen(string $provider): bool
    {
        $key = $this->getBreakerKey($provider);
        $data = Cache::get($key);

        if (empty($data)) {
            return false;
        }

        $info = json_decode($data, true);
        if (!$info) return false;

        // 检查是否在熔断期
        $elapsed = time() - ($info['tripped_at'] ?? 0);

        if ($elapsed >= $this->breakerResetTime) {
            // 超过恢复时间，进入半开状态
            $halfOpenKey = $this->getHalfOpenKey($provider);
            $probeCount = (int) Cache::get($halfOpenKey, 0);

            if ($probeCount < $this->breakerHalfOpenMax) {
                // 允许探测
                Cache::inc($halfOpenKey);
                Cache::set($halfOpenKey, $probeCount + 1, $this->breakerResetTime);
                return false;
            }

            return true; // 半开期探测次数用完，仍熔断
        }

        return true; // 还在熔断期
    }

    /**
     * 记录成功（重置失败计数）
     */
    protected function recordBreakerSuccess(string $provider): void
    {
        $key = $this->getBreakerKey($provider);
        $halfOpenKey = $this->getHalfOpenKey($provider);

        // 成功后清除熔断状态
        Cache::delete($key);
        Cache::delete($halfOpenKey);
    }

    /**
     * 记录失败（增加失败计数，达到阈值则熔断）
     */
    protected function recordBreakerFailure(string $provider): void
    {
        $key = $this->getBreakerKey($provider);
        $data = Cache::get($key);

        if (empty($data)) {
            $info = ['failures' => 1, 'tripped_at' => 0];
        } else {
            $info = json_decode($data, true) ?: ['failures' => 0, 'tripped_at' => 0];
            $info['failures'] = ($info['failures'] ?? 0) + 1;
        }

        // 达到阈值，触发熔断
        if ($info['failures'] >= $this->breakerFailureThreshold && empty($info['tripped_at'])) {
            $info['tripped_at'] = time();
            Cache::set($key, json_encode($info), $this->breakerResetTime * 2);
        } else {
            Cache::set($key, json_encode($info), $this->breakerResetTime * 2);
        }
    }

    /**
     * 获取熔断器状态（调试用）
     */
    protected function getBreakerStatus(string $provider): array
    {
        $key = $this->getBreakerKey($provider);
        $data = Cache::get($key);

        if (empty($data)) {
            return ['state' => 'closed', 'failures' => 0];
        }

        $info = json_decode($data, true) ?: ['failures' => 0, 'tripped_at' => 0];
        $elapsed = time() - ($info['tripped_at'] ?? 0);

        if (empty($info['tripped_at'])) {
            return ['state' => 'closed', 'failures' => $info['failures']];
        }

        if ($elapsed >= $this->breakerResetTime) {
            return ['state' => 'half_open', 'failures' => $info['failures']];
        }

        return ['state' => 'open', 'failures' => $info['failures'], 'reset_in' => $this->breakerResetTime - $elapsed];
    }
}
