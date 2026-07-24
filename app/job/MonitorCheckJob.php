<?php

declare(strict_types=1);

namespace app\job;

use think\queue\Job;
use app\common\service\sys\MonitorAlertService;

/**
 * 监控检查定时任务
 */
class MonitorCheckJob
{
    public function fire(Job $job, array $data): void
    {
        try {
            MonitorAlertService::checkAlerts();
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
