<?php

return [
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
];
