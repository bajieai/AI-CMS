-- ============================================================
-- AI-CMS V2.9.24 H-2: 移动端底部导航Tab表
-- 幂等DDL：可重复执行不报错
-- ============================================================

SET NAMES utf8mb4;
SET @dbname = DATABASE();

-- 获取表前缀
SET @tbl_prefix = (SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME LIKE '%template_store_category' LIMIT 1);
SET @tbl_prefix = IFNULL(@tbl_prefix, 'i8j_template_store_category');
SET @tbl_prefix = SUBSTRING(@tbl_prefix, 1, LENGTH(@tbl_prefix) - LENGTH('template_store_category'));

-- 1. 创建移动端导航Tab表
SET @t_h2 = CONCAT(@tbl_prefix, 'mobile_nav_tab');
SET @e_h2 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t_h2);
SET @s_h2 = IF(@e_h2 = 0, CONCAT('CREATE TABLE `', @t_h2, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL DEFAULT "" COMMENT "Tab名称",
  `icon` varchar(64) NOT NULL DEFAULT "" COMMENT "图标类名(Bootstrap Icons)",
  `icon_active` varchar(64) NOT NULL DEFAULT "" COMMENT "激活图标类名",
  `tab_type` varchar(20) NOT NULL DEFAULT "custom" COMMENT "类型:home/category/member/message/custom",
  `url` varchar(255) NOT NULL DEFAULT "" COMMENT "链接URL",
  `require_login` tinyint(1) NOT NULL DEFAULT 0 COMMENT "是否需要登录:0否/1是",
  `show_badge` tinyint(1) NOT NULL DEFAULT 0 COMMENT "是否显示角标(未读数):0否/1是",
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT "排序号",
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT "是否启用:0否/1是",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_enabled_sort` (`is_enabled`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="移动端底部导航Tab(V2.9.24)"'), 'SELECT "mobile_nav_tab already exists" AS msg');
PREPARE stmt_h2 FROM @s_h2;
EXECUTE stmt_h2;
DEALLOCATE PREPARE stmt_h2;

-- 2. 种子数据：默认4个Tab
SET @seed_h2 = CONCAT('INSERT IGNORE INTO `', @tbl_prefix, 'mobile_nav_tab` (`id`, `name`, `icon`, `icon_active`, `tab_type`, `url`, `require_login`, `show_badge`, `sort`, `is_enabled`) VALUES
(1, "首页", "bi bi-house", "bi bi-house-fill", "home", "/", 0, 0, 1, 1),
(2, "分类", "bi bi-grid", "bi bi-grid-fill", "category", "/product", 0, 0, 2, 1),
(3, "我的", "bi bi-person", "bi bi-person-fill", "member", "/member/index", 1, 0, 3, 1),
(4, "消息", "bi bi-bell", "bi bi-bell-fill", "message", "/message/index", 1, 1, 4, 1)');
PREPARE stmt_seed_h2 FROM @seed_h2;
EXECUTE stmt_seed_h2;
DEALLOCATE PREPARE stmt_seed_h2;

-- 3. 菜单项：移动端导航配置
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`)
VALUES (530, 4, 0, '移动端导航', '/admin/system/mobileNav', 'system.*', 'system', 'bi bi-phone', 95, 1);
