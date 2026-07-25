<?php
declare(strict_types=1);

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\service\task\TaskNotifyService;

class TaskRemindCommand extends Command
{
    protected function configure() { $this->setName('task:remind')->setDescription('任务催办通知检查'); }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始检查任务催办...');
        $svc = new TaskNotifyService();
        $r1 = $svc->checkAndNotify();
        $output->writeln('到期提醒: ' . ($r1['msg'] ?? '完成'));
        $r2 = $svc->checkOverdue();
        $output->writeln('超期催办: ' . ($r2['msg'] ?? '完成'));
        $r3 = $svc->checkStalled();
        $output->writeln('停滞检测: ' . ($r3['msg'] ?? '完成'));
        $output->writeln('催办检查完成');
        return 0;
    }
}
