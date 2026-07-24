<?php
declare(strict_types=1);

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\service\task\TaskStatsService;

class TaskReportCommand extends Command
{
    protected function configure() { $this->setName('task:report')->setDescription('任务进度报告生成'); }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('生成任务报告...');
        $svc = new TaskStatsService();
        $daily = $svc->getReport('daily');
        $output->writeln('日报: ' . ($daily['msg'] ?? '完成'));
        $weekly = $svc->getReport('weekly');
        $output->writeln('周报: ' . ($weekly['msg'] ?? '完成'));
        $monthly = $svc->getReport('monthly');
        $output->writeln('月报: ' . ($monthly['msg'] ?? '完成'));
        $output->writeln('报告生成完成');
        return 0;
    }
}
