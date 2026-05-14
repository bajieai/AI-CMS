-- V2.9.7 "模力定制" 数据库迁移
-- 执行时间: 2026-05-15

-- 1. 主题定制数据表（Phase 1核心表）
CREATE TABLE IF NOT EXISTS `i8j_theme_customization` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `theme_id` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '主题目录名',
    `variant_name` VARCHAR(100) NOT NULL DEFAULT 'default' COMMENT '变体名称',
    `custom_data` JSON NOT NULL COMMENT '定制数据(CSS变量覆盖)',
    `is_active` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否激活(0否1是)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_theme_variant` (`theme_id`, `variant_name`),
    KEY `idx_theme_active` (`theme_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='V2.9.7 主题定制数据';

-- 2. 主题分析日志表（Phase 3统计用）
CREATE TABLE IF NOT EXISTS `i8j_theme_analytics` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `theme_id` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '主题目录名',
    `event_type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '事件类型(install/uninstall/switch/customize/export/import)',
    `event_data` JSON DEFAULT NULL COMMENT '事件附加数据',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_theme_event` (`theme_id`, `event_type`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='V2.9.7 主题分析日志';
