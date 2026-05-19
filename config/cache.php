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
// AI-CMS V2.0 缓存配置（Redis优先，降级为文件）

return [
    // 默认缓存驱动
    'default' => env('cache.driver', 'file'),
    
    // 缓存连接配置
    'stores' => [
        // 文件缓存
        'file' => [
            'type' => 'File',
            // 缓存保存目录
            'path' => runtime_path() . 'cache',
            // 缓存前缀
            'prefix' => 'i8j_',
            // 缓存有效期（秒），0表示永久
            'expire' => 0,
        ],
        
        // Redis缓存（可选，V2.1推荐）
        'redis' => [
            'type' => 'redis',
            'host' => env('redis.host', '127.0.0.1'),
            'port' => env('redis.port', 6379),
            'password' => env('redis.password', ''),
            'select' => env('redis.select', 0),
            'timeout' => 0,
            'persistent' => false,
            'prefix' => 'i8j_',
            'expire' => 0,
        ],
    ],
    
    // 缓存标签（用于批量清除）
    'tag' => [
        'cate' => 'i8j_cate',       // 分类缓存标签
        'tag' => 'i8j_tag',         // 标签缓存标签
        'config' => 'i8j_config',   // 配置缓存标签
        'content' => 'i8j_content', // 内容缓存标签
    ],
];
