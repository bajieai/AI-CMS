<?php
// OAuth 第三方登录配置
// 建议在 .env 中配置敏感信息

return [
    // Gitee OAuth 应用配置
    // 申请地址：https://gitee.com/oauth/applications
    'gitee_client_id'     => env('OAUTH_GITEE_CLIENT_ID', ''),
    'gitee_client_secret' => env('OAUTH_GITEE_CLIENT_SECRET', ''),
];
