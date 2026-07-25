<?php

// V2.9.30 版本配置

return [
    // 功能看板配置
    'feature_registry' => [
        'health_check_interval' => 60,
        'cache_ttl' => 600,
        'auto_scan' => true,
    ],

    // AI改写配置
    'ai_rewrite' => [
        'max_batch' => 20,
        'timeout_per_content' => 30,
        'style_preview_enabled' => true,
        'rollback_enabled' => true,
    ],

    // AI SEO配置
    'ai_seo' => [
        'title_max_length' => 60,
        'desc_max_length' => 160,
        'batch_concurrency' => 5,
        'score_warning_threshold' => 60,
    ],

    // AI配图配置
    'ai_image' => [
        'provider' => 'template_library',
        'timeout' => 15,
        'max_concurrent' => 3,
        'default_size' => '1024x576',
    ],

    // 批量管理配置
    'template_batch' => [
        'max_batch' => 100,
        'preview_enabled' => true,
        'log_retention_days' => 90,
    ],

    // 模板质量检测配置
    'template_quality' => [
        'scan_interval' => '0 3 * * *',
        'pass_threshold' => 60,
        'excellent_threshold' => 80,
        'auto_scan_enabled' => true,
    ],

    // 搜索配置
    'search' => [
        'keyword_min_length' => 2,
        'max_results' => 50,
        'cache_ttl' => 120,
    ],
];
