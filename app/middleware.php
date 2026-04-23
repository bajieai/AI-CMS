<?php
// AI-CMS V2.0 全局中间件配置
// SessionInit 必须全局注册，否则 session() 无法跨请求持久化

return [
    // Session初始化中间件（必须首位，确保所有请求都能读写session）
    \think\middleware\SessionInit::class,
];
