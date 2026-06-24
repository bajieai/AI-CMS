-- ============================================================
-- AI-CMS V2.9.29 Sprint T 数据库变更脚本
-- 主题：模板生态进阶
-- 变更：4新表 (template_user_action + template_recommend_queue + template_category_v2 + template_audit_report)
-- ============================================================

SET NAMES utf8mb4;
SET @db = DATABASE();

-- 1. 模板用户行为表 (T-2)
CREATE TABLE IF NOT EXISTS `i8j_template_user_action` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `template_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `action` VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'view/download/buy/favorite',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_template` (`template_id`),
    KEY `idx_action` (`action`, `create_time`),
    KEY `idx_user_action` (`user_id`, `action`, `create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板用户行为表';

-- 2. 模板推荐队列表 (T-2)
CREATE TABLE IF NOT EXISTS `i8j_template_recommend_queue` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `template_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `score` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `reason` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'hot/collaborative/category',
    `expire_time` INT UNSIGNED DEFAULT NULL,
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_user_score` (`user_id`, `score`),
    UNIQUE KEY `uk_user_template` (`user_id`, `template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板推荐队列表';

-- 3. 模板分类v2表 (T-4)
CREATE TABLE IF NOT EXISTS `i8j_template_category_v2` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL DEFAULT '',
    `slug` VARCHAR(100) NOT NULL DEFAULT '',
    `dimension` VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'industry/style/function',
    `parent_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `sort` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `template_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_dimension` (`dimension`),
    KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板分类v2(三维分类)';

-- 4. 模板审核报告表 (T-5)
CREATE TABLE IF NOT EXISTS `i8j_template_audit_report` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `template_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `code_quality_score` DECIMAL(3,1) NOT NULL DEFAULT 0.0,
    `compatibility_score` DECIMAL(3,1) NOT NULL DEFAULT 0.0,
    `responsive_score` DECIMAL(3,1) NOT NULL DEFAULT 0.0,
    `security_score` DECIMAL(3,1) NOT NULL DEFAULT 0.0,
    `total_score` DECIMAL(3,1) NOT NULL DEFAULT 0.0,
    `issues` TEXT COMMENT '问题详情(JSON)',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0待审1通过2驳回',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_template` (`template_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板自动审核报告';
