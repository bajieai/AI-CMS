-- ============================================================
-- AI-CMS V2.3 数据库增量升级脚本
-- 基础：V2.2 → V2.3
-- 变更：新增14张表 + 2张ALTER + 系统配置项
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------
-- 1. 消息通知表（receiver_type+receiver_id统一方案）
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_notification` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `receiver_type` varchar(20) NOT NULL DEFAULT 'admin' COMMENT '接收者类型:admin/member/system',
  `receiver_id` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '接收者ID',
  `type` varchar(20) NOT NULL DEFAULT 'system' COMMENT '类型:system/review/publish/title',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '通知标题',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '通知内容',
  `link` varchar(500) DEFAULT '' COMMENT '跳转链接',
  `is_read` tinyint NOT NULL DEFAULT 0 COMMENT '是否已读:0否/1是',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_receiver_read` (`receiver_type`, `receiver_id`, `is_read`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='消息通知表';

-- -----------------------------------------------------------
-- 2. 前台会员表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_member` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(50) DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) DEFAULT '' COMMENT '头像',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态:0禁用/1启用',
  `last_login_time` int UNSIGNED NOT NULL DEFAULT 0,
  `last_login_ip` varchar(45) DEFAULT '',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='前台会员表';

-- -----------------------------------------------------------
-- 3. 会员OAuth绑定表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_member_oauth` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '会员ID',
  `provider` varchar(20) NOT NULL DEFAULT '' COMMENT '平台:gitee/wechat/qq/weibo',
  `openid` varchar(100) NOT NULL DEFAULT '' COMMENT '平台唯一标识',
  `unionid` varchar(100) DEFAULT '' COMMENT '平台UnionID',
  `access_token` varchar(255) DEFAULT '' COMMENT 'Access Token',
  `refresh_token` varchar(255) DEFAULT '' COMMENT 'Refresh Token',
  `expire_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Token过期时间',
  `nickname` varchar(50) DEFAULT '' COMMENT '平台昵称',
  `avatar` varchar(255) DEFAULT '' COMMENT '平台头像',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provider_openid` (`provider`, `openid`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员OAuth绑定表';

-- -----------------------------------------------------------
-- 4. 会员点赞表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_member_like` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '会员ID',
  `content_id` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '内容ID',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_content` (`member_id`, `content_id`),
  KEY `idx_content` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员点赞表';

-- -----------------------------------------------------------
-- 5. 会员收藏表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_member_favorite` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '会员ID',
  `content_id` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '内容ID',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_content` (`member_id`, `content_id`),
  KEY `idx_content` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员收藏表';

-- -----------------------------------------------------------
-- 6. 评论表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_comment` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `content_id` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '内容ID',
  `member_id` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '会员ID(0为游客)',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
  `email` varchar(100) DEFAULT '' COMMENT '邮箱',
  `content` text COMMENT '评论内容',
  `parent_id` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '父评论ID(0为顶级)',
  `status` tinyint NOT NULL DEFAULT 0 COMMENT '状态:0待审/1已通过/-1已拒绝',
  `ip` varchar(45) DEFAULT '' COMMENT 'IP地址',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_content_status` (`content_id`, `status`),
  KEY `idx_member` (`member_id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='评论表';

-- -----------------------------------------------------------
-- 7. API令牌表（双模式认证：Bearer + HMAC）
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_api_token` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '令牌名称',
  `auth_type` varchar(20) NOT NULL DEFAULT 'bearer' COMMENT '认证类型:bearer/hmac',
  `token_hash` varchar(64) NOT NULL DEFAULT '' COMMENT '令牌哈希(sha256)',
  `secret_key` varchar(64) DEFAULT '' COMMENT 'HMAC密钥(仅auth_type=hmac时有效)',
  `scopes` varchar(255) NOT NULL DEFAULT '*' COMMENT '权限范围(*/content.read/content.write等)',
  `rate_limit` int NOT NULL DEFAULT 60 COMMENT '速率限制(次/小时)',
  `last_used_time` int UNSIGNED NOT NULL DEFAULT 0,
  `expire_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '过期时间(0永不过期)',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态:0禁用/1启用',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token_hash` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='API令牌表';

-- -----------------------------------------------------------
-- 8. 友情链接分组表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_link_group` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '分组名称',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态:0禁用/1启用',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='友情链接分组表';

-- -----------------------------------------------------------
-- 9. SEO死链检测表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_seo_deadlinks` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `url` varchar(500) NOT NULL DEFAULT '' COMMENT '死链URL',
  `status_code` int NOT NULL DEFAULT 0 COMMENT 'HTTP状态码',
  `source` varchar(255) DEFAULT '' COMMENT '来源页面',
  `check_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '检测时间',
  `is_fixed` tinyint NOT NULL DEFAULT 0 COMMENT '是否已修复:0否/1是',
  PRIMARY KEY (`id`),
  KEY `idx_is_fixed` (`is_fixed`),
  KEY `idx_check_time` (`check_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEO死链检测表';

-- -----------------------------------------------------------
-- 10. 广告位表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_ad_position` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '广告位名称',
  `code` varchar(50) NOT NULL DEFAULT '' COMMENT '广告位标识(唯一)',
  `width` int NOT NULL DEFAULT 0 COMMENT '宽度(px)',
  `height` int NOT NULL DEFAULT 0 COMMENT '高度(px)',
  `description` varchar(255) DEFAULT '' COMMENT '描述',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态:0禁用/1启用',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='广告位表';

-- -----------------------------------------------------------
-- 11. 广告表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_ad` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `position_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '广告位ID',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '广告标题',
  `image` varchar(500) DEFAULT '' COMMENT '图片地址',
  `link` varchar(500) DEFAULT '' COMMENT '链接地址',
  `content` text COMMENT '代码/富文本内容',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态:0禁用/1启用',
  `start_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '开始时间',
  `end_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '结束时间',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_position_status` (`position_id`, `status`),
  KEY `idx_status_sort` (`status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='广告表';

-- -----------------------------------------------------------
-- 12. 广告统计表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_ad_stat` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `ad_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '广告ID',
  `stat_date` date NOT NULL COMMENT '统计日期',
  `views` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '展示次数',
  `clicks` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '点击次数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ad_date` (`ad_id`, `stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='广告统计表';

-- -----------------------------------------------------------
-- 13. 邮件订阅表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_email_subscriber` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱地址',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态:1订阅/0退订',
  `source` varchar(50) DEFAULT '' COMMENT '来源页面',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件订阅表';

-- -----------------------------------------------------------
-- 14. 访问日志表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_visit_log` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `content_id` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '内容ID(0为首页/列表)',
  `ip` varchar(45) DEFAULT '' COMMENT 'IP地址',
  `ua` varchar(500) DEFAULT '' COMMENT 'User-Agent',
  `visit_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '访问时间',
  `page_url` varchar(500) DEFAULT '' COMMENT '访问页面',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_visit_time` (`visit_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='访问日志表';

-- -----------------------------------------------------------
-- ALTER 1：i8j_content 表扩展（SEO+互动+定时发布）
-- 拆分为独立语句，避免单条ALTER中某列已存在导致全部失败
-- -----------------------------------------------------------
ALTER TABLE `i8j_content` ADD COLUMN `publish_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '定时发布时间(0立即)' AFTER `status`;
ALTER TABLE `i8j_content` ADD COLUMN `seo_title` varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO标题' AFTER `publish_time`;
ALTER TABLE `i8j_content` ADD COLUMN `seo_keywords` varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO关键词' AFTER `seo_title`;
ALTER TABLE `i8j_content` ADD COLUMN `seo_description` varchar(500) NOT NULL DEFAULT '' COMMENT 'SEO描述' AFTER `seo_keywords`;
ALTER TABLE `i8j_content` ADD COLUMN `hotness` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '热度值' AFTER `views`;
ALTER TABLE `i8j_content` ADD COLUMN `is_recommend` tinyint UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否推荐:0否/1是' AFTER `hotness`;
ALTER TABLE `i8j_content` ADD COLUMN `like_count` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数' AFTER `is_recommend`;
ALTER TABLE `i8j_content` ADD COLUMN `comment_count` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '评论数' AFTER `like_count`;
ALTER TABLE `i8j_content` ADD INDEX `idx_publish_time` (`publish_time`);
ALTER TABLE `i8j_content` ADD INDEX `idx_hotness` (`hotness`);
ALTER TABLE `i8j_content` ADD INDEX `idx_is_recommend` (`is_recommend`);

-- -----------------------------------------------------------
-- ALTER 2：i8j_link 表扩展（分组+描述+联系+申请）
-- -----------------------------------------------------------
ALTER TABLE `i8j_link` ADD COLUMN `group_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '分组ID' AFTER `title`;
ALTER TABLE `i8j_link` ADD COLUMN `description` varchar(255) DEFAULT '' COMMENT '网站描述' AFTER `group_id`;
ALTER TABLE `i8j_link` ADD COLUMN `contact` varchar(100) DEFAULT '' COMMENT '联系人/邮箱' AFTER `description`;
ALTER TABLE `i8j_link` ADD COLUMN `is_apply` tinyint NOT NULL DEFAULT 0 COMMENT '是否申请中:0否/1是' AFTER `contact`;
ALTER TABLE `i8j_link` ADD INDEX `idx_group_status` (`group_id`, `status`);

-- -----------------------------------------------------------
-- ALTER 3：性能优化索引（V2.3.1）
-- -----------------------------------------------------------
ALTER TABLE `i8j_content` ADD INDEX `idx_status_create_time` (`status`, `create_time`);
ALTER TABLE `i8j_content` ADD INDEX `idx_status_cate` (`status`, `cate_id`);
ALTER TABLE `i8j_comment` ADD INDEX `idx_parent_status` (`parent_id`, `status`);

-- -----------------------------------------------------------
-- 新增系统配置项
-- -----------------------------------------------------------
INSERT INTO `i8j_config` (`group`, `name`, `value`, `type`, `sort`, `remark`) VALUES
('seo', 'seo_sitemap_enabled', '1', 'switch', 1, '启用Sitemap自动生成'),
('seo', 'seo_sitemap_frequency', 'daily', 'select', 2, 'Sitemap更新频率'),
('seo', 'seo_robots_txt', 'User-agent: *\nAllow: /\nDisallow: /admin/', 'textarea', 3, 'robots.txt内容'),
('comment', 'comment_enabled', '1', 'switch', 1, '启用评论功能'),
('comment', 'comment_auto_approve', '0', 'switch', 2, '评论自动审核通过'),
('comment', 'comment_captcha', '1', 'switch', 3, '评论验证码'),
('notification', 'notification_enabled', '1', 'switch', 1, '启用消息通知'),
('member', 'member_register_enabled', '1', 'switch', 1, '启用前台注册'),
('member', 'member_oauth_gitee_enabled', '1', 'switch', 2, '启用Gitee登录'),
('ad', 'ad_enabled', '1', 'switch', 1, '启用广告系统')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- -----------------------------------------------------------
-- 15. 自定义变量表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_custom_var` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '变量名(英文,模板调用用)',
  `value` text COMMENT '变量值',
  `remark` varchar(255) DEFAULT '' COMMENT '备注说明',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='自定义变量表';

-- -----------------------------------------------------------
-- 16. 功能模块注册表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_module` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL DEFAULT '' COMMENT '模块标识(唯一)',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '模块名称',
  `description` varchar(255) DEFAULT '' COMMENT '模块描述',
  `icon` varchar(50) DEFAULT '' COMMENT 'Bootstrap Icons类名',
  `category` varchar(30) NOT NULL DEFAULT 'core' COMMENT '分类:core/operation/interaction/seo_data/extension',
  `is_system` tinyint NOT NULL DEFAULT 0 COMMENT '系统模块:0否/1是',
  `is_enabled` tinyint NOT NULL DEFAULT 1 COMMENT '是否启用:0禁用/1启用',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `config_group` varchar(30) DEFAULT '' COMMENT '关联配置组',
  `menu_ids` varchar(100) DEFAULT '' COMMENT '关联菜单ID(JSON数组)',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_category` (`category`),
  KEY `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='功能模块注册表';

-- -----------------------------------------------------------
-- 功能模块初始数据
-- -----------------------------------------------------------
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
