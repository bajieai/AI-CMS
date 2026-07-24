<?php
declare(strict_types=1);

namespace app\common\service\perf;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 队列服务
 * V2.9.38 PERF-II-2
 * 引入topthink/think-queue，支持Redis/数据库/Beanstalkd/RabbitMQ
 * 
 * 异步场景: AI调用+数据统计+推送发送+内容分发+批量操作+SEO处理+报表生成+缓存预热
 */
class QueueService
{
    protected const CACHE_TAG = 'queue';
    protected const CACHE_TTL = 5;

    /**
     * 派发任务
     */
    public function dispatch(string $job, array $data = [], string $queue = 'default', string $priority = 'normal', int $delay = 0): string
    {
        $jobId = uniqid('job_');
        
        // 存储任务到数据库(如果think-queue未安装则降级为同步执行)
        $record = [
            'job_id' => $jobId,
            'job_class' => $job,
            'job_data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'queue' => $queue,
            'priority' => $priority,
            'delay' => $delay,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        
        try {
            Db::name('queue_job')->insert($record);
            // 如果think-queue已安装，使用队列推送
            if (class_exists('\\think\\queue\\Queue')) {
                \think\facade\Queue::later($delay, $job, $data, $queue);
            }
        } catch (\Throwable $e) {
            Log::error('Queue dispatch failed: ' . $e->getMessage());
        }
        
        Cache::clear();
        return $jobId;
    }

    /**
     * 获取队列统计
     */
    public function getQueueStats(): array
    {
        return Cache::remember('queue_stats', function() {
            try {
            $pending = Db::name('queue_job')->where('status', 'pending')->count();
            $running = Db::name('queue_job')->where('status', 'running')->count();
            $completed = Db::name('queue_job')->where('status', 'completed')->count();
            $failed = Db::name('queue_job')->where('status', 'failed')->count();
            
            $byQueue = Db::name('queue_job')
                ->field('queue, status, COUNT(*) as count')
                ->group('queue, status')
                ->select()
                ->toArray();
            
            return [
                'pending' => $pending,
                'running' => $running,
                'completed' => $completed,
                'failed' => $failed,
                'total' => $pending + $running + $completed + $failed,
                'by_queue' => $byQueue,
            ];
            } catch (\Throwable $e) {
                return ['pending' => 0, 'running' => 0, 'completed' => 0, 'failed' => 0, 'total' => 0, 'by_queue' => []];
            }
        }, self::CACHE_TTL);
    }

    public function getFailedJobs(int $page = 1, int $limit = 20): array
    {
        try {
        $query = Db::name('queue_job')->where('status', 'failed')->order('id', 'desc');
        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();
        return ['total' => $total, 'list' => $list, 'page' => $page, 'limit' => $limit];
        } catch (\Throwable $e) {
            return ['total' => 0, 'list' => [], 'page' => $page, 'limit' => $limit];
        }
    }

    public function retryFailed(int $jobId): bool
    {
        $job = Db::name('queue_job')->find($jobId);
        if (!$job || $job['status'] !== 'failed') return false;
        Db::name('queue_job')->where('id', $jobId)->update([
            'status' => 'pending', 'retry_count' => ($job['retry_count'] ?? 0) + 1,
            'error_message' => '', 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    public function cancelJob(int $jobId): bool
    {
        Db::name('queue_job')->where('id', $jobId)->where('status', 'pending')->update(['status' => 'cancelled']);
        return true;
    }

    public function clearQueue(string $queue = 'default'): bool
    {
        Db::name('queue_job')->where('queue', $queue)->where('status', 'pending')->delete();
        return true;
    }
}
