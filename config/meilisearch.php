<?php
return [
    // Meilisearch服务地址
    'host' => env('meilisearch.host', 'http://localhost:7700'),
    // API密钥（留空表示无认证）
    'api_key' => env('meilisearch.api_key', ''),
    // 是否启用全站搜索
    'enabled' => (bool) env('meilisearch.enabled', false),
];
