-- ============================================================
-- AI-CMS V2.9.29 Sprint C-5 数据库变更脚本
-- 主题：模板商店模型兼容
-- 变更：ALTER i8j_template_store 增加 support_models(JSON)
-- ============================================================

SET NAMES utf8mb4;
SET @db = DATABASE();

-- ------------------------------------------------------------
-- 1. i8j_template_store 增加 support_models 字段 (C-5)
-- 声明模板支持的模型类型(JSON数组，如 ["article","product"])
-- ------------------------------------------------------------
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_template_store' AND COLUMN_NAME = 'support_models');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_template_store` ADD COLUMN `support_models` VARCHAR(500) NOT NULL DEFAULT \'[]\' COMMENT \'支持的模型类型(JSON数组)\'', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 2. 更新现有模板默认支持所有模型
-- ------------------------------------------------------------
UPDATE `i8j_template_store` SET `support_models` = '["article","image","download","product","video"]' WHERE `support_models` = '' OR `support_models` = '[]';
