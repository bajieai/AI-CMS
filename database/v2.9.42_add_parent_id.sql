-- V2.9.42: 给content表添加parent_id列（章节管理必需）
-- 执行前确认列不存在
SELECT COUNT(*) AS col_exists FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'i8j_content' AND COLUMN_NAME = 'parent_id';

ALTER TABLE `i8j_content` ADD COLUMN `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父内容ID(章节归属)' AFTER `chapter_title`;
ALTER TABLE `i8j_content` ADD INDEX `idx_parent_id` (`parent_id`);
