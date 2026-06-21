-- ============================================================
-- AI-CMS V2.9.27 Sprint T 数据库变更脚本
-- 主题：SSE实时推送 — DB持久化队列+连接管理+离线消息
-- ============================================================

SET NAMES utf8mb4;
SET @db = DATABASE();

-- ------------------------------------------------------------
-- 1. SSE消息队列表 (T-1 DB持久化队列)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_sse_message_queue` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID(自增,用作Last-Event-Id)',
    `channel` VARCHAR(30) NOT NULL DEFAULT 'system' COMMENT '通道(audit/comment/system/notification)',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '目标用户ID(0=广播)',
    `event_type` VARCHAR(50) NOT NULL DEFAULT 'message' COMMENT '事件类型',
    `payload` TEXT COMMENT '消息内容(JSON)',
    `is_delivered` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已投递(1是/0否)',
    `delivered_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '投递时间',
    `expires_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '过期时间(0=不过期)',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_channel_user` (`channel`, `user_id`),
    KEY `idx_is_delivered` (`is_delivered`),
    KEY `idx_create_time` (`create_time`),
    KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SSE消息队列(DB持久化)';

-- ------------------------------------------------------------
-- 2. SSE客户端连接表 (T-4 连接管理)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_sse_client` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `client_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '客户端唯一标识(UUID)',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID(0=游客)',
    `ip_address` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '客户端IP',
    `user_agent` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'User-Agent',
    `channels` VARCHAR(200) NOT NULL DEFAULT 'system' COMMENT '订阅通道(逗号分隔)',
    `last_event_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后接收的消息ID',
    `last_active` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后活跃时间',
    `connect_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '连接建立时间',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态(1在线/0离线)',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_client_id` (`client_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_ip_address` (`ip_address`),
    KEY `idx_status` (`status`),
    KEY `idx_last_active` (`last_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SSE客户端连接';

-- ------------------------------------------------------------
-- 3. 菜单项 (V2.9.27 Sprint T)
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(520, 1, 0, 'SSE监控', '/admin/sse_monitor/index', 'sse_monitor.*', 'sse_monitor', 'bi bi-broadcast', 90, 1);

-- ------------------------------------------------------------
-- 4. 系统设置
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_config` (`name`, `value`, `group`) VALUES
('sse_max_connections_per_ip', '5', 'system'),
('sse_max_connections_per_user', '3', 'system'),
('sse_connection_timeout', '1800', 'system'),
('sse_heartbeat_interval', '30', 'system'),
('sse_message_ttl', '3600', 'system'),
('sse_offline_message_limit', '100', 'system');
