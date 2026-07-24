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
// V2.9.31 Sprint PERF: CDN配置

declare(strict_types=1);

return [
    // 是否启用CDN
    'enabled'        => (bool) env('cdn.enabled', false),
    // CDN域名（不含协议和尾部斜杠）
    'domain'         => env('cdn.domain', ''),
    // 静态资源版本号（用于缓存刷新）
    'static_version' => env('cdn.static_version', 'v2.9.31'),
    // 静态文件类型
    'static_types'   => 'css,js,png,jpg,jpeg,gif,svg,woff,woff2,ttf,eot',
    // 图片压缩质量（0-100）
    'image_quality'  => 85,
    // 是否启用WebP
    'webp_enabled'   => (bool) env('cdn.webp_enabled', false),
];
