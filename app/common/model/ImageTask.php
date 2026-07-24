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

namespace app\common\model;

use think\Model;

/**
 * 配图异步任务模型 - V2.9.1 M14a
 * 对应表: i8j_image_task
 */
class ImageTask extends Model
{
    // 表名（不含前缀）
    protected $name = 'image_task';

    // 自动时间戳（使用int时间戳）
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'status'        => 'integer',
        'attempts'      => 'integer',
        'max_attempts'  => 'integer',
        'related_id'    => 'integer',
        'retry_count'   => 'integer',
        'result'        => 'json',
        'create_time'   => 'integer',
        'update_time'   => 'integer',
    ];

    // 状态常量
    public const STATUS_PENDING    = 0;
    public const STATUS_PROCESSING = 1;
    public const STATUS_COMPLETED  = 2;
    public const STATUS_FAILED     = 3;

    // 状态文本映射
    protected static array $statusMap = [
        self::STATUS_PENDING    => 'pending',
        self::STATUS_PROCESSING => 'processing',
        self::STATUS_COMPLETED  => 'completed',
        self::STATUS_FAILED     => 'failed',
    ];

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        return self::$statusMap[$data['status'] ?? 0] ?? 'unknown';
    }

    /**
     * 获取进度百分比 (0-100)
     */
    public function getProgressAttr($value, $data): int
    {
        $max = max((int) ($data['max_attempts'] ?? 30), 1);
        $attempts = (int) ($data['attempts'] ?? 0);
        return min(100, (int) round($attempts / $max * 100));
    }

    /**
     * 查询待处理的pending任务
     */
    public static function getPendingTasks(int $limit = 10): array
    {
        return self::where('status', self::STATUS_PENDING)
            ->whereColumn('attempts', '<', 'max_attempts')
            ->where('retry_count', '<', 3)
            ->order('create_time', 'asc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 根据外部task_id查找
     */
    public static function findByTaskId(string $taskId): ?self
    {
        return self::where('task_id', $taskId)->find();
    }

    /**
     * 标记任务为完成
     */
    public static function markCompleted(int $id, array $result, string $localPath = ''): bool
    {
        $update = [
            'status'      => self::STATUS_COMPLETED,
            'result'      => $result,
            'local_path'  => $localPath,
            'update_time' => time(),
        ];
        return self::where('id', $id)->update($update) > 0;
    }

    /**
     * 标记任务为失败
     */
    public static function markFailed(int $id, string $errorMsg, bool $shouldRetry = false): bool
    {
        $task = self::find($id);
        if (!$task) {
            return false;
        }

        $retryCount = (int) $task->retry_count + 1;
        $status = ($shouldRetry && $retryCount < 3) ? self::STATUS_PENDING : self::STATUS_FAILED;

        $update = [
            'status'      => $status,
            'error_msg'   => $errorMsg,
            'retry_count' => $retryCount,
            'attempts'    => 0, // 重置尝试次数，等待重试
            'update_time' => time(),
        ];

        return self::where('id', $id)->update($update) > 0;
    }

    /**
     * 递增尝试次数
     */
    public static function incrementAttempts(int $id): bool
    {
        return self::where('id', $id)->inc('attempts')->update(['update_time' => time()]) > 0;
    }

    /**
     * 标记为处理中
     */
    public static function markProcessing(int $id): bool
    {
        return self::where('id', $id)->update([
            'status'      => self::STATUS_PROCESSING,
            'update_time' => time(),
        ]) > 0;
    }
}
