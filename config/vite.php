<?php
declare(strict_types=1);

// V2.9.32 PERF2-1: Vite构建配置
return [
    'enabled'        => (bool) env('vite.enabled', false),
    'dev_url'        => env('vite.dev_url', 'http://localhost:5173'),
    'manifest_path'  => 'public/static/dist/.vite/manifest.json',
    'output_dir'     => 'public/static/dist/',
    'entry_points'   => [
        'admin'  => 'resources/js/admin.js',
        'home'   => 'resources/js/home.js',
        'common' => 'resources/js/common.js',
    ],
    'css_entries'    => [
        'admin'  => 'resources/css/admin.css',
        'home'   => 'resources/css/home.css',
        'common' => 'resources/css/common.css',
    ],
];
