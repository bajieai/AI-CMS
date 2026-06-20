-- AI-CMS V2.9.25 L-1: 插件市场在线安装 — 5 张插件模型表
-- 编码: utf8mb4
-- 执行: bin\migrate.bat database\v2.9.25_plugin_models.sql

-- ============================================
-- 1. i8j_plugin_package — 插件包主表
-- ============================================
DROP TABLE IF EXISTS `i8j_plugin_package`;
CREATE TABLE `i8j_plugin_package` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(64) NOT NULL COMMENT '插件标识（目录名）',
    `name` VARCHAR(128) NOT NULL COMMENT '插件名称',
    `version` VARCHAR(32) NOT NULL DEFAULT '1.0.0' COMMENT '当前版本',
    `description` TEXT COMMENT '插件描述',
    `author` VARCHAR(128) DEFAULT '' COMMENT '作者',
    `author_url` VARCHAR(255) DEFAULT '' COMMENT '作者链接',
    `icon` VARCHAR(255) DEFAULT '' COMMENT '图标URL',
    `screenshots` JSON COMMENT '截图URL数组',
    `tags` VARCHAR(255) DEFAULT '' COMMENT '标签，逗号分隔',
    `category_id` INT UNSIGNED DEFAULT 0 COMMENT '分类ID',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '价格（0=免费）',
    `is_free` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否免费',
    `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0=下架 1=上架 2=审核中',
    `download_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '下载次数',
    `install_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '安装次数',
    `rating_avg` DECIMAL(2,1) NOT NULL DEFAULT 5.0 COMMENT '平均评分',
    `rating_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '评分人数',
    `sort` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `is_recommended` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否推荐',
    `is_hot` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否热门',
    `signature` VARCHAR(255) DEFAULT '' COMMENT 'HMAC-SHA256 签名',
    `signature_method` VARCHAR(32) DEFAULT 'HMAC-SHA256' COMMENT '签名算法',
    `file_path` VARCHAR(255) DEFAULT '' COMMENT '包文件路径',
    `file_size` INT UNSIGNED DEFAULT 0 COMMENT '包文件大小（字节）',
    `file_hash` VARCHAR(64) DEFAULT '' COMMENT '包文件 SHA256 哈希',
    `requirements` JSON COMMENT '依赖要求（PHP版本、扩展等）',
    `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `update_time` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `delete_time` DATETIME DEFAULT NULL,
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_category` (`category_id`),
    KEY `idx_status` (`status`),
    KEY `idx_price` (`price`),
    KEY `idx_sort` (`sort`),
    KEY `idx_recommended` (`is_recommended`),
    KEY `idx_hot` (`is_hot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件包主表-V2.9.25';

-- ============================================
-- 2. i8j_plugin_version — 插件版本历史
-- ============================================
DROP TABLE IF EXISTS `i8j_plugin_version`;
CREATE TABLE `i8j_plugin_version` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `plugin_id` INT UNSIGNED NOT NULL COMMENT '插件包ID',
    `version` VARCHAR(32) NOT NULL COMMENT '版本号',
    `changelog` TEXT COMMENT '更新日志',
    `file_path` VARCHAR(255) DEFAULT '' COMMENT '版本包路径',
    `file_size` INT UNSIGNED DEFAULT 0 COMMENT '包大小',
    `file_hash` VARCHAR(64) DEFAULT '' COMMENT 'SHA256 哈希',
    `signature` VARCHAR(255) DEFAULT '' COMMENT '签名',
    `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0=废弃 1=可用',
    `is_current` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否当前版本',
    `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `update_time` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_plugin_version` (`plugin_id`, `version`),
    KEY `idx_plugin_id` (`plugin_id`),
    KEY `idx_is_current` (`is_current`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件版本历史-V2.9.25';

-- ============================================
-- 3. i8j_plugin_category — 插件分类
-- ============================================
DROP TABLE IF EXISTS `i8j_plugin_category`;
CREATE TABLE `i8j_plugin_category` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(64) NOT NULL COMMENT '分类名称',
    `description` VARCHAR(255) DEFAULT '' COMMENT '分类描述',
    `icon` VARCHAR(64) DEFAULT '' COMMENT '图标类名',
    `sort` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0=禁用 1=启用',
    `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `update_time` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_sort` (`sort`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件分类-V2.9.25';

-- 插入默认分类
INSERT INTO `i8j_plugin_category` (`name`, `description`, `icon`, `sort`, `status`) VALUES
('功能增强', '扩展系统核心功能的插件', 'bi bi-plug', 10, 1),
('SEO优化', '搜索引擎优化相关插件', 'bi bi-search', 20, 1),
('社交分享', '社交平台和分享功能插件', 'bi bi-share', 30, 1),
('数据统计', '数据分析和统计报表插件', 'bi bi-bar-chart', 40, 1),
('内容管理', '内容编辑和排版增强插件', 'bi bi-file-text', 50, 1),
('安全防护', '安全加固和防护插件', 'bi bi-shield-check', 60, 1),
('界面美化', '主题和界面美化插件', 'bi bi-palette', 70, 1),
('第三方集成', '第三方服务和API集成', 'bi bi-cloud', 80, 1);

-- ============================================
-- 4. i8j_plugin_dependency — 插件依赖关系
-- ============================================
DROP TABLE IF EXISTS `i8j_plugin_dependency`;
CREATE TABLE `i8j_plugin_dependency` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `plugin_id` INT UNSIGNED NOT NULL COMMENT '主插件ID',
    `depends_on_plugin_id` INT UNSIGNED NOT NULL COMMENT '依赖插件ID',
    `min_version` VARCHAR(32) DEFAULT '1.0.0' COMMENT '最低版本要求',
    `max_version` VARCHAR(32) DEFAULT '*' COMMENT '最高版本要求（*=无限制）',
    `is_required` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0=可选 1=必须',
    `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_plugin_dep` (`plugin_id`, `depends_on_plugin_id`),
    KEY `idx_plugin_id` (`plugin_id`),
    KEY `idx_depends_on` (`depends_on_plugin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件依赖关系-V2.9.25';

-- ============================================
-- 5. i8j_plugin_download_log — 插件下载日志
-- ============================================
DROP TABLE IF EXISTS `i8j_plugin_download_log`;
CREATE TABLE `i8j_plugin_download_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `plugin_id` INT UNSIGNED NOT NULL COMMENT '插件包ID',
    `version` VARCHAR(32) NOT NULL COMMENT '下载版本',
    `user_id` INT UNSIGNED DEFAULT 0 COMMENT '用户ID（0=匿名）',
    `ip` VARCHAR(64) DEFAULT '' COMMENT 'IP地址',
    `user_agent` VARCHAR(255) DEFAULT '' COMMENT 'UA',
    `source` VARCHAR(32) DEFAULT 'web' COMMENT '来源：web/admin/api',
    `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0=失败 1=成功',
    `error_msg` VARCHAR(255) DEFAULT '' COMMENT '失败原因',
    `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_plugin_id` (`plugin_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_create_time` (`create_time`),
    KEY `idx_source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件下载日志-V2.9.25';
