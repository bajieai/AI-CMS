<?php
// AI-CMS V2.0 权限配置（简化RBAC，role_id字段控制）

return [
    // 角色定义
    'roles' => [
        1 => ['name' => '超级管理员', 'permissions' => '*'],
        2 => ['name' => '管理员', 'permissions' => ['content.*', 'cate.*', 'tag.*', 'user.*', 'system.*', 'media.*', 'banner.*', 'link.*', 'review.*', 'comment.*', 'member.*', 'seo.*', 'export.*', 'token.*', 'ad.*', 'notification.*', 'log.*']],
        3 => ['name' => '编辑', 'permissions' => ['content.*', 'cate.list', 'tag.list', 'media.list', 'media.upload']],
    ],
    
    // 权限映射（permission key => 控制器方法映射）
    'map' => [
        'content.*' => ['admin/content/index', 'admin/content/add', 'admin/content/edit', 'admin/content/delete', 'admin/content/recycleBin', 'admin/content/restore', 'admin/content/forceDelete'],
        'content.list' => ['admin/content/index'],
        'content.add' => ['admin/content/add'],
        'content.edit' => ['admin/content/edit'],
        'content.delete' => ['admin/content/delete'],
        'content.recycle' => ['admin/content/recycleBin', 'admin/content/restore', 'admin/content/forceDelete'],
        'cate.*' => ['admin/cate/index', 'admin/cate/add', 'admin/cate/edit', 'admin/cate/delete'],
        'cate.list' => ['admin/cate/index'],
        'tag.*' => ['admin/tag/index', 'admin/tag/add', 'admin/tag/edit', 'admin/tag/delete'],
        'tag.list' => ['admin/tag/index'],
        'user.*' => ['admin/user/index', 'admin/user/add', 'admin/user/edit', 'admin/user/delete'],
        'system.*' => ['admin/system/config', 'admin/system/customVar', 'admin/system/moduleControl', 'admin/log/index'],
        'system.log' => ['admin/log/index'],
        'system.customVar' => ['admin/system/customVar', 'admin/system/customVarSave', 'admin/system/customVarDelete'],
        'system.moduleControl' => ['admin/system/moduleControl', 'admin/system/moduleToggle'],
        'media.*' => ['admin/media/index', 'admin/media/upload', 'admin/media/edit', 'admin/media/delete', 'admin/media/select'],
        'media.list' => ['admin/media/index', 'admin/media/select'],
        'media.upload' => ['admin/media/upload'],
        'banner.*' => ['admin/banner/index', 'admin/banner/add', 'admin/banner/edit', 'admin/banner/delete'],
        'link.*' => ['admin/link/index', 'admin/link/add', 'admin/link/edit', 'admin/link/delete'],
        'review.*' => ['admin/review/index', 'admin/review/approve', 'admin/review/reject', 'admin/review/history'],
        'backup.*' => ['admin/backup/index', 'admin/backup/create', 'admin/backup/restore', 'admin/backup/delete', 'admin/backup/download'],
        'comment.*' => ['admin/comment/index', 'admin/comment/audit', 'admin/comment/delete', 'admin/comment/batch'],
        'member.*' => ['admin/member/index', 'admin/member/detail', 'admin/member/edit', 'admin/member/toggleStatus'],
        'seo.*' => ['admin/seo/index', 'admin/seo/sitemap', 'admin/seo/robots'],
        'export.*' => ['admin/export/index'],
        'token.*' => ['admin/token/index', 'admin/token/create', 'admin/token/revoke'],
        'ad.*' => ['admin/ad/index', 'admin/ad/add', 'admin/ad/edit', 'admin/ad/delete', 'admin/ad/stat', 'admin/ad_position/index', 'admin/ad_position/add', 'admin/ad_position/edit', 'admin/ad_position/delete'],
        'link.*' => ['admin/link/index', 'admin/link/add', 'admin/link/edit', 'admin/link/delete', 'admin/link_group/index', 'admin/link_group/add', 'admin/link_group/edit', 'admin/link_group/delete', 'admin/link_group/toggleStatus'],
        'notification.*' => ['admin/notification/index', 'admin/notification/read'],
        'log.*' => ['admin/log/index', 'admin/log/export', 'admin/log/cleanup'],
    ],
];
