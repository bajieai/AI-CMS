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

    // ==================== AI主题对话修改配置（V3.0 Phase 3） ====================
    'theme_chat' => [
        'max_rounds'     => (int) env('ai.theme_chat.max_rounds', 10),
        'timeout'        => (int) env('ai.theme_chat.timeout', 60),
        'max_tokens'     => (int) env('ai.theme_chat.max_tokens', 8192),
        'context_budget' => (int) env('ai.theme_chat.context_budget', 15000),
    ],

    // ==================== AI配图配置（V2.9补全Flux/DALL-E） ====================
    'image' => [
        'default_provider'  => env('ai.image.default', 'tongyi_wanxiang'),
        'fallback_provider' => env('ai.image.fallback', 'flux'),
        'timeout'           => (int) env('ai.image.timeout', 30),
        // V3.1: 配图配额控制
        'daily_limit'       => (int) env('ai.image.daily_limit', 50),
        'max_batch_count'   => (int) env('ai.image.max_batch_count', 5),
        // V3.1: 发布时自动配图（需手动开启）
        'auto_on_publish'   => (bool) env('ai.image.auto_on_publish', false),
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

    // ==================== AI多写作风格配置（V3.1新增） ====================
    'writing_styles' => [
        'formal' => [
            'name' => '正式风格',
            'system_prompt' => '你是一位专业的内容编辑。请使用正式、严谨、权威的语言风格撰写内容。避免口语化表达，使用规范的语法和词汇，适合企业官网、新闻发布等正式场景。',
        ],
        'casual' => [
            'name' => '轻松风格',
            'system_prompt' => '你是一位亲切的内容创作者。请使用轻松、自然、口语化的语言风格撰写内容。像朋友一样与读者交流，适合博客、社交媒体等非正式场景。',
        ],
        'professional' => [
            'name' => '专业风格',
            'system_prompt' => '你是一位行业专家。请使用专业、深度、有洞察力的语言风格撰写内容。使用行业术语，提供有价值的分析和见解，适合技术文档、行业报告等场景。',
        ],
        'humorous' => [
            'name' => '幽默风格',
            'system_prompt' => '你是一位幽默风趣的作家。请使用幽默、有趣、富有创意的语言风格撰写内容。适当使用比喻、双关等修辞手法，让读者会心一笑，适合娱乐、生活类内容。',
        ],
        'concise' => [
            'name' => '简洁风格',
            'system_prompt' => '你是一位高效的内容编辑。请使用简洁、精炼、直切要点的语言风格撰写内容。删除冗余词汇，每句话都有明确的信息量，适合快速阅读、摘要等场景。',
        ],
    ],
];
