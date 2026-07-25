<?php

return [
    'enabled'  => true,
    'settings' => [
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
];
