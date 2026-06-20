-- ============================================================
-- AI-CMS V2.9.26 Sprint P-2: 多级分类管理与SEO增强
-- 修改表: i8j_template_store_category (icon字段已存在)
-- ============================================================

SET NAMES utf8mb4;

ALTER TABLE `i8j_template_store_category`
  ADD COLUMN `parent_id` int(11) NOT NULL DEFAULT 0 COMMENT '父分类ID(0=顶级)' AFTER `id`,
  ADD COLUMN `level` tinyint(2) NOT NULL DEFAULT 1 COMMENT '分类层级(1=顶级)' AFTER `parent_id`,
  ADD COLUMN `meta_title` varchar(200) NOT NULL DEFAULT '' COMMENT 'SEO标题' AFTER `description`,
  ADD COLUMN `meta_description` varchar(500) NOT NULL DEFAULT '' COMMENT 'SEO描述' AFTER `meta_title`,
  ADD COLUMN `meta_keywords` varchar(200) NOT NULL DEFAULT '' COMMENT 'SEO关键词' AFTER `meta_description`,
  ADD INDEX `idx_parent_id` (`parent_id`),
  ADD INDEX `idx_level` (`level`);
