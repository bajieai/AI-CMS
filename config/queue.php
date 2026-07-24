<?php
// +----------------------------------------------------------------------
// | AI-CMS 队列配置
// | V2.9.38 PERF-II-2
// +----------------------------------------------------------------------
return [
    // 默认队列连接
    'default' => 'database',
    
    // Redis队列连接
    'connections' => [
        'redis' => [
            'type' => 'redis',
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', ''),
            'select' => 0,
            'timeout' => 0,
            'persistent' => false,
        ],
        'database' => [
            'type' => 'database',
            'queue' => 'default',
            'table' => 'queue_job',
            'retry_after' => 90,
        ],
    ],
    
    // 队列名称
    'queues' => [
        'default' => '默认队列',
        'ai' => 'AI任务队列',
        'notification' => '通知队列',
        'content' => '内容处理队列',
        'seo' => 'SEO处理队列',
        'report' => '报表生成队列',
        'cache' => '缓存预热队列',
    ],
    
    // 工作进程配置
    'worker' => [
        'memory_limit' => 128, // MB
        'timeout' => 60, // 秒
        'sleep' => 3, // 空闲休眠秒数
        'max_tries' => 3, // 最大重试次数
    ],
    
    // 失败任务配置
    'failed' => [
        'table' => 'queue_failed',
        'retention_days' => 7, // 保留天数
    ],
];
