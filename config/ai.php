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

    // ==================== AI主题生成配置（V3.0 Phase 2） ====================
    'theme_generate' => [
        'daily_limit'   => (int) env('ai.theme_generate.daily_limit', 50),
        'timeout'       => (int) env('ai.theme_generate.timeout', 300),
        'max_tokens'    => (int) env('ai.theme_generate.max_tokens', 8192),
        'temperature'   => (float) env('ai.theme_generate.temperature', 0.5),
    ],

    // ==================== AI配图配置（V2.9补全Flux/DALL-E） ====================
    'image' => [
        'default_provider'  => env('ai.image.default', 'tongyi_wanxiang'),
        'fallback_provider' => env('ai.image.fallback', 'flux'),
        'timeout'           => (int) env('ai.image.timeout', 30),
        'providers'        => [
            'tongyi_wanxiang' => [
                'enabled'  => (bool) env('ai.image.tongyi.enabled', true),
                'api_key' => env('ai.image.tongyi.api_key', ''),
                'model'   => env('ai.image.tongyi.model', 'wanx-v1'),
                'timeout'  => (int) env('ai.image.tongyi.timeout', 15),
            ],
            'flux' => [
                'enabled'  => (bool) env('ai.image.flux.enabled', false),
                'api_key' => env('ai.image.flux.api_key', ''),
                'model'   => env('ai.image.flux.model', 'flux-pro'),
                'timeout'  => (int) env('ai.image.flux.timeout', 30),
                'steps'   => (int) env('ai.image.flux.steps', 25),
            ],
            'dalle' => [
                'enabled'  => (bool) env('ai.image.dalle.enabled', false),
                'api_key' => env('ai.image.dalle.api_key', ''),
                'model'   => env('ai.image.dalle.model', 'dall-e-3'),
                'base_url' => env('ai.image.dalle.base_url', 'https://api.openai.com/v1'),
                'timeout'  => (int) env('ai.image.dalle.timeout', 30),
            ],
        ],
    ],
];
