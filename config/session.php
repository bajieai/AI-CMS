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
// AI-CMS V2.0 Session配置（PHP原生文件Session）

return [
    // Session驱动类型（MVP使用文件，V2.1可升级为Redis）
    'type' => 'file',
    
    // Session前缀
    'prefix' => 'i8j_',
    
    // Session有效期（秒），24小时
    'expire' => 86400,
    
    // Session自动启动
    'auto_start' => true,
    
    // Session存储路径
    'path' => runtime_path() . 'session',
    
    // Session Cookie名称
    'name' => 'I8J_SID',
    
    // Session Cookie有效期
    'cookie_lifetime' => 0,
    
    // Session Cookie路径
    'cookie_path' => '/',
    
    // Session Cookie域名
    'cookie_domain' => '',
    
    // 是否仅HTTPS传输
    'cookie_secure' => false,
    
    // 是否仅HTTP访问（防JS读取）
    'cookie_httponly' => true,
    
    // 是否使用SameSite
    'cookie_samesite' => 'Lax',
];
