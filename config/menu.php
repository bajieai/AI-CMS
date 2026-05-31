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
            // V2.9.9 P1-1: 工作流审批
            ['id' => 161, 'name' => '审批工作流', 'url' => '/admin/workflow/index', 'permission' => 'workflow.*', 'active' => 'workflow', 'icon' => 'bi bi-journal-check'],
            ['id' => 162, 'name' => '审批记录', 'url' => '/admin/workflow/records', 'permission' => 'workflow.*', 'active' => 'workflow_records', 'icon' => 'bi bi-clock-history'],
            // V2.9.1 M18: 批量内容管理（复用content权限，不新增独立菜单）
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
            // V2.9.2 M20: 会员权益配置
            ['id' => 271, 'name' => '权益配置', 'url' => '/admin/member_benefit/index', 'permission' => 'member_benefit.*', 'active' => 'member_benefit', 'icon' => 'bi bi-stars'],
            ['id' => 272, 'name' => '会员等级管理', 'url' => '/admin/member_benefit/members', 'permission' => 'member_benefit.*', 'active' => 'member_benefit_members', 'icon' => 'bi bi-people'],
            ['id' => 28, 'name' => '积分规则', 'url' => '/admin/points_rule/index', 'permission' => 'points.*', 'active' => 'points_rule', 'icon' => 'bi bi-star'],
            // V2.7 积分商城
            ['id' => 29, 'name' => '积分商品', 'url' => '/admin/points_product/index', 'permission' => 'points_product.*', 'active' => 'points_product', 'icon' => 'bi bi-gift'],
            ['id' => 210, 'name' => '兑换记录', 'url' => '/admin/points_exchange/index', 'permission' => 'points_exchange.*', 'active' => 'points_exchange', 'icon' => 'bi bi-arrow-left-right'],
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
            // V2.6 表单管理从系统设置迁移至此
            ['id' => 47, 'name' => '表单管理', 'url' => '/admin/form/index', 'permission' => 'form.*', 'active' => 'form', 'icon' => 'bi bi-card-checklist'],
            // V2.9 优惠券管理
            ['id' => 48, 'name' => '优惠券', 'url' => '/admin/coupon/index', 'permission' => 'coupon.*', 'active' => 'coupon', 'icon' => 'bi bi-ticket-perforated'],
        ],
    ],
    [
        'id' => 5,
        'name' => '互动管理',
        'icon' => 'bi bi-chat-dots',
        'url' => '',
        'children' => [
            ['id' => 51, 'name' => '评论管理', 'url' => '/admin/comment/index', 'permission' => 'comment.*', 'active' => 'comment', 'icon' => 'bi bi-chat-left-text'],
            // V2.9 评价管理
            ['id' => 513, 'name' => '评价管理', 'url' => '/admin/rating/index', 'permission' => 'rating.*', 'active' => 'rating', 'icon' => 'bi bi-star'],
            ['id' => 52, 'name' => '前台会员', 'url' => '/admin/member/index', 'permission' => 'member.*', 'active' => 'member', 'icon' => 'bi bi-person-badge'],
            // V2.8 邀请排行
            ['id' => 511, 'name' => '邀请排行', 'url' => '/admin/invite/index', 'permission' => 'invite.*', 'active' => 'invite', 'icon' => 'bi bi-gift'],
            ['id' => 53, 'name' => '付费订单', 'url' => '/admin/paid_order/index', 'permission' => 'paid_order.*', 'active' => 'paid_order', 'icon' => 'bi bi-credit-card'],
            // V2.6 私信系统
            ['id' => 56, 'name' => '系统通知', 'url' => '/admin/message/system', 'permission' => 'message.*', 'active' => 'message_system', 'icon' => 'bi bi-bell'],
            ['id' => 57, 'name' => '发送通知', 'url' => '/admin/message/sendSystem', 'permission' => 'message.*', 'active' => 'message_send', 'icon' => 'bi bi-send-plus'],
            // V2.6 OAuth配置
            ['id' => 510, 'name' => 'OAuth配置', 'url' => '/admin/oauth_config/index', 'permission' => 'oauth.*', 'active' => 'oauth_config', 'icon' => 'bi bi-key-fill'],
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
            // V2.6 AI内容模板
            ['id' => 74, 'name' => 'AI内容模板', 'url' => '/admin/ai_template/index', 'permission' => 'ai_template.*', 'active' => 'ai_template', 'icon' => 'bi bi-file-earmark-text'],
            // V2.9 前台模板可视化设计
            ['id' => 75, 'name' => '模板设计器', 'url' => '/admin/template_design/index', 'permission' => 'template_design.*', 'active' => 'template_design', 'icon' => 'bi bi-palette'],
            // V2.9.2 M19a: AI翻译管理
            ['id' => 76, 'name' => 'AI翻译管理', 'url' => '/admin/ai_translation/index', 'permission' => 'ai_translation.*', 'active' => 'ai_translation', 'icon' => 'bi bi-translate'],
            // V2.9.10: AI配置中心
            ['id' => 77, 'name' => 'AI配置', 'url' => '/admin/system/aiConfig', 'permission' => 'ai_config.*', 'active' => 'ai_config', 'icon' => 'bi bi-sliders'],
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
            // V2.9.2 M23: 高级导出
            ['id' => 621, 'name' => '高级导出', 'url' => '/admin/export/dialog', 'permission' => 'export_advanced.*', 'active' => 'export_dialog', 'icon' => 'bi bi-file-earmark-arrow-down'],
            ['id' => 63, 'name' => 'API令牌', 'url' => '/admin/token/index', 'permission' => 'token.*', 'active' => 'token', 'icon' => 'bi bi-key'],
            // V2.9.2 M24: 系统监控
            ['id' => 69, 'name' => '系统监控', 'url' => '/admin/monitor/index', 'permission' => 'monitor.*', 'active' => 'monitor', 'icon' => 'bi bi-speedometer2'],
            // V2.8 流量分析与AI统计
            ['id' => 66, 'name' => '流量分析', 'url' => '/admin/traffic/index', 'permission' => 'traffic.*', 'active' => 'traffic', 'icon' => 'bi bi-graph-up'],
            ['id' => 67, 'name' => 'AI统计', 'url' => '/admin/aiStat/index', 'permission' => 'ai_stat.*', 'active' => 'ai_stat', 'icon' => 'bi bi-robot'],
            // V2.9.1 M9: AI数据分析报告
            ['id' => 68, 'name' => '数据报告', 'url' => '/admin/report/index', 'permission' => 'report.*', 'active' => 'report', 'icon' => 'bi bi-graph-up-arrow'],
            // V2.9.13: 运营分析看板
            ['id' => 691, 'name' => '运营分析', 'url' => '/admin/data_dashboard/index', 'permission' => 'data_dashboard.*', 'active' => 'data_dashboard', 'icon' => 'bi bi-bar-chart-line'],
            // V2.9.9 P0-2: 社交分享追踪
            ['id' => 690, 'name' => '分享追踪', 'url' => '/admin/social_share/index', 'permission' => 'social_share.*', 'active' => 'social_share', 'icon' => 'bi bi-share'],
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
            // V2.9.2 M25: 插件市场
            ['id' => 911, 'name' => '插件市场', 'url' => '/admin/plugin_market/index', 'permission' => 'plugin_market.*', 'active' => 'plugin_market', 'icon' => 'bi bi-shop'],
            ['id' => 92, 'name' => '多语言管理', 'url' => '/admin/language/index', 'permission' => 'language.*', 'active' => 'language', 'icon' => 'bi bi-translate'],
            ['id' => 93, 'name' => '模板市场', 'url' => '/admin/theme_market/index', 'permission' => 'theme_market.*', 'active' => 'theme_market', 'icon' => 'bi bi-palette2'],
            // V2.9.12: 模板商店
            ['id' => 931, 'name' => '模板商店管理', 'url' => '/admin/template_store/index', 'permission' => 'template_store.*', 'active' => 'template_store', 'icon' => 'bi bi-shop'],
            ['id' => 932, 'name' => '评论审核', 'url' => '/admin/template_store/reviews', 'permission' => 'template_store.*', 'active' => 'template_reviews', 'icon' => 'bi bi-star-half'],
            ['id' => 933, 'name' => '模板分类', 'url' => '/admin/template_store/categories', 'permission' => 'template_store.*', 'active' => 'template_categories', 'icon' => 'bi bi-folder2'],
            // V2.9.1 M10: API文档
            ['id' => 94, 'name' => 'API文档', 'url' => '/admin/api_doc/index', 'permission' => 'apidoc.*', 'active' => 'api_doc', 'icon' => 'bi bi-file-code'],
        ],
    ],
    [
        'id' => 4,
        'name' => '系统设置',
        'icon' => 'bi bi-gear',
        'url' => '',
        'children' => [
            ['id' => 41, 'name' => '系统配置', 'url' => '/admin/system/config', 'permission' => 'system.*', 'active' => 'system_config', 'icon' => 'bi bi-gear'],
            // 自定义变量(id=45)、功能开关(id=46) 已作为系统配置页内tab，不再重复显示为独立菜单项
            ['id' => 42, 'name' => '操作日志', 'url' => '/admin/log/index', 'permission' => 'system.log', 'active' => 'log', 'icon' => 'bi bi-journal-text'],
            ['id' => 43, 'name' => '数据库备份', 'url' => '/admin/backup/index', 'permission' => 'backup.*', 'active' => 'backup', 'icon' => 'bi bi-database'],
            ['id' => 44, 'name' => '通知中心', 'url' => '/admin/notification/index', 'permission' => 'notification.*', 'active' => 'notification', 'icon' => 'bi bi-bell'],
            // 表单管理已迁移至运营管理(id=47)
            ['id' => 480, 'name' => '导入管理', 'url' => '/admin/import/index', 'permission' => 'import.*', 'active' => 'import', 'icon' => 'bi bi-upload'],
            ['id' => 49, 'name' => '邮件订阅', 'url' => '/admin/email_subscriber/index', 'permission' => 'email_subscriber.*', 'active' => 'email_subscriber', 'icon' => 'bi bi-envelope'],
            ['id' => 50, 'name' => '访问归档', 'url' => '/admin/visit_archive/index', 'permission' => 'visit_archive.*', 'active' => 'visit_archive', 'icon' => 'bi bi-archive'],
            ['id' => 58, 'name' => '验证码配置', 'url' => '/admin/captcha/config', 'permission' => 'captcha.*', 'active' => 'captcha', 'icon' => 'bi bi-shield-check'],
            // V2.6 CDN集成
            ['id' => 59, 'name' => '存储配置', 'url' => '/admin/storage/config', 'permission' => 'storage.*', 'active' => 'storage_config', 'icon' => 'bi bi-hdd-network'],
            // V2.9.10 菜单管理（数据库驱动）
            ['id' => 70, 'name' => '菜单管理', 'url' => '/admin/menu_manager/index', 'permission' => 'menu_manager.*', 'active' => 'menu_manager', 'icon' => 'bi bi-list-nested'],
        ],
    ],
];
