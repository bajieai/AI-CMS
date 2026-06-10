-- =====================================================
-- V2.9.21 数据库迁移脚本
-- 包含：D-3 映射表扩展 + D-1 播放量字段 + download_count 补建
-- 执行方式: bin\migrate.bat database\v2.9.21.sql
-- =====================================================

-- -----------------------------------------------------
-- 1. D-3: template_category_map 表扩展字段
--    使用 INFORMATION_SCHEMA 探测保证可重复执行
-- -----------------------------------------------------
SET @dbname = DATABASE();

-- 1.1 is_primary
SET @col_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='i8j_template_category_map' AND COLUMN_NAME='is_primary');
SET @sql := IF(@col_exists=0,
    'ALTER TABLE `i8j_template_category_map` ADD COLUMN `is_primary` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''是否主分类（1=主分类，0=次分类）'' AFTER `category_id`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1.2 confidence
SET @col_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='i8j_template_category_map' AND COLUMN_NAME='confidence');
SET @sql := IF(@col_exists=0,
    'ALTER TABLE `i8j_template_category_map` ADD COLUMN `confidence` tinyint(3) unsigned NOT NULL DEFAULT 100 COMMENT ''匹配置信度（0-100）'' AFTER `is_primary`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1.3 created_by
SET @col_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='i8j_template_category_map' AND COLUMN_NAME='created_by');
SET @sql := IF(@col_exists=0,
    'ALTER TABLE `i8j_template_category_map` ADD COLUMN `created_by` tinyint(1) NOT NULL DEFAULT 1 COMMENT ''创建来源（1=人工，2=AI自动）'' AFTER `confidence`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1.4 idx_template_primary
SET @idx_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='i8j_template_category_map' AND INDEX_NAME='idx_template_primary');
SET @sql := IF(@idx_exists=0,
    'ALTER TABLE `i8j_template_category_map` ADD INDEX `idx_template_primary` (`template_id`, `is_primary`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1.5 idx_category_confidence
SET @idx_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='i8j_template_category_map' AND INDEX_NAME='idx_category_confidence');
SET @sql := IF(@idx_exists=0,
    'ALTER TABLE `i8j_template_category_map` ADD INDEX `idx_category_confidence` (`category_id`, `confidence`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------
-- 2. D-1: i8j_content 表新增 play_count 字段
-- -----------------------------------------------------
SET @col_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='i8j_content' AND COLUMN_NAME='play_count');
SET @sql := IF(@col_exists=0,
    'ALTER TABLE `i8j_content` ADD COLUMN `play_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT ''视频/音频播放量（V2.9.21 D-1）'' AFTER `views`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='i8j_content' AND INDEX_NAME='idx_play_count');
SET @sql := IF(@idx_exists=0,
    'ALTER TABLE `i8j_content` ADD INDEX `idx_play_count` (`play_count`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------
-- 3. BUG-1 修复: i8j_content 表补建 download_count 字段
--    （V2.9.20 遗留：downloadCount() 方法引用但表结构缺失）
-- -----------------------------------------------------
SET @col_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='i8j_content' AND COLUMN_NAME='download_count');
SET @sql := IF(@col_exists=0,
    'ALTER TABLE `i8j_content` ADD COLUMN `download_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT ''下载次数（V2.9.20 A-4）'' AFTER `play_count`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='i8j_content' AND INDEX_NAME='idx_download_count');
SET @sql := IF(@idx_exists=0,
    'ALTER TABLE `i8j_content` ADD INDEX `idx_download_count` (`download_count`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------
-- 4. 初始化数据（可选）：将现有模板-分类关联的 is_primary 设为 1
--    （已有数据全部视为人工设置的主分类）
-- -----------------------------------------------------
UPDATE `i8j_template_category_map`
    SET `is_primary` = 1,
        `confidence` = 100,
        `created_by` = 1
    WHERE `is_primary` = 0;

-- -----------------------------------------------------
-- 5. D-2: 邮件统计菜单（子菜单 530）
--    作为 484 邮件日志的子菜单，点击展开后访问 mail_log/statistics
-- -----------------------------------------------------
INSERT IGNORE INTO `i8j_menu_item`
    (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`)
VALUES
    (530, 4, 484, '发送趋势', '/admin/mail_log/statistics', 'mail_log.*', 'mail_log_statistics', 'bi bi-graph-up', 1, 1);
