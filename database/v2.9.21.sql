-- =====================================================
-- V2.9.21 数据库迁移脚本
-- 包含：D-3 映射表扩展 + D-1 播放量字段 + download_count 补建
-- 执行方式: bin\migrate.bat database\v2.9.21.sql
-- =====================================================

-- -----------------------------------------------------
-- 1. D-3: template_category_map 表扩展字段
-- -----------------------------------------------------
ALTER TABLE `i8j_template_category_map`
    ADD COLUMN `is_primary` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否主分类（1=主分类，0=次分类）' AFTER `category_id`,
    ADD COLUMN `confidence` tinyint(3) unsigned NOT NULL DEFAULT 100 COMMENT '匹配置信度（0-100）' AFTER `is_primary`,
    ADD COLUMN `created_by` tinyint(1) NOT NULL DEFAULT 1 COMMENT '创建来源（1=人工，2=AI自动）' AFTER `confidence`,
    ADD INDEX `idx_template_primary` (`template_id`, `is_primary`),
    ADD INDEX `idx_category_confidence` (`category_id`, `confidence`);

-- -----------------------------------------------------
-- 2. D-1: i8j_content 表新增 play_count 字段
-- -----------------------------------------------------
ALTER TABLE `i8j_content`
    ADD COLUMN `play_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '视频/音频播放量（V2.9.21 D-1）' AFTER `views`,
    ADD INDEX `idx_play_count` (`play_count`);

-- -----------------------------------------------------
-- 3. BUG-1 修复: i8j_content 表补建 download_count 字段
--    （V2.9.20 遗留：downloadCount() 方法引用但表结构缺失）
-- -----------------------------------------------------
ALTER TABLE `i8j_content`
    ADD COLUMN `download_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '下载次数（V2.9.20 A-4）' AFTER `play_count`,
    ADD INDEX `idx_download_count` (`download_count`);

-- -----------------------------------------------------
-- 4. 初始化数据（可选）：将现有模板-分类关联的 is_primary 设为 1
--    （已有数据全部视为人工设置的主分类）
-- -----------------------------------------------------
UPDATE `i8j_template_category_map`
    SET `is_primary` = 1,
        `confidence` = 100,
        `created_by` = 1
    WHERE `is_primary` = 0;
