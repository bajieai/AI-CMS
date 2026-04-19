<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Config;
use app\model\AiTask;

/**
 * AI任务队列服务
 */
class AiTaskQueue
{
    /**
     * Redis队列键
     */
    protected string $queueKey;

    /**
     * 处理中任务键
     */
    protected string $processingKey;

    /**
     * 最大重试次数
     */
    protected int $maxRetry;

    /**
     * 任务过期时间(秒)
     */
    protected int $expireTime;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $config = Config::get('ai.task_queue', []);
        $this->queueKey = $config['redis_key'] ?? 'ai:task:queue';
        $this->processingKey = $config['processing_key'] ?? 'ai:task:processing';
        $this->maxRetry = $config['max_retry'] ?? 3;
        $this->expireTime = $config['expire_time'] ?? 3600;
    }

    /**
     * 添加任务到队列
     */
    public function push(array $taskData, int $priority = 0): string
    {
        $taskId = $this->generateTaskId();
        
        $task = [
            'id' => $taskId,
            'data' => $taskData,
            'priority' => $priority,
            'retry' => 0,
            'created_at' => time(),
            'started_at' => null,
            'completed_at' => null,
            'status' => 'pending',
            'result' => null,
            'error' => null,
        ];
        
        // 存储任务详情
        $taskKey = "ai:task:{$taskId}";
        Cache::set($taskKey, $task, $this->expireTime);
        
        // 添加到优先级队列
        $score = $priority * 1000000 + (1000000 - time() % 1000000);
        Cache::zAdd($this->queueKey, $score, $taskId);
        
        // 记录到数据库
        $this->saveToDatabase($task);
        
        return $taskId;
    }

    /**
     * 从队列取出任务
     */
    public function pop(int $timeout = 0): ?array
    {
        // 使用ZPOPMIN获取最高优先级任务
        $result = Cache::zPopMin($this->queueKey, 1);
        
        if (empty($result)) {
            if ($timeout > 0) {
                // 阻塞等待
                usleep($timeout * 1000000);
                $result = Cache::zPopMin($this->queueKey, 1);
            }
        }
        
        if (empty($result)) {
            return null;
        }
        
        $taskId = is_array($result) ? array_keys($result)[0] : $result;
        
        // 获取任务详情
        $taskKey = "ai:task:{$taskId}";
        $task = Cache::get($taskKey);
        
        if (!$task) {
            // 从数据库恢复
            $dbTask = AiTask::findByTaskId($taskId);
            if ($dbTask) {
                $task = $dbTask->toArray();
            } else {
                return null;
            }
        }
        
        // 检查是否过期
        if ($task['created_at'] + $this->expireTime < time()) {
            $this->fail($taskId, '任务已过期');
            return null;
        }
        
        // 更新任务状态
        $task['status'] = 'processing';
        $task['started_at'] = time();
        Cache::set($taskKey, $task, $this->expireTime);
        
        // 添加到处理中队列
        Cache::zAdd($this->processingKey, time(), $taskId);
        
        // 更新数据库
        $this->updateDatabase($task);
        
        return $task;
    }

    /**
     * 标记任务完成
     */
    public function complete(string $taskId, mixed $result): bool
    {
        $taskKey = "ai:task:{$taskId}";
        $task = Cache::get($taskKey);
        
        if (!$task) {
            return false;
        }
        
        $task['status'] = 'completed';
        $task['result'] = $result;
        $task['completed_at'] = time();
        
        // 移除处理中队列
        Cache::zRem($this->processingKey, $taskId);
        
        // 更新缓存
        Cache::set($taskKey, $task, $this->expireTime);
        
        // 更新数据库
        $this->updateDatabase($task);
        
        return true;
    }

    /**
     * 标记任务失败
     */
    public function fail(string $taskId, string $error): bool
    {
        $taskKey = "ai:task:{$taskId}";
        $task = Cache::get($taskKey);
        
        if (!$task) {
            return false;
        }
        
        $task['retry'] = ($task['retry'] ?? 0) + 1;
        
        if ($task['retry'] < $this->maxRetry) {
            // 重试
            $task['status'] = 'pending';
            $task['error'] = $error;
            
            // 重新加入队列
            $score = $task['priority'] * 1000000 + (1000000 - time() % 1000000);
            Cache::zAdd($this->queueKey, $score, $taskId);
            
            // 从处理中移除
            Cache::zRem($this->processingKey, $taskId);
        } else {
            // 彻底失败
            $task['status'] = 'failed';
            $task['error'] = $error;
            $task['completed_at'] = time();
            
            // 移除处理中队列
            Cache::zRem($this->processingKey, $taskId);
        }
        
        // 更新缓存
        Cache::set($taskKey, $task, $this->expireTime);
        
        // 更新数据库
        $this->updateDatabase($task);
        
        return true;
    }

    /**
     * 获取任务状态
     */
    public function status(string $taskId): ?array
    {
        $taskKey = "ai:task:{$taskId}";
        $task = Cache::get($taskKey);
        
        if (!$task) {
            $dbTask = AiTask::findByTaskId($taskId);
            if ($dbTask) {
                return $dbTask->toArray();
            }
            return null;
        }
        
        return $task;
    }

    /**
     * 获取队列长度
     */
    public function size(): int
    {
        return Cache::zCard($this->queueKey);
    }

    /**
     * 获取处理中任务数
     */
    public function processingCount(): int
    {
        return Cache::zCard($this->processingKey);
    }

    /**
     * 清理过期任务
     */
    public function cleanup(): int
    {
        $count = 0;
        $expiredTime = time() - $this->expireTime;
        
        // 清理处理中超时的任务
        $processing = Cache::zRangeByScore($this->processingKey, 0, $expiredTime);
        foreach ($processing as $taskId) {
            $this->fail($taskId, '任务处理超时');
            $count++;
        }
        
        return $count;
    }

    /**
     * 保存到数据库
     */
    protected function saveToDatabase(array $task): void
    {
        try {
            AiTask::create([
                'task_id' => $task['id'],
                'type' => $task['data']['type'] ?? 'generate',
                'user_id' => $task['data']['user_id'] ?? 0,
                'params' => json_encode($task['data'], JSON_UNESCAPED_UNICODE),
                'status' => $task['status'],
                'priority' => $task['priority'],
                'retry' => $task['retry'],
                'error' => $task['error'] ?? '',
                'result' => '',
                'created_time' => date('Y-m-d H:i:s', $task['created_at']),
                'started_time' => null,
                'completed_time' => null,
            ]);
        } catch (\Exception $e) {
            // 数据库保存失败不影响队列
            trace('AI任务数据库保存失败: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * 更新数据库
     */
    protected function updateDatabase(array $task): void
    {
        try {
            $dbTask = AiTask::findByTaskId($task['id']);
            if ($dbTask) {
                $dbTask->status = $task['status'];
                $dbTask->retry = $task['retry'] ?? 0;
                $dbTask->error = $task['error'] ?? '';
                $dbTask->result = is_array($task['result']) ? json_encode($task['result'], JSON_UNESCAPED_UNICODE) : ($task['result'] ?? '');
                $dbTask->started_time = $task['started_at'] ? date('Y-m-d H:i:s', $task['started_at']) : null;
                $dbTask->completed_time = $task['completed_at'] ? date('Y-m-d H:i:s', $task['completed_at']) : null;
                $dbTask->save();
            }
        } catch (\Exception $e) {
            trace('AI任务数据库更新失败: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * 生成任务ID
     */
    protected function generateTaskId(): string
    {
        return 'ait_' . date('YmdHis') . '_' . bin2hex(random_bytes(6));
    }

    /**
     * 批量获取任务
     */
    public function batchPop(int $count = 10, int $timeout = 0): array
    {
        $tasks = [];
        
        for ($i = 0; $i < $count; $i++) {
            $task = $this->pop($timeout);
            if (!$task) {
                break;
            }
            $tasks[] = $task;
        }
        
        return $tasks;
    }

    /**
     * 重新队列任务
     */
    public function requeue(string $taskId): bool
    {
        $taskKey = "ai:task:{$taskId}";
        $task = Cache::get($taskKey);
        
        if (!$task) {
            return false;
        }
        
        $task['status'] = 'pending';
        $task['retry'] = 0;
        $task['error'] = null;
        $task['started_at'] = null;
        
        Cache::set($taskKey, $task, $this->expireTime);
        
        $score = $task['priority'] * 1000000 + (1000000 - time() % 1000000);
        Cache::zAdd($this->queueKey, $score, $taskId);
        
        return true;
    }
}
