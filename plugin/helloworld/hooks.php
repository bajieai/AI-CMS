<?php

/**
 * HelloWorld插件钩子注册
 */

return [
    'content.after_save' => [
        'callback' => 'HelloWorldPlugin@onContentSave',
        'type'     => 'action',
        'priority' => 10,
    ],
];
