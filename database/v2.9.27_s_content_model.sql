-- ============================================================
-- AI-CMS V2.9.27 Sprint S 数据库变更脚本
-- 主题：内容模型差异化
-- 功能：S-1~S-8 + S-3d模型专属分类 + S-3e内容关系 + S-5c一键切换
-- ============================================================

SET NAMES utf8mb4;
SET @db = DATABASE();

-- ------------------------------------------------------------
-- 1. i8j_content_model 增加 SEO 相关字段 (S-1/S-6)
-- ------------------------------------------------------------
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_content_model' AND COLUMN_NAME = 'seo_title');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_content_model` ADD COLUMN `seo_title` VARCHAR(200) NOT NULL DEFAULT \'\' COMMENT \'SEO标题模板\' AFTER `description`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_content_model' AND COLUMN_NAME = 'seo_keywords');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_content_model` ADD COLUMN `seo_keywords` VARCHAR(500) NOT NULL DEFAULT \'\' COMMENT \'SEO关键词模板\' AFTER `seo_title`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_content_model' AND COLUMN_NAME = 'seo_description');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_content_model` ADD COLUMN `seo_description` VARCHAR(500) NOT NULL DEFAULT \'\' COMMENT \'SEO描述模板\' AFTER `seo_keywords`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_content_model' AND COLUMN_NAME = 'template_file');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_content_model` ADD COLUMN `template_file` VARCHAR(200) NOT NULL DEFAULT \'\' COMMENT \'前台专属模板文件名\' AFTER `seo_description`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 2. i8j_content_model_field 增加新字段类型支持 (S-2)
-- 增加：validation规则、is_searchable、is_list_show
-- ------------------------------------------------------------
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_content_model_field' AND COLUMN_NAME = 'validation');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_content_model_field` ADD COLUMN `validation` VARCHAR(500) NOT NULL DEFAULT \'\' COMMENT \'验证规则(JSON)\' AFTER `placeholder`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_content_model_field' AND COLUMN_NAME = 'is_searchable');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_content_model_field` ADD COLUMN `is_searchable` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT \'是否可搜索(1是/0否)\' AFTER `validation`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_content_model_field' AND COLUMN_NAME = 'is_list_show');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_content_model_field` ADD COLUMN `is_list_show` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT \'列表页是否显示(1是/0否)\' AFTER `is_searchable`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 更新字段类型注释，新增 rich_text/datetime/color/tags/location 类型
ALTER TABLE `i8j_content_model_field` MODIFY COLUMN `type` VARCHAR(30) NOT NULL DEFAULT 'text' COMMENT '字段类型(text/textarea/rich_text/number/select/radio/checkbox/date/datetime/image/file/color/tags/location)';

