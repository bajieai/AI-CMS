<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | V2.9.35 安全配置

return [
    // 安全级别: relaxed(宽松) / standard(标准) / strict(严格)
    'level' => env('security.level', 'standard'),

    // XSS输入过滤
    'xss_input' => [
        'enabled'       => true,
        // 富文本字段白名单(不进行输入过滤，由HTMLPurifier处理)
        'rich_text_fields' => ['content', 'description', 'body', 'text', 'seo_description', 'remark'],
        // 严格模式: strip_tags
        // 标准模式: 移除script/iframe/object/embed
        // 宽松模式: 仅移除script
        'strict_tags'   => ['script', 'iframe', 'object', 'embed', 'base', 'form'],
        // 移除的事件属性正则
        'event_attrs'   => '/on\w+\s*=\s*["\']?[^"\'>]*/i',
        // JS伪协议
        'js_protocol'   => '/javascript:\s*/i',
        // CSS注入
        'css_injection' => '/expression\s*\(/i',
    ],

    // HTMLPurifier富文本过滤
    'html_purifier' => [
        'enabled'     => true,
        // 允许的HTML标签
        'allowed_tags' => 'p,br,strong,em,u,s,ol,ul,li,a,img,table,thead,tbody,tr,td,th,blockquote,pre,code,h1,h2,h3,h4,h5,h6,div,span,hr,video,audio,source',
        // 允许的属性
        'allowed_attrs' => 'class,id,style,href,src,alt,title,width,height,target,rel,controls,data-*',
        // 允许的CSS属性
        'allowed_css'  => 'color,background-color,font-size,font-weight,text-align,text-decoration,margin,padding,width,height,border,text-indent',
    ],

    // CSRF保护
    'csrf' => [
        // Token有效期(秒)
        'token_ttl'     => 1800, // 30分钟
        // 是否绑定IP
        'bind_ip'       => false,
        // 白名单URL(不需要CSRF校验)
        'whitelist'     => ['/api/', '/webhook/', '/notify/'],
        // Ajax Header名称
        'ajax_header'   => 'X-CSRF-Token',
    ],

    // SQL注入检测
    'sql_injection' => [
        'enabled'       => true,
        // 模式: block(阻断) / log(仅记录)
        'mode'          => 'block',
        // 白名单URL
        'whitelist'     => ['/admin/sql_query/', '/admin/db_optimize/'],
        // 慢查询阈值(秒)
        'slow_threshold' => 2,
    ],

    // 文件上传安全
    'file_upload' => [
        // 图片二次渲染(清除EXIF)
        'image_reprocess'  => true,
        // SVG安全过滤
        'svg_filter'       => true,
        // 病毒扫描(ClamAV)
        'virus_scan'       => false,
        'clamav_socket'    => '/var/run/clamav/clamd.sock',
        // 隔离目录
        'quarantine_dir'   => runtime_path() . 'quarantine/',
        // 允许的MIME类型
        'allowed_mime'     => [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
            'application/pdf', 'application/zip',
            'text/plain', 'text/csv',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
    ],

    // 密码策略
    'password' => [
        // 最小长度
        'min_length'        => 8,
        // 必须包含大小写字母
        'require_case'      => true,
        // 必须包含数字
        'require_number'    => true,
        // 必须包含特殊字符
        'require_special'   => false,
        // 历史密码检查数量(防止重用)
        'history_count'     => 5,
        // 密码有效期(天, 0=不过期)
        'expire_days'       => 0,
        // 最大登录失败次数
        'max_login_attempts' => 5,
        // 锁定时长(分钟)
        'lock_minutes'      => 30,
    ],

    // 数据脱敏
    'data_mask' => [
        'phone'     => true,  // 138****8888
        'email'     => true,  // z***@example.com
        'id_card'   => true,  // 420***********1234
        'ip'        => false, // 192.168.1.***
        'bank_card' => true,  // 6222****1234
    ],

    // 加密配置
    'encryption' => [
        // 系统主密钥(从环境变量读取)
        'master_key' => env('AI_CMS_ENC_KEY', ''),
        // 默认算法
        'algorithm'  => 'AES-256-CBC',
        // 密钥缓存时间(秒)
        'key_cache_ttl' => 3600,
    ],

    // 安全日志
    'log' => [
        // 异步写入
        'async'        => true,
        // 批量写入大小
        'batch_size'   => 50,
        // 日志保留天数
        'retain_days'  => 90,
        // 严重事件告警
        'alert' => [
            'enabled'   => true,
            'min_severity' => 3, // 3=高 4=严重
            'channels'  => ['email', 'notification'], // 飞书/邮件/站内通知
        ],
    ],
];
