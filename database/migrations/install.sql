-- ============================================================
-- AI-CMS V2.0 数据库建表脚本
-- 方案B：删除字段前缀，简化表名
-- 表前缀：{prefix}（安装时可配置，默认i8j_）
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------
-- 1. 内容主表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}content` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `content` longtext COMMENT '内容',
  `excerpt` text COMMENT '摘要',
  `type` tinyint NOT NULL DEFAULT 1 COMMENT '类型:1产品/2案例/3新闻/4下载/5招聘/6单页',
  `status` tinyint NOT NULL DEFAULT 0 COMMENT '状态:0草稿/1待审/2已发布/-1已删除',
  `cate_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '分类ID',
  `user_id` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '作者ID',
  `cover` varchar(255) DEFAULT '' COMMENT '封面图',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `is_top` tinyint NOT NULL DEFAULT 0 COMMENT '是否置顶:0否/1是',
  `views` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '浏览量',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_type_status` (`type`, `status`),
  KEY `idx_cate` (`cate_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容表';

-- -----------------------------------------------------------
-- 2. 内容扩展表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}content_ext` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `content_id` bigint UNSIGNED NOT NULL COMMENT '内容ID',
  `type` tinyint NOT NULL COMMENT '内容类型',
  `data` json DEFAULT NULL COMMENT '扩展数据(JSON)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_content_type` (`content_id`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容扩展表';

-- -----------------------------------------------------------
-- 3. 分类表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}cate` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '分类名称',
  `type` tinyint NOT NULL DEFAULT 1 COMMENT '分类类型:1产品/2案例/3新闻/4下载/5招聘/6单页',
  `parent_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级ID',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态:0禁用/1启用',
  `seo_title` varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `seo_description` varchar(500) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分类表';

-- -----------------------------------------------------------
-- 4. 标签表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}tag` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '标签名称',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='标签表';

-- -----------------------------------------------------------
-- 5. 内容-标签关联表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}content_tag` (
  `content_id` bigint UNSIGNED NOT NULL COMMENT '内容ID',
  `tag_id` int UNSIGNED NOT NULL COMMENT '标签ID',
  PRIMARY KEY (`content_id`, `tag_id`),
  KEY `idx_tag` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容标签关联表';

-- -----------------------------------------------------------
-- 6. 用户表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}user` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(50) DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) DEFAULT '' COMMENT '头像',
  `role_id` tinyint NOT NULL DEFAULT 3 COMMENT '角色:1超管/2管理员/3编辑',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态:0禁用/1启用',
  `last_login_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后登录时间',
  `last_login_ip` varchar(45) DEFAULT '' COMMENT '最后登录IP',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- -----------------------------------------------------------
-- 7. 系统配置表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}config` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `group` varchar(30) NOT NULL DEFAULT '' COMMENT '分组',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '配置名',
  `value` text COMMENT '配置值',
  `type` varchar(20) NOT NULL DEFAULT 'text' COMMENT '类型:text/textarea/number/switch/select',
  `options` varchar(500) DEFAULT '' COMMENT '选项(JSON,select/switch用)',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `remark` varchar(255) DEFAULT '' COMMENT '说明',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

-- -----------------------------------------------------------
-- 8. 操作日志表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}log` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `module` varchar(30) NOT NULL DEFAULT '' COMMENT '模块',
  `action` varchar(50) NOT NULL DEFAULT '' COMMENT '操作',
  `target` varchar(100) DEFAULT '' COMMENT '操作对象',
  `ip` varchar(45) DEFAULT '' COMMENT 'IP地址',
  `data` text COMMENT '操作数据',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表';

