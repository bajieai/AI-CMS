-- =====================================================
-- V2.9.23 数据库迁移脚本
-- 「模板生态启动 · AI能力深化」
-- 包含：模板区块配置 + 预设配色方案 + 插件配置Schema + 模板商店推荐位 + 模板缓存日志
-- 执行方式: bin\migrate.bat database\v2.9.23.sql
-- 幂等DDL：可重复执行不报错
-- =====================================================

SET NAMES utf8mb4;
SET @dbname = DATABASE();

-- =====================================================
-- 1. CREATE `i8j_template_section_config` — 前台区块配置表 (C-2)
-- =====================================================
CREATE TABLE IF NOT EXISTS `i8j_template_section_config` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
    `theme_slug` varchar(100) NOT NULL DEFAULT '' COMMENT '模板标识',
    `member_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
    `page_type` varchar(50) NOT NULL DEFAULT 'index' COMMENT '页面类型: index/detail/list',
    `sections` json NOT NULL COMMENT '区块配置JSON[{id,name,visible,sort}]',
    `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_theme_page` (`theme_slug`, `member_id`, `page_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='前台区块配置表(V2.9.23 C-2)';

-- =====================================================
-- 2. CREATE `i8j_template_preset_color` — 预设配色方案表 (C-4)
-- =====================================================
CREATE TABLE IF NOT EXISTS `i8j_template_preset_color` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
    `name` varchar(100) NOT NULL DEFAULT '' COMMENT '配色名称',
    `description` varchar(255) NOT NULL DEFAULT '' COMMENT '配色描述',
    `colors` json NOT NULL COMMENT '配色JSON {primary, secondary, bg, text, heading, link, accent}',
    `industry_tags` varchar(200) NOT NULL DEFAULT '' COMMENT '行业标签(逗号分隔)',
    `is_system` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否系统预设(1=系统/0=自定义)',
    `sort` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '排序',
    `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_industry` (`industry_tags`),
    KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='预设配色方案表(V2.9.23 C-4)';

