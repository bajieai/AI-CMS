-- ============================================================
-- AI-CMS V2.9.19 数据库变更脚本
-- 主题：推送增强 · 通知深化 · 风险修复
-- ============================================================

-- ------------------------------------------------------------
-- 1. 推送重试队列表 (D-1)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_push_retry` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `push_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '推送内容ID',
    `channel` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '通道标识',
    `reason` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '入队原因',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0=待重试 1=成功 -1=失败',
    `retry_count` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '已重试次数',
    `error_msg` TEXT COMMENT '错误信息',
    `next_retry_at` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '下次重试时间戳',
    `created_at` INT UNSIGNED NOT NULL DEFAULT 0,
    `updated_at` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    INDEX `idx_status_next` (`status`, `next_retry_at`),
    INDEX `idx_push_id` (`push_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推送重试队列';

-- ------------------------------------------------------------
-- 2. 分享点击追踪表幂等保护 (R-1)
-- 已存在于 v2.9.18.sql，此处确保新环境可用
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_share_click` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `content_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `source` VARCHAR(50) NOT NULL DEFAULT '',
    `ip` VARCHAR(45) NOT NULL DEFAULT '',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_content_id` (`content_id`),
    INDEX `idx_source` (`source`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分享点击日志';

-- ------------------------------------------------------------
-- 3. i8j_user 增加通知偏好设置 (N-1)
-- ------------------------------------------------------------
SET @db = DATABASE();
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_user' AND COLUMN_NAME = 'notify_settings');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_user` ADD COLUMN `notify_settings` TEXT COMMENT \'通知偏好设置 JSON\' AFTER `bio`', 'SELECT \'notify_settings字段已存在，跳过\' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 4. i8j_subscriber 增加标签和静默检测 (S-1)
-- ------------------------------------------------------------
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_subscriber' AND COLUMN_NAME = 'tag');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_subscriber` ADD COLUMN `tag` VARCHAR(100) NOT NULL DEFAULT \'\' COMMENT \'分组标签\' AFTER `source`', 'SELECT \'tag字段已存在，跳过\' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_subscriber' AND COLUMN_NAME = 'invalid_at');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_subscriber` ADD COLUMN `invalid_at` DATETIME DEFAULT NULL COMMENT \'标记为无效的时间\' AFTER `unsubscribed_at`', 'SELECT \'invalid_at字段已存在，跳过\' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 5. 邮件模板种子数据 (S-1)
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_email_template` (`code`, `name`, `subject`, `body`, `vars`, `is_enabled`, `create_time`, `update_time`)
VALUES
('subscribe_confirm', '订阅确认', '请确认订阅【{site_name}】', '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:Arial,sans-serif;background:#f5f5f5;padding:20px"><div style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;padding:30px"><h2 style="color:#333">📬 确认订阅</h2><p style="color:#666">您好！感谢您订阅<strong>{site_name}</strong>。</p><p style="color:#666">请点击下方按钮确认：</p><div style="text-align:center;margin:30px 0"><a href="{confirm_url}" style="display:inline-block;background:#007bff;color:#fff;padding:12px 32px;border-radius:6px;text-decoration:none;font-size:16px">确认订阅</a></div></div></body></html>', 'site_name,confirm_url', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('content_notify', '新内容通知', '【{site_name}】新内容发布：{title}', '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:Arial,sans-serif;background:#f5f5f5;padding:20px"><div style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;padding:30px"><h2 style="color:#333">{title}</h2><p style="color:#666;line-height:1.8">{summary}</p><div style="text-align:center;margin:30px 0"><a href="{content_url}" style="display:inline-block;background:#007bff;color:#fff;padding:12px 32px;border-radius:6px;text-decoration:none;font-size:16px">查看详情</a></div></div></body></html>', 'site_name,title,summary,content_url', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('subscribe_welcome', '订阅欢迎', '欢迎订阅【{site_name}】', '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:Arial,sans-serif;background:#f5f5f5;padding:20px"><div style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;padding:30px"><h2 style="color:#333">🎉 订阅成功</h2><p style="color:#666">您已成功订阅<strong>{site_name}</strong>，我们将第一时间推送最新内容。</p></div></body></html>', 'site_name', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ------------------------------------------------------------
-- 6. 系统设置：推送全局超时 (D-1)
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_config` (`name`, `value`, `group`)
VALUES ('push_global_timeout', '60', 'push');

-- ------------------------------------------------------------
-- 7. 系统设置：通知默认偏好 (N-1)
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_config` (`name`, `value`, `group`)
VALUES ('notify_default_settings', '{"system":1,"review":1,"publish":1,"comment_reply":1,"content_approve":1,"content_reject":1,"reward_receive":1,"level_upgrade":1,"level_downgrade":1,"level_grace_warning":1}', 'notification');

-- ------------------------------------------------------------
-- 8. 菜单项：推送重试管理 + 通知设置
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`)
VALUES
(482, 4, 0, '推送重试', '/admin/push/retry', 'push.*', 'push_retry', 'bi bi-arrow-repeat', 92, 1),
(483, 4, 0, '通知设置', '/admin/notification/setting', 'notification.*', 'notify_setting', 'bi bi-sliders', 93, 1);

-- ------------------------------------------------------------
-- 9. i8j_subscriber 增加 fail_count (S-1c 静默检测)
-- ------------------------------------------------------------
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'i8j_subscriber' AND COLUMN_NAME = 'fail_count');
SET @sql = IF(@col = 0, 'ALTER TABLE `i8j_subscriber` ADD COLUMN `fail_count` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT \'连续发送失败次数\' AFTER `invalid_at`', 'SELECT \'fail_count字段已存在，跳过\' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 10. 菜单项：退订分析 (S-1b)
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`)
VALUES (491, 4, 0, '退订分析', '/admin/subscriber/analysis', 'subscriber.*', 'subscriber', 'bi bi-graph-down', 95, 1);

-- ------------------------------------------------------------
-- 11. 邮件模板：退订确认 (S-1a 补充)
-- ------------------------------------------------------------
INSERT IGNORE INTO `i8j_email_template` (`code`, `name`, `subject`, `body`, `vars`, `is_enabled`, `create_time`, `update_time`)
VALUES
('content_publish', '内容发布通知', '【{site_name}】新内容发布：{content_title}', '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:Arial,sans-serif;background:#f5f5f5;padding:20px"><div style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;padding:30px"><h2 style="color:#333">{content_title}</h2><p style="color:#666">{content_summary}</p><div style="text-align:center;margin:30px 0"><a href="{content_url}" style="display:inline-block;background:#007bff;color:#fff;padding:12px 32px;border-radius:6px;text-decoration:none;font-size:16px">查看详情</a></div></div></body></html>', 'site_name,content_title,content_summary,content_url,content_cover,unsubscribe_url,subscriber_email', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('unsubscribe', '退订确认', '您已成功退订 {site_name} 的邮件通知', '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:Arial,sans-serif;background:#f5f5f5;padding:20px"><div style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;padding:30px"><h2 style="color:#333">退订确认</h2><p style="color:#666">您已成功退订 <strong>{site_name}</strong> 的邮件通知，将不再收到相关内容推送。</p><p style="color:#666">如想重新订阅，请 <a href="{subscribe_url}">点击此处</a>。</p></div></body></html>', 'site_name,subscribe_url,subscriber_email', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
