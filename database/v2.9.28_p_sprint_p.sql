-- AI-CMS V2.9.28 Sprint P 数据库变更脚本
-- 主题：插件市场在线安装（P-1~P-6）
-- 包含：插件安装日志表 + 插件更新检查表

SET NAMES utf8mb4;
SET @db = DATABASE();

-- ============================================================
-- P-1: 插件安装日志表（如不存在则创建，V2.9.25可能已创建）
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_plugin_install_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `plugin_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '插件标识',
    `action` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '操作类型(install/update/rollback)',
    `version_from` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '旧版本',
    `version_to` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '新版本',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态:0进行中1成功2失败',
    `log` TEXT COMMENT '详细日志',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作人',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_plugin` (`plugin_name`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件安装日志表(V2.9.28 P-1)';

-- ============================================================
-- P-6: 插件更新检查记录表
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_plugin_update_check` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `plugin_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '插件标识',
    `current_version` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '当前版本',
    `latest_version` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '最新版本',
    `has_update` TINYINT NOT NULL DEFAULT 0 COMMENT '是否有更新',
    `check_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '检查时间',
    `changelog` TEXT COMMENT '更新日志',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_plugin` (`plugin_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件更新检查记录表(V2.9.28 P-6)';

-- ============================================================
-- 菜单项
-- ============================================================
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(560, 2, 0, '插件在线安装', '/admin/plugin_store/index', 'plugin_store.*', 'plugin_store', 'bi bi-cloud-download', 80, 1),
(561, 2, 0, '插件批量管理', '/admin/plugin/batchIndex', 'plugin.*', 'plugin', 'bi bi-list-check', 81, 1);

-- ============================================================
-- 系统配置
-- ============================================================
INSERT IGNORE INTO `i8j_config` (`name`, `value`, `group`) VALUES
('plugin_market_url', 'https://market.aicms.io/api', 'plugin'),
('plugin_auto_update_check', '1', 'plugin'),
('plugin_security_scan', '1', 'plugin'),
('plugin_max_filesize', '52428800', 'plugin');
