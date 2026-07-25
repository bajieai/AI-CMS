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

namespace app\common\command;

use app\common\model\EmailQueue;
use app\common\service\EmailService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * 邮件队列恢复命令 - V2.7 P0-2
 * 扫描 i8j_email_queue 中 status=0 的记录，重新入 Redis 队列
 * 应在队列 Worker 启动前执行
 */
class EmailQueueRecoverCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('email:recover')
            ->setDescription('恢复未发送的邮件队列（扫描DB中status=0的记录重新入Redis）');
    }

    protected function execute(Input $input, Output $output): int
    {
        $output->writeln('开始扫描待发送邮件...');

        $pending = EmailQueue::scanPending(200);
        if (empty($pending)) {
            $output->writeln('没有待恢复的邮件队列记录');
            return 0;
        }

        $count = 0;
        foreach ($pending as $item) {
            $pushed = EmailService::queuePush('email_queue_pending', [
                'db_id'       => $item['db_id'],
                'template_code' => $item['template_code'],
                'to_email'     => $item['to_email'],
                'vars'         => $item['vars'],
                'retry'        => 0,
                'create_time'   => time(),
            ]);
            if ($pushed) {
                $count++;
            }
        }

        $output->writeln("恢复完成：{$count}/" . count($pending) . " 条记录已重新入队");
        Log::info("EmailQueueRecover: 恢复{$count}条待发送邮件");

        return 0;
    }
}
