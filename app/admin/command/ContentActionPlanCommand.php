<?php
declare(strict_types=1);
namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\model\ContentActionPlan;
use app\common\model\Content;

/**
 * 内容行动计划定时执行命令 (V2.9.29 I-3)
 * 每5分钟执行：定时发布/下线/归档
 */
class ContentActionPlanCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('content:action_plan')
            ->setDescription('内容行动计划定时执行（每5分钟）');
    }

    protected function execute(Input $input, Output $output): void
    {
        $now = time();
        $output->writeln('<info>=== 内容行动计划执行开始 ===</info>');

        $plans = ContentActionPlan::where('status', ContentActionPlan::STATUS_PENDING)
            ->where('execute_time', '<=', $now)
            ->limit(100)
            ->select();

        $output->writeln("待执行计划: {$plans->count()}");

        foreach ($plans as $plan) {
            try {
                $content = Content::find($plan->content_id);
                if (!$content) {
                    $plan->status = ContentActionPlan::STATUS_FAILED;
                    $plan->execute_log = '内容不存在';
                    $plan->save();
                    continue;
                }

                switch ($plan->action) {
                    case 'publish':
                        $content->status = 1;
                        break;
                    case 'unpublish':
                        $content->status = 0;
                        break;
                    case 'archive':
                        $content->status = 2;
                        break;
                }
                $content->save();

                $plan->status = ContentActionPlan::STATUS_EXECUTED;
                $plan->execute_log = "执行成功: {$plan->action}";
                $plan->save();

                $output->writeln("  内容ID {$plan->content_id} 执行 {$plan->action} 成功");
            } catch (\Exception $e) {
                $plan->status = ContentActionPlan::STATUS_FAILED;
                $plan->execute_log = $e->getMessage();
                $plan->save();
                $output->writeln("<error>  内容ID {$plan->content_id} 执行失败: {$e->getMessage()}</error>");
            }
        }

        $output->writeln('<info>=== 内容行动计划执行完成 ===</info>');
    }
}
