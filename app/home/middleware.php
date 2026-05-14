<?php
// V2.9.5 前台应用中间件配置

return [
    // V2.9.5 前台CSRF保护
    \app\common\middleware\FrontCsrfMiddleware::class,
    // V3.0 Phase 2 主题预览中间件
    \app\common\middleware\ThemePreviewMiddleware::class,
    // V2.9.7 Phase 1 主题定制CSS注入中间件（零侵入）
    \app\common\middleware\ThemeCustomMiddleware::class,
];
