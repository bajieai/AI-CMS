<?php
// +----------------------------------------------------------------------
// | AI-CMS 短信服务配置
// | V2.9.38 SYS-INTEG-3
// +----------------------------------------------------------------------
return [
    // 默认发送渠道(为空则按优先级自动选择)
    'default' => '',

    // V2.9.55: 短信宝（门槛低：个人注册即可用、按条计费、无需模板报备，适合低成本起步/真实短信测试）
    // 文档: https://www.smsbao.com/open.html
    'smsbao' => [
        'username' => env('SMS_SMSBAO_USERNAME', ''),
        'password' => env('SMS_SMSBAO_PASSWORD', ''),
        // 短信宝后台申请的签名（不含【】括号），内容自动加【签名】前缀
        'sign_name' => env('SMS_SMSBAO_SIGN_NAME', ''),
        // 验证码内容模板（{code}=验证码，{expire}=分钟数），留空用默认文案
        'content_template' => env('SMS_SMSBAO_CONTENT_TEMPLATE', ''),
    ],

    // 阿里云短信（注：当前为占位适配器，未实现真实API调用，接入前请优先使用短信宝）
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
