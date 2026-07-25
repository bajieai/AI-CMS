<?php

/**
 * V2.9.35 插件示例：第三方登录
 * 演示插件注册路由+控制器+用户钩子
 */

return [
    'identifier'  => 'oauth_login',
    'name'        => '第三方登录插件',
    'description' => '演示插件路由注册+控制器+用户登录钩子，支持GitHub/微信OAuth',
    'version'     => '1.0.0',
    'author'      => 'AI-CMS Team',
    'homepage'    => 'https://www.i8j.cn',
    'min_version' => '2.9.35',
    'hooks' => [
        'user.before_login' => [
            'callback' => 'OauthLoginPlugin@beforeLogin',
            'type'     => 'filter',
            'priority' => 30,
        ],
        'user.after_register' => [
            'callback' => 'OauthLoginPlugin@afterRegister',
            'type'     => 'action',
            'priority' => 5,
        ],
    ],
    'config' => [
        'github_enabled'   => true,
        'github_client_id' => '',
        'github_secret'    => '',
        'wechat_enabled'   => false,
        'wechat_app_id'    => '',
        'wechat_secret'    => '',
    ],
    'permissions' => ['db_read', 'db_write'],
    'menu' => [
        [
            'name' => '第三方登录',
            'url'  => '/admin/oauth_login/config',
            'icon' => 'bi bi-box-arrow-in-right',
        ],
    ],
];
