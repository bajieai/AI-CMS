-- ============================================================
-- AI-CMS V2.9.29 Sprint F 数据库变更脚本
-- 主题：V2.9.28修复完善
-- 变更：1新表 i8j_template_user_profile (F-2 用户画像)
-- ============================================================

SET NAMES utf8mb4;
SET @db = DATABASE();

-- ------------------------------------------------------------
-- 1. 模板用户画像聚合表 (F-2)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_template_user_profile` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `stat_date` DATE NOT NULL COMMENT '统计日期',
    `dimension` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '维度(region/hobby/hour)',
    `dimension_value` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '维度值',
    `user_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户数',
    `download_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '下载数',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_date_dimension` (`stat_date`, `dimension`, `dimension_value`),
    KEY `idx_date` (`stat_date`),
    KEY `idx_dimension` (`dimension`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板用户画像聚合表';
