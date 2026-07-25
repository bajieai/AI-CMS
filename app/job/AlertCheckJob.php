<?php

declare(strict_types=1);

namespace app\job;

use think\queue\Job;
use app\common\service\data\DataAlertService;

/**
 * 预警检查定时任务
 */
class AlertCheckJob
{
    public function fire(Job $job, array $data): void
    {
        try {
            DataAlertService::checkAlerts();
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
