<?php
// AI-CMS V2.0 AI服务配置

return [
    // DeepSeek API配置
    'deepseek' => [
        'base_url' => env('ai.deepseek_base_url', 'https://api.deepseek.com'),
        'api_key' => env('ai.deepseek_api_key', ''),
        'default_model' => env('ai.deepseek_model', 'deepseek-chat'),
        'temperature' => 0.7,
        'max_tokens' => 2000,
    ],
    
    // 请求配置
    'request' => [
        'timeout' => 60,
        'connect_timeout' => 10,
        'retry_times' => 2,
        'retry_delay' => 1000,
    ],
    
    // AI写作预设模板
    'templates' => [
        'continue' => [
            'name' => '续写内容',
            'system_prompt' => '你是一个专业的内容编辑助手。请根据已有内容自然地续写，保持风格和语气一致。',
        ],
        'rewrite' => [
            'name' => '改写内容',
            'system_prompt' => '你是一个专业的内容编辑助手。请改写以下内容，使其更加专业、流畅，但保留核心信息不变。',
        ],
        'expand' => [
            'name' => '扩写内容',
            'system_prompt' => '你是一个专业的内容编辑助手。请扩展以下内容，添加更多细节和描述，使其更加丰富完整。',
        ],
        'summarize' => [
            'name' => '生成摘要',
            'system_prompt' => '你是一个专业的内容编辑助手。请为以下内容生成一段简洁的摘要，不超过200字。',
        ],
    ],
];
