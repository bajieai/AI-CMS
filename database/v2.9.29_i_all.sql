-- ============================================================
-- AI-CMS V2.9.29 Sprint I 数据库变更脚本
-- 主题：内容智能增强
-- 变更：7新表 + 1 ALTER (content_relation加2字段)
-- ============================================================

SET NAMES utf8mb4;
SET @db = DATABASE();

-- 0. ALTER i8j_content_relation 加 relation_weight / is_manual (I-1)
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_content_relation' AND COLUMN_NAME = 'relation_weight');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_content_relation` ADD COLUMN `relation_weight` DECIMAL(5,2) NOT NULL DEFAULT 1.00 COMMENT \'关联权重(0-1)\' AFTER `relation_type`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_content_relation' AND COLUMN_NAME = 'is_manual');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_content_relation` ADD COLUMN `is_manual` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT \'是否手动关联(1是/0AI自动)\' AFTER `relation_weight`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1. 内容行动计划表 (I-3)
CREATE TABLE IF NOT EXISTS `i8j_content_action_plan` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `content_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `action` VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'publish/unpublish/archive',
    `execute_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0待执行1已执行2已取消3失败',
    `execute_log` TEXT,
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_content` (`content_id`),
    KEY `idx_action` (`action`),
    KEY `idx_time` (`execute_time`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容行动计划表';

-- 2. 评论表 (I-5)
CREATE TABLE IF NOT EXISTS `i8j_comment` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `content_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `parent_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `content` TEXT NOT NULL,
    `likes` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0待审1已审核2已隐藏',
    `ip_address` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '评论者IP(防刷)',
    `deleted_at` INT UNSIGNED DEFAULT NULL COMMENT '软删除时间(审计追溯)',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_content` (`content_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='评论表';

-- 3. 收藏表 (I-5)
CREATE TABLE IF NOT EXISTS `i8j_favorite` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `content_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_content` (`user_id`, `content_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_content` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='收藏表';

-- 4. 点赞记录表 (I-5)
CREATE TABLE IF NOT EXISTS `i8j_like` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `content_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_content` (`user_id`, `content_id`),
    KEY `idx_content` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='点赞记录表';

-- 5. 内容操作日志表 (I-6)
CREATE TABLE IF NOT EXISTS `i8j_content_audit_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `content_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `operation` VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'create/update/delete/audit/publish/unpublish/restore',
    `diff_summary` TEXT COMMENT '变更摘要(JSON)',
    `ip_address` VARCHAR(50) NOT NULL DEFAULT '',
    `user_agent` VARCHAR(500) NOT NULL DEFAULT '',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_content` (`content_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_operation` (`operation`),
    KEY `idx_create` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容操作日志表';

-- 6. 内容订阅表 (I-7)
CREATE TABLE IF NOT EXISTS `i8j_content_subscription` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `subscribe_type` VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'category/tag/author',
    `subscribe_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `notify_email` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `notify_site` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `digest_frequency` VARCHAR(20) NOT NULL DEFAULT 'instant' COMMENT 'instant/daily/weekly',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_subscription` (`user_id`, `subscribe_type`, `subscribe_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_type_id` (`subscribe_type`, `subscribe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容订阅表';

-- 7. 内容推荐日志表 (I-2)
CREATE TABLE IF NOT EXISTS `i8j_content_recommend_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `content_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `recommended_content_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `user_id` INT UNSIGNED DEFAULT 0,
    `source` VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'tag/category/relation',
    `impressed` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `clicked` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_content` (`content_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_create` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容推荐日志表';
