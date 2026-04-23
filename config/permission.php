<?php
// AI-CMS V2.0 权限配置（简化RBAC，role_id字段控制）

return [
    // 角色定义
    'roles' => [
        1 => ['name' => '超级管理员', 'permissions' => '*'],
        2 => ['name' => '管理员', 'permissions' => ['content.*', 'cate.*', 'tag.*', 'user.*', 'system.*']],
        3 => ['name' => '编辑', 'permissions' => ['content.*', 'cate.list', 'tag.list']],
    ],
    
    // 权限映射（permission key => 控制器方法映射）
    'map' => [
        'content.*' => ['admin/content/index', 'admin/content/add', 'admin/content/edit', 'admin/content/delete'],
        'content.list' => ['admin/content/index'],
        'content.add' => ['admin/content/add'],
        'content.edit' => ['admin/content/edit'],
        'content.delete' => ['admin/content/delete'],
        'cate.*' => ['admin/cate/index', 'admin/cate/add', 'admin/cate/edit', 'admin/cate/delete'],
        'cate.list' => ['admin/cate/index'],
        'tag.*' => ['admin/tag/index', 'admin/tag/add', 'admin/tag/edit', 'admin/tag/delete'],
        'tag.list' => ['admin/tag/index'],
        'user.*' => ['admin/user/index', 'admin/user/add', 'admin/user/edit', 'admin/user/delete'],
        'system.*' => ['admin/system/config', 'admin/log/index'],
        'system.log' => ['admin/log/index'],
    ],
];