-- -----------------------------------------------------------
-- 8. 内容版本历史表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}content_version` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `content_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '内容ID',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `content` longtext COMMENT '正文内容',
  `excerpt` text COMMENT '摘要',
  `cover` varchar(255) DEFAULT '' COMMENT '封面图',
  `cate_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '分类ID',
  `status` tinyint NOT NULL DEFAULT 0 COMMENT '状态',
  `ext_data` text COMMENT '扩展字段数据(JSON)',
  `tag_ids` varchar(255) DEFAULT '' COMMENT '标签ID集合',
  `user_id` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作人ID',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '版本创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容版本历史表';

-- -----------------------------------------------------------
-- 初始数据
-- -----------------------------------------------------------

-- 默认超级管理员（密码: admin123）
INSERT INTO `{prefix}user` (`id`, `username`, `email`, `password`, `nickname`, `role_id`, `status`, `create_time`, `update_time`) VALUES
(1, 'admin', 'admin@aicms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '超级管理员', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 默认系统配置
INSERT INTO `{prefix}config` (`group`, `name`, `value`, `type`, `sort`, `remark`) VALUES
('basic', 'site_name', 'AI-CMS', 'text', 1, '网站名称'),
('basic', 'site_keywords', 'AI,CMS,内容管理', 'text', 2, '网站关键词'),
('basic', 'site_description', 'AI驱动的企业信息管理系统', 'textarea', 3, '网站描述'),
('basic', 'site_logo', '', 'text', 4, '网站Logo'),
('basic', 'site_icp', '', 'text', 5, 'ICP备案号'),
('upload', 'upload_max_size', '10', 'number', 1, '上传大小限制(MB)'),
('upload', 'upload_image_ext', 'jpg,jpeg,png,gif,webp,svg', 'text', 2, '允许的图片格式'),
('ai', 'ai_enabled', '1', 'switch', 1, '启用AI功能'),
('ai', 'ai_default_model', 'deepseek-chat', 'text', 2, '默认AI模型');

-- 默认分类
INSERT INTO `{prefix}cate` (`name`, `type`, `parent_id`, `sort`, `status`, `create_time`, `update_time`) VALUES
('产品中心', 1, 0, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('成功案例', 2, 0, 2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('新闻动态', 3, 0, 3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('资料下载', 4, 0, 4, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('人才招聘', 5, 0, 5, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- -----------------------------------------------------------
-- V2.2 新增表：媒体资源表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}media` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '上传用户ID',
  `filename` varchar(255) NOT NULL DEFAULT '' COMMENT '原始文件名',
  `filepath` varchar(500) NOT NULL DEFAULT '' COMMENT '文件路径',
  `filetype` varchar(20) NOT NULL DEFAULT 'image' COMMENT '文件类型:image/video/file',
  `mimetype` varchar(100) DEFAULT '' COMMENT 'MIME类型',
  `filesize` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '文件大小(字节)',
  `cate_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '分类ID',
  `alt_text` varchar(255) DEFAULT '' COMMENT '替代文本/描述',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_type` (`filetype`),
  KEY `idx_cate` (`cate_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='媒体资源表';

-- -----------------------------------------------------------
-- V2.2 新增表：轮播图表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}banner` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `image` varchar(500) NOT NULL DEFAULT '' COMMENT '图片地址',
  `link` varchar(500) DEFAULT '' COMMENT '链接地址',
  `target` varchar(10) DEFAULT '_self' COMMENT '打开方式:_self/_blank',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态:0禁用/1启用',
  `start_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '开始时间',
  `end_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '结束时间',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='轮播图表';

-- -----------------------------------------------------------
-- V2.2 新增表：友情链接表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}link` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '网站名称',
  `url` varchar(500) NOT NULL DEFAULT '' COMMENT '链接地址',
  `logo` varchar(500) DEFAULT '' COMMENT 'Logo地址',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态:0禁用/1启用',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='友情链接表';

-- -----------------------------------------------------------
-- V2.2 新增表：审核记录表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}review` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `content_id` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '内容ID',
  `user_id` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核人ID',
  `action` varchar(20) NOT NULL DEFAULT '' COMMENT '操作:approve通过/reject驳回',
  `remark` text COMMENT '审核意见',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审核记录表';

-- -----------------------------------------------------------
-- V2.2 搜索增强：内容表 FULLTEXT 索引
-- -----------------------------------------------------------
ALTER TABLE `{prefix}content` ADD FULLTEXT INDEX `ft_title_excerpt` (`title`, `excerpt`);

-- -----------------------------------------------------------
-- V2.2 新增系统配置
-- -----------------------------------------------------------
INSERT INTO `{prefix}config` (`group`, `name`, `value`, `type`, `sort`, `remark`) VALUES
('upload', 'upload_video_ext', 'mp4,webm,ogg', 'text', 3, '允许的视频格式'),
('upload', 'upload_file_ext', 'pdf,doc,docx,xls,xlsx,zip,rar', 'text', 4, '允许的文件格式'),
('basic', 'site_copyright', '', 'text', 6, '版权信息'),
('basic', 'site_stat_code', '', 'textarea', 7, '统计代码');

SET FOREIGN_KEY_CHECKS = 1;
