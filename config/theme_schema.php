<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
/**
 * V2.9.9 I-4: 模板主题 Schema 校验规则配置（单源模式）
 * 行业列表在此统一定义，不再双写 config/ai.php
 */
return [
    // 格式版本定义
    'v2' => [
        'required'    => ['name', 'version', 'description', 'author'],
        'recommended' => ['colors', 'options', 'supports', 'default_device'],
    ],
    'v3' => [
        'required'    => ['name', 'version', 'description', 'author', 'type'],
        'recommended' => ['color', 'layouts', 'assets', 'pages'],
    ],
    // 市场标准字段（I-4引入）
    'market_standard' => [
        'name', 'version', 'description', 'author', 'category', 'tags',
        'preview', 'type', 'supports', 'colors', 'layouts', 'assets',
    ],
    // 行业分类（单源）
    'industries' => [
        'enterprise'  => ['name' => '企业商务', 'color' => '#1e293b', 'icon' => 'bi-building'],
        'ecommerce'   => ['name' => '电商零售', 'color' => '#ea580c', 'icon' => 'bi-cart'],
        'education'   => ['name' => '教育培训', 'color' => '#2563eb', 'icon' => 'bi-mortarboard'],
        'healthcare'  => ['name' => '医疗健康', 'color' => '#059669', 'icon' => 'bi-heart-pulse'],
        'finance'     => ['name' => '金融理财', 'color' => '#7c3aed', 'icon' => 'bi-bank'],
        'travel'      => ['name' => '旅游出行', 'color' => '#0891b2', 'icon' => 'bi-airplane'],
        'portal'      => ['name' => '门户资讯', 'color' => '#4f46e5', 'icon' => 'bi-newspaper'],
        'blog'        => ['name' => '个人博客', 'color' => '#db2777', 'icon' => 'bi-pen'],
        'other'       => ['name' => '其他',     'color' => '#6b7280', 'icon' => 'bi-folder'],
    ],
];
