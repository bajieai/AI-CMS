-- ============================================================
-- V2.9.12: 模板生态·内容智能化 — 数据库迁移脚本
-- 幂等DDL：可重复执行不报错
-- ============================================================

SET NAMES utf8mb4;
SET @dbname = DATABASE();
SET @tbl_prefix = (SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME LIKE '%template_store' LIMIT 1);
SET @tbl_prefix = IFNULL(@tbl_prefix, 'i8j_template_store');
SET @tbl_prefix = SUBSTRING(@tbl_prefix, 1, LENGTH(@tbl_prefix) - LENGTH('template_store'));

-- ============================================================
-- 1. 新建 10 张模板生态相关表
-- ============================================================

-- 1.1 模板商店表
SET @t1 = CONCAT(@tbl_prefix, 'template_store');
SET @e1 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t1);
SET @s1 = IF(@e1 = 0, CONCAT('CREATE TABLE `', @t1, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(64) NOT NULL DEFAULT "" COMMENT "模板唯一标识",
  `name` varchar(128) NOT NULL DEFAULT "" COMMENT "模板名称",
  `category_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "分类ID",
  `description` text COMMENT "模板描述",
  `screenshots` json DEFAULT NULL COMMENT "预览截图JSON数组",
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT "售价(0表示免费)",
  `author_name` varchar(100) NOT NULL DEFAULT "" COMMENT "作者名称",
  `author_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "开发者用户ID",
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT "状态:0待审核/1上架/2下架/3拒绝",
  `is_featured` tinyint(1) NOT NULL DEFAULT 0 COMMENT "是否推荐:0否/1是",
  `quality_score` int(11) NOT NULL DEFAULT 0 COMMENT "AI质量评分(0-100)",
  `install_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "安装次数",
  `rating_avg` decimal(2,1) NOT NULL DEFAULT 5.0 COMMENT "平均评分(1-5)",
  `rating_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "评分人数",
  `version` varchar(20) NOT NULL DEFAULT "1.0.0" COMMENT "版本号",
  `requirements` json DEFAULT NULL COMMENT "环境要求JSON",
  `file_size` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "文件大小(字节)",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_category_status` (`category_id`, `status`),
  KEY `idx_featured` (`is_featured`, `status`),
  KEY `idx_author` (`author_id`),
  KEY `idx_rating` (`rating_avg`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="模板商店表(V2.9.12)"'), 'SELECT "template_store already exists" AS msg');
PREPARE stmt1 FROM @s1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

-- 1.2 模板分类表
SET @t2 = CONCAT(@tbl_prefix, 'template_store_category');
SET @e2 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t2);
SET @s2 = IF(@e2 = 0, CONCAT('CREATE TABLE `', @t2, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT "" COMMENT "分类名称",
  `slug` varchar(64) NOT NULL DEFAULT "" COMMENT "分类标识",
  `description` varchar(255) DEFAULT NULL COMMENT "分类描述",
  `icon` varchar(64) DEFAULT NULL COMMENT "图标类名",
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT "排序号",
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT "是否启用:0否/1是",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_enabled_sort` (`is_enabled`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="模板商店分类表(V2.9.12)"'), 'SELECT "template_store_category already exists" AS msg');
PREPARE stmt2 FROM @s2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- 1.3 模板安装记录表
SET @t3 = CONCAT(@tbl_prefix, 'template_install');
SET @e3 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t3);
SET @s3 = IF(@e3 = 0, CONCAT('CREATE TABLE `', @t3, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "商店模板ID",
  `member_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "网站主用户ID",
  `slug` varchar(64) NOT NULL DEFAULT "" COMMENT "模板标识",
  `theme_name` varchar(128) NOT NULL DEFAULT "" COMMENT "模板名称",
  `is_active` tinyint(1) NOT NULL DEFAULT 0 COMMENT "是否当前激活:0否/1是",
  `install_path` varchar(255) NOT NULL DEFAULT "" COMMENT "安装路径",
  `config` json DEFAULT NULL COMMENT "配置数据JSON",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_slug` (`member_id`, `slug`),
  KEY `idx_member_active` (`member_id`, `is_active`),
  KEY `idx_store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="模板安装记录表(V2.9.12)"'), 'SELECT "template_install already exists" AS msg');
PREPARE stmt3 FROM @s3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

-- 1.4 模板订单表
SET @t4 = CONCAT(@tbl_prefix, 'template_order');
SET @e4 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t4);
SET @s4 = IF(@e4 = 0, CONCAT('CREATE TABLE `', @t4, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL DEFAULT "" COMMENT "订单编号",
  `store_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "商店模板ID",
  `member_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "购买者用户ID",
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT "订单金额",
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT "状态:0待支付/1已支付/2已退款/3已关闭",
  `pay_type` varchar(20) NOT NULL DEFAULT "" COMMENT "支付方式",
  `pay_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "支付时间",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_member_status` (`member_id`, `status`),
  KEY `idx_store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="模板订单表(V2.9.12)"'), 'SELECT "template_order already exists" AS msg');
PREPARE stmt4 FROM @s4;
EXECUTE stmt4;
DEALLOCATE PREPARE stmt4;

-- 1.5 模板评分评论表
SET @t5 = CONCAT(@tbl_prefix, 'template_review');
SET @e5 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t5);
SET @s5 = IF(@e5 = 0, CONCAT('CREATE TABLE `', @t5, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "商店模板ID",
  `member_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "评论者用户ID",
  `rating` tinyint(1) NOT NULL DEFAULT 5 COMMENT "评分1-5",
  `content` text COMMENT "评论内容",
  `is_audited` tinyint(1) NOT NULL DEFAULT 0 COMMENT "审核状态:0待审核/1通过/2拒绝",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_store_member` (`store_id`, `member_id`),
  KEY `idx_store_audit` (`store_id`, `is_audited`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="模板评分评论表(V2.9.12)"'), 'SELECT "template_review already exists" AS msg');
PREPARE stmt5 FROM @s5;
EXECUTE stmt5;
DEALLOCATE PREPARE stmt5;

-- 1.6 模板配色变体表
SET @t6 = CONCAT(@tbl_prefix, 'template_color_variant');
SET @e6 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t6);
SET @s6 = IF(@e6 = 0, CONCAT('CREATE TABLE `', @t6, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "商店模板ID",
  `name` varchar(64) NOT NULL DEFAULT "" COMMENT "配色方案名称",
  `description` varchar(255) DEFAULT NULL COMMENT "描述",
  `colors` json DEFAULT NULL COMMENT "色值JSON对象",
  `css_variables` text COMMENT "CSS变量文本",
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT "是否默认:0否/1是",
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT "排序号",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_store_sort` (`store_id`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="模板配色变体表(V2.9.12)"'), 'SELECT "template_color_variant already exists" AS msg');
PREPARE stmt6 FROM @s6;
EXECUTE stmt6;
DEALLOCATE PREPARE stmt6;

-- 1.7 模板自定义配置表
SET @t7 = CONCAT(@tbl_prefix, 'template_custom_config');
SET @e7 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t7);
SET @s7 = IF(@e7 = 0, CONCAT('CREATE TABLE `', @t7, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "网站主用户ID",
  `slug` varchar(64) NOT NULL DEFAULT "" COMMENT "模板标识",
  `config_key` varchar(64) NOT NULL DEFAULT "" COMMENT "配置键",
  `config_value` text COMMENT "配置值",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_slug_key` (`member_id`, `slug`, `config_key`),
  KEY `idx_member_slug` (`member_id`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="模板自定义配置表(V2.9.12)"'), 'SELECT "template_custom_config already exists" AS msg');
PREPARE stmt7 FROM @s7;
EXECUTE stmt7;
DEALLOCATE PREPARE stmt7;

-- 1.8 模板备份记录表
SET @t8 = CONCAT(@tbl_prefix, 'template_backup');
SET @e8 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t8);
SET @s8 = IF(@e8 = 0, CONCAT('CREATE TABLE `', @t8, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "网站主用户ID",
  `slug` varchar(64) NOT NULL DEFAULT "" COMMENT "模板标识",
  `backup_name` varchar(128) NOT NULL DEFAULT "" COMMENT "备份名称",
  `backup_path` varchar(255) NOT NULL DEFAULT "" COMMENT "备份文件路径",
  `config_snapshot` json DEFAULT NULL COMMENT "配置快照JSON",
  `type` varchar(20) NOT NULL DEFAULT "manual" COMMENT "备份类型:manual手动/auto自动",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_member_slug` (`member_id`, `slug`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="模板备份记录表(V2.9.12)"'), 'SELECT "template_backup already exists" AS msg');
PREPARE stmt8 FROM @s8;
EXECUTE stmt8;
DEALLOCATE PREPARE stmt8;

-- 1.9 AI配图任务表
SET @t9 = CONCAT(@tbl_prefix, 'ai_image_task');
SET @e9 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t9);
SET @s9 = IF(@e9 = 0, CONCAT('CREATE TABLE `', @t9, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "关联内容ID",
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT "状态:0排队中/1生成中/2完成/3失败",
  `prompt` text COMMENT "生成提示词",
  `image_url` varchar(500) DEFAULT NULL COMMENT "生成图片URL",
  `provider` varchar(32) DEFAULT NULL COMMENT "图片提供商",
  `error_msg` varchar(500) DEFAULT NULL COMMENT "错误信息",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_status` (`status`, `create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="AI配图任务表(V2.9.12)"'), 'SELECT "ai_image_task already exists" AS msg');
PREPARE stmt9 FROM @s9;
EXECUTE stmt9;
DEALLOCATE PREPARE stmt9;

-- 1.10 开发者上传审核表
SET @t10 = CONCAT(@tbl_prefix, 'template_dev_upload');
SET @e10 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t10);
SET @s10 = IF(@e10 = 0, CONCAT('CREATE TABLE `', @t10, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "开发者用户ID",
  `store_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "关联商店模板ID(审核通过后)",
  `slug` varchar(64) NOT NULL DEFAULT "" COMMENT "模板标识",
  `name` varchar(128) NOT NULL DEFAULT "" COMMENT "模板名称",
  `version` varchar(20) NOT NULL DEFAULT "1.0.0" COMMENT "版本号",
  `file_path` varchar(255) NOT NULL DEFAULT "" COMMENT "上传文件路径",
  `screenshots` json DEFAULT NULL COMMENT "预览截图JSON数组",
  `description` text COMMENT "模板描述",
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT "状态:0待审核/1通过/2拒绝/3需修改",
  `audit_comment` varchar(500) DEFAULT NULL COMMENT "审核意见",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_status` (`status`, `create_time`),
  KEY `idx_store` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="开发者模板上传审核表(V2.9.12)"'), 'SELECT "template_dev_upload already exists" AS msg');
PREPARE stmt10 FROM @s10;
EXECUTE stmt10;
DEALLOCATE PREPARE stmt10;

-- ============================================================
-- 2. 3张 ALTER 表结构变更
-- ============================================================

-- 2.1 content 表增加 AI配图URL字段
SET @col_a1 = 'ai_image_url';
SET @exists_a1 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = CONCAT(@tbl_prefix, 'content') AND COLUMN_NAME = @col_a1);
SET @sql_a1 = IF(@exists_a1 = 0,
  CONCAT('ALTER TABLE `', @tbl_prefix, 'content` ADD COLUMN `', @col_a1, '` varchar(255) DEFAULT NULL COMMENT ''AI配图URL(V2.9.12)'' AFTER `cover`'),
  'SELECT "ai_image_url already exists" AS msg'
);
PREPARE stmt_a1 FROM @sql_a1;
EXECUTE stmt_a1;
DEALLOCATE PREPARE stmt_a1;

-- 2.2 content 表增加 AI SEO优化数据JSON字段
SET @col_a2 = 'ai_seo_json';
SET @exists_a2 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = CONCAT(@tbl_prefix, 'content') AND COLUMN_NAME = @col_a2);
SET @sql_a2 = IF(@exists_a2 = 0,
  CONCAT('ALTER TABLE `', @tbl_prefix, 'content` ADD COLUMN `', @col_a2, '` json DEFAULT NULL COMMENT ''AI SEO优化数据JSON(V2.9.12)'' AFTER `seo_description`'),
  'SELECT "ai_seo_json already exists" AS msg'
);
PREPARE stmt_a2 FROM @sql_a2;
EXECUTE stmt_a2;
DEALLOCATE PREPARE stmt_a2;

-- 2.3 theme_info 表增加来源商店模板ID字段
SET @col_a3 = 'store_id';
SET @exists_a3 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = CONCAT(@tbl_prefix, 'theme_info') AND COLUMN_NAME = @col_a3);
SET @sql_a3 = IF(@exists_a3 = 0,
  CONCAT('ALTER TABLE `', @tbl_prefix, 'theme_info` ADD COLUMN `', @col_a3, '` int(11) unsigned NOT NULL DEFAULT 0 COMMENT ''来源商店模板ID，0表示非商店模板(V2.9.12)'' AFTER `screenshots`'),
  'SELECT "store_id already exists" AS msg'
);
PREPARE stmt_a3 FROM @sql_a3;
EXECUTE stmt_a3;
DEALLOCATE PREPARE stmt_a3;

-- ============================================================
-- 3. 行业分类种子数据（幂等：先删后插，确保ID稳定）
-- ============================================================

SET @cat_tbl = CONCAT('`', @tbl_prefix, 'template_store_category`');
SET @del_cats = CONCAT('DELETE FROM ', @cat_tbl, ' WHERE `id` <= 10');
PREPARE stmt_del_cat FROM @del_cats;
EXECUTE stmt_del_cat;
DEALLOCATE PREPARE stmt_del_cat;

SET @insert_cats = CONCAT('
INSERT INTO ', @cat_tbl, ' (`id`, `name`, `slug`, `description`, `icon`, `sort`, `is_enabled`) VALUES
(1, "企业商务", "corporate", "企业官网、商务展示类模板", "bi bi-briefcase", 1, 1),
(2, "电商促销", "ecommerce", "在线商城、促销活动类模板", "bi bi-cart", 2, 1),
(3, "博客文艺", "blog", "个人博客、文学创作类模板", "bi bi-journal-text", 3, 1),
(4, "门户资讯", "portal", "新闻门户、资讯聚合类模板", "bi bi-newspaper", 4, 1),
(5, "医疗健康", "medical", "医院诊所、健康管理类模板", "bi bi-heart-pulse", 5, 1),
(6, "教育培训", "education", "学校机构、在线教育类模板", "bi bi-mortarboard", 6, 1),
(7, "餐饮美食", "catering", "餐厅酒店、美食推荐类模板", "bi bi-cup-hot", 7, 1),
(8, "金融理财", "finance", "银行保险、投资理财类模板", "bi bi-bank", 8, 1),
(9, "科技互联网", "technology", "科技公司、SaaS产品类模板", "bi bi-cpu", 9, 1),
(10, "房产家居", "realestate", "房产中介、家居装修类模板", "bi bi-house-door", 10, 1)
');
PREPARE stmt_ins_cat FROM @insert_cats;
EXECUTE stmt_ins_cat;
DEALLOCATE PREPARE stmt_ins_cat;

-- ============================================================
-- 11. 模板自定义配置表
-- ============================================================
SET @t11 = CONCAT(@tbl_prefix, 'template_custom_config');
SET @e11 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t11);
SET @s11 = IF(@e11 = 0, CONCAT('CREATE TABLE `', @t11, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "用户ID",
  `theme_slug` varchar(64) NOT NULL DEFAULT "" COMMENT "模板标识",
  `config_key` varchar(64) NOT NULL DEFAULT "" COMMENT "配置键",
  `config_value` text COMMENT "配置值(JSON或字符串)",
  `config_type` varchar(32) NOT NULL DEFAULT "style" COMMENT "配置类型:style/layout",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_slug_key` (`member_id`,`theme_slug`,`config_key`),
  KEY `idx_theme` (`theme_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT="模板自定义配置";'), 'SELECT "template_custom_config exists" AS msg');
PREPARE stmt11 FROM @s11; EXECUTE stmt11; DEALLOCATE PREPARE stmt11;

-- ============================================================
-- 12. 模板备份记录表
-- ============================================================
SET @t12 = CONCAT(@tbl_prefix, 'template_backup');
SET @e12 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t12);
SET @s12 = IF(@e12 = 0, CONCAT('CREATE TABLE `', @t12, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "用户ID",
  `theme_slug` varchar(64) NOT NULL DEFAULT "" COMMENT "模板标识",
  `backup_name` varchar(128) NOT NULL DEFAULT "" COMMENT "备份名称",
  `backup_file` varchar(255) NOT NULL DEFAULT "" COMMENT "备份文件名",
  `backup_size` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "备份大小(字节)",
  `config_json` text COMMENT "配置JSON数据",
  `is_auto` tinyint(1) NOT NULL DEFAULT 0 COMMENT "是否自动备份:0否/1是",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_member_theme` (`member_id`,`theme_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT="模板备份记录";'), 'SELECT "template_backup exists" AS msg');
PREPARE stmt12 FROM @s12; EXECUTE stmt12; DEALLOCATE PREPARE stmt12;

-- ============================================================
-- 13. AI配图任务表
-- ============================================================
SET @t13 = CONCAT(@tbl_prefix, 'ai_image_task');
SET @e13 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t13);
SET @s13 = IF(@e13 = 0, CONCAT('CREATE TABLE `', @t13, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "内容ID",
  `member_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "用户ID",
  `prompt` text COMMENT "生成提示词",
  `image_url` varchar(512) NOT NULL DEFAULT "" COMMENT "生成图片URL",
  `provider` varchar(32) NOT NULL DEFAULT "" COMMENT "图片提供商",
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT "状态:0排队中/1生成中/2完成/3失败",
  `error_msg` varchar(500) NOT NULL DEFAULT "" COMMENT "错误信息",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  `complete_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT="AI配图任务";'), 'SELECT "ai_image_task exists" AS msg');
PREPARE stmt13 FROM @s13; EXECUTE stmt13; DEALLOCATE PREPARE stmt13;

-- ============================================================
-- 14. 模板开发者上传审核表
-- ============================================================
SET @t14 = CONCAT(@tbl_prefix, 'template_dev_upload');
SET @e14 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t14);
SET @s14 = IF(@e14 = 0, CONCAT('CREATE TABLE `', @t14, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "开发者用户ID",
  `theme_slug` varchar(64) NOT NULL DEFAULT "" COMMENT "模板标识",
  `theme_name` varchar(128) NOT NULL DEFAULT "" COMMENT "模板名称",
  `version` varchar(32) NOT NULL DEFAULT "1.0.0" COMMENT "版本号",
  `file_path` varchar(255) NOT NULL DEFAULT "" COMMENT "上传文件路径",
  `manifest_json` text COMMENT "manifest描述JSON",
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT "状态:0待审核/1通过/2拒绝/3需修改",
  `audit_remark` varchar(500) NOT NULL DEFAULT "" COMMENT "审核意见",
  `auditor_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "审核员ID",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  `audit_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_status` (`status`),
  KEY `idx_slug` (`theme_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT="模板开发者上传审核";'), 'SELECT "template_dev_upload exists" AS msg');
PREPARE stmt14 FROM @s14; EXECUTE stmt14; DEALLOCATE PREPARE stmt14;

SELECT 'V2.9.12 migration completed' AS result;
