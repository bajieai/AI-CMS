<?php
declare(strict_types=1);

// V2.9.32 模板生态深化配置
return [
    'v2.9.32' => [
        // 模板评分评论（T4-1）
        'review' => [
            'auto_audit'         => false,
            'max_images'         => 5,
            'min_content_length' => 10,
            'rating_guide_delay' => 3000,
        ],
        // 模板版本管理（T4-2）
        'version' => [
            'max_versions' => 10,
            'auto_notify'  => true,
            'rollback_max' => 3,
        ],
        // 模板排行（T4-3）
        'ranking' => [
            'cache_ttl'    => 3600,
            'top_count'    => 10,
            'category_top' => 5,
        ],
        // 模板样式版本历史（CUS2-4）
        'style_version' => [
            'max_versions'     => 30,
            'auto_cleanup'     => true,
            'cleanup_interval' => 86400,
        ],
    ],
];
