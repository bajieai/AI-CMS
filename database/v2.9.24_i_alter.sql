-- ============================================================
-- AI-CMS V2.9.24 I-2/I-3: AI编辑器增强数据库变更
-- I-2: template_preset_color 表增加 member_id 字段
-- I-3: template_section_config 表 page_type 默认值迁移
-- 幂等DDL：可重复执行不报错
-- ============================================================

SET NAMES utf8mb4;
SET @dbname = DATABASE();

-- 获取表前缀
SET @tbl_prefix = (SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME LIKE '%template_store_category' LIMIT 1);
SET @tbl_prefix = IFNULL(@tbl_prefix, 'i8j_template_store_category');
SET @tbl_prefix = SUBSTRING(@tbl_prefix, 1, LENGTH(@tbl_prefix) - LENGTH('template_store_category'));

-- ============================================================
-- I-2: template_preset_color 表增加 member_id 字段
-- ============================================================
SET @col_i2 = 'member_id';
SET @exists_i2 = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = CONCAT(@tbl_prefix, 'template_preset_color')
    AND COLUMN_NAME = @col_i2);

SET @sql_i2 = IF(@exists_i2 = 0,
    CONCAT('ALTER TABLE `', @tbl_prefix, 'template_preset_color` ADD COLUMN `', @col_i2, '` int(11) unsigned NOT NULL DEFAULT 0 COMMENT ''用户ID，0表示系统预设(V2.9.24)'' AFTER `is_system`'),
    'SELECT "member_id column already exists" AS msg'
);
PREPARE stmt_i2 FROM @sql_i2;
EXECUTE stmt_i2;
DEALLOCATE PREPARE stmt_i2;

-- 添加 member_id 索引
SET @idx_i2 = 'idx_member';
SET @exists_idx_i2 = (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = CONCAT(@tbl_prefix, 'template_preset_color')
    AND INDEX_NAME = @idx_i2);

SET @sql_idx_i2 = IF(@exists_idx_i2 = 0,
    CONCAT('ALTER TABLE `', @tbl_prefix, 'template_preset_color` ADD INDEX `', @idx_i2, '` (`member_id`, `is_system`)'),
    'SELECT "idx_member already exists" AS msg'
);
PREPARE stmt_idx_i2 FROM @sql_idx_i2;
EXECUTE stmt_idx_i2;
DEALLOCATE PREPARE stmt_idx_i2;

-- ============================================================
-- I-3: template_section_config 表 page_type 数据迁移
-- 将空字符串 page_type 更新为 'index'
-- ============================================================
SET @migrate_i3 = CONCAT('UPDATE `', @tbl_prefix, 'template_section_config` SET `page_type` = ''index'' WHERE `page_type` = '''' OR `page_type` IS NULL');
PREPARE stmt_migrate_i3 FROM @migrate_i3;
EXECUTE stmt_migrate_i3;
DEALLOCATE PREPARE stmt_migrate_i3;
