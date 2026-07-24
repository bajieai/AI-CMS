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
// AI-CMS V2.0 全局中间件配置
// SessionInit 必须全局注册，否则 session() 无法跨请求持久化

return [
    // Session初始化中间件（必须首位，确保所有请求都能读写session）
    \think\middleware\SessionInit::class,
    // V2.9.35 XSS输入过滤（与V2.9.5输出过滤配合，形成双重防护）
    \app\common\middleware\XssInputFilterMiddleware::class,
    // V2.9.35 SQL注入检测
    \app\common\middleware\SqlInjectionDetectMiddleware::class,
    // V2.9.5 XSS输出过滤 + CSP安全头
    \app\common\middleware\XssEscapeMiddleware::class,
    // V2.9.35 性能监控（采样率10%，慢请求100%记录）
    \app\common\middleware\PerformanceMonitorMiddleware::class,
];
