-- ============================================
-- AI-CMS V2.9.18 数据库迁移脚本
-- 功能: 内容分发增强 + 会员体系奠基
-- 日期: 2026-06-05
-- 注意: 本脚本支持幂等执行（可重复运行不报错）
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------
-- 1. 推送通道配置表
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_push_channel` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '通道名称',
    `type` VARCHAR(50) NOT NULL DEFAULT 'webhook' COMMENT '通道类型: webhook|wechat_push|broadcast',
    `config` TEXT NULL COMMENT '配置信息JSON: {url, headers, method, format, token}',
    `trigger_mode` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '触发方式: 0=手动, 1=自动(发布时触发)',
    `push_scope` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '推送范围: 空=全部, 分类ID逗号分隔',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态: 0=禁用, 1=启用',
    `last_push_at` DATETIME DEFAULT NULL COMMENT '最后推送时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推送通道配置';

-- --------------------------------------------
-- 2. 推送日志表
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_push_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `channel_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联推送通道ID',
    `content_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联内容ID',
    `request_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '请求URL',
    `request_body` TEXT NULL COMMENT '请求体JSON',
    `response_code` INT NOT NULL DEFAULT 0 COMMENT '响应状态码',
    `response_body` TEXT NULL COMMENT '响应内容摘要',
    `duration_ms` INT NOT NULL DEFAULT 0 COMMENT '请求耗时(毫秒)',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态: 0=待发送, 1=成功, 2=失败',
    `error_msg` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '失败原因',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `retried_at` DATETIME DEFAULT NULL COMMENT '重试时间',
    PRIMARY KEY (`id`),
    INDEX `idx_channel_id` (`channel_id`),
    INDEX `idx_content_id` (`content_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推送日志';

-- --------------------------------------------
-- 3. 邮件订阅者表
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_subscriber` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `email` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '邮箱地址',
    `nickname` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '昵称(可选)',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态: 0=待确认, 1=已确认, 2=已退订',
    `confirm_token` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '确认token(唯一)',
    `source` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '订阅来源: detail_page|footer|admin_add|register',
    `subscribed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '订阅时间',
    `confirmed_at` DATETIME DEFAULT NULL COMMENT '确认时间',
    `unsubscribed_at` DATETIME DEFAULT NULL COMMENT '退订时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`),
    UNIQUE KEY `uk_confirm_token` (`confirm_token`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件订阅者';

-- --------------------------------------------
-- 4. 邮件发送日志表
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_mail_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `subscriber_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订阅者ID',
    `content_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联内容ID(可空)',
    `email` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '收件人邮箱',
    `subject` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '邮件主题',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态: 0=待发送, 1=已发送, 2=失败',
    `error_msg` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '失败原因',
    `sent_at` DATETIME DEFAULT NULL COMMENT '发送时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    INDEX `idx_subscriber_id` (`subscriber_id`),
    INDEX `idx_content_id` (`content_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件发送日志';

-- --------------------------------------------
-- 5. 分享点击追踪表
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_share_click` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `content_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联内容ID',
    `source` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '分享来源: wechat|weibo|qq|twitter|copy',
    `ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '访客IP',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '点击时间',
    PRIMARY KEY (`id`),
    INDEX `idx_content_id` (`content_id`),
    INDEX `idx_source` (`source`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分享点击日志';

-- --------------------------------------------
-- 6. ALTER i8j_user 扩展字段（幂等保护）
-- --------------------------------------------
SET @db = DATABASE();

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_user' AND COLUMN_NAME = 'avatar');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_user` ADD COLUMN `avatar` VARCHAR(255) NOT NULL DEFAULT \'\' COMMENT \'头像URL\' AFTER `username`', 'SELECT \'avatar字段已存在，跳过\' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_user' AND COLUMN_NAME = 'bio');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_user` ADD COLUMN `bio` VARCHAR(500) NOT NULL DEFAULT \'\' COMMENT \'个人简介\' AFTER `avatar`', 'SELECT \'bio字段已存在，跳过\' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_user' AND COLUMN_NAME = 'lang_pref');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_user` ADD COLUMN `lang_pref` VARCHAR(10) NOT NULL DEFAULT \'\' COMMENT \'偏好语言代码\' AFTER `bio`', 'SELECT \'lang_pref字段已存在，跳过\' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_user' AND COLUMN_NAME = 'email_verified');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_user` ADD COLUMN `email_verified` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT \'邮箱是否已验证\' AFTER `email`', 'SELECT \'email_verified字段已存在，跳过\' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_user' AND COLUMN_NAME = 'register_ip');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_user` ADD COLUMN `register_ip` VARCHAR(45) NOT NULL DEFAULT \'\' COMMENT \'注册IP\' AFTER `email_verified`', 'SELECT \'register_ip字段已存在，跳过\' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_user' AND COLUMN_NAME = 'register_source');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_user` ADD COLUMN `register_source` VARCHAR(50) NOT NULL DEFAULT \'\' COMMENT \'注册来源: username|email\' AFTER `register_ip`', 'SELECT \'register_source字段已存在，跳过\' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
