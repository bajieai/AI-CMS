<?php

/**
 * V2.9.35 插件示例：HelloWorld
 * 最基础的插件示例，演示plugin.php/config.php/hooks.php结构
 */

return [
    'identifier'  => 'helloworld',
    'name'        => 'HelloWorld 示例插件',
    'description' => '最基础的插件示例，演示V2.9.35插件规范',
    'version'     => '1.0.0',
    'author'      => 'AI-CMS Team',
    'homepage'    => 'https://www.i8j.cn',
    'min_version' => '2.9.35',
    'hooks'       => [
        'content.after_save' => [
            'callback' => 'HelloWorldPlugin@onContentSave',
            'type'     => 'action',
            'priority' => 10,
        ],
    ],
    'config' => [
        'show_message' => true,
        'message_text' => 'Hello, AI-CMS!',
    ],
    'permissions' => ['db_read'],
    'menu' => [],
];
