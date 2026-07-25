<?php

return [
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
];
