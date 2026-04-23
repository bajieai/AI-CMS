<?php
// AI-CMS V2.0 全局配置

return [
    // 应用名称
    'app_name' => 'AI-CMS',
    
    // 应用地址
    'app_host' => '',
    
    // 应用调试模式
    'app_debug' => false,
    
    // 应用Trace
    'app_trace' => false,
    
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',
    
    // 异常页面的模板文件
    'exception_tmpl' => '',
    
    // 错误显示信息,非调试模式有效
    'error_message' => '页面错误！请稍后再试～',
    
    // 显示错误信息
    'show_error_msg' => false,
    
    // 多应用模式
    'app_map' => [],
    'domain_bind' => [],
    'deny_app_list' => ['common'],
];
