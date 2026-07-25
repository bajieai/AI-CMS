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
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

/**
 * 积分月归档定时任务 - V2.7
 * 将上月积分日志归档到汇总表，减少主表数据量
 * 用法: php think points:archive [--month=202604]
 */
class PointsArchiveCommand extends Command
{
    protected function configure()
    {
        $this->setName('points:archive')
            ->setDescription('积分日志月归档')
            ->addOption('month', 'm', \think\console\input\Option::VALUE_OPTIONAL, '指定归档月份(Ym)', '');
    }

    protected function execute(Input $input, Output $output)
    {
        $month = $input->getOption('month');
        if (empty($month)) {
            $month = date('Ym', strtotime('first day of last month'));
        }

        $startTime = strtotime($month . '01');
        $endTime = strtotime('+1 month', $startTime);

        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        $logTable = $prefix . 'points_log';
        $archiveTable = $prefix . 'points_log_archive_' . $month;

        // 检查是否有数据
        $count = Db::name('points_log')
            ->where('create_time', '>=', $startTime)
            ->where('create_time', '<', $endTime)
            ->count();

        if ($count === 0) {
            $output->writeln("<comment>{$month}月无积分日志需要归档</comment>");
            return 0;
        }

        // 创建归档表
        $createSql = "CREATE TABLE IF NOT EXISTS {$archiveTable} LIKE {$logTable}";
        Db::execute($createSql);

        // 迁移数据（V2.9.5 安全修复：时间条件使用参数绑定，防止潜在SQL注入）
        $migrated = Db::execute(
            "INSERT INTO `{$archiveTable}` SELECT * FROM `{$logTable}` WHERE create_time >= ? AND create_time < ?",
            [$startTime, $endTime]
        );

        // 删除原表数据
        $deleted = Db::name('points_log')
            ->where('create_time', '>=', $startTime)
            ->where('create_time', '<', $endTime)
            ->delete();

        $output->writeln("<info>积分归档完成: {$month}</info>");
        $output->writeln("<comment>归档记录数: {$migrated}, 清理记录数: {$deleted}</comment>");
        Log::info("积分月归档完成: month={$month}, migrated={$migrated}, deleted={$deleted}");

        return 0;
    }
}
