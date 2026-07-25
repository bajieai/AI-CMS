<?php

return [
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
];
