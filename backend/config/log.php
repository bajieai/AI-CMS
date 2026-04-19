<?php

return [
    // 默认日志记录通道
    'default' => env('LOG_CHANNEL', 'file'),

    // 日志记录级别
    'level' => explode(',', env('LOG_LEVEL', 'error,warning,info')),

    // 日志通道配置
    'channels' => [
        'file' => [
            // 日志驱动
            'type' => 'file',
            // 日志存储路径
            'path' => runtime_path() . 'logs' . DIRECTORY_SEPARATOR,
            // 单文件日志写入
            'single' => false,
            // 独立日志级别
            'apart_level' => [],
            // 最大日志文件数
            'max_files' => 30,
            // JSON格式
            'json' => false,
            // 日志时间格式
            'time_format' => 'Y-m-d H:i:s',
            // 文件大小限制(MB)
            'file_size' => 10 * 1024 * 1024,
        ],
    ],

    // 是否关闭日志
    'close' => false,
];
