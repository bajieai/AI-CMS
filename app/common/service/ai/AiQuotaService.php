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

namespace app\common\service\ai;

use think\facade\Cache;
use think\facade\Db;

/**
 * AI配额管理服务 (V2.9.29 F-4)
 * 
 * 按用户角色/全局设置每日AI调用次数上限
 */
class AiQuotaService
{
    private const CACHE_TAG = 'ai_quota';
    private const CACHE_TTL = 86400; // 24小时

    /**
     * 检查用户是否还有配额
     */
    public function checkQuota(int $userId): bool
    {
        $limit = $this->getDailyLimit($userId);
        if ($limit <= 0) {
            return true; // 0=不限制
        }
        $used = $this->getUsedCount($userId);
        return $used < $limit;
    }

    /**
     * 增加调用计数
     */
    public function incrementUsage(int $userId): void
    {
        $key = 'ai_quota_' . $userId . '_' . date('Ymd');
        Cache::inc($key);
        Cache::expire($key, self::CACHE_TTL);
    }

    /**
     * 获取今日已用次数
     */
    public function getUsedCount(int $userId): int
    {
        $key = 'ai_quota_' . $userId . '_' . date('Ymd');
        return (int) Cache::get($key, 0);
    }

    /**
     * 获取用户每日限制
     */
    public function getDailyLimit(int $userId): int
    {
        // 从系统配置读取全局限制
        $globalLimit = (int) config('ai.daily_quota', 100);
        return $globalLimit;
    }

    /**
     * 获取剩余次数
     */
    public function getRemainingCount(int $userId): int
    {
        $limit = $this->getDailyLimit($userId);
        if ($limit <= 0) {
            return -1; // 不限制
        }
        return max(0, $limit - $this->getUsedCount($userId));
    }
}
