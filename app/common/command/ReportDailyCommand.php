<?php
declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\service\DashboardService;

/**
 * V2.9.9 J-2: 日报生成CLI
 * 输出JSON到 runtime/report/daily/YYYY-MM-DD.json
 */
class ReportDailyCommand extends Command
{
    protected function configure()
    {
        $this->setName('report:daily')
            ->setDescription('生成每日运营报表');
    }

    protected function execute(Input $input, Output $output)
    {
        $date = date('Y-m-d');
        $startTime = strtotime('today midnight');
        $endTime = time();
        $yesterdayStart = strtotime('yesterday midnight');
        $yesterdayEnd = $startTime;

        // 核心指标
        $todayReport = DashboardService::getOperationsReport($startTime, $endTime);
        $yesterdayReport = DashboardService::getOperationsReport($yesterdayStart, $yesterdayEnd);

        // 趋势
        $trend = DashboardService::getTrend(7);

        // 浏览器分布
        $browser = DashboardService::getBrowserStats(1);

        $report = [
            'date'        => $date,
            'generated_at'=> date('c'),
            'today'       => $todayReport,
            'yesterday'   => $yesterdayReport,
            'trend_7d'    => $trend,
            'browser_today'=> $browser,
        ];

        $dir = root_path() . 'runtime' . DIRECTORY_SEPARATOR . 'report' . DIRECTORY_SEPARATOR . 'daily';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $path = $dir . DIRECTORY_SEPARATOR . $date . '.json';
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $output->writeln("<info>日报已生成: {$path}</info>");
        return 0;
    }
}
