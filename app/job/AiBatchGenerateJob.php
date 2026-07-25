<?php

declare(strict_types=1);

namespace app\job;

use think\queue\Job;
use app\common\service\ai\AiBatchGenerateService;

/**
 * AI批量生成队列任务
 */
class AiBatchGenerateJob
{
    public function fire(Job $job, array $data): void
    {
        try {
            AiBatchGenerateService::executeBatch(
                (int)$data['task_id'],
                (int)$data['batch_index'],
                (int)$data['batch_size'],
                $data['config'] ?? []
            );
            AiBatchGenerateService::updateProgress((int)$data['task_id']);
            $job->delete();
        } catch (\Exception $e) {
            if ($job->attempts() >= 3) {
                $job->delete();
            } else {
                $job->release(60);
            }
        }
    }
}
