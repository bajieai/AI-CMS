<?php
declare(strict_types=1);

namespace app\common\service\perf;

use think\facade\Db;
use think\facade\Log;

/**
 * 队列工作进程管理器
 */
class QueueWorkerManager
{
    public function startWorker(string $queue = 'default', int $workers = 1): array
    {
        // 记录工作进程状态
        $workerId = 'worker_' . uniqid();
        Db::name('queue_worker')->insert([
            'worker_id' => $workerId, 'queue' => $queue, 'workers' => $workers,
            'status' => 'running', 'started_at' => date('Y-m-d H:i:s'),
        ]);
        
        // 实际启动: php think queue:work --queue=default
        Log::info("Started queue worker: {$workerId} for queue: {$queue}");
        
        return ['worker_id' => $workerId, 'queue' => $queue, 'workers' => $workers];
    }

    public function stopWorker(string $workerId): bool
    {
        Db::name('queue_worker')->where('worker_id', $workerId)->update([
            'status' => 'stopped', 'stopped_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    public function restartWorker(string $workerId): bool
    {
        $this->stopWorker($workerId);
        $worker = Db::name('queue_worker')->where('worker_id', $workerId)->find();
        if ($worker) {
            $this->startWorker($worker['queue'], $worker['workers']);
        }
        return true;
    }

    public function getWorkerStatus(): array
    {
        return Db::name('queue_worker')->where('status', 'running')->select()->toArray();
    }
}
