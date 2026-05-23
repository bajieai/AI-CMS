-- ============================================================
-- V2.9.11: 主题模板生成系统改造 — 数据库迁移
-- 幂等DDL：可重复执行不报错
-- ============================================================

SET @dbname = DATABASE();
SET @tbl_prefix = (SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME LIKE '%ai_theme_record' LIMIT 1);
SET @tbl_prefix = IFNULL(@tbl_prefix, 'i8j_ai_theme_record');
SET @tbl_prefix = SUBSTRING(@tbl_prefix, 1, LENGTH(@tbl_prefix) - LENGTH('ai_theme_record'));

-- ============================================================
-- 1. ai_theme_record 新增字段
-- ============================================================

-- 1.1 source_theme_id: 源骨架主题ID（骨架模式复制来源）
SET @col1 = 'source_theme_id';
SET @sql1 = CONCAT(
    'ALTER TABLE `', @tbl_prefix, 'ai_theme_record`',
    ' ADD COLUMN `', @col1, '` varchar(64) DEFAULT NULL COMMENT ''源骨架主题ID，骨架模式时记录复制来源(V2.9.11)'' AFTER `theme_name`'
);
SET @exists1 = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = CONCAT(@tbl_prefix, 'ai_theme_record') AND COLUMN_NAME = @col1
);
SET @exec1 = IF(@exists1 = 0, @sql1, 'SELECT "source_theme_id already exists" AS msg');
PREPARE stmt1 FROM @exec1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

-- 1.2 generate_mode: 生成模式
SET @col2 = 'generate_mode';
SET @sql2 = CONCAT(
    'ALTER TABLE `', @tbl_prefix, 'ai_theme_record`',
    ' ADD COLUMN `', @col2, '` enum(''full'',''skeleton'') NOT NULL DEFAULT ''full'' COMMENT ''生成模式:full从零生成/skeleton基于骨架(V2.9.11)'' AFTER `source_theme_id`'
);
SET @exists2 = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = CONCAT(@tbl_prefix, 'ai_theme_record') AND COLUMN_NAME = @col2
);
SET @exec2 = IF(@exists2 = 0, @sql2, 'SELECT "generate_mode already exists" AS msg');
PREPARE stmt2 FROM @exec2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- 1.3 layout_type: 布局类型（骨架模式用）
SET @col3 = 'layout_type';
SET @sql3 = CONCAT(
    'ALTER TABLE `', @tbl_prefix, 'ai_theme_record`',
    ' ADD COLUMN `', @col3, '` enum(''showcase'',''content'') DEFAULT NULL COMMENT ''布局类型:showcase展示型/content内容型(V2.9.11)'' AFTER `generate_mode`'
);
SET @exists3 = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = CONCAT(@tbl_prefix, 'ai_theme_record') AND COLUMN_NAME = @col3
);
SET @exec3 = IF(@exists3 = 0, @sql3, 'SELECT "layout_type already exists" AS msg');
PREPARE stmt3 FROM @exec3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

-- 1.4 industry_type: 行业类型
SET @col4 = 'industry_type';
SET @sql4 = CONCAT(
    'ALTER TABLE `', @tbl_prefix, 'ai_theme_record`',
    ' ADD COLUMN `', @col4, '` varchar(32) DEFAULT NULL COMMENT ''行业类型:corporate/ecommerce/blog/portal/medical/education/catering/finance(V2.9.11)'' AFTER `layout_type`'
);
SET @exists4 = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = CONCAT(@tbl_prefix, 'ai_theme_record') AND COLUMN_NAME = @col4
);
SET @exec4 = IF(@exists4 = 0, @sql4, 'SELECT "industry_type already exists" AS msg');
PREPARE stmt4 FROM @exec4;
EXECUTE stmt4;
DEALLOCATE PREPARE stmt4;

-- 1.5 batch_id: 批量生成批次ID
SET @col5 = 'batch_id';
SET @sql5 = CONCAT(
    'ALTER TABLE `', @tbl_prefix, 'ai_theme_record`',
    ' ADD COLUMN `', @col5, '` varchar(32) DEFAULT NULL COMMENT ''批量生成批次ID(V2.9.11)'' AFTER `industry_type`'
);
SET @exists5 = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = CONCAT(@tbl_prefix, 'ai_theme_record') AND COLUMN_NAME = @col5
);
SET @exec5 = IF(@exists5 = 0, @sql5, 'SELECT "batch_id already exists" AS msg');
PREPARE stmt5 FROM @exec5;
EXECUTE stmt5;
DEALLOCATE PREPARE stmt5;

