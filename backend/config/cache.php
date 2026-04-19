<?php

return [
    // 默认缓存驱动
    'default' => env('CACHE_DRIVER', 'redis'),
    
    // 缓存连接配置
    'stores' => [
        // 文件缓存
        'file' => [
            'type' => 'file',
            'path' => runtime_path() . 'cache' . DIRECTORY_SEPARATOR,
            'prefix' => env('CACHE_PREFIX', 'aicms_'),
            'expire' => 0,
            'serialize' => ['serialize', 'unserialize'],
        ],
        
        // Redis缓存
        'redis' => [
            'type' => 'redis',
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASS', ''),
            'select' => env('REDIS_DB', 0),
            'timeout' => 0,
            'expire' => 0,
            'persistent' => false,
            'prefix' => env('CACHE_PREFIX', 'aicms_'),
            'serialize' => ['serialize', 'unserialize'],
            'cache_subdir' => true,
            'path' => runtime_path() . 'cache' . DIRECTORY_SEPARATOR,
        ],
    ],
];
