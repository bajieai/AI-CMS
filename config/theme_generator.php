<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------

/**
 * AI-THEME 适配器配置
 *
 * 默认关闭：关闭时 4 条路由统一 404、后台菜单入口隐藏。
 * 打开：环境变量 THEME_GENERATOR_ENABLED=1（改完需清缓存，菜单缓存 3600s）。
 *
 * limits 三个键同时被 ThemeBundleValidator 读取；配置文件会被核心升级覆盖，
 * 现场自定义请走环境变量。
 */
return [
    // 总开关
    'enabled' => filter_var(env('THEME_GENERATOR_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    // 包体量限制（与 ThemeBundleValidator 的兜底常量保持一致）
    'limits' => [
        // 最大文件数
        'max_files' => (int) env('THEME_GENERATOR_MAX_FILES', 500),

        // 解压后总大小上限（MB）
        'max_size_mb' => (int) env('THEME_GENERATOR_MAX_SIZE_MB', 20),

        // 允许的扩展名（小写）
        'allowed_extensions' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env(
                'THEME_GENERATOR_ALLOWED_EXTENSIONS',
                'html,htm,css,js,json,map,txt,png,jpg,jpeg,gif,svg,webp,ico,woff,woff2,ttf,eot'
            ))
        ))),
    ],
];
