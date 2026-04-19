<?php
use app\exception\Handler;

return [
    'app_name' => 'AI-CMS',
    'app_version' => '1.0.0',
    
    // 默认语言
    'default_lang' => 'zh-cn',
    
    // 显示错误信息
    'show_error_msg' => true,
    
    // 异常处理
    'exception_handle' => Handler::class,
    
    // 错误显示
    'error_display' => true,
    
    // 时区
    'default_timezone' => 'Asia/Shanghai',
    
    // 应用命名空间
    'app_namespace' => 'app',

    // 自定义请求类（修复 UrlHandler trait 的 $this->config() 缺失问题）
    'http_request_class' => \app\Request::class,

    // 服务提供者
    'providers' => [
        \app\provider\AppServiceProvider::class,
    ],
    
    // 是否启用多语言
    'lang_switch_on' => false,
    
    // 默认验证器
    'default_validate' => '',
    
    // 默认AJAX请求返回格式
    'default_ajax_return' => 'json',
    
    // 默认JSONP返回格式
    'default_jsonp_handler' => 'jsonpReturn',
    
    // 验证JSONP
    'var_jsonp_handler' => 'callback',
    
    // 开启路由
    'url_route_on' => true,
    
    // 路由配置文件
    'route_config_file' => ['api'],
    
    // 开启路由完全匹配
    'route_complete_match' => false,
    
    // 路由区分大小写
    'route_case_insensitive' => false,
    
    // 使用路由缓存
    'route_check_cache' => false,
    
    // 路由缓存标识
    'route_check_cache_key' => '',
    
    // 域名部署
    'url_domain_deploy' => false,
    
    // 域名根
    'url_domain_root' => '',
    
    // 关闭URL访问
    'url_route_on' => true,
    'url_route_must' => false,
    
    // 禁止访问模块
    'deny_module_list' => ['common'],
    
    // 兼容模式PATHINFO
    'pathinfo_fetch' => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    
    // pathinfo分隔符
    'pathinfo_var' => 's',
    
    // 兼容PATH_INFO获取
    'pathinfo_depr' => '/',
    
    // URL伪静态后缀
    'url_html_suffix' => 'html',
    
    // URL普通参数
    'url_common_param' => false,
    
    // 不自动转换控制器
    'url_controller_layer' => 'controller',
    
    // 控制器名后缀
    'controller_suffix' => true,
    
    // 默认控制器
    'default_controller' => 'Index',
    
    // 默认操作
    'default_action' => 'index',
    
    // 默认JSONP渲染器
    'default_jsonp_handler' => 'jsonpReturn',
    
    // 验证JSONP
    'var_jsonp_handler' => 'callback',
    
    // 操作方法后缀
    'action_suffix' => '',
    
    // 自动JSON响应
    'auto_response' => false,
    
    // 成功操作默认回调
    'success_tmpl' => '',
    'error_tmpl' => '',
    
    // 异常模板
    'exception_tmpl' => '',
    
    // 视图配置
    'view_path' => '',
    'view_suffix' => 'html',
    'view_depr' => DIRECTORY_SEPARATOR,
    'tpl_begin' => '{',
    'tpl_end' => '}',
    
    // 标签库配置
    'taglib_begin' => '{',
    'taglib_end' => '}',
    'taglib_load' => true,
    'taglib_build_in' => 'cx',
    
    // 默认过滤机制
    'default_filter' => 'htmlspecialchars',
    
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',
];
