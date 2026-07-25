<?php

/**
 * V2.9.35 插件示例：数据统计
 * 演示自定义数据表 + 定时任务钩子 + 统计报表
 */

return [
    'identifier'  => 'data_stats',
    'name'        => '数据统计插件',
    'description' => '演示插件自定义数据表+定时任务钩子+统计报表',
    'version'     => '1.0.0',
    'author'      => 'AI-CMS Team',
    'homepage'    => 'https://www.i8j.cn',
    'min_version' => '2.9.35',
    'hooks' => [
        'content.after_save' => [
            'callback' => 'DataStatsPlugin@onContentSave',
            'type'     => 'action',
            'priority' => 5,
        ],
        'user.after_register' => [
            'callback' => 'DataStatsPlugin@onUserRegister',
            'type'     => 'action',
            'priority' => 5,
        ],
        'system.daily_cron' => [
            'callback' => 'DataStatsPlugin@onDailyCron',
            'type'     => 'action',
            'priority' => 10,
        ],
    ],
    'config' => [
        'stats_retention_days' => 90,
        'enable_content_stats' => true,
        'enable_user_stats'    => true,
    ],
    'permissions' => ['db_read', 'db_write'],
    'menu' => [
        [
            'name' => '数据统计',
            'url'  => '/admin/data_stats/index',
            'icon' => 'bi bi-bar-chart',
        ],
    ],
];
