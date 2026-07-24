<?php

declare(strict_types=1);

namespace app\job;

use think\queue\Job;
use app\common\service\data\ReportSubscriptionService;

/**
 * 报告推送定时任务
 */
class ReportPushJob
{
    public function fire(Job $job, array $data): void
    {
        try {
            if (!empty($data['subscription_id'])) {
                ReportSubscriptionService::pushReport((int)$data['subscription_id']);
            } else {
                ReportSubscriptionService::checkAndPush();
            }
            $job->delete();
        } catch (\Exception $e) {
            if ($job->attempts() >= 3) {
                $job->delete();
            } else {
                $job->release(300);
            }
        }
    }
}
