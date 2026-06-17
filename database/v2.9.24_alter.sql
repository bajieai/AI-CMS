-- ============================================================
-- AI-CMS V2.9.24 G-3 数据库变更
-- 模板商店分类表增加 is_visible 字段（前台可见性控制）
-- 幂等DDL：可重复执行不报错
-- ============================================================

SET NAMES utf8mb4;
SET @dbname = DATABASE();

-- 获取表前缀（参考 v2.9.12.sql 的方式）
SET @tbl_prefix = (SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME LIKE '%template_store_category' LIMIT 1);
SET @tbl_prefix = IFNULL(@tbl_prefix, 'i8j_template_store_category');
SET @tbl_prefix = SUBSTRING(@tbl_prefix, 1, LENGTH(@tbl_prefix) - LENGTH('template_store_category'));

-- 1. template_store_category 表增加 is_visible 字段
SET @col_g3_1 = 'is_visible';
SET @exists_g3_1 = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = CONCAT(@tbl_prefix, 'template_store_category')
    AND COLUMN_NAME = @col_g3_1);

SET @sql_g3_1 = IF(@exists_g3_1 = 0,
    CONCAT('ALTER TABLE `', @tbl_prefix, 'template_store_category` ADD COLUMN `', @col_g3_1, '` tinyint(1) NOT NULL DEFAULT 1 COMMENT ''前台是否可见:0隐藏/1显示(V2.9.24)'' AFTER `is_enabled`'),
    'SELECT "is_visible column already exists" AS msg'
);
PREPARE stmt_g3_1 FROM @sql_g3_1;
EXECUTE stmt_g3_1;
DEALLOCATE PREPARE stmt_g3_1;

-- 2. 更新索引（包含 is_visible 的联合索引）
SET @idx_g3_1 = 'idx_visible_sort';
SET @exists_idx_g3_1 = (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = CONCAT(@tbl_prefix, 'template_store_category')
    AND INDEX_NAME = @idx_g3_1);

SET @sql_g3_2 = IF(@exists_idx_g3_1 = 0,
    CONCAT('ALTER TABLE `', @tbl_prefix, 'template_store_category` ADD INDEX `', @idx_g3_1, '` (`is_enabled`, `is_visible`, `sort`)'),
    'SELECT "idx_visible_sort already exists" AS msg'
);
PREPARE stmt_g3_2 FROM @sql_g3_2;
EXECUTE stmt_g3_2;
DEALLOCATE PREPARE stmt_g3_2;

-- 3. 菜单项：模板商店分类管理（G-3）
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`)
VALUES (520, 4, 0, '商店分类', '/admin/template_store_ops/categoryIndex', 'template_store_ops.*', 'template_store_ops', 'bi bi-folder2-open', 88, 1);

-- 4. 菜单项：Banner管理（G-1）
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`)
VALUES (521, 4, 0, 'Banner管理', '/admin/template_store_ops/bannerIndex', 'template_store_ops.*', 'template_store_ops', 'bi bi-images', 89, 1);

-- 5. 菜单项：推荐位配置（G-2）
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`)
VALUES (522, 4, 0, '推荐位配置', '/admin/template_store_ops/recommendIndex', 'template_store_ops.*', 'template_store_ops', 'bi bi-star', 90, 1);

-- 6. 菜单项：统计看板（G-4）
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`)
VALUES (523, 4, 0, '商店统计', '/admin/template_store_ops/statsDashboard', 'template_store_ops.*', 'template_store_ops', 'bi bi-bar-chart-line', 91, 1);

-- 7. 菜单项：评论批量管理（G-5）
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`)
VALUES (524, 4, 0, '评论批量管理', '/admin/template_store_ops/reviewBatch', 'template_store_ops.*', 'template_store_ops', 'bi bi-chat-dots', 92, 1);
