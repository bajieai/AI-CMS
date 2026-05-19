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
return [
    // 默认存储驱动: local/oss/cos
    'default' => env('storage.driver', 'local'),

    // 驱动配置
    'drivers' => [
        'local' => [],

        // 阿里云OSS配置
        'oss' => [
            'access_key_id' => env('storage.oss_access_key_id', ''),
            'access_key_secret' => env('storage.oss_access_key_secret', ''),
            'bucket' => env('storage.oss_bucket', ''),
            'endpoint' => env('storage.oss_endpoint', ''),
            'cdn_domain' => env('storage.oss_cdn_domain', ''),
        ],

        // 腾讯云COS配置
        'cos' => [
            'secret_id' => env('storage.cos_secret_id', ''),
            'secret_key' => env('storage.cos_secret_key', ''),
            'bucket' => env('storage.cos_bucket', ''),
            'region' => env('storage.cos_region', ''),
            'cdn_domain' => env('storage.cos_cdn_domain', ''),
        ],
    ],
];