-- 1.6 为新增字段加索引
SET @idx1 = 'idx_industry_type';
SET @idxExists1 = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = CONCAT(@tbl_prefix, 'ai_theme_record') AND INDEX_NAME = @idx1
);
SET @sqlIdx1 = IF(@idxExists1 = 0,
    CONCAT('ALTER TABLE `', @tbl_prefix, 'ai_theme_record` ADD INDEX `', @idx1, '` (`industry_type`)'),
    'SELECT "idx_industry_type already exists" AS msg'
);
PREPARE stmtIdx1 FROM @sqlIdx1;
EXECUTE stmtIdx1;
DEALLOCATE PREPARE stmtIdx1;

SET @idx2 = 'idx_batch_id';
SET @idxExists2 = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = CONCAT(@tbl_prefix, 'ai_theme_record') AND INDEX_NAME = @idx2
);
SET @sqlIdx2 = IF(@idxExists2 = 0,
    CONCAT('ALTER TABLE `', @tbl_prefix, 'ai_theme_record` ADD INDEX `', @idx2, '` (`batch_id`)'),
    'SELECT "idx_batch_id already exists" AS msg'
);
PREPARE stmtIdx2 FROM @sqlIdx2;
EXECUTE stmtIdx2;
DEALLOCATE PREPARE stmtIdx2;

SET @idx3 = 'idx_generate_mode';
SET @idxExists3 = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = CONCAT(@tbl_prefix, 'ai_theme_record') AND INDEX_NAME = @idx3
);
SET @sqlIdx3 = IF(@idxExists3 = 0,
    CONCAT('ALTER TABLE `', @tbl_prefix, 'ai_theme_record` ADD INDEX `', @idx3, '` (`generate_mode`, `layout_type`)'),
    'SELECT "idx_generate_mode already exists" AS msg'
);
PREPARE stmtIdx3 FROM @sqlIdx3;
EXECUTE stmtIdx3;
DEALLOCATE PREPARE stmtIdx3;


-- ============================================================
-- 2. 新建 ai_theme_palette 行业调色板表
-- ============================================================

SET @palette_table = CONCAT(@tbl_prefix, 'ai_theme_palette');
SET @palette_exists = (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @palette_table
);

