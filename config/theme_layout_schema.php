<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------

/**
 * 主题布局配置Schema - V2.9.12
 *
 * 定义首页可配置板块及其元数据
 */
return [
    'sections' => [
        [
            'id'          => 'hero',
            'name'        => '首屏大图',
            'desc'        => '首页顶部大图/欢迎区域，通常包含标题、副标题和行动按钮',
            'icon'        => 'bi bi-image-alt',
            'default_visible' => true,
            'default_sort'    => 1,
            'configurable'    => true,
        ],
        [
            'id'          => 'features',
            'name'        => '功能特色',
            'desc'        => '核心功能或服务亮点展示，通常以卡片网格形式呈现',
            'icon'        => 'bi bi-stars',
            'default_visible' => true,
            'default_sort'    => 2,
            'configurable'    => true,
        ],
        [
            'id'          => 'about',
            'name'        => '关于我们',
            'desc'        => '公司/团队介绍区域，可包含图片和文字描述',
            'icon'        => 'bi bi-building',
            'default_visible' => true,
            'default_sort'    => 3,
            'configurable'    => true,
        ],
        [
            'id'          => 'news',
            'name'        => '最新动态',
            'desc'        => '新闻/文章列表区域，展示最新发布的内容',
            'icon'        => 'bi bi-newspaper',
            'default_visible' => true,
            'default_sort'    => 4,
            'configurable'    => true,
        ],
        [
            'id'          => 'gallery',
            'name'        => '图库展示',
            'desc'        => '图片/作品展示区域，支持瀑布流或网格布局',
            'icon'        => 'bi bi-images',
            'default_visible' => false,
            'default_sort'    => 5,
            'configurable'    => true,
        ],
        [
            'id'          => 'pricing',
            'name'        => '价格方案',
            'desc'        => '产品/服务定价展示，通常以价格卡片形式',
            'icon'        => 'bi bi-tags',
            'default_visible' => false,
            'default_sort'    => 6,
            'configurable'    => true,
        ],
        [
            'id'          => 'faq',
            'name'        => '常见问题',
            'desc'        => 'FAQ问答区域，可折叠展开形式',
            'icon'        => 'bi bi-question-circle',
            'default_visible' => false,
            'default_sort'    => 7,
            'configurable'    => true,
        ],
        [
            'id'          => 'contact',
            'name'        => '联系我们',
            'desc'        => '联系表单/信息区域，包含地址、电话、邮箱等',
            'icon'        => 'bi bi-envelope',
            'default_visible' => true,
            'default_sort'    => 8,
            'configurable'    => true,
        ],
    ],
];
