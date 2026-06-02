<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
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

    // V2.9.8 B-2: 自动重试每日上限
    'retry_daily_limit' => (int) env('ai.retry_daily_limit', 20),

    // ==================== 行业分类配置（V3.1-下一阶段 Sprint 14） ====================
    'theme_industry_categories' => [
        'enterprise' => [
            'name'         => '企业官网',
            'description'  => '适用于企业官方网站、品牌展示',
            'styles'       => ['现代简约', '商务专业', '科技感'],
            'colors'       => ['蓝色系', '灰色系', '深色系'],
            'layouts'      => ['响应式', '全屏展示'],
            'descriptions' => [
                '为企业官网生成一套现代简约风格主题，突出品牌形象',
                '为企业官网生成一套商务专业风格主题，适合B2B展示',
            ],
        ],
        'ecommerce' => [
            'name'         => '电商平台',
            'description'  => '适用于电商购物、产品展示',
            'styles'       => ['购物促销', '简约时尚', '大牌质感'],
            'colors'       => ['红色系', '橙色系', '粉色系'],
            'layouts'      => ['响应式', '瀑布流'],
            'descriptions' => [
                '为电商平台生成一套购物促销风格主题，突出转化',
                '为电商平台生成一套简约时尚风格主题，提升品牌调性',
            ],
        ],
        'blog' => [
            'name'         => '个人博客',
            'description'  => '适用于个人博客、内容创作',
            'styles'       => ['文艺清新', '极简主义', '杂志风'],
            'colors'       => ['绿色系', '米色系', '暖色系'],
            'layouts'      => ['响应式', '单列阅读'],
            'descriptions' => [
                '为个人博客生成一套文艺清新风格主题，适合文字创作',
                '为个人博客生成一套杂志风格主题，适合图文混排',
            ],
        ],
        'portal' => [
            'name'         => '门户网站',
            'description'  => '适用于新闻门户、资讯聚合',
            'styles'       => ['新闻资讯', '综合门户', '政务公开'],
            'colors'       => ['红色系', '蓝色系', '白色系'],
            'layouts'      => ['响应式', '多栏布局'],
            'descriptions' => [
                '为新闻门户生成一套资讯风格主题，信息密度高',
                '为门户网站生成一套综合风格主题，栏目丰富',
            ],
        ],
        'education' => [
            'name'         => '教育培训',
            'description'  => '适用于在线教育、培训机构',
            'styles'       => ['学术严谨', '活泼活力', '专业可信'],
            'colors'       => ['绿色系', '黄色系', '蓝色系'],
            'layouts'      => ['响应式', '课程展示'],
            'descriptions' => [
                '为教育培训生成一套学术风格主题，适合在线课程',
                '为教育培训生成一套活力风格主题，适合少儿教育',
            ],
        ],
    ],

    // ==================== AI主题对话修改配置（V3.0 Phase 3） ====================
    'theme_chat' => [
        'max_rounds'     => (int) env('ai.theme_chat.max_rounds', 10),
        'timeout'        => (int) env('ai.theme_chat.timeout', 60),
        'max_tokens'     => (int) env('ai.theme_chat.max_tokens', 8192),
        'context_budget' => (int) env('ai.theme_chat.context_budget', 15000),
    ],

    // ==================== AI配图配置（V2.9.16增强） ====================
    'image' => [
        'default_provider'  => env('ai.image.default', 'tongyi_wanxiang'),
        'fallback_provider' => env('ai.image.fallback', 'flux'),
        // V2.9.16: 链式降级顺序（按此顺序遍历所有备用Provider）
        'fallback_chain'    => ['tongyi_wanxiang', 'flux', 'dalle'],
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

    // ==================== AI翻译引擎配置（V2.9.16增强） ====================
    'translate' => [
        'provider'          => env('AI_TRANSLATE_PROVIDER', 'deepseek'),
        'fallback_provider' => env('AI_TRANSLATE_FALLBACK', ''),
        'fallback_chain'    => ['deepseek', 'openai'], // V2.9.16: fallback遍历顺序

        // 速率限制（V2.9.16新增）
        'rate_limit' => [
            'rpm' => (int) env('AI_TRANSLATE_RATE_RPM', 30),   // 每分钟请求数
            'rph' => (int) env('AI_TRANSLATE_RATE_RPH', 500),  // 每小时请求数
        ],

        // 通用配置（DeepSeek默认回退）
        'api_key'           => env('DEEPSEEK_API_KEY', ''),
        'base_url'          => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        'model'             => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'timeout'           => (int) env('AI_TRANSLATE_TIMEOUT', 60),
        'max_tokens'        => (int) env('AI_TRANSLATE_MAX_TOKENS', 4096),
        'temperature'       => (float) env('AI_TRANSLATE_TEMPERATURE', 0.3),

        // V2.9.16: 重试配置
        'max_retries'       => (int) env('AI_TRANSLATE_MAX_RETRIES', 2),
        'retry_delay'       => (int) env('AI_TRANSLATE_RETRY_DELAY', 1000), // ms

        // 缓存配置（建议2前台翻译缓存）
        'cache_ttl'         => (int) env('AI_TRANSLATE_CACHE_TTL', 3600),
        // 分段翻译阈值（建议1）
        'segment_threshold' => (int) env('AI_TRANSLATE_SEGMENT_THRESHOLD', 1500),

        // V2.9.16: 多Provider/多账号支持
        'providers' => [
            'deepseek' => [
                'api_key'  => env('DEEPSEEK_API_KEY', ''),
                'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
                'model'    => env('DEEPSEEK_MODEL', 'deepseek-chat'),
                'timeout'  => (int) env('DEEPSEEK_TIMEOUT', 60),
            ],
            'openai' => [
                'api_key'  => env('OPENAI_API_KEY', ''),
                'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com'),
                'model'    => env('OPENAI_MODEL', 'gpt-4o-mini'),
                'timeout'  => (int) env('OPENAI_TIMEOUT', 60),
            ],
        ],

        // V2.9.16: 支持的语言列表（11种含元数据）
        // 管理员可直接编辑此配置文件来启用/禁用语言
        'languages' => [
            'zh' => ['name' => '中文',     'flag' => '🇨🇳', 'direction' => 'ltr', 'enabled' => true],
            'en' => ['name' => '英语',     'flag' => '🇺🇸', 'direction' => 'ltr', 'enabled' => true],
            'ja' => ['name' => '日语',     'flag' => '🇯🇵', 'direction' => 'ltr', 'enabled' => true],
            'ko' => ['name' => '韩语',     'flag' => '🇰🇷', 'direction' => 'ltr', 'enabled' => true],
            'fr' => ['name' => '法语',     'flag' => '🇫🇷', 'direction' => 'ltr', 'enabled' => true],
            'de' => ['name' => '德语',     'flag' => '🇩🇪', 'direction' => 'ltr', 'enabled' => true],
            'es' => ['name' => '西班牙语', 'flag' => '🇪🇸', 'direction' => 'ltr', 'enabled' => true],
            'pt' => ['name' => '葡萄牙语', 'flag' => '🇵🇹', 'direction' => 'ltr', 'enabled' => false],
            'ru' => ['name' => '俄语',     'flag' => '🇷🇺', 'direction' => 'ltr', 'enabled' => false],
            'ar' => ['name' => '阿拉伯语', 'flag' => '🇸🇦', 'direction' => 'rtl', 'enabled' => false],
            'th' => ['name' => '泰语',     'flag' => '🇹🇭', 'direction' => 'ltr', 'enabled' => false],
            'vi' => ['name' => '越南语',   'flag' => '🇻🇳', 'direction' => 'ltr', 'enabled' => false],
            'id' => ['name' => '印尼语',   'flag' => '🇮🇩', 'direction' => 'ltr', 'enabled' => false],
            'tr' => ['name' => '土耳其语', 'flag' => '🇹🇷', 'direction' => 'ltr', 'enabled' => false],
            'it' => ['name' => '意大利语', 'flag' => '🇮🇹', 'direction' => 'ltr', 'enabled' => false],
            'hi' => ['name' => '印地语',   'flag' => '🇮🇳', 'direction' => 'ltr', 'enabled' => false],
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
