<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\AiTaskQueue;

/**
 * AI任务队列服务 - V2.9.14
 *
 * 统一管理服务：入队、出队、状态更新、消费者调度
 */
class AiTaskQueueService
{
    /**
     * 创建新任务入队
     */
    public function enqueue(string $taskType, array $params): int
    {
        $now = time();
        $task = AiTaskQueue::create([
            'task_type'    => $taskType,
            'biz_id'       => $params['biz_id'] ?? 0,
            'biz_key'      => $params['biz_key'] ?? '',
            'payload'      => json_encode($params['payload'] ?? [], JSON_UNESCAPED_UNICODE),
            'status'       => AiTaskQueue::STATUS_PENDING,
            'progress'     => 0,
            'priority'     => $params['priority'] ?? 0,
            'scheduled_at' => $params['scheduled_at'] ?? 0,
            'max_retries'  => $params['max_retries'] ?? 3,
            'create_time'  => $now,
            'update_time'  => $now,
        ]);

        return (int) $task->id;
    }

    /**
     * 取出待处理任务（按优先级/时间排序）
     */
    public function dequeue(string $taskType, int $limit = 3): array
    {
        $tasks = AiTaskQueue::where('task_type', $taskType)
            ->where('status', AiTaskQueue::STATUS_PENDING)
            ->where('scheduled_at', '<=', time())
            ->order('priority', 'desc')
            ->order('create_time', 'asc')
            ->limit($limit)
            ->select();

        return $tasks->toArray();
    }

    /**
     * 查询单个任务状态
     */
    public function getStatus(int $taskId): ?array
    {
        $task = AiTaskQueue::find($taskId);
        if (!$task) {
            return null;
        }
        $data = $task->toArray();
        $data['status_name'] = AiTaskQueue::getStatusName((int) $task->status);
        $data['payload'] = json_decode($task->payload ?: '{}', true);
        $data['result'] = json_decode($task->result ?: '{}', true);
        return $data;
    }

    /**
     * 按业务标识查询任务组状态
     */
    public function getBizStatus(string $bizKey): array
    {
        $tasks = AiTaskQueue::where('biz_key', $bizKey)
            ->order('create_time', 'asc')
            ->select();

        $list = [];
        $total = count($tasks);
        $completed = 0;
        $failed = 0;

        foreach ($tasks as $task) {
            $item = $task->toArray();
            $item['payload'] = json_decode($task->payload ?: '{}', true);
            $item['result'] = json_decode($task->result ?: '{}', true);
            $item['status_name'] = AiTaskQueue::getStatusName((int) $task->status);
            $list[] = $item;

            if ($task->status == AiTaskQueue::STATUS_COMPLETED) {
                $completed++;
            } elseif ($task->status == AiTaskQueue::STATUS_FAILED) {
                $failed++;
            }
        }

        return [
            'biz_key'   => $bizKey,
            'total'     => $total,
            'completed' => $completed,
            'failed'    => $failed,
            'pending'   => $total - $completed - $failed,
            'progress'  => $total > 0 ? (int) round(($completed + $failed) / $total * 100) : 0,
            'tasks'     => $list,
        ];
    }

    /**
     * 更新进度和状态
     */
    public function updateProgress(int $taskId, int $progress, string $status = ''): bool
    {
        $update = ['progress' => max(0, min(100, $progress)), 'update_time' => time()];
        if ($status !== '') {
            $update['status'] = $this->resolveStatus($status);
        }
        return AiTaskQueue::where('id', $taskId)->update($update) > 0;
    }

    /**
     * 标记任务为运行中
     */
    public function markRunning(int $taskId): bool
    {
        return AiTaskQueue::where('id', $taskId)
            ->where('status', AiTaskQueue::STATUS_PENDING)
            ->update([
                'status'      => AiTaskQueue::STATUS_RUNNING,
                'started_at'  => time(),
                'update_time' => time(),
            ]) > 0;
    }

    /**
     * 标记任务完成
     */
    public function complete(int $taskId, array $result): bool
    {
        return AiTaskQueue::where('id', $taskId)->update([
            'status'       => AiTaskQueue::STATUS_COMPLETED,
            'progress'     => 100,
            'result'       => json_encode($result, JSON_UNESCAPED_UNICODE),
            'completed_at' => time(),
            'update_time'  => time(),
        ]) > 0;
    }

