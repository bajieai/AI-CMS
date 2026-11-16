-- +----------------------------------------------------------------------
-- | 八界AI-CMS 内容管理系统
-- +----------------------------------------------------------------------
-- | 迁移：为 {prefix}theme_info 补充 AI-THEME 适配器所需的 4 列
-- | 适用：已安装的存量库（新装请直接使用 database/install.sql，其中已含这 4 列）
-- | 特性：可重复执行。列已存在或表不存在时静默跳过，不报错、不改动已有数据。
-- | 前缀：与 install.sql 一致使用 {prefix} 占位符，执行前请替换为实际表前缀（默认 i8j_）。
-- +----------------------------------------------------------------------

-- 记录库名与实际表名
SET @tbl := '{prefix}theme_info';
SET @tbl_exists := (
  SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @tbl
);

-- ----------------------------------------------------------------------
-- 1) source：来源标记（source=ai-theme 表示 AI-THEME 适配器安装）
-- ----------------------------------------------------------------------
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @tbl AND COLUMN_NAME = 'source'
);
SET @sql := IF(@tbl_exists = 0 OR @col_exists > 0, 'DO 0',
  'ALTER TABLE `{prefix}theme_info` ADD COLUMN `source` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '''' COMMENT ''来源(source=ai-theme 表示 AI-THEME 适配器安装)''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------
-- 2) task_id：AI-THEME 任务 ID
-- ----------------------------------------------------------------------
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @tbl AND COLUMN_NAME = 'task_id'
);
SET @sql := IF(@tbl_exists = 0 OR @col_exists > 0, 'DO 0',
  'ALTER TABLE `{prefix}theme_info` ADD COLUMN `task_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '''' COMMENT ''AI-THEME 任务 ID''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------
-- 3) installed_at：安装时间
-- ----------------------------------------------------------------------
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @tbl AND COLUMN_NAME = 'installed_at'
);
SET @sql := IF(@tbl_exists = 0 OR @col_exists > 0, 'DO 0',
  'ALTER TABLE `{prefix}theme_info` ADD COLUMN `installed_at` datetime DEFAULT NULL COMMENT ''安装时间''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------
-- 4) package_sha256：整包 sha256
-- ----------------------------------------------------------------------
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @tbl AND COLUMN_NAME = 'package_sha256'
);
SET @sql := IF(@tbl_exists = 0 OR @col_exists > 0, 'DO 0',
  'ALTER TABLE `{prefix}theme_info` ADD COLUMN `package_sha256` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '''' COMMENT ''整包 sha256''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
