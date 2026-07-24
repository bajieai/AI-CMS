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
use app\common\service\NotificationService;

/**
 * AI告警通知服务 (V2.9.29 F-4)
 * 
 * 当AI API连续失败超过阈值时发送站内通知给管理员
 */
class AiAlertService
{
    private const CACHE_TAG = 'ai_alert';
    private const FAIL_THRESHOLD = 5; // 连续失败5次触发告警

    /**
     * 记录API失败
     */
    public function recordFailure(string $apiName, string $error): void
    {
        $key = 'ai_fail_count_' . $apiName;
        $count = (int) Cache::get($key, 0);
        $count++;
        Cache::set($key, $count, 3600);

        if ($count >= self::FAIL_THRESHOLD) {
            $this->sendAlert($apiName, $count, $error);
            // 重置计数
            Cache::set($key, 0, 3600);
        }
    }

    /**
     * 记录API成功（重置失败计数）
     */
    public function recordSuccess(string $apiName): void
    {
        $key = 'ai_fail_count_' . $apiName;
        Cache::set($key, 0, 3600);
    }

    /**
     * 发送告警通知
     */
    private function sendAlert(string $apiName, int $failCount, string $error): void
    {
        $message = sprintf(
            'AI API连续失败告警：%s 已连续失败 %d 次，最近错误：%s',
            $apiName,
            $failCount,
            mb_substr($error, 0, 200)
        );

        // 尝试调用通知服务
        if (class_exists(NotificationService::class)) {
            try {
                NotificationService::sendToAdmins('ai_alert', 'AI服务告警', $message);
            } catch (\Exception $e) {
                \think\facade\Log::error('AI告警发送失败: ' . $e->getMessage());
            }
        }
    }
}
