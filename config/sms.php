<?php
// +----------------------------------------------------------------------
// | AI-CMS 短信服务配置
// | V2.9.38 SYS-INTEG-3
// +----------------------------------------------------------------------
return [
    // 默认发送渠道(为空则按优先级自动选择)
    'default' => '',
    
    // 阿里云短信
    'aliyun' => [
        'access_key' => env('SMS_ALIYUN_ACCESS_KEY', ''),
        'access_secret' => env('SMS_ALIYUN_ACCESS_SECRET', ''),
        'sign_name' => env('SMS_ALIYUN_SIGN_NAME', 'AI-CMS'),
        'endpoint' => 'dysmsapi.aliyuncs.com',
    ],
    
    // 腾讯云短信
    'tencent' => [
        'secret_id' => env('SMS_TENCENT_SECRET_ID', ''),
        'secret_key' => env('SMS_TENCENT_SECRET_KEY', ''),
        'sign_name' => env('SMS_TENCENT_SIGN_NAME', 'AI-CMS'),
        'sdk_app_id' => env('SMS_TENCENT_SDK_APP_ID', ''),
        'endpoint' => 'sms.tencentcloudapi.com',
    ],
    
    // 七牛云短信
    'qiniu' => [
        'access_key' => env('SMS_QINIU_ACCESS_KEY', ''),
        'secret_key' => env('SMS_QINIU_SECRET_KEY', ''),
        'sign_name' => env('SMS_QINIU_SIGN_NAME', 'AI-CMS'),
    ],
    
    // 验证码设置
    'verify_code' => [
        'length' => 6,
        'expire' => 300, // 有效期(秒)
        'frequency_limit' => 60, // 发送频率限制(秒)
        'ip_daily_limit' => 10, // 每IP每日上限
    ],
];
