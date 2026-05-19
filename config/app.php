<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
// AI-CMS V2.0 全局配置

return [
    // 应用名称
    'app_name' => env('APP_NAME', 'AI-CMS'),
    
    // 应用地址
    'app_host' => env('APP_HOST', ''),
    
    // 应用调试模式
    'app_debug' => (bool) env('APP_DEBUG', false),
    
    // 应用Trace
    'app_trace' => (bool) env('APP_TRACE', false),
    
    // 默认时区
    'default_timezone' => env('DEFAULT_TIMEZONE', 'Asia/Shanghai'),
    
    // 异常页面的模板文件
    'exception_tmpl' => '',
    
    // 错误显示信息,非调试模式有效
    'error_message' => '页面错误！请稍后再试～',
    
    // 显示错误信息
    'show_error_msg' => (bool) env('APP_DEBUG', false),
    
    // 多应用模式
    'app_map' => [],
    'domain_bind' => [],
    'deny_app_list' => ['common'],

    // ===== 编码根治：全局默认字符集 =====
    'default_charset' => 'utf-8',
    'default_return_type' => 'html',
];
