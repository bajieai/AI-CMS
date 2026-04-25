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
        ],
    ],
    [
        'id' => 4,
        'name' => '系统设置',
        'icon' => 'bi bi-gear',
        'url' => '',
        'children' => [
            ['id' => 41, 'name' => '系统配置', 'url' => '/admin/system/config', 'permission' => 'system.*', 'active' => 'system', 'icon' => 'bi bi-gear'],
            ['id' => 42, 'name' => '操作日志', 'url' => '/admin/log/index', 'permission' => 'system.log', 'active' => 'log', 'icon' => 'bi bi-journal-text'],
            ['id' => 43, 'name' => '数据库备份', 'url' => '/admin/backup/index', 'permission' => 'backup.*', 'active' => 'backup', 'icon' => 'bi bi-database'],
        ],
    ],
];
