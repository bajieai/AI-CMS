-- ========================================================
-- AI-CMS V2.9.10 优化升级脚本
-- 1. 后台菜单系统：i8j_menu_group + i8j_menu_item 建表+数据迁移
-- 2. 新增 points_shop_enabled 配置（如不存在）
-- 3. 新增 menu_manager 权限配置
-- ========================================================
-- 执行: bin\migrate.bat database\v2.9.10_optimization.sql

-- --------------------------------------------------------
-- 1. 菜单分组表
-- --------------------------------------------------------
DROP TABLE IF EXISTS `i8j_menu_group`;
CREATE TABLE `i8j_menu_group` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '分组名称',
  `code` varchar(50) NOT NULL COMMENT '分组标识',
  `icon` varchar(50) DEFAULT NULL COMMENT '图标类名',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态:0禁用1启用',
  `create_time` int unsigned DEFAULT '0',
  `update_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `sort_status` (`sort`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台菜单分组表';

-- --------------------------------------------------------
-- 2. 菜单项表
-- --------------------------------------------------------
DROP TABLE IF EXISTS `i8j_menu_item`;
CREATE TABLE `i8j_menu_item` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int unsigned NOT NULL COMMENT '所属分组ID',
  `parent_id` int unsigned NOT NULL DEFAULT '0' COMMENT '父级ID(0为一级)',
  `name` varchar(50) NOT NULL COMMENT '菜单名称',
  `url` varchar(255) DEFAULT NULL COMMENT '链接地址',
  `permission` varchar(100) DEFAULT NULL COMMENT '权限标识',
  `active` varchar(50) DEFAULT NULL COMMENT '激活标识',
  `icon` varchar(50) DEFAULT NULL COMMENT '图标类名',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态:0禁用1启用',
  `module` varchar(50) DEFAULT NULL COMMENT '所属模块',
  `create_time` int unsigned DEFAULT '0',
  `update_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `group_parent_status` (`group_id`,`parent_id`,`status`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台菜单项表';

-- --------------------------------------------------------
-- 3. 插入6个分组（内容管理、用户管理、运营管理、AI中心、界面设计、系统设置）
-- --------------------------------------------------------
INSERT INTO `i8j_menu_group` (`id`, `name`, `code`, `icon`, `sort`, `status`, `create_time`, `update_time`) VALUES
(1, '内容管理', 'content', 'bi bi-file-text', 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, '用户管理', 'user', 'bi bi-people', 20, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, '运营管理', 'operation', 'bi bi-shop', 30, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, 'AI中心', 'ai', 'bi bi-robot', 40, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(5, '界面设计', 'design', 'bi bi-palette', 50, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(6, '系统设置', 'system', 'bi bi-gear', 60, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- --------------------------------------------------------
-- 4. 插入菜单项（按6组重组）
-- --------------------------------------------------------

-- ====== 内容管理 (group_id=1) ======
INSERT INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(11, 1, 0, '信息管理', '/admin/content/index', 'content.*', 'content', 'bi bi-file-text', 10, 1),
(12, 1, 0, '分类管理', '/admin/cate/index', 'cate.*', 'cate', 'bi bi-folder2', 20, 1),
(13, 1, 0, '标签管理', '/admin/tag/index', 'tag.*', 'tag', 'bi bi-tags', 30, 1),
(14, 1, 0, '回收站', '/admin/content/recycleBin', 'content.recycle', 'recycle', 'bi bi-trash3', 40, 1),
(15, 1, 0, '媒体资源库', '/admin/media/index', 'media.*', 'media', 'bi bi-images', 50, 1),
(16, 1, 0, '内容审核', '/admin/review/index', 'review.*', 'review', 'bi bi-patch-check', 60, 1),
(161, 1, 0, '审批工作流', '/admin/workflow/index', 'workflow.*', 'workflow', 'bi bi-journal-check', 70, 1),
(162, 1, 0, '审批记录', '/admin/workflow/records', 'workflow.*', 'workflow_records', 'bi bi-clock-history', 80, 1);

-- ====== 用户管理 (group_id=2) ======
INSERT INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(21, 2, 0, '用户列表', '/admin/user/index', 'user.*', 'user', 'bi bi-people', 10, 1),
(27, 2, 0, '会员等级', '/admin/member_level/index', 'member_level.*', 'member_level', 'bi bi-award', 20, 1),
(271, 2, 0, '权益配置', '/admin/member_benefit/index', 'member_benefit.*', 'member_benefit', 'bi bi-stars', 30, 1),
(272, 2, 0, '会员等级管理', '/admin/member_benefit/members', 'member_benefit.*', 'member_benefit_members', 'bi bi-people', 40, 1),
(28, 2, 0, '积分规则', '/admin/points_rule/index', 'points.*', 'points_rule', 'bi bi-star', 50, 1),
(29, 2, 0, '积分商品', '/admin/points_product/index', 'points_product.*', 'points_product', 'bi bi-gift', 60, 1),
(210, 2, 0, '兑换记录', '/admin/points_exchange/index', 'points_exchange.*', 'points_exchange', 'bi bi-arrow-left-right', 70, 1),
(52, 2, 0, '前台会员', '/admin/member/index', 'member.*', 'member', 'bi bi-person-badge', 80, 1),
(511, 2, 0, '邀请排行', '/admin/invite/index', 'invite.*', 'invite', 'bi bi-gift', 90, 1),
(53, 2, 0, '付费订单', '/admin/paid_order/index', 'paid_order.*', 'paid_order', 'bi bi-credit-card', 100, 1),
(56, 2, 0, '系统通知', '/admin/message/system', 'message.*', 'message_system', 'bi bi-bell', 110, 1),
(57, 2, 0, '发送通知', '/admin/message/sendSystem', 'message.*', 'message_send', 'bi bi-send-plus', 120, 1),
(510, 2, 0, 'OAuth配置', '/admin/oauth_config/index', 'oauth.*', 'oauth_config', 'bi bi-key-fill', 130, 1);

-- ====== 运营管理 (group_id=3) ======
INSERT INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(33, 3, 0, '轮播图管理', '/admin/banner/index', 'banner.*', 'banner', 'bi bi-images', 10, 1),
(34, 3, 0, '友情链接', '/admin/link/index', 'link.*', 'link', 'bi bi-link-45deg', 20, 1),
(35, 3, 0, '友链分组', '/admin/link_group/index', 'link.*', 'link_group', 'bi bi-folder2-open', 30, 1),
(36, 3, 0, '广告管理', '/admin/ad/index', 'ad.*', 'ad', 'bi bi-badge-ad', 40, 1),
(47, 3, 0, '表单管理', '/admin/form/index', 'form.*', 'form', 'bi bi-card-checklist', 50, 1),
(48, 3, 0, '优惠券', '/admin/coupon/index', 'coupon.*', 'coupon', 'bi bi-ticket-perforated', 60, 1),
(51, 3, 0, '评论管理', '/admin/comment/index', 'comment.*', 'comment', 'bi bi-chat-left-text', 70, 1),
(513, 3, 0, '评价管理', '/admin/rating/index', 'rating.*', 'rating', 'bi bi-star', 80, 1),
(81, 3, 0, '采集源管理', '/admin/collect_source/index', 'collect.*', 'collect_source', 'bi bi-cloud-download', 90, 1),
(82, 3, 0, '采集日志', '/admin/collect_log/index', 'collect.*', 'collect_log', 'bi bi-journal', 100, 1),
(83, 3, 0, '发布平台', '/admin/publish_platform/index', 'publish.*', 'publish_platform', 'bi bi-send', 110, 1),
(84, 3, 0, '发布记录', '/admin/publish_log/index', 'publish.*', 'publish_log', 'bi bi-clock-history', 120, 1),
(85, 3, 0, '邮件模板', '/admin/email_template/index', 'email.*', 'email_template', 'bi bi-envelope-paper', 130, 1),
(86, 3, 0, '邮件日志', '/admin/email_log/index', 'email.*', 'email_log', 'bi bi-envelope-check', 140, 1),
(54, 3, 0, '支付管理', '/admin/payment/index', 'payment.*', 'payment', 'bi bi-wallet2', 150, 1),
(55, 3, 0, '收入统计', '/admin/payment/revenue', 'payment.*', 'payment_revenue', 'bi bi-cash-stack', 160, 1);

-- ====== AI中心 (group_id=4) ======
INSERT INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(71, 4, 0, 'AI模型管理', '/admin/ai_model/index', 'ai_model.*', 'ai_model', 'bi bi-cpu', 10, 1),
(72, 4, 0, 'AI调用日志', '/admin/ai_log/index', 'ai_log.*', 'ai_log', 'bi bi-journal-code', 20, 1),
(73, 4, 0, 'AI批量生成', '/admin/ai_batch/index', 'ai_batch.*', 'ai_batch', 'bi bi-magic', 30, 1),
(74, 4, 0, 'AI内容模板', '/admin/ai_template/index', 'ai_template.*', 'ai_template', 'bi bi-file-earmark-text', 40, 1),
(76, 4, 0, 'AI翻译管理', '/admin/ai_translation/index', 'ai_translation.*', 'ai_translation', 'bi bi-translate', 50, 1),
(67, 4, 0, 'AI统计', '/admin/aiStat/index', 'ai_stat.*', 'ai_stat', 'bi bi-robot', 60, 1),
(68, 4, 0, '数据报告', '/admin/report/index', 'report.*', 'report', 'bi bi-graph-up-arrow', 70, 1);

-- ====== 界面设计 (group_id=5) ======
INSERT INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(75, 5, 0, '模板设计器', '/admin/template_design/index', 'template_design.*', 'template_design', 'bi bi-palette', 10, 1),
(93, 5, 0, '模板市场', '/admin/theme_market/index', 'theme_market.*', 'theme_market', 'bi bi-palette2', 20, 1),
(60, 5, 0, '数据看板', '/admin/dashboard/index', 'dashboard.*', 'dashboard', 'bi bi-speedometer2', 30, 1),
(66, 5, 0, '流量分析', '/admin/traffic/index', 'traffic.*', 'traffic', 'bi bi-graph-up', 40, 1),
(690, 5, 0, '分享追踪', '/admin/social_share/index', 'social_share.*', 'social_share', 'bi bi-share', 50, 1),
(91, 5, 0, '插件管理', '/admin/plugin/index', 'plugin.*', 'plugin', 'bi bi-plug', 60, 1),
(911, 5, 0, '插件市场', '/admin/plugin_market/index', 'plugin_market.*', 'plugin_market', 'bi bi-shop', 70, 1),
(92, 5, 0, '多语言管理', '/admin/language/index', 'language.*', 'language', 'bi bi-translate', 80, 1),
(94, 5, 0, 'API文档', '/admin/api_doc/index', 'apidoc.*', 'api_doc', 'bi bi-file-code', 90, 1);

-- ====== 系统设置 (group_id=6) ======
INSERT INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(41, 6, 0, '系统配置', '/admin/system/config', 'system.*', 'system_config', 'bi bi-gear', 10, 1),
(42, 6, 0, '操作日志', '/admin/log/index', 'system.log', 'log', 'bi bi-journal-text', 20, 1),
(43, 6, 0, '数据库备份', '/admin/backup/index', 'backup.*', 'backup', 'bi bi-database', 30, 1),
(44, 6, 0, '通知中心', '/admin/notification/index', 'notification.*', 'notification', 'bi bi-bell', 40, 1),
(480, 6, 0, '导入管理', '/admin/import/index', 'import.*', 'import', 'bi bi-upload', 50, 1),
(49, 6, 0, '邮件订阅', '/admin/email_subscriber/index', 'email_subscriber.*', 'email_subscriber', 'bi bi-envelope', 60, 1),
(50, 6, 0, '访问归档', '/admin/visit_archive/index', 'visit_archive.*', 'visit_archive', 'bi bi-archive', 70, 1),
(58, 6, 0, '验证码配置', '/admin/captcha/config', 'captcha.*', 'captcha', 'bi bi-shield-check', 80, 1),
(59, 6, 0, '存储配置', '/admin/storage/config', 'storage.*', 'storage_config', 'bi bi-hdd-network', 90, 1),
(61, 6, 0, 'SEO管理', '/admin/seo/index', 'seo.*', 'seo', 'bi bi-search', 100, 1),
(64, 6, 0, 'SEO关键词', '/admin/seo_keyword/index', 'seo_keyword.*', 'seo_keyword', 'bi bi-hash', 110, 1),
(65, 6, 0, '关键词分组', '/admin/seo_keyword/group', 'seo_keyword.*', 'seo_keyword_group', 'bi bi-folder', 120, 1),
(62, 6, 0, '数据导出', '/admin/export/index', 'export.*', 'export', 'bi bi-download', 130, 1),
(621, 6, 0, '高级导出', '/admin/export/dialog', 'export_advanced.*', 'export_dialog', 'bi bi-file-earmark-arrow-down', 140, 1),
(63, 6, 0, 'API令牌', '/admin/token/index', 'token.*', 'token', 'bi bi-key', 150, 1),
(69, 6, 0, '系统监控', '/admin/monitor/index', 'monitor.*', 'monitor', 'bi bi-speedometer2', 160, 1);

-- --------------------------------------------------------
-- 5. 新增 points_shop_enabled 配置（如不存在）
-- 注：已修正列名，原 SQL 用 key/label/status 与实表 name/remark 不匹配
-- --------------------------------------------------------
INSERT IGNORE INTO `i8j_config` (`name`, `value`, `type`, `group`, `remark`, `sort`) VALUES
('points_shop_enabled', '1', 'switch', 'points', '积分商城开关', 10);

-- --------------------------------------------------------
-- 6. 新增 menu_manager 权限到 i8j_permission 表（如存在该表）
--    如果不存在则忽略，权限系统从 config/permission.php 读取
-- --------------------------------------------------------
