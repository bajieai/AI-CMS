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
/**
 * V2.9.5 CSP（Content Security Policy）配置
 *
 * 使用说明：
 * 1. enabled：CSP总开关，设为 false 可完全关闭 CSP 头
 * 2. enforce：
 *    - false（默认）：发送 Content-Security-Policy-Report-Only，仅观察不阻断
 *    - true：发送 Content-Security-Policy，违规将被浏览器阻断
 *    ⚠️ 切换 enforce=true 前，务必在浏览器控制台确认无第三方资源误报
 * 3. directives：CSP 指令，数组形式便于各主题/插件按需扩展
 * 4. headers：其他固定安全响应头
 */
return [
    // CSP 总开关
    'enabled' => true,

    // 是否强制模式（false=仅报告，true=阻断违规）
    // 建议在上线前保持 false 观察 1-2 周，确认无资源误报后再切 true
    'enforce' => false,

    // 其他安全响应头（与 CSP 独立，始终生效）
    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options'        => 'SAMEORIGIN',
        'Referrer-Policy'        => 'strict-origin-when-cross-origin',
    ],

    // CSP 指令配置
    // 各指令值为字符串数组，中间件会自动用空格拼接
    'directives' => [
        'default-src'     => ["'self'"],
        'script-src'      => ["'self'", "'unsafe-inline'", "'unsafe-eval'", 'fonts.googleapis.com', 'fonts.gstatic.com', 'cdn.jsdelivr.net', 'cdn.bootcdn.net'],
        'style-src'       => ["'self'", "'unsafe-inline'", 'fonts.googleapis.com'],
        'img-src'         => ["'self'", 'data:', 'blob:', '*.gravatar.com', '*.googleusercontent.com'],
        'font-src'        => ["'self'", 'fonts.gstatic.com'],
        'connect-src'     => ["'self'"],
        'frame-ancestors' => ["'self'"],
        'base-uri'        => ["'self'"],
        'form-action'     => ["'self'"],
    ],
];
