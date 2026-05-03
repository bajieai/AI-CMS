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
            ['id' => 27, 'name' => '会员等级', 'url' => '/admin/member_level/index', 'permission' => 'member_level.*', 'active' => 'member_level', 'icon' => 'bi bi-award'],
            ['id' => 28, 'name' => '积分规则', 'url' => '/admin/points_rule/index', 'permission' => 'points.*', 'active' => 'points_rule', 'icon' => 'bi bi-star'],
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
            ['id' => 53, 'name' => '付费订单', 'url' => '/admin/paid_order/index', 'permission' => 'paid_order.*', 'active' => 'paid_order', 'icon' => 'bi bi-credit-card'],
            // V2.5 支付管理
            ['id' => 54, 'name' => '支付管理', 'url' => '/admin/payment/index', 'permission' => 'payment.*', 'active' => 'payment', 'icon' => 'bi bi-wallet2'],
            ['id' => 55, 'name' => '收入统计', 'url' => '/admin/payment/revenue', 'permission' => 'payment.*', 'active' => 'payment_revenue', 'icon' => 'bi bi-cash-stack'],
        ],
    ],
    [
        'id' => 7,
        'name' => 'AI中心',
        'icon' => 'bi bi-robot',
        'url' => '',
        'children' => [
            ['id' => 71, 'name' => 'AI模型管理', 'url' => '/admin/ai_model/index', 'permission' => 'ai_model.*', 'active' => 'ai_model', 'icon' => 'bi bi-cpu'],
            ['id' => 72, 'name' => 'AI调用日志', 'url' => '/admin/ai_log/index', 'permission' => 'ai_log.*', 'active' => 'ai_log', 'icon' => 'bi bi-journal-code'],
            // V2.5 AI增强
            ['id' => 73, 'name' => 'AI批量生成', 'url' => '/admin/ai_batch/index', 'permission' => 'ai_batch.*', 'active' => 'ai_batch', 'icon' => 'bi bi-magic'],
        ],
    ],
    [
        'id' => 6,
        'name' => 'SEO与数据',
        'icon' => 'bi bi-bar-chart',
        'url' => '',
        'children' => [
            ['id' => 60, 'name' => '数据看板', 'url' => '/admin/dashboard/index', 'permission' => 'dashboard.*', 'active' => 'dashboard', 'icon' => 'bi bi-speedometer2'],
            ['id' => 61, 'name' => 'SEO管理', 'url' => '/admin/seo/index', 'permission' => 'seo.*', 'active' => 'seo', 'icon' => 'bi bi-search'],
            ['id' => 64, 'name' => 'SEO关键词', 'url' => '/admin/seo_keyword/index', 'permission' => 'seo_keyword.*', 'active' => 'seo_keyword', 'icon' => 'bi bi-hash'],
            ['id' => 65, 'name' => '关键词分组', 'url' => '/admin/seo_keyword/group', 'permission' => 'seo_keyword.*', 'active' => 'seo_keyword_group', 'icon' => 'bi bi-folder'],
            ['id' => 62, 'name' => '数据导出', 'url' => '/admin/export/index', 'permission' => 'export.*', 'active' => 'export', 'icon' => 'bi bi-download'],
            ['id' => 63, 'name' => 'API令牌', 'url' => '/admin/token/index', 'permission' => 'token.*', 'active' => 'token', 'icon' => 'bi bi-key'],
        ],
    ],
    // V2.5 内容生态
    [
        'id' => 8,
        'name' => '内容生态',
        'icon' => 'bi bi-globe2',
        'url' => '',
        'children' => [
            ['id' => 81, 'name' => '采集源管理', 'url' => '/admin/collect_source/index', 'permission' => 'collect.*', 'active' => 'collect_source', 'icon' => 'bi bi-cloud-download'],
            ['id' => 82, 'name' => '采集日志', 'url' => '/admin/collect_log/index', 'permission' => 'collect.*', 'active' => 'collect_log', 'icon' => 'bi bi-journal'],
            ['id' => 83, 'name' => '发布平台', 'url' => '/admin/publish_platform/index', 'permission' => 'publish.*', 'active' => 'publish_platform', 'icon' => 'bi bi-send'],
            ['id' => 84, 'name' => '发布记录', 'url' => '/admin/publish_log/index', 'permission' => 'publish.*', 'active' => 'publish_log', 'icon' => 'bi bi-clock-history'],
            ['id' => 85, 'name' => '邮件模板', 'url' => '/admin/email_template/index', 'permission' => 'email.*', 'active' => 'email_template', 'icon' => 'bi bi-envelope-paper'],
            ['id' => 86, 'name' => '邮件日志', 'url' => '/admin/email_log/index', 'permission' => 'email.*', 'active' => 'email_log', 'icon' => 'bi bi-envelope-check'],
        ],
    ],
    // V2.5 平台化
    [
        'id' => 9,
        'name' => '平台扩展',
        'icon' => 'bi bi-puzzle',
        'url' => '',
        'children' => [
            ['id' => 91, 'name' => '插件管理', 'url' => '/admin/plugin/index', 'permission' => 'plugin.*', 'active' => 'plugin', 'icon' => 'bi bi-plug'],
            ['id' => 92, 'name' => '多语言管理', 'url' => '/admin/language/index', 'permission' => 'language.*', 'active' => 'language', 'icon' => 'bi bi-translate'],
            ['id' => 93, 'name' => '模板市场', 'url' => '/admin/theme_market/index', 'permission' => 'theme_market.*', 'active' => 'theme_market', 'icon' => 'bi bi-palette2'],
        ],
    ],
    [
        'id' => 4,
        'name' => '系统设置',
        'icon' => 'bi bi-gear',
        'url' => '',
        'children' => [
            ['id' => 41, 'name' => '系统配置', 'url' => '/admin/system/config', 'permission' => 'system.*', 'active' => 'system_config', 'icon' => 'bi bi-gear'],
            ['id' => 45, 'name' => '自定义变量', 'url' => '/admin/system/customVar', 'permission' => 'system.*', 'active' => 'system_custom_var', 'icon' => 'bi bi-braces'],
            ['id' => 46, 'name' => '功能开关', 'url' => '/admin/system/moduleControl', 'permission' => 'system.*', 'active' => 'system_module', 'icon' => 'bi bi-toggle-on'],
            ['id' => 42, 'name' => '操作日志', 'url' => '/admin/log/index', 'permission' => 'system.log', 'active' => 'log', 'icon' => 'bi bi-journal-text'],
            ['id' => 43, 'name' => '数据库备份', 'url' => '/admin/backup/index', 'permission' => 'backup.*', 'active' => 'backup', 'icon' => 'bi bi-database'],
            ['id' => 44, 'name' => '通知中心', 'url' => '/admin/notification/index', 'permission' => 'notification.*', 'active' => 'notification', 'icon' => 'bi bi-bell'],
            ['id' => 47, 'name' => '表单管理', 'url' => '/admin/form/index', 'permission' => 'form.*', 'active' => 'form', 'icon' => 'bi bi-card-checklist'],
            ['id' => 48, 'name' => '导入管理', 'url' => '/admin/import/index', 'permission' => 'import.*', 'active' => 'import', 'icon' => 'bi bi-upload'],
            ['id' => 49, 'name' => '邮件订阅', 'url' => '/admin/email_subscriber/index', 'permission' => 'email_subscriber.*', 'active' => 'email_subscriber', 'icon' => 'bi bi-envelope'],
            ['id' => 50, 'name' => '访问归档', 'url' => '/admin/visit_archive/index', 'permission' => 'visit_archive.*', 'active' => 'visit_archive', 'icon' => 'bi bi-archive'],
            ['id' => 58, 'name' => '验证码配置', 'url' => '/admin/captcha/config', 'permission' => 'captcha.*', 'active' => 'captcha', 'icon' => 'bi bi-shield-check'],
        ],
    ],
];
