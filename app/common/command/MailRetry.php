<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\command;

use app\common\model\MailLog;
use app\common\service\MailService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * V2.9.20 C-2: 邮件失败重试命令
 * 
 * 用法：php think mail:retry
 * 逻辑：重试发送 status=0(失败) 且 retry_count<3 的邮件
 */
class MailRetry extends Command
{
    protected function configure()
    {
        $this->setName('mail:retry')
            ->setDescription('重试发送失败的邮件');
    }

    protected function execute(Input $input, Output $output)
    {
        $logs = MailLog::where('status', 0)
            ->where('retry_count', '<', 3)
            ->order('id', 'asc')
            ->limit(50)
            ->select();

        if ($logs->isEmpty()) {
            $output->writeln('<info>没有需要重试的邮件</info>');
            return;
        }

        $service = new MailService();
        $success = 0;
        $failed = 0;

        foreach ($logs as $log) {
            $result = $service->sendWithRetry(
                $log->to_email,
                $log->subject,
                $log->body,
                1 // 只重试1次，避免阻塞
            );

            $log->retry_count += 1;
            $log->status = $result ? 1 : 0;
            if ($result) {
                $log->sent_at = date('Y-m-d H:i:s');
            }
            $log->save();

            if ($result) {
                $success++;
                $output->writeln("<info>重试成功: {$log->to_email} - {$log->subject}</info>");
            } else {
                $failed++;
                $output->writeln("<error>重试失败: {$log->to_email} - {$log->subject} (已重试{$log->retry_count}次)</error>");
            }

            // 避免触发频率限制，间隔 1 秒
            sleep(1);
        }

        $output->writeln("<info>邮件重试完成：成功 {$success} 条，失败 {$failed} 条</info>");
    }
}
