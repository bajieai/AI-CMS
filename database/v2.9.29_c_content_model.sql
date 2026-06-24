-- ============================================================
-- AI-CMS V2.9.29 Sprint C 数据库变更脚本
-- 主题：内容模型差异化（扩展现有V2.9.27体系，不新建表）
-- 变更：ALTER i8j_content_model(2字段) + ALTER i8j_cate(1字段) + INSERT 5预置模型
-- ============================================================

SET NAMES utf8mb4;
SET @db = DATABASE();

-- ------------------------------------------------------------
-- 1. i8j_content_model 增加 default_list_template / default_detail_template 字段 (C-1)
-- ------------------------------------------------------------
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_content_model' AND COLUMN_NAME = 'default_list_template');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_content_model` ADD COLUMN `default_list_template` VARCHAR(200) NOT NULL DEFAULT \'\' COMMENT \'默认列表模板(list_{code}.html)\' AFTER `template_file`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_content_model' AND COLUMN_NAME = 'default_detail_template');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_content_model` ADD COLUMN `default_detail_template` VARCHAR(200) NOT NULL DEFAULT \'\' COMMENT \'默认详情模板(detail_{code}.html)\' AFTER `default_list_template`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 2. i8j_cate 增加 content_model_code 字段 (C-1)
-- 用于栏目直接指定内容模型code（与model_id互补，code更直观）
-- ------------------------------------------------------------
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_cate' AND COLUMN_NAME = 'content_model_code');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_cate` ADD COLUMN `content_model_code` VARCHAR(50) NOT NULL DEFAULT \'\' COMMENT \'内容模型code(留空=通用/article)\' AFTER `model_id`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_cate' AND INDEX_NAME = 'idx_content_model_code');
SET @sql = IF(@idx = 0, 'ALTER TABLE `i8j_cate` ADD INDEX `idx_content_model_code` (`content_model_code`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 3. i8j_cate 增加 list_template / detail_template 自定义模板字段 (C-1)
-- 栏目可覆盖模型默认模板（Fallback链：栏目自定义 → 模型默认 → 系统默认）
-- ------------------------------------------------------------
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_cate' AND COLUMN_NAME = 'list_template');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_cate` ADD COLUMN `list_template` VARCHAR(200) NOT NULL DEFAULT \'\' COMMENT \'自定义列表模板(留空=使用模型默认)\' AFTER `content_model_code`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_cate' AND COLUMN_NAME = 'detail_template');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_cate` ADD COLUMN `detail_template` VARCHAR(200) NOT NULL DEFAULT \'\' COMMENT \'自定义详情模板(留空=使用模型默认)\' AFTER `list_template`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 4. 更新现有模型的默认模板 (C-1)
-- 按code命名规范: list_{code}.html / detail_{code}.html
-- ------------------------------------------------------------
UPDATE `i8j_content_model` SET `default_list_template` = 'list_article', `default_detail_template` = 'detail_article' WHERE `code` = 'article' AND `default_list_template` = '';
UPDATE `i8j_content_model` SET `default_list_template` = 'list_product', `default_detail_template` = 'detail_product' WHERE `code` = 'product' AND `default_list_template` = '';
UPDATE `i8j_content_model` SET `default_list_template` = 'list_case', `default_detail_template` = 'detail_case' WHERE `code` = 'case' AND `default_list_template` = '';
UPDATE `i8j_content_model` SET `default_list_template` = 'list_download', `default_detail_template` = 'detail_download' WHERE `code` = 'download' AND `default_list_template` = '';
UPDATE `i8j_content_model` SET `default_list_template` = 'list_image', `default_detail_template` = 'detail_image' WHERE `code` = 'model_image' AND `default_list_template` = '';
UPDATE `i8j_content_model` SET `default_list_template` = 'list_video', `default_detail_template` = 'detail_video' WHERE `code` = 'model_video' AND `default_list_template` = '';

-- ------------------------------------------------------------
-- 5. INSERT 5个预置模型（幂等：不存在才插入）
-- 确保 article/image/download/product/video 五大模型存在
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_content_model` (`id`, `name`, `code`, `description`, `icon`, `type`, `seo_title`, `seo_keywords`, `seo_description`, `template_file`, `default_list_template`, `default_detail_template`, `status`, `sort`, `create_time`, `update_time`) VALUES
(10, '文章模型', 'article', '标准文章模型，适用于新闻、博客、资讯等内容类型', 'bi bi-file-text', 3, '{$title} - {$site_name}', '{$title},文章,资讯', '{$title}文章详情页', 'content/article_show', 'list_article', 'detail_article', 1, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(11, '图片模型', 'image', '图片图集模型，适用于画廊、作品集、相册等视觉内容', 'bi bi-images', 3, '{$title} - 图集 - {$site_name}', '{$title},图片,图集', '{$title}图片展示页', 'content/image_show', 'list_image', 'detail_image', 1, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(12, '下载模型', 'download', '下载资源模型，适用于软件、文档、模板等资源下载', 'bi bi-download', 4, '{$title} - 下载 - {$site_name}', '{$title},下载,资源', '{$title}资源下载页', 'content/download_show', 'list_download', 'detail_download', 1, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(13, '产品模型', 'product', '产品展示模型，适用于商品、服务展示等电商场景', 'bi bi-box', 1, '{$title} - 产品 - {$site_name}', '{$title},产品,商品', '{$title}产品详情页', 'content/product_show', 'list_product', 'detail_product', 1, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(14, '视频模型', 'video', '视频播放模型，适用于视频站、课程、演示等多媒体内容', 'bi bi-play-btn', 3, '{$title} - 视频 - {$site_name}', '{$title},视频,播放', '{$title}视频播放页', 'content/video_show', 'list_video', 'detail_video', 1, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ------------------------------------------------------------
-- 6. 系统配置：内容模型差异化启用 (C-1)
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_config` (`name`, `value`, `group`) VALUES
('content_model_diff_enabled', '1', 'content'),
('content_model_fallback_enabled', '1', 'content');

-- ------------------------------------------------------------
-- 7. 菜单项 (V2.9.29 Sprint C)
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(520, 4, 0, '内容模型管理', '/admin/content_model/index', 'content_model.*', 'content_model', 'bi bi-diagram-2', 80, 1);
