-- ============================================================
-- AI-CMS V2.9.3 数据库增量脚本
-- 执行环境：MySQL 8.0+
-- ============================================================
SET NAMES utf8mb4;

-- 获取当前数据库名（用于动态条件判断）
SET @dbname = DATABASE();

-- ============================================================
-- 1. i8j_member 表：添加缓冲期字段（M20 自动降级缓冲期）
-- ============================================================
SET @exists_grace = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'i8j_member'
    AND COLUMN_NAME = 'grace_end_time'
);
SET @sql_grace = IF(@exists_grace = 0,
  'ALTER TABLE `i8j_member` ADD COLUMN `grace_end_time` int UNSIGNED DEFAULT 0 COMMENT "降级缓冲期截止时间(0=无缓冲期)" AFTER `last_signin_date`',
  'SELECT "grace_end_time column already exists" AS info'
);
PREPARE stmt_grace FROM @sql_grace;
EXECUTE stmt_grace;
DEALLOCATE PREPARE stmt_grace;

-- ============================================================
-- 2. 配置项：发布平台自动同步开关（M28）
-- ============================================================
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('publish_auto_sync_enabled', '0', 'publish', 'switch', '内容发布后自动同步到已启用平台', 1)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- ============================================================
-- 3. 配置项：自动降级缓冲期天数（M20）
-- ============================================================
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('member_auto_downgrade_grace_days', '7', 'member', 'number', '自动降级缓冲期天数(0=直接降级)', 5)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- ============================================================
-- 4. 配置项：备份保留数量（M26）
-- ============================================================
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('backup_keep_count', '10', 'system', 'number', '自动备份保留最近N个', 30)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- ============================================================
-- 5. 版本标记
-- ============================================================
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('app_version', '2.9.3', 'system', 'text', '当前系统版本号', 0)
ON DUPLICATE KEY UPDATE `value` = '2.9.3';
