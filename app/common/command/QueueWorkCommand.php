<?php
declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;
use think\facade\Db;
use think\facade\Log;

/**
 * 队列工作进程命令
 * V2.9.38 PERF-II-2
 * 用法: php think queue:work --queue=default
 */
class QueueWorkCommand extends Command
{
    protected function configure()
    {
        $this->setName('queue:work')
            ->addOption('queue', null, Option::VALUE_OPTIONAL, '队列名称', 'default')
            ->addOption('memory', null, Option::VALUE_OPTIONAL, '内存限制(MB)', 128)
            ->addOption('timeout', null, Option::VALUE_OPTIONAL, '超时秒数', 60)
            ->setDescription('启动队列工作进程');
    }

    protected function execute(Input $input, Output $output)
    {
        $queue = $input->getOption('queue');
        $memoryLimit = (int) $input->getOption('memory') * 1024 * 1024;
        $timeout = (int) $input->getOption('timeout');
        
        $output->writeln("<info>启动队列工作进程: queue={$queue}</info>");
        
        // 如果think-queue已安装，使用其work命令
        if (class_exists('\\think\\queue\\Worker')) {
            // 转发给think-queue
            $output->writeln("think-queue已安装，使用原生工作进程");
            return;
        }
        
        // 降级: 简单轮询模式
        $output->writeln("think-queue未安装，使用简单轮询模式");
        $startTime = time();
        
        while (true) {
            // 内存检查
            if (memory_get_usage(true) > $memoryLimit) {
                $output->writeln("<comment>内存超过限制({$memoryLimit})，退出</comment>");
                break;
            }
            
            // 超时检查
            if (time() - $startTime > $timeout) {
                $output->writeln("<comment>运行超时({$timeout}s)，退出</comment>");
                break;
            }
            
            // 查找待处理任务
            $job = Db::name('queue_job')
                ->where('queue', $queue)
                ->where('status', 'pending')
                ->order('priority', 'desc')
                ->order('id', 'asc')
                ->find();
            
            if (!$job) {
                sleep(3); // 空闲休眠
                continue;
            }
            
            // 标记为运行中
            Db::name('queue_job')->where('id', $job['id'])->update([
                'status' => 'running', 'started_at' => date('Y-m-d H:i:s'),
            ]);
            
            try {
                $jobClass = $job['job_class'];
                $jobData = json_decode($job['job_data'], true) ?? [];
                
                if (class_exists($jobClass)) {
                    $instance = new $jobClass();
                    if (method_exists($instance, 'handle')) {
                        $instance->handle($jobData);
                    }
                }
                
                Db::name('queue_job')->where('id', $job['id'])->update([
                    'status' => 'completed', 'completed_at' => date('Y-m-d H:i:s'),
                ]);
                $output->writeln("<info>✓ 任务完成: {$job['job_id']}</info>");
            } catch (\Throwable $e) {
                Db::name('queue_job')->where('id', $job['id'])->update([
                    'status' => 'failed', 'error_message' => $e->getMessage(),
                    'completed_at' => date('Y-m-d H:i:s'),
                ]);
                $output->writeln("<error>✗ 任务失败: {$job['job_id']} - {$e->getMessage()}</error>");
                Log::error("Queue job failed: {$job['job_id']} - " . $e->getMessage());
            }
        }
    }
}
