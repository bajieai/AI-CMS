<?php

return [
    // DeepSeek API配置
    'deepseek' => [
        'api_key' => env('AI_DEEPSEEK_API_KEY', ''),
        'base_url' => env('AI_DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        'default_model' => env('AI_DEFAULT_MODEL', 'deepseek-chat'),
        'max_tokens' => env('AI_MAX_TOKENS', 4096),
        'temperature' => env('AI_TEMPERATURE', 0.7),
        'timeout' => 120,
    ],
    
    // 可用模型列表
    'models' => [
        'deepseek-chat' => [
            'name' => 'DeepSeek Chat',
            'provider' => 'deepseek',
            'input_price' => 0.001,  // 每1000 token价格(美元)
            'output_price' => 0.002,
            'max_tokens' => 4096,
        ],
        'deepseek-coder' => [
            'name' => 'DeepSeek Coder',
            'provider' => 'deepseek',
            'input_price' => 0.001,
            'output_price' => 0.002,
            'max_tokens' => 4096,
        ],
    ],
    
    // 请求配置
    'request' => [
        'timeout' => 120,
        'connect_timeout' => 30,
        'retry_times' => 3,
        'retry_delay' => 1000,  // 毫秒
    ],
    
    // 缓存配置
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,  // 秒
        'prefix' => 'ai:cache:',
    ],
    
    // AI任务队列配置
    'task_queue' => [
        'redis_key' => 'ai:task:queue',
        'processing_key' => 'ai:task:processing',
        'max_retry' => 3,
        'expire_time' => 3600,  // 任务过期时间(秒)
    ],
];
