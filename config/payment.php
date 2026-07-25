<?php
return [
    'enabled' => true,
    'wechat' => [
        'app_id' => env('WECHAT_APP_ID', ''),
        'mch_id' => env('WECHAT_MCH_ID', ''),
        'key' => env('WECHAT_PAY_KEY', ''),
        'notify_url' => '/home/payment/wechatNotify',
        'ip_whitelist' => '',
    ],
    'alipay' => [
        'app_id' => env('ALIPAY_APP_ID', ''),
        'private_key' => env('ALIPAY_PRIVATE_KEY', ''),
        'public_key' => env('ALIPAY_PUBLIC_KEY', ''),
        'gateway' => 'https://openapi.alipay.com/gateway.do',
        'notify_url' => '/home/payment/alipayNotify',
        'return_url' => '/home/payment/alipayReturn',
        'ip_whitelist' => '',
    ],
];
