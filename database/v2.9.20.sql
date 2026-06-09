-- ============================================================
-- AI-CMS V2.9.20 数据库变更脚本
-- 主题：内容差异化 · 模板强化 · 基础设施
-- 整合三方共识9项修正：
--   [小扣-1] i8j_template_category 加 type 列(varchar20)+索引
--   [小扣-2] i8j_content_model 加 icon 列(varchar100)
--   [小扣-3] i8j_content_model_field.default_value 改为 text
--   [小产-8] 模板分类种子改为 model×industry×style 三维度18条
-- ============================================================

SET NAMES utf8mb4;
SET @db = DATABASE();

-- ------------------------------------------------------------
-- 1. 内容模型定义表 (A-1)
-- [修正：小扣-2] 新增 icon 列
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_content_model` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '模型名称',
    `code` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '模型标识(unique)',
    `description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '描述',
    `icon` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '图标CSS class或URL',
    `type` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '关联内容类型(1产品/2案例/3新闻/4下载/5招聘/6单页)',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(1启用/0禁用)',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_type` (`type`),
    KEY `idx_status_sort` (`status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容模型定义';

-- ------------------------------------------------------------
-- 2. 内容模型扩展字段表 (A-1)
-- [修正：小扣-3] default_value 从 varchar(255) 改为 text
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_content_model_field` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `model_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '模型ID',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '字段名(英文标识)',
    `label` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '字段标签(中文显示名)',
    `type` VARCHAR(30) NOT NULL DEFAULT 'text' COMMENT '字段类型(text/textarea/number/select/radio/checkbox/date/image/file)',
    `options` TEXT COMMENT '选项(JSON,用于select/radio/checkbox)',
    `default_value` TEXT COMMENT '默认值',
    `placeholder` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '占位提示',
    `required` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否必填(1是/0否)',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(1启用/0禁用)',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_model_id` (`model_id`),
    KEY `idx_model_status_sort` (`model_id`, `status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容模型扩展字段';

-- ------------------------------------------------------------
-- 3. 模板分类表 (B-1)
-- [修正：小扣-1] 新增 type 列(varchar20) 用于区分分类维度
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_template_category` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `parent_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级ID(0=顶级)',
    `type` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '分类维度(content_model/industry/style)',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '分类名称',
    `code` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '分类标识(unique)',
    `description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '描述',
    `icon` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '图标',
    `sort` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(1启用/0禁用)',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_type` (`type`),
    KEY `idx_status_sort` (`status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板分类';

-- ------------------------------------------------------------
-- 4. 模板-分类多对多映射表 (B-1)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_template_category_map` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `template_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '模板ID',
    `category_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '分类ID',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tmpl_cat` (`template_id`, `category_id`),
    KEY `idx_category_id` (`category_id`),
    KEY `idx_template_id` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板-分类映射';

-- ------------------------------------------------------------
-- 5. i8j_content 增加 model_id 列 (A-1)
-- ------------------------------------------------------------
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_content' AND COLUMN_NAME = 'model_id');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_content` ADD COLUMN `model_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT \'内容模型ID(0=未分配/使用旧逻辑)\' AFTER `type`', 'SELECT \'model_id字段已存在，跳过\' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 6. 内容模型种子数据 (A-1) — 6种预置模型
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_content_model` (`id`, `name`, `code`, `description`, `icon`, `type`, `status`, `sort`, `create_time`, `update_time`) VALUES
(1, '产品信息', 'model_product', '用于展示产品详情，支持价格、库存、规格等字段', 'bi bi-box-seam', 1, 1, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, '企业案例', 'model_case', '用于展示企业案例/项目，支持客户名称、项目周期等字段', 'bi bi-briefcase', 2, 1, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, '新闻资讯', 'model_news', '用于发布新闻文章，支持来源、作者等字段', 'bi bi-newspaper', 3, 1, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, '软件下载', 'model_download', '用于软件/资源下载，支持版本号、文件大小、下载次数等字段', 'bi bi-download', 4, 1, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(5, '人才招聘', 'model_job', '用于发布招聘信息，支持薪资范围、工作地点、学历要求等字段', 'bi bi-people', 5, 1, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(6, '单页介绍', 'model_page', '用于单页内容展示，支持副标题、封面图等字段', 'bi bi-file-earmark-text', 6, 1, 60, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ------------------------------------------------------------
-- 7. 内容模型扩展字段种子数据 (A-1) — 约15条
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_content_model_field` (`id`, `model_id`, `name`, `label`, `type`, `options`, `default_value`, `placeholder`, `required`, `sort`, `status`, `create_time`, `update_time`) VALUES
-- 产品信息模型 (model_id=1)
(1, 1, 'price', '价格', 'number', NULL, '0', '请输入产品价格', 1, 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, 1, 'stock', '库存数量', 'number', NULL, '0', '请输入库存数量', 1, 20, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, 1, 'spec', '产品规格', 'textarea', NULL, '', '请输入产品规格参数', 0, 30, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, 1, 'brand', '品牌', 'text', NULL, '', '请输入品牌名称', 0, 40, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- 企业案例模型 (model_id=2)
(5, 2, 'client_name', '客户名称', 'text', NULL, '', '请输入客户/公司名称', 1, 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(6, 2, 'project_period', '项目周期', 'text', NULL, '', '如：2024.01-2024.06', 0, 20, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(7, 2, 'industry', '所属行业', 'select', '["互联网","金融","教育","医疗","制造","其他"]', '互联网', '请选择所属行业', 0, 30, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- 新闻资讯模型 (model_id=3)
(8, 3, 'source', '文章来源', 'text', NULL, '', '请输入文章来源', 0, 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9, 3, 'author', '作者', 'text', NULL, '', '请输入作者姓名', 0, 20, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10, 3, 'is_top', '是否置顶', 'radio', '["否","是"]', '0', '', 0, 30, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- 软件下载模型 (model_id=4)
(11, 4, 'version', '版本号', 'text', NULL, '1.0.0', '如：1.0.0', 1, 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(12, 4, 'file_size', '文件大小', 'text', NULL, '', '如：15.6 MB', 0, 20, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(13, 4, 'download_url', '下载链接', 'text', NULL, '', '请输入下载链接', 1, 30, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- 人才招聘模型 (model_id=5)
(14, 5, 'salary_range', '薪资范围', 'text', NULL, '', '如：15K-25K', 1, 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(15, 5, 'location', '工作地点', 'text', NULL, '', '如：北京市海淀区', 1, 20, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(16, 5, 'education', '学历要求', 'select', '["不限","大专","本科","硕士","博士"]', '本科', '请选择学历要求', 1, 30, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- 单页介绍模型 (model_id=6)
(17, 6, 'subtitle', '副标题', 'text', NULL, '', '请输入副标题', 0, 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(18, 6, 'cover_image', '封面图', 'image', NULL, '', '请上传封面图片', 0, 20, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ------------------------------------------------------------
-- 8. 模板分类种子数据 (B-1)
-- [修正：小产-8] 改为 model×industry×style 三维度18条
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_template_category` (`id`, `parent_id`, `type`, `name`, `code`, `description`, `icon`, `sort`, `status`, `create_time`, `update_time`) VALUES
-- content_model 维度（6条）
(1, 0, 'content_model', '通用型', 'cat_model_general', '适用于多种内容类型的通用模板', 'bi bi-grid', 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, 0, 'content_model', '文章型', 'cat_model_article', '专注于文章、博客类内容展示', 'bi bi-file-text', 20, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, 0, 'content_model', '产品型', 'cat_model_product', '适用于产品展示、电商类站点', 'bi bi-box', 30, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, 0, 'content_model', '图片型', 'cat_model_gallery', '专注于图片画廊、作品集展示', 'bi bi-images', 40, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(5, 0, 'content_model', '下载型', 'cat_model_download', '适用于软件下载、资源分享类站点', 'bi bi-cloud-download', 50, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(6, 0, 'content_model', '视频型', 'cat_model_video', '专注于视频内容展示与播放', 'bi bi-play-btn', 60, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- industry 维度（8条）
(7, 0, 'industry', '企业官网', 'cat_ind_enterprise', '适用于企业官方网站', 'bi bi-building', 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(8, 0, 'industry', '电商', 'cat_ind_ecommerce', '适用于在线商城、电商平台', 'bi bi-cart', 20, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9, 0, 'industry', '科技', 'cat_ind_tech', '适用于科技公司、IT服务类站点', 'bi bi-cpu', 30, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10, 0, 'industry', '教育', 'cat_ind_edu', '适用于培训机构、学校、在线课程', 'bi bi-mortarboard', 40, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(11, 0, 'industry', '餐饮', 'cat_ind_catering', '适用于餐厅、酒店、美食类站点', 'bi bi-cup-hot', 50, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(12, 0, 'industry', '医疗', 'cat_ind_medical', '适用于医院、诊所、健康类站点', 'bi bi-heart-pulse', 60, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(13, 0, 'industry', '金融', 'cat_ind_finance', '适用于银行、保险、投资类站点', 'bi bi-bank', 70, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(14, 0, 'industry', '个人博客', 'cat_ind_blog', '适用于个人博客、自媒体站点', 'bi bi-person', 80, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- style 维度（4条）
(15, 0, 'style', '简约现代', 'cat_style_minimal', '简洁大气的现代设计风格', 'bi bi-layout-text-window', 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(16, 0, 'style', '科技时尚', 'cat_style_tech', '充满科技感的时尚设计风格', 'bi bi-rocket', 20, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(17, 0, 'style', '自然温暖', 'cat_style_nature', '自然温馨、亲和力强设计风格', 'bi bi-tree', 30, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(18, 0, 'style', '活泼创意', 'cat_style_creative', '色彩丰富、富有创意的设计风格', 'bi bi-palette', 40, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ------------------------------------------------------------
-- 9. 菜单项 (V2.9.20 从500开始)
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(500, 4, 0, '内容模型', '/admin/content_model/index', 'content_model.*', 'content_model', 'bi bi-layers', 85, 1),
(501, 4, 0, '模板分类', '/admin/template_category/index', 'template_category.*', 'template_category', 'bi bi-tags', 86, 1),
(502, 4, 0, '模板安装', '/admin/template_install/index', 'template_install.*', 'template_install', 'bi bi-cloud-arrow-down', 87, 1);

-- ------------------------------------------------------------
-- 10. 系统设置：内容模型默认启用 (A-1)
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_config` (`name`, `value`, `group`)
VALUES ('content_model_enabled', '1', 'content');

-- ------------------------------------------------------------
-- 11. 系统设置：模板商店默认配置 (B-1)
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_config` (`name`, `value`, `group`)
VALUES ('template_store_category_enabled', '1', 'template');
