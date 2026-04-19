<?php

return [
    // 默认数据连接标识
    'default' => 'mysql',
    
    // 数据库连接配置
    'connections' => [
        'mysql' => [
            'type' => 'mysql',
            'hostname' => env('DB_HOST', '127.0.0.1'),
            'database' => env('DB_NAME', 'aicms'),
            'username' => env('DB_USER', 'root'),
            'password' => env('DB_PASS', 'root'),
            'hostport' => env('DB_PORT', '3306'),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'prefix' => env('DB_PREFIX', 'i8j_aicms_'),
            'deploy' => 0,
            'rw_separate' => false,
            'master_num' => 1,
            'slave_no' => '',
            'fields_strict' => true,
            'fields_cache' => false,
            'trigger_sql' => env('APP_DEBUG', false),
            'break_reconnect' => false,
            'break_match_str' => [],
            'datetime_format' => 'Y-m-d H:i:s',
            'auto_timestamp' => false,
            'query' => \think\db\Query::class,
            'builder' => '',
        ],
    ],
    
    // 查询缓存配置
    'query_cache' => [
        'enable' => false,
        'expire' => 60,
        'cache_prefix' => '',
        'cache_dir' => '',
    ],
    
    // 自动时间戳
    'auto_timestamp' => false,
    
    // 时间戳格式
    'datetime_format' => 'Y-m-d H:i:s',
    
    // 是否开启自动写入时间戳
    'auto_write_timestamp' => false,
    
    // 时间戳字段类型（创建时间,更新时间）- 必须是逗号分隔的两个字段名
    'datetime_field' => 'create_time,update_time',
    
    // 主键名
    'pk' => 'id',
    
    // 自增时间戳字段
    'auto_cache' => false,
];
