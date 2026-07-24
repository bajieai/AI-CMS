<?php
declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\service\template\TemplateStatsAggregator;

/**
 * 模板统计每日聚合命令 — V2.9.28 M-3
 * 每日凌晨执行，将业务表数据聚合写入 template_daily_stats
 *
 * 使用：php think template:aggregate-stats [date]
 * Crontab: 0 3 * * * cd /var/www/html && php think template:aggregate-stats
 */
class TemplateStatsAggregateCommand extends Command
{
    protected function configure()
    {
        $this->setName('template:aggregate-stats')
            ->addArgument('date')
            ->setDescription('模板商店统计每日聚合（V2.9.28 M-3）');
    }

    protected function execute(Input $input, Output $output)
    {
        $date = $input->getArgument('date') ?: date('Y-m-d', strtotime('-1 day'));

        $service = new TemplateStatsAggregator();
        $result = $service->aggregateDaily($date);

        if ($result['success']) {
            $output->writeln('<info>[OK]</info> 统计聚合完成: 日期=' . $result['date'] . ', 模板数=' . $result['templates']);
        } else {
            $output->writeln('<error>[FAIL]</error> 统计聚合失败');
        }
    }
}
