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
// V2.9.5 前台应用中间件配置

return [
    // V2.9.5 前台CSRF保护
    \app\common\middleware\FrontCsrfMiddleware::class,
    // V3.0 Phase 2 主题预览中间件
    \app\common\middleware\ThemePreviewMiddleware::class,
    // V2.9.7 Phase 1 主题定制CSS注入中间件（零侵入）
    \app\common\middleware\ThemeCustomMiddleware::class,
];
