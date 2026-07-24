<?php

return [
    'enabled' => true,
    'driver' => env('MAIL_DRIVER', 'smtp'),
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@i8j.cn'),
        'name' => env('MAIL_FROM_NAME', '八界AI-CMS'),
    ],
    'smtp' => [
        'host' => env('MAIL_HOST', 'smtp.qq.com'),
        'port' => env('MAIL_PORT', 465),
        'username' => env('MAIL_USERNAME', ''),
        'password' => env('MAIL_PASSWORD', ''),
        'encryption' => env('MAIL_ENCRYPTION', 'ssl'),
    ],
    'templates' => [
        'welcome', 'verify', 'reset', 'notify', 'order', 'audit',
    ],
];