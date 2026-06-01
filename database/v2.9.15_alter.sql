-- ============================================
-- AI-CMS V2.9.15 数据库迁移脚本
-- 功能: AI翻译引擎 + SEO增强
-- 注意: 本脚本支持幂等执行（可重复运行不报错）
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------
-- 1. i8j_content 新增 lang 字段（幂等保护）
-- --------------------------------------------
SET @db = DATABASE();

-- 检查 lang 字段是否存在
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_content' AND COLUMN_NAME = 'lang');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `i8j_content` ADD COLUMN `lang` VARCHAR(10) NOT NULL DEFAULT \'zh-cn\' COMMENT \'内容语言代码\' AFTER `id`',
    'SELECT \'lang字段已存在，跳过\' AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查 idx_lang 索引是否存在
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_content' AND INDEX_NAME = 'idx_lang');

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `i8j_content` ADD INDEX `idx_lang` (`lang`)',
    'SELECT \'idx_lang索引已存在，跳过\' AS info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --------------------------------------------
-- 2. 新建 i8j_content_lang 内容翻译版本表
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_content_lang` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `content_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '内容ID',
    `lang` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '语言代码(en/ja/ko/...)',
    `title` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '翻译标题',
    `content` LONGTEXT NULL COMMENT '翻译正文',
    `description` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '翻译摘要',
    `seo_title` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'SEO标题',
    `seo_desc` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'SEO描述',
    `keywords` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '翻译关键词',
    `image_alt` TEXT NULL COMMENT '图片ALT翻译(JSON格式)',
    `error_msg` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '翻译失败错误信息',
    `translate_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '翻译状态(0=PENDING,1=PROCESSING,2=COMPLETED,3=FAILED)',
    `translate_provider` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '翻译Provider',
    `translate_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '翻译耗时(秒)',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_content_id_lang` (`content_id`, `lang`),
    INDEX `idx_lang` (`lang`),
    INDEX `idx_status` (`translate_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容多语言翻译版本表';

SET FOREIGN_KEY_CHECKS = 1;
