<?php

declare(strict_types=1);

use think\facade\Db;

/**
 * 数据统计插件主类
 * 演示插件自定义数据表+定时任务钩子
 */
class DataStatsPlugin
{
    /**
     * 内容保存后：更新当日内容统计
     */
    public function onContentSave(array $data): void
    {
        $config = require __DIR__ . '/../config.php';
        if (empty($config['settings']['enable_content_stats'])) {
            return;
        }

        $today = date('Y-m-d');
        Db::table('plugin_data_stats_daily')
            ->where('stats_date', $today)
            ->inc('content_published')
            ->exp('updated_at', 'NOW()')
            ->updateOrInsert([
                'stats_date' => $today,
                'content_published' => 1,
            ]);
    }

    /**
     * 用户注册后：更新当日用户统计
     */
    public function onUserRegister(array $data): void
    {
        $config = require __DIR__ . '/../config.php';
        if (empty($config['settings']['enable_user_stats'])) {
            return;
        }

        $today = date('Y-m-d');
        Db::table('plugin_data_stats_daily')
            ->where('stats_date', $today)
            ->inc('user_registered')
            ->exp('updated_at', 'NOW()')
            ->updateOrInsert([
                'stats_date' => $today,
                'user_registered' => 1,
            ]);
    }

    /**
     * 每日定时任务：清理过期统计数据
     */
    public function onDailyCron(array $data): void
    {
        $config = require __DIR__ . '/../config.php';
        $retentionDays = $config['settings']['stats_retention_days'] ?? 90;

        $cutoff = date('Y-m-d', strtotime("-{$retentionDays} days"));
        Db::table('plugin_data_stats_daily')
            ->where('stats_date', '<', $cutoff)
            ->delete();
    }
}
