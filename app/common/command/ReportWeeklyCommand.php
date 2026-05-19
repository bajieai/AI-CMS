<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\service\DashboardService;

/**
 * V2.9.9 J-2: 周报生成CLI
 * 聚合7天数据，输出JSON到 runtime/report/weekly/YYYY-Www.json
 */
class ReportWeeklyCommand extends Command
{
    protected function configure()
    {
        $this->setName('report:weekly')
            ->setDescription('生成每周运营报表');
    }

    protected function execute(Input $input, Output $output)
    {
        $now = time();
        $weekStart = strtotime('monday this week midnight');
        $weekEnd = $now;
        $prevWeekStart = strtotime('monday last week midnight');
        $prevWeekEnd = $weekStart;

        $weekNum = date('W');
        $year = date('Y');

        // 核心指标
        $weekReport = DashboardService::getOperationsReport($weekStart, $weekEnd);
        $prevWeekReport = DashboardService::getOperationsReport($prevWeekStart, $prevWeekEnd);

        // 趋势
        $trend = DashboardService::getTrend(7);

        // DAU/MAU
        $dauMau = DashboardService::getDauMau(7);

        // 跳出率
        $bounce = DashboardService::getBounceRate(7);

        $report = [
            'year'         => $year,
            'week'         => $weekNum,
            'period'       => [date('Y-m-d', $weekStart), date('Y-m-d', $weekEnd)],
            'generated_at' => date('c'),
            'this_week'    => $weekReport,
            'last_week'    => $prevWeekReport,
            'trend_7d'     => $trend,
            'dau_mau'      => $dauMau,
            'bounce_rate'  => $bounce,
        ];

        $dir = root_path() . 'runtime' . DIRECTORY_SEPARATOR . 'report' . DIRECTORY_SEPARATOR . 'weekly';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $path = $dir . DIRECTORY_SEPARATOR . "{$year}-W{$weekNum}.json";
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $output->writeln("<info>周报已生成: {$path}</info>");
        return 0;
    }
}
