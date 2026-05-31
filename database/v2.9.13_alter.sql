-- ============================================================
-- AI-CMS V2.9.13 数据库变更脚本
-- 执行时间：Sprint3 Day15
-- ============================================================

-- 1. template_review 表新增 images 字段（评论图片URL数组）
ALTER TABLE `i8j_template_review`
    ADD COLUMN `images` JSON NULL DEFAULT NULL COMMENT '评论图片URL数组' AFTER `content`;

-- 2. template_install 表新增 quality_on_install 字段（安装时质量分）
ALTER TABLE `i8j_template_install`
    ADD COLUMN `quality_on_install` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '安装时质量分(0-100)' AFTER `install_path`;
