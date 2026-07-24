<?php

/**
 * V2.9.35 插件示例：短信通知
 * 演示Action钩子发送通知+外部API调用
 */

return [
    'identifier'  => 'sms_notify',
    'name'        => '短信通知插件',
    'description' => '演示Action钩子发送短信通知，支持内容审核/注册/登录提醒',
    'version'     => '1.0.0',
    'author'      => 'AI-CMS Team',
    'homepage'    => 'https://www.i8j.cn',
    'min_version' => '2.9.35',
    'hooks' => [
        'content.after_audit' => [
            'callback' => 'SmsNotifyPlugin@onContentAudit',
            'type'     => 'action',
            'priority' => 10,
        ],
        'user.after_register' => [
            'callback' => 'SmsNotifyPlugin@onUserRegister',
            'type'     => 'action',
            'priority' => 20,
        ],
        'user.login_fail' => [
            'callback' => 'SmsNotifyPlugin@onLoginFail',
            'type'     => 'action',
            'priority' => 10,
        ],
    ],
    'config' => [
        'sms_provider'    => 'aliyun',
        'sms_access_key'  => '',
        'sms_secret'      => '',
        'sms_sign_name'   => 'AI-CMS',
        'admin_mobile'    => '',
        'notify_audit'    => true,
        'notify_register' => false,
        'notify_login_fail' => true,
        'login_fail_threshold' => 5,
    ],
    'permissions' => ['db_read'],
    'menu' => [
        [
            'name' => '短信通知',
            'url'  => '/admin/sms_notify/config',
            'icon' => 'bi bi-phone',
        ],
    ],
];