SET @sql_palette = IF(@palette_exists = 0,
    CONCAT('CREATE TABLE `', @palette_table, '` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `industry_type` varchar(32) NOT NULL DEFAULT "" COMMENT "行业标识",
      `name` varchar(64) NOT NULL DEFAULT "" COMMENT "调色板名称",
      `description` varchar(255) DEFAULT NULL COMMENT "描述",
      `colors` json NOT NULL COMMENT "色板JSON: {primary,primaryLight,primaryDark,secondary,accent,bg,bgSecondary,bgSection,text,textSecondary,border}",
      `is_system` tinyint(1) NOT NULL DEFAULT 0 COMMENT "是否系统内置:1是/0否",
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_industry_system` (`industry_type`, `is_system`),
      KEY `idx_industry` (`industry_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="AI主题行业调色板(V2.9.11)"'),
    'SELECT "ai_theme_palette already exists" AS msg'
);

PREPARE stmt_palette FROM @sql_palette;
EXECUTE stmt_palette;
DEALLOCATE PREPARE stmt_palette;


-- ============================================================
-- 3. 插入10个行业默认色板数据（幂等：先删后插系统色板）
-- ============================================================

SET @palette_tbl = CONCAT('`', @tbl_prefix, 'ai_theme_palette`');

SET @delete_system = CONCAT('DELETE FROM ', @palette_tbl, ' WHERE `is_system` = 1');
PREPARE stmt_del FROM @delete_system;
EXECUTE stmt_del;
DEALLOCATE PREPARE stmt_del;

SET @insert_palettes = CONCAT('
INSERT INTO ', @palette_tbl, ' (`industry_type`, `name`, `description`, `colors`, `is_system`) VALUES
("corporate", "企业商务", "专业、可信、现代简约", ''{"primary":"#2563EB","primaryLight":"#DBEAFE","primaryDark":"#1E40AF","secondary":"#64748B","accent":"#F59E0B","bg":"#FFFFFF","bgSecondary":"#F8FAFC","bgSection":"#F1F5F9","text":"#1E293B","textSecondary":"#64748B","border":"#E2E8F0"}'', 1),
("ecommerce", "电商促销", "热闹、促销、信任感", ''{"primary":"#F97316","primaryLight":"#FFEDD5","primaryDark":"#EA580C","secondary":"#6B7280","accent":"#EF4444","bg":"#FFFFFF","bgSecondary":"#FFF7ED","bgSection":"#FFFBEB","text":"#1F2937","textSecondary":"#6B7280","border":"#E5E7EB"}'', 1),
("blog", "博客文艺", "舒适阅读、极简、知识分享", ''{"primary":"#059669","primaryLight":"#D1FAE5","primaryDark":"#047857","secondary":"#6B7280","accent":"#8B5CF6","bg":"#FFFFFF","bgSecondary":"#F9FAFB","bgSection":"#F3F4F6","text":"#111827","textSecondary":"#6B7280","border":"#E5E7EB"}'', 1),
("portal", "门户资讯", "信息密集、权威、时效", ''{"primary":"#1D4ED8","primaryLight":"#DBEAFE","primaryDark":"#1E3A8A","secondary":"#475569","accent":"#0EA5E9","bg":"#FFFFFF","bgSecondary":"#F1F5F9","bgSection":"#E2E8F0","text":"#0F172A","textSecondary":"#475569","border":"#CBD5E1"}'', 1),
("medical", "医疗健康", "清洁、专业、信任、安心", ''{"primary":"#0EA5E9","primaryLight":"#E0F2FE","primaryDark":"#0284C7","secondary":"#64748B","accent":"#14B8A6","bg":"#FFFFFF","bgSecondary":"#F8FAFC","bgSection":"#F0F9FF","text":"#1E293B","textSecondary":"#64748B","border":"#E2E8F0"}'', 1),
("education", "教育培训", "活力、知识、信任、成长", ''{"primary":"#3B82F6","primaryLight":"#DBEAFE","primaryDark":"#1D4ED8","secondary":"#64748B","accent":"#F59E0B","bg":"#FFFFFF","bgSecondary":"#FEF3C7","bgSection":"#FFFBEB","text":"#1E293B","textSecondary":"#64748B","border":"#E2E8F0"}'', 1),
("catering", "餐饮美食", "食欲、温暖、热闹、品质", ''{"primary":"#F97316","primaryLight":"#FFEDD5","primaryDark":"#EA580C","secondary":"#6B7280","accent":"#EF4444","bg":"#FFFFFF","bgSecondary":"#FFF7ED","bgSection":"#FEF2F2","text":"#1F2937","textSecondary":"#6B7280","border":"#E5E7EB"}'', 1),
("finance", "金融理财", "稳重、专业、信任、安全", ''{"primary":"#1E3A8A","primaryLight":"#DBEAFE","primaryDark":"#0F172A","secondary":"#475569","accent":"#D97706","bg":"#FFFFFF","bgSecondary":"#F1F5F9","bgSection":"#E2E8F0","text":"#0F172A","textSecondary":"#475569","border":"#CBD5E1"}'', 1),
("technology", "科技互联网", "创新、前沿、简洁、高效", ''{"primary":"#6366F1","primaryLight":"#E0E7FF","primaryDark":"#4338CA","secondary":"#6B7280","accent":"#06B6D4","bg":"#FFFFFF","bgSecondary":"#F8FAFC","bgSection":"#F1F5F9","text":"#1E293B","textSecondary":"#6B7280","border":"#E2E8F0"}'', 1),
("realestate", "房产家居", "品质、温馨、稳重、信赖", ''{"primary":"#0D9488","primaryLight":"#CCFBF1","primaryDark":"#0F766E","secondary":"#64748B","accent":"#F59E0B","bg":"#FFFFFF","bgSecondary":"#F0FDFA","bgSection":"#F8FAFC","text":"#1E293B","textSecondary":"#64748B","border":"#E2E8F0"}'', 1)
');

PREPARE stmt_ins FROM @insert_palettes;
EXECUTE stmt_ins;
DEALLOCATE PREPARE stmt_ins;

SELECT 'V2.9.11 migration completed' AS result;
