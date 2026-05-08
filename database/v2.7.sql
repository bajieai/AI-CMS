SET NAMES utf8mb4;
-- AI-CMS V2.7 数据库增量脚本
-- 执行顺序：先建表 → 再ALTER → 最后INSERT

-- ============================================
-- 1. 新建表
-- ============================================

-- 1.1 邮件队列持久化表 (P0-2)
CREATE TABLE IF NOT EXISTS `i8j_email_queue` (
    `id` int UNSIGNED AUTO_INCREMENT,
    `template_code` varchar(100) NOT NULL DEFAULT '',
    `to_email` varchar(255) NOT NULL,
    `vars` text COMMENT '模板变量JSON',
    `status` tinyint DEFAULT 0 COMMENT '0待发 1已发 2失败',
    `retry_count` tinyint DEFAULT 0,
    `max_retries` tinyint DEFAULT 3 COMMENT '最大重试次数',
    `error_msg` varchar(500) DEFAULT '',
    `create_time` int UNSIGNED DEFAULT 0,
    `sent_time` int UNSIGNED DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮件队列';

-- 1.2 用户已购章节表 (Sprint2-2.1)
CREATE TABLE IF NOT EXISTS `i8j_user_chapter` (
    `id` int UNSIGNED AUTO_INCREMENT,
    `member_id` int UNSIGNED NOT NULL,
    `content_id` int UNSIGNED NOT NULL COMMENT '章节content_id',
    `parent_id` int UNSIGNED DEFAULT 0 COMMENT '父内容id',
    `order_sn` varchar(50) DEFAULT '' COMMENT '订单号',
    `price` decimal(10,2) DEFAULT 0.00 COMMENT '购买价格',
    `create_time` int UNSIGNED DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_member_chapter` (`member_id`, `content_id`),
    KEY `idx_content` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户已购章节';

-- ============================================
-- 2. ALTER 现有表（MySQL 8.0 不支持 ADD COLUMN IF NOT EXISTS，以下语句已在 V2.7 部署时手动执行）
-- ============================================

-- 2.1 VIP规范化：member_level 添加 is_vip 字段 (P0-5)
-- ALTER TABLE `i8j_member_level`
--     ADD COLUMN `is_vip` tinyint NOT NULL DEFAULT 0 COMMENT '是否VIP等级' AFTER `is_default`;

-- 2.2 visit_log 添加 visitor_id (P0-6)
-- ALTER TABLE `i8j_visit_log`
--     ADD COLUMN `visitor_id` int UNSIGNED DEFAULT 0 COMMENT '会员id,0=游客' AFTER `content_id`,
--     ADD KEY `idx_visitor_time` (`visitor_id`, `visit_time`);

-- 2.3 form 添加 fields_config (Sprint3-3.3 表单可视化编辑器)
-- ALTER TABLE `i8j_form`
--     ADD COLUMN `fields_config` json NULL COMMENT '可视化编辑器字段配置' AFTER `fields`;

-- 2.4 content 添加 preview_length (Sprint2-2.2 试读截断)
-- ALTER TABLE `i8j_content`
--     ADD COLUMN `preview_length` int DEFAULT 500 COMMENT '付费章节试读截断字数' AFTER `content`;

-- ============================================
-- 3. 配置项新增/更新
-- ============================================

-- 3.1 验证码配置 (P0-7)
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('captcha_driver', 'local', 'security', 'select', '验证码驱动(local=本地GD/tencent=腾讯验证码)', 10)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('captcha_tencent_appid', '', 'security', 'text', '腾讯验证码AppID', 11)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('captcha_tencent_secret', '', 'security', 'password', '腾讯验证码Secret', 12)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- 3.2 系统配置 (Sprint4)
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('cdn_enabled', '0', 'system', 'switch', '是否启用CDN', 20)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('cdn_domain', '', 'system', 'text', 'CDN域名(如 https://cdn.example.com)', 21)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- 3.3 积分签到配置 (Sprint2-2.5)
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('points_signin', '5', 'points', 'number', '每日签到基础积分', 1)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('points_signin_3days', '10', 'points', 'number', '连续签到3天额外奖励', 2)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('points_signin_7days', '30', 'points', 'number', '连续签到7天额外奖励', 3)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('points_consume_ratio', '0', 'points', 'number', '消费返积分比例(0=不返, 0.1=返10%)', 4)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- ============================================
-- 4. 开关类型修正 (已在运行时用SQL修正，此处保留记录)
-- ============================================
-- UPDATE i8j_config SET type='switch' WHERE name IN ('smtp_ssl','oauth_qq_enabled','oauth_wechat_enabled','wechat_pay_enabled');
