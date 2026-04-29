-- V2.3 自定义变量表 + 功能模块表（独立执行脚本）
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `i8j_custom_var` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `value` text,
  `remark` varchar(255) DEFAULT '',
  `sort` int NOT NULL DEFAULT 0,
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='自定义变量表';

CREATE TABLE IF NOT EXISTS `i8j_module` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL DEFAULT '',
  `name` varchar(50) NOT NULL DEFAULT '',
  `description` varchar(255) DEFAULT '',
  `icon` varchar(50) DEFAULT '',
  `category` varchar(30) NOT NULL DEFAULT 'core',
  `is_system` tinyint NOT NULL DEFAULT 0,
  `is_enabled` tinyint NOT NULL DEFAULT 1,
  `sort` int NOT NULL DEFAULT 0,
  `config_group` varchar(30) DEFAULT '',
  `menu_ids` varchar(100) DEFAULT '',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_category` (`category`),
  KEY `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='功能模块注册表';

INSERT INTO `i8j_module` (`code`, `name`, `description`, `icon`, `category`, `is_system`, `is_enabled`, `sort`, `menu_ids`) VALUES
('content', '内容管理', '内容发布、分类、标签、回收站', 'bi-file-text', 'core', 1, 1, 1, '[11,12,13,14,15,16]'),
('user', '用户管理', '后台用户管理', 'bi-people', 'core', 1, 1, 2, '[21]'),
('banner', '轮播图', '首页轮播图管理', 'bi-images', 'operation', 0, 1, 10, '[33]'),
('link', '友情链接', '友链及分组管理', 'bi-link-45deg', 'operation', 0, 1, 11, '[34,35]'),
('ad', '广告系统', '广告位与广告管理', 'bi-badge-ad', 'operation', 0, 1, 12, '[36]'),
('comment', '评论系统', '前台评论与审核', 'bi-chat-left-text', 'interaction', 0, 1, 20, '[51]'),
('member', '前台会员', '会员注册登录与互动', 'bi-person-badge', 'interaction', 0, 1, 21, '[52]'),
('seo', 'SEO管理', 'Sitemap、robots.txt、结构化数据', 'bi-search', 'seo_data', 0, 1, 30, '[61]'),
('export', '数据导出', 'Excel/CSV导入导出', 'bi-download', 'seo_data', 0, 1, 31, '[62]'),
('token', 'API令牌', 'RESTful API Token管理', 'bi-key', 'seo_data', 0, 1, 32, '[63]'),
('notification', '消息通知', '站内通知与提醒', 'bi-bell', 'extension', 0, 1, 40, '[44]'),
('backup', '数据库备份', '数据库备份与恢复', 'bi-database', 'extension', 0, 1, 41, '[43]')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

SET FOREIGN_KEY_CHECKS = 1;
