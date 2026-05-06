-- ============================================================
-- 八界AI-CMS V2.6 补丁：创建 message_system 相关表
-- 执行方式：通过浏览器访问 /admin/upgrade/fixMessageSystem
-- 或手动在数据库中执行本文件
-- ============================================================

CREATE TABLE IF NOT EXISTS `{prefix}message_system` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL DEFAULT '' COMMENT '通知标题',
    `content` text COMMENT '通知内容',
    `type` varchar(50) DEFAULT 'system' COMMENT '类型:system/vip/ai/order',
    `target_url` varchar(500) DEFAULT '' COMMENT '跳转链接',
    `send_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送时间',
    `expire_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '过期时间(0永不过期)',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_type_time` (`type`, `send_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统通知表';

CREATE TABLE IF NOT EXISTS `{prefix}message_system_read` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `message_id` int UNSIGNED NOT NULL COMMENT '通知ID',
    `user_id` int UNSIGNED NOT NULL COMMENT '用户ID',
    `read_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '阅读时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_message_user` (`message_id`, `user_id`),
    KEY `idx_user` (`user_id`, `read_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统通知已读记录表';
