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
// OAuth 第三方登录配置
// 建议在 .env 中配置敏感信息

return [
    // Gitee OAuth 应用配置
    'gitee_client_id'     => env('OAUTH_GITEE_CLIENT_ID', ''),
    'gitee_client_secret' => env('OAUTH_GITEE_CLIENT_SECRET', ''),

    // 微信开放平台（PC扫码登录）
    'wechat_open_appid'     => env('OAUTH_WECHAT_OPEN_APPID', ''),
    'wechat_open_secret'    => env('OAUTH_WECHAT_OPEN_SECRET', ''),

    // QQ互联
    'qq_appid'     => env('OAUTH_QQ_APPID', ''),
    'qq_appkey'    => env('OAUTH_QQ_APPKEY', ''),
];