-- =====================================================
-- 3. ALTER `i8j_plugin` — 新增 config_schema 字段 (D)
-- =====================================================
SET @col_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='i8j_plugin' AND COLUMN_NAME='config_schema');
SET @sql := IF(@col_exists=0,
    'ALTER TABLE `i8j_plugin` ADD COLUMN `config_schema` json DEFAULT NULL COMMENT ''插件配置Schema(JSON)'' AFTER `config`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- 4. ALTER `i8j_template_store` — 新增 is_recommended/banner_url 字段 (B-3)
-- =====================================================
SET @col_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='i8j_template_store' AND COLUMN_NAME='is_recommended');
SET @sql := IF(@col_exists=0,
    'ALTER TABLE `i8j_template_store` ADD COLUMN `is_recommended` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''是否推荐(0否/1是)'' AFTER `is_featured`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='i8j_template_store' AND COLUMN_NAME='banner_url');
SET @sql := IF(@col_exists=0,
    'ALTER TABLE `i8j_template_store` ADD COLUMN `banner_url` varchar(500) NOT NULL DEFAULT '''' COMMENT ''商店首页轮播Banner图'' AFTER `is_recommended`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='i8j_template_store' AND COLUMN_NAME='install_count_7d');
SET @sql := IF(@col_exists=0,
    'ALTER TABLE `i8j_template_store` ADD COLUMN `install_count_7d` int(11) unsigned NOT NULL DEFAULT 0 COMMENT ''近7天安装数(B-5排行用)'' AFTER `install_count`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- 5. CREATE `i8j_template_cache_log` — 模板缓存变更日志 (A-4)
-- =====================================================
CREATE TABLE IF NOT EXISTS `i8j_template_cache_log` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
    `template_path` varchar(500) NOT NULL DEFAULT '' COMMENT '模板路径',
    `template_md5` varchar(32) NOT NULL DEFAULT '' COMMENT 'MD5校验值',
    `action` varchar(20) NOT NULL DEFAULT 'refresh' COMMENT '操作类型: refresh/clear/rebuild',
    `file_size` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '文件大小(字节)',
    `operator` varchar(50) NOT NULL DEFAULT 'system' COMMENT '操作者',
    `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '操作时间',
    PRIMARY KEY (`id`),
    KEY `idx_template_path` (`template_path`(191)),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板缓存变更日志表(V2.9.23 A-4)';

-- =====================================================
-- 6. ALTER `i8j_content_model` — 确认移动端详情差异化所需字段 (E-1)
--    新增 mobile_partial_suffix 字段
-- =====================================================
SET @col_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME='i8j_content_model' AND COLUMN_NAME='mobile_partial_suffix');
SET @sql := IF(@col_exists=0,
    'ALTER TABLE `i8j_content_model` ADD COLUMN `mobile_partial_suffix` varchar(50) NOT NULL DEFAULT '''' COMMENT ''移动端详情模板片段后缀(E-1)'' AFTER `code`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- 7. 种子数据：8条预设配色方案 (C-4)
-- =====================================================
INSERT IGNORE INTO `i8j_template_preset_color`
    (`name`, `description`, `colors`, `industry_tags`, `is_system`, `sort`, `create_time`)
VALUES
    ('商务蓝', '专业稳重的商务蓝色调', JSON_OBJECT('primary', '#1e40af', 'secondary', '#3b82f6', 'bg', '#ffffff', 'text', '#1f2937', 'heading', '#0f172a', 'link', '#2563eb', 'accent', '#f59e0b'), '科技,企业,金融', 1, 100, UNIX_TIMESTAMP()),
    ('温暖橙', '充满活力的暖色系', JSON_OBJECT('primary', '#ea580c', 'secondary', '#fb923c', 'bg', '#fffbeb', 'text', '#1c1917', 'heading', '#7c2d12', 'link', '#ea580c', 'accent', '#dc2626'), '电商,餐饮,教育', 1, 90, UNIX_TIMESTAMP()),
    ('自然绿', '清新自然的绿色系', JSON_OBJECT('primary', '#15803d', 'secondary', '#22c55e', 'bg', '#f0fdf4', 'text', '#14532d', 'heading', '#052e16', 'link', '#16a34a', 'accent', '#84cc16'), '农业,环保,健康', 1, 85, UNIX_TIMESTAMP()),
    ('典雅紫', '神秘高贵的紫色调', JSON_OBJECT('primary', '#7e22ce', 'secondary', '#a855f7', 'bg', '#faf5ff', 'text', '#1e1b4b', 'heading', '#2e1065', 'link', '#9333ea', 'accent', '#ec4899'), '美妆,时尚,设计', 1, 80, UNIX_TIMESTAMP()),
    ('简约灰', '极简现代的灰白调', JSON_OBJECT('primary', '#374151', 'secondary', '#6b7280', 'bg', '#ffffff', 'text', '#111827', 'heading', '#030712', 'link', '#4b5563', 'accent', '#0ea5e9'), '设计,艺术,建筑', 1, 75, UNIX_TIMESTAMP()),
    ('热情红', '激情澎湃的红色调', JSON_OBJECT('primary', '#dc2626', 'secondary', '#ef4444', 'bg', '#fef2f2', 'text', '#1f2937', 'heading', '#7f1d1d', 'link', '#dc2626', 'accent', '#f59e0b'), '餐饮,娱乐,体育', 1, 70, UNIX_TIMESTAMP()),
    ('医疗青', '专业可信的医疗色调', JSON_OBJECT('primary', '#0e7490', 'secondary', '#06b6d4', 'bg', '#ecfeff', 'text', '#164e63', 'heading', '#083344', 'link', '#0891b2', 'accent', '#14b8a6'), '医疗,健康,生物', 1, 65, UNIX_TIMESTAMP()),
    ('奢华金', '尊贵典雅的奢华金调', JSON_OBJECT('primary', '#92400e', 'secondary', '#d97706', 'bg', '#fffbeb', 'text', '#1c1917', 'heading', '#451a03', 'link', '#b45309', 'accent', '#a16207'), '珠宝,奢侈品,金融', 1, 60, UNIX_TIMESTAMP());

-- =====================================================
-- 8. 应用版本号更新
-- =====================================================
UPDATE `i8j_config` SET `value` = '2.9.23' WHERE `name` = 'app_version';
UPDATE `i8j_config` SET `value` = 'V2.9.23' WHERE `name` = 'version';

-- =====================================================
-- 迁移完成
-- =====================================================
SELECT 'V2.9.23 数据库迁移完成' AS status;
