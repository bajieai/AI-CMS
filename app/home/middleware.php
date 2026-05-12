<?php
// V2.9.5 前台应用中间件配置

return [
    // V2.9.5 前台CSRF保护
    \app\common\middleware\FrontCsrfMiddleware::class,
    // V3.0 Phase 2 主题预览中间件
    \app\common\middleware\ThemePreviewMiddleware::class,
];
