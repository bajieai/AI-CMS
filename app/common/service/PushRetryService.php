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

use app\common\model\PushRetry;
use app\common\model\PushLog;
use app\common\service\push\PushDispatchService;

/**
 * 推送重试队列服务 - V2.9.19 D-1
 *
 * 指数退避重试策略：
 * 第1次：1分钟后
 * 第2次：5分钟后
 * 第3次：15分钟后
 * 第4次：30分钟后
 * 第5次+：60分钟后
 */
class PushRetryService
{
    /** 退避间隔（秒） */
    protected static array $backoffIntervals = [
        1 => 60,   // 1分钟
        2 => 300,  // 5分钟
        3 => 900,  // 15分钟
        4 => 1800, // 30分钟
    ];

    /** 最大重试次数 */
    protected static int $maxRetries = 5;

    /**
     * 将推送任务入重试队列
     */
    public static function enqueue(int $pushId, string $channel, string $reason = ''): void
    {
        PushRetry::create([
            'push_id'       => $pushId,
            'channel'       => $channel,
            'reason'        => $reason,
            'status'        => PushRetry::STATUS_PENDING,
            'retry_count'   => 0,
            'next_retry_at' => time() + 60,
            'created_at'    => time(),
            'updated_at'    => time(),
        ]);
    }

    /**
     * 处理重试队列（由 PushRetryCommand 调用）
     *
     * @return array ['success' => int, 'fail' => int, 'skip' => int]
     */
    public static function processRetries(int $limit = 50): array
    {
        $items = PushRetry::where('status', PushRetry::STATUS_PENDING)
            ->where('next_retry_at', '<=', time())
            ->order('id', 'asc')
            ->limit($limit)
            ->select();

        $success = 0;
        $fail    = 0;
        $skip    = 0;

        $dispatchService = new PushDispatchService();

        foreach ($items as $item) {
            // 超过最大重试次数则标记失败
            if ($item->retry_count >= self::$maxRetries) {
                $item->save([
                    'status'     => PushRetry::STATUS_FAILED,
                    'error_msg'  => '超过最大重试次数',
                    'updated_at' => time(),
                ]);
                $fail++;
                continue;
            }

            // 执行重试
            try {
                $channel = \app\common\model\PushChannel::where('name', $item->channel)
                    ->where('status', \app\common\model\PushChannel::STATUS_ENABLED)
                    ->find();

                if (!$channel) {
                    $item->save([
                        'status'     => PushRetry::STATUS_FAILED,
                        'error_msg'  => '推送通道不存在或已禁用',
                        'updated_at' => time(),
                    ]);
                    $fail++;
                    continue;
                }

                $result = $dispatchService->dispatchToChannel((int) $channel->id, $item->push_id);

                if ($result['success'] ?? false) {
                    $item->save([
                        'status'     => PushRetry::STATUS_SUCCESS,
                        'error_msg'  => '',
                        'updated_at' => time(),
                    ]);
                    $success++;
                } else {
                    $nextRetry = self::calculateNextRetry($item->retry_count + 1);
                    $item->save([
                        'retry_count'   => $item->retry_count + 1,
                        'next_retry_at' => $nextRetry,
                        'error_msg'     => $result['error_msg'] ?? '重试失败',
                        'updated_at'    => time(),
                    ]);
                    $skip++;
                }
            } catch (\Throwable $e) {
                $nextRetry = self::calculateNextRetry($item->retry_count + 1);
                $item->save([
                    'retry_count'   => $item->retry_count + 1,
                    'next_retry_at' => $nextRetry,
                    'error_msg'     => $e->getMessage(),
                    'updated_at'    => time(),
                ]);
                $skip++;
            }
        }

        return ['success' => $success, 'fail' => $fail, 'skip' => $skip];
    }

    /**
     * 计算下次重试时间
     */
    public static function calculateNextRetry(int $retryCount): int
    {
        $seconds = self::$backoffIntervals[$retryCount] ?? 3600;
        return time() + $seconds;
    }

    /**
     * 获取队列统计
     */
    public static function getStats(): array
    {
        return [
            'pending' => PushRetry::where('status', PushRetry::STATUS_PENDING)->count(),
            'success' => PushRetry::where('status', PushRetry::STATUS_SUCCESS)->count(),
            'failed'  => PushRetry::where('status', PushRetry::STATUS_FAILED)->count(),
        ];
    }
}
