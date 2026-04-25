<?php
// api应用中间件配置
// API路由主要供后台调用，需要认证+CSRF防护
return [
    \app\common\middleware\AdminAuth::class,
    \app\common\middleware\AdminCsrf::class,
];