    /**
     * 标记任务失败
     */
    public function fail(int $taskId, string $errorMsg): bool
    {
        $task = AiTaskQueue::find($taskId);
        if (!$task) {
            return false;
        }

        $retryCount = (int) $task->retry_count + 1;
        $maxRetries = (int) $task->max_retries;

        // 如果还有重试次数，回到pending状态
        if ($retryCount < $maxRetries) {
            return AiTaskQueue::where('id', $taskId)->update([
                'status'      => AiTaskQueue::STATUS_PENDING,
                'retry_count' => $retryCount,
                'error_msg'   => $errorMsg,
                'update_time' => time(),
            ]) > 0;
        }

        return AiTaskQueue::where('id', $taskId)->update([
            'status'      => AiTaskQueue::STATUS_FAILED,
            'error_msg'   => $errorMsg,
            'retry_count' => $retryCount,
            'update_time' => time(),
        ]) > 0;
    }

    /**
     * 暂停任务
     */
    public function pause(int $taskId): bool
    {
        return AiTaskQueue::where('id', $taskId)
            ->whereIn('status', [AiTaskQueue::STATUS_PENDING, AiTaskQueue::STATUS_RUNNING])
            ->update([
                'status'      => AiTaskQueue::STATUS_PAUSED,
                'update_time' => time(),
            ]) > 0;
    }

    /**
     * 恢复任务（条件更新防竞态）
     */
    public function resume(int $taskId): bool
    {
        return AiTaskQueue::where('id', $taskId)
            ->where('status', AiTaskQueue::STATUS_PAUSED)
            ->update([
                'status'      => AiTaskQueue::STATUS_PENDING,
                'update_time' => time(),
            ]) > 0;
    }

    /**
     * 取消整个业务组的所有待处理/运行中任务
     */
    public function cancelBiz(string $bizKey): int
    {
        return AiTaskQueue::where('biz_key', $bizKey)
            ->whereIn('status', [AiTaskQueue::STATUS_PENDING, AiTaskQueue::STATUS_RUNNING, AiTaskQueue::STATUS_PAUSED])
            ->update([
                'status'      => AiTaskQueue::STATUS_CANCELLED,
                'update_time' => time(),
            ]);
    }

    /**
     * 消费者：取出并标记为运行中
     */
    public function consume(string $taskType, int $limit = 3): array
    {
        $tasks = $this->dequeue($taskType, $limit);
        foreach ($tasks as &$task) {
            $this->markRunning((int) $task['id']);
            $task['status'] = AiTaskQueue::STATUS_RUNNING;
            $task['payload'] = json_decode($task['payload'] ?: '{}', true);
        }
        return $tasks;
    }

    /**
     * 清理过期任务
     */
    public function cleanup(int $expireHours = 72): int
    {
        $expireTime = time() - ($expireHours * 3600);
        // completed保留7天，其他状态保留72小时
        $count1 = AiTaskQueue::where('status', AiTaskQueue::STATUS_COMPLETED)
            ->where('update_time', '<', $expireTime - (5 * 24 * 3600))
            ->delete();

        $count2 = AiTaskQueue::whereIn('status', [
            AiTaskQueue::STATUS_FAILED,
            AiTaskQueue::STATUS_PAUSED,
            AiTaskQueue::STATUS_CANCELLED,
        ])->where('update_time', '<', $expireTime)
            ->delete();

        return $count1 + $count2;
    }

    /**
     * 解析状态字符串为常量
     */
    protected function resolveStatus(string $status): int
    {
        $map = [
            'pending'    => AiTaskQueue::STATUS_PENDING,
            'running'    => AiTaskQueue::STATUS_RUNNING,
            'completed'  => AiTaskQueue::STATUS_COMPLETED,
            'failed'     => AiTaskQueue::STATUS_FAILED,
            'paused'     => AiTaskQueue::STATUS_PAUSED,
            'cancelled'  => AiTaskQueue::STATUS_CANCELLED,
        ];
        return $map[$status] ?? AiTaskQueue::STATUS_PENDING;
    }
}
