<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------

/**
 * 模板Manifest规范 - V2.9.12
 *
 * 定义模板描述文件的标准结构，用于打包导入时的校验
 */
return [
    'schema_version' => '1.0',
    'required_fields' => ['name', 'slug', 'version'],
    'optional_fields' => ['description', 'author', 'author_url', 'screenshots', 'tags', 'requirements', 'license', 'homepage'],

    'field_types' => [
        'name'         => 'string|max:100',
        'slug'         => 'string|max:50|regex:/^[a-z0-9_-]+$/',
        'version'      => 'string|regex:/^\d+\.\d+\.\d+$/',
        'description'  => 'string|max:500',
        'author'       => 'string|max:100',
        'author_url'   => 'string|url',
        'screenshots'  => 'array',
        'tags'         => 'array',
        'requirements' => 'array',
        'license'      => 'string',
        'homepage'     => 'string|url',
        'protected'    => 'bool',
    ],

    'requirements_schema' => [
        'php' => '>=8.1',
        'cms' => '>=2.9.0',
        'extensions' => [],
    ],

    'example' => [
        'name'        => '企业商务模板',
        'slug'        => 'corporate-pro',
        'version'     => '1.0.0',
        'description' => '适用于企业官网的商务风格模板',
        'author'      => '八界AI',
        'author_url'  => 'https://www.i8j.cn',
        'screenshots' => ['screenshot1.jpg', 'screenshot2.jpg'],
        'tags'        => ['企业', '商务', '响应式'],
        'requirements' => [
            'php' => '>=8.1',
            'cms' => '>=2.9.0',
        ],
        'license'     => 'MIT',
        'homepage'    => 'https://www.i8j.cn',
    ],
];