-- ------------------------------------------------------------
-- 3. i8j_cate 增加 model_id 字段 (S-3d 模型专属分类)
-- ------------------------------------------------------------
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_cate' AND COLUMN_NAME = 'model_id');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_cate` ADD COLUMN `model_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT \'内容模型ID(0=通用分类)\' AFTER `type`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_cate' AND INDEX_NAME = 'idx_model_id');
SET @sql = IF(@idx = 0, 'ALTER TABLE `i8j_cate` ADD INDEX `idx_model_id` (`model_id`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 4. i8j_content 增加 template 字段 (S-5c 一键切换)
-- ------------------------------------------------------------
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_content' AND COLUMN_NAME = 'template');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_content` ADD COLUMN `template` VARCHAR(100) NOT NULL DEFAULT \'\' COMMENT \'前台展示模板(空=使用模型默认)\' AFTER `model_id`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 5. 内容关系表 (S-3e 内容关系)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_content_relation` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `content_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '主内容ID',
    `relation_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联内容ID',
    `relation_type` VARCHAR(30) NOT NULL DEFAULT 'related' COMMENT '关系类型(related/previous_next/recommended/similar)',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_content_relation_type` (`content_id`, `relation_id`, `relation_type`),
    KEY `idx_content_id` (`content_id`),
    KEY `idx_relation_id` (`relation_id`),
    KEY `idx_relation_type` (`relation_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容关系表';

-- ------------------------------------------------------------
-- 6. 内容模型-模板映射表 (S-5 模型与模板推荐联动)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_content_model_template_map` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `model_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '内容模型ID',
    `template_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '模板ID(i8j_template_store)',
    `tag_match` TEXT COMMENT '标签匹配规则(JSON)',
    `priority` TINYINT UNSIGNED NOT NULL DEFAULT 50 COMMENT '优先级(1-100,越大越优先)',
    `is_default` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认模板(1是/0否)',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(1启用/0禁用)',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_model_template` (`model_id`, `template_id`),
    KEY `idx_model_id` (`model_id`),
    KEY `idx_template_id` (`template_id`),
    KEY `idx_status_priority` (`status`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容模型-模板映射';

-- ------------------------------------------------------------
-- 7. 内容模型数据统计表 (S-7 数据统计)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_content_model_stats` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `model_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '内容模型ID',
    `stat_date` DATE NOT NULL COMMENT '统计日期',
    `total_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '内容总数',
    `published_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '已发布数',
    `draft_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '草稿数',
    `pending_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '待审核数',
    `new_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '当日新增数',
    `total_views` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '总浏览量',
    `avg_quality_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '平均质量分',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_model_date` (`model_id`, `stat_date`),
    KEY `idx_model_id` (`model_id`),
    KEY `idx_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容模型数据统计';

-- ------------------------------------------------------------
-- 8. 内容模型迁移日志表 (S-8 迁移工具)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_content_model_migration_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `model_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '模型ID',
    `migration_type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '迁移类型(batch_assign/import_from_type/init_fields)',
    `total_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理总数',
    `success_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '成功数',
    `fail_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '失败数',
    `error_detail` TEXT COMMENT '错误详情(JSON)',
    `operator` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '操作人',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_model_id` (`model_id`),
    KEY `idx_migration_type` (`migration_type`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容模型迁移日志';

-- ------------------------------------------------------------
-- 9. i8j_content_model 增加新的预置模型种子 (S-1)
-- V2.9.27 新增：图片图集模型(model_id=7) 和 视频模型(model_id=8)
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_content_model` (`id`, `name`, `code`, `description`, `icon`, `type`, `seo_title`, `seo_keywords`, `seo_description`, `template_file`, `status`, `sort`, `create_time`, `update_time`) VALUES
(7, '图片图集', 'model_image', '用于图片画廊、作品集展示，支持多图轮播、图片说明等字段', 'bi bi-images', 3, '{$title} - 图集 - {$site_name}', '{$title},图片,图集,作品集', '{$title}图片图集展示页面', 'content/image_show', 1, 35, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(8, '视频内容', 'model_video', '用于视频内容展示与播放，支持视频链接、时长、封面等字段', 'bi bi-play-btn', 3, '{$title} - 视频 - {$site_name}', '{$title},视频,播放', '{$title}视频播放页面', 'content/video_show', 1, 36, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ------------------------------------------------------------
-- 10. 新增模型字段种子 (S-2 FieldTypeRegistry)
-- 图片图集模型(model_id=7)字段
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_content_model_field` (`id`, `model_id`, `name`, `label`, `type`, `options`, `default_value`, `placeholder`, `required`, `sort`, `status`, `create_time`, `update_time`) VALUES
(19, 7, 'gallery', '图集', 'image', NULL, '', '请上传图片(可多选)', 1, 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20, 7, 'image_description', '图片说明', 'textarea', NULL, '', '请输入图片描述', 0, 20, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21, 7, 'photographer', '摄影师', 'text', NULL, '', '请输入摄影师姓名', 0, 30, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- 视频模型(model_id=8)字段
(22, 8, 'video_url', '视频链接', 'text', NULL, '', '请输入视频播放链接', 1, 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(23, 8, 'video_cover', '视频封面', 'image', NULL, '', '请上传视频封面图', 0, 20, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(24, 8, 'duration', '视频时长', 'text', NULL, '', '如：12:30', 0, 30, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ------------------------------------------------------------
-- 11. 菜单项 (V2.9.27 Sprint S)
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(510, 4, 0, '模型统计', '/admin/content_model_stats/index', 'content_model_stats.*', 'content_model_stats', 'bi bi-bar-chart', 88, 1),
(511, 4, 0, '模型模板映射', '/admin/content_model_map/index', 'content_model_map.*', 'content_model_map', 'bi bi-diagram-3', 89, 1),
(512, 4, 0, '模型迁移工具', '/admin/content_model_migration/index', 'content_model_migration.*', 'content_model_migration', 'bi bi-arrow-left-right', 90, 1);

-- ------------------------------------------------------------
-- 12. 系统设置：V2.9.27 内容模型差异化启用
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_config` (`name`, `value`, `group`) VALUES
('content_model_seo_enabled', '1', 'content'),
('content_model_relation_enabled', '1', 'content'),
('content_model_template_map_enabled', '1', 'content');
