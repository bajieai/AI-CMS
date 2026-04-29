<?php
// AI-CMS V2.0 后台菜单配置（MVP使用配置文件，V2.1升级为数据库管理）

return [
    [
        'id' => 1,
        'name' => '内容管理',
        'icon' => 'bi bi-file-text',
        'url' => '',
        'children' => [
            ['id' => 11, 'name' => '信息管理', 'url' => '/admin/content/index', 'permission' => 'content.*', 'active' => 'content', 'icon' => 'bi bi-file-text'],
            ['id' => 12, 'name' => '分类管理', 'url' => '/admin/cate/index', 'permission' => 'cate.*', 'active' => 'cate', 'icon' => 'bi bi-folder2'],
            ['id' => 13, 'name' => '标签管理', 'url' => '/admin/tag/index', 'permission' => 'tag.*', 'active' => 'tag', 'icon' => 'bi bi-tags'],
            ['id' => 14, 'name' => '回收站', 'url' => '/admin/content/recycleBin', 'permission' => 'content.recycle', 'active' => 'recycle', 'icon' => 'bi bi-trash3'],
            ['id' => 15, 'name' => '媒体资源库', 'url' => '/admin/media/index', 'permission' => 'media.*', 'active' => 'media', 'icon' => 'bi bi-images'],
            ['id' => 16, 'name' => '内容审核', 'url' => '/admin/review/index', 'permission' => 'review.*', 'active' => 'review', 'icon' => 'bi bi-patch-check'],
        ],
    ],
    [
        'id' => 2,
        'name' => '用户管理',
        'icon' => 'bi bi-people',
        'url' => '',
        'children' => [
            ['id' => 21, 'name' => '用户列表', 'url' => '/admin/user/index', 'permission' => 'user.*', 'active' => 'user', 'icon' => 'bi bi-people'],
        ],
    ],
    [
        'id' => 3,
        'name' => '运营管理',
        'icon' => 'bi bi-shop',
        'url' => '',
        'children' => [
            ['id' => 33, 'name' => '轮播图管理', 'url' => '/admin/banner/index', 'permission' => 'banner.*', 'active' => 'banner', 'icon' => 'bi bi-images'],
            ['id' => 34, 'name' => '友情链接', 'url' => '/admin/link/index', 'permission' => 'link.*', 'active' => 'link', 'icon' => 'bi bi-link-45deg'],
            ['id' => 35, 'name' => '友链分组', 'url' => '/admin/link_group/index', 'permission' => 'link.*', 'active' => 'link_group', 'icon' => 'bi bi-folder2-open'],
            ['id' => 36, 'name' => '广告管理', 'url' => '/admin/ad/index', 'permission' => 'ad.*', 'active' => 'ad', 'icon' => 'bi bi-badge-ad'],
        ],
    ],
    [
        'id' => 5,
        'name' => '互动管理',
        'icon' => 'bi bi-chat-dots',
        'url' => '',
        'children' => [
            ['id' => 51, 'name' => '评论管理', 'url' => '/admin/comment/index', 'permission' => 'comment.*', 'active' => 'comment', 'icon' => 'bi bi-chat-left-text'],
            ['id' => 52, 'name' => '前台会员', 'url' => '/admin/member/index', 'permission' => 'member.*', 'active' => 'member', 'icon' => 'bi bi-person-badge'],
        ],
    ],
    [
        'id' => 6,
        'name' => 'SEO与数据',
        'icon' => 'bi bi-bar-chart',
        'url' => '',
        'children' => [
            ['id' => 61, 'name' => 'SEO管理', 'url' => '/admin/seo/index', 'permission' => 'seo.*', 'active' => 'seo', 'icon' => 'bi bi-search'],
            ['id' => 62, 'name' => '数据导出', 'url' => '/admin/export/index', 'permission' => 'export.*', 'active' => 'export', 'icon' => 'bi bi-download'],
            ['id' => 63, 'name' => 'API令牌', 'url' => '/admin/token/index', 'permission' => 'token.*', 'active' => 'token', 'icon' => 'bi bi-key'],
        ],
    ],
    [
        'id' => 4,
        'name' => '系统设置',
        'icon' => 'bi bi-gear',
        'url' => '',
        'children' => [
            ['id' => 41, 'name' => '系统配置', 'url' => '/admin/system/config', 'permission' => 'system.*', 'active' => 'system', 'icon' => 'bi bi-gear'],
            ['id' => 45, 'name' => '自定义变量', 'url' => '/admin/system/customVar', 'permission' => 'system.*', 'active' => 'system', 'icon' => 'bi bi-braces'],
            ['id' => 46, 'name' => '功能开关', 'url' => '/admin/system/moduleControl', 'permission' => 'system.*', 'active' => 'system', 'icon' => 'bi bi-toggle-on'],
            ['id' => 42, 'name' => '操作日志', 'url' => '/admin/log/index', 'permission' => 'system.log', 'active' => 'log', 'icon' => 'bi bi-journal-text'],
            ['id' => 43, 'name' => '数据库备份', 'url' => '/admin/backup/index', 'permission' => 'backup.*', 'active' => 'backup', 'icon' => 'bi bi-database'],
            ['id' => 44, 'name' => '通知中心', 'url' => '/admin/notification/index', 'permission' => 'notification.*', 'active' => 'notification', 'icon' => 'bi bi-bell'],
        ],
    ],
];
