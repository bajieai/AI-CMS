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

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

class VisitArchive extends Command
{
    protected function configure()
    {
        $this->setName('visit:archive')
            ->setDescription('归档访问日志（按日聚合PV/UV后删除原始日志）')
            ->addOption('months', null, Option::VALUE_OPTIONAL, '归档多少个月前的数据', 1);
    }

    protected function execute(Input $input, Output $output)
    {
        $monthsAgo = (int) $input->getOption('months');
        $archiveDate = date('Y-m-d', strtotime("-{$monthsAgo} months"));
        $startTime = strtotime($archiveDate . ' 00:00:00');
        $endTime = strtotime($archiveDate . ' 23:59:59');

        $pv = Db::name('visit_log')->whereBetween('visit_time', [$startTime, $endTime])->count();
        $uv = Db::name('visit_log')->whereBetween('visit_time', [$startTime, $endTime])->group('ip')->count();

        $output->writeln("{$archiveDate} PV: {$pv}, UV: {$uv}");

        if ($pv > 0) {
            // 按content_id聚合分页PV/UV
            $contentStats = Db::name('visit_log')
                ->field('content_id, COUNT(*) as pv, COUNT(DISTINCT ip) as uv')
                ->whereBetween('visit_time', [$startTime, $endTime])
                ->group('content_id')
                ->select();

            // 写入归档记录到visit_log_archive表（如存在）或输出汇总
            $archiveTable = config('database.connections.mysql.prefix', 'i8j_') . 'visit_log_archive';
            try {
                foreach ($contentStats as $stat) {
                    Db::name('visit_log_archive')->insert([
                        'content_id' => $stat['content_id'],
                        'stat_date'  => $archiveDate,
                        'pv'         => $stat['pv'],
                        'uv'         => $stat['uv'],
                        'create_time' => time(),
                    ]);
                }
                $output->writeln('归档数据已写入visit_log_archive表');
            } catch (\Throwable $e) {
                // 归档表不存在时输出汇总日志作为备份
                $output->writeln('归档表不存在，输出汇总数据:');
                foreach ($contentStats as $stat) {
                    $output->writeln("  content_id={$stat['content_id']} PV={$stat['pv']} UV={$stat['uv']}");
                }
            }
        }

        // 删除原始日志
        Db::name('visit_log')->whereBetween('visit_time', [$startTime, $endTime])->delete();
        $output->writeln('原始日志已清理');

        return 0;
    }
}