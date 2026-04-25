-- ============================================================
-- AI-CMS V2.2 数据库迁移脚本
-- 从 V2.1 升级到 V2.2 需要执行的SQL
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------
-- 1. 媒体资源表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_media` (
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
-- 2. 轮播图表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_banner` (
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
-- 3. 友情链接表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_link` (
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
-- 4. 审核记录表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_review` (
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
-- 5. 内容表添加 FULLTEXT 索引（搜索增强）
-- -----------------------------------------------------------
ALTER TABLE `i8j_content` ADD FULLTEXT INDEX `ft_title_excerpt` (`title`, `excerpt`);

-- -----------------------------------------------------------
-- 6. 系统配置扩展（V2.2新增配置项）
-- -----------------------------------------------------------
INSERT INTO `i8j_config` (`group`, `name`, `value`, `type`, `sort`, `remark`) VALUES
('upload', 'upload_video_ext', 'mp4,webm,ogg', 'text', 3, '允许的视频格式'),
('upload', 'upload_file_ext', 'pdf,doc,docx,xls,xlsx,zip,rar', 'text', 4, '允许的文件格式'),
('basic', 'site_copyright', '', 'text', 6, '版权信息'),
('basic', 'site_stat_code', '', 'textarea', 7, '统计代码');

SET FOREIGN_KEY_CHECKS = 1;
