<?php

/**
 * V2.9.35 插件示例：内容增强
 * 演示Filter钩子修改内容数据 + 自定义内容字段
 */

return [
    'identifier'  => 'content_enhancer',
    'name'        => '内容增强插件',
    'description' => '演示Filter钩子修改内容数据，自动提取摘要和关键词',
    'version'     => '1.0.0',
    'author'      => 'AI-CMS Team',
    'homepage'    => 'https://www.i8j.cn',
    'min_version' => '2.9.35',
    'hooks' => [
        'content.before_save' => [
            'callback' => 'ContentEnhancerPlugin@beforeSave',
            'type'     => 'filter',
            'priority' => 20,
        ],
        'content.after_display' => [
            'callback' => 'ContentEnhancerPlugin@afterDisplay',
            'type'     => 'filter',
            'priority' => 15,
        ],
    ],
    'config' => [
        'auto_summary_length' => 200,
        'auto_keywords_count' => 10,
        'add_reading_time'    => true,
    ],
    'permissions' => ['db_read', 'db_write'],
    'menu' => [],
];
