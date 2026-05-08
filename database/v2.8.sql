-- V2.8 数据库迁移脚本
-- 执行日期: 2026-05-08
-- 说明: AI配图+质量检测+SEO优化+运营报表+流量分析+AI统计+社交分享+邀请返积分+VIP免费阅读

-- 1. content表补全章节相关字段（技术审核要求补全3个字段）
ALTER TABLE `i8j_content`
  ADD COLUMN IF NOT EXISTS `chapter_price` decimal(10,2) DEFAULT 0.00 COMMENT '章节单购价格' AFTER `is_free_chapter`,
  ADD COLUMN IF NOT EXISTS `chapter_count` int UNSIGNED DEFAULT 0 COMMENT '总章节数(父记录)' AFTER `chapter_price`,
  ADD COLUMN IF NOT EXISTS `chapter_title` varchar(255) DEFAULT '' COMMENT '章节标题' AFTER `chapter_sort`;

-- 2. VIP免费阅读范围配置
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('vip_free_read_mode', '0', 'member', 'select', 'VIP免费阅读范围: 0=不免费 1=全部免费', 30)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- 3. 邀请关系表(产品需求设计+技术审核增加invitee_ip)
CREATE TABLE IF NOT EXISTS `i8j_invite_relation` (
  `id` int UNSIGNED AUTO_INCREMENT,
  `inviter_id` int UNSIGNED NOT NULL COMMENT '邀请人',
  `invitee_id` int UNSIGNED NOT NULL COMMENT '被邀请人',
  `invite_code` varchar(32) NOT NULL COMMENT '邀请码',
  `invitee_ip` varchar(45) DEFAULT '' COMMENT '被邀请人IP(防刷审计)',
  `reward_points` int DEFAULT 0 COMMENT '已发放积分',
  `reward_stage` tinyint DEFAULT 0 COMMENT '0注册/1首次签到/2首次付费',
  `create_time` int UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_inviter` (`inviter_id`),
  UNIQUE KEY `uk_invitee` (`invitee_id`),
  KEY `idx_code` (`invite_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邀请关系表';

-- 4. 邀请积分配置
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('points_invite_register', '50', 'points', 'number', '邀请注册奖励积分', 5),
('points_invite_signin', '20', 'points', 'number', '被邀请人首次签到奖励邀请人积分', 6),
('points_invite_pay', '50', 'points', 'number', '被邀请人首次付费奖励邀请人积分', 7),
('points_invitee_register', '20', 'points', 'number', '被邀请人注册奖励积分', 8)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- 5. 社交分享配置
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('wechat_share_appid', '', 'social', 'text', '微信JS-SDK AppID', 1),
('wechat_share_secret', '', 'social', 'password', '微信JS-SDK Secret', 2),
('social_share_enabled', '1', 'social', 'switch', '是否启用社交分享', 3)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- 6. AI配图配置
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('image_provider', 'tongyi_wanxiang', 'ai', 'select', 'AI配图Provider', 10),
('image_api_key', '', 'ai', 'password', 'AI配图API Key', 11),
('image_default_count', '1', 'ai', 'number', '默认生成配图数(1-5)', 12),
('image_default_style', 'realistic', 'ai', 'select', '默认配图风格', 13),
('image_timeout', '15', 'ai', 'number', '配图API超时(秒)', 14)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- 7. visit_log表增加event_type字段（用于分享统计埋点）
ALTER TABLE `i8j_visit_log`
  ADD COLUMN IF NOT EXISTS `event_type` varchar(20) DEFAULT 'visit' COMMENT '事件类型: visit/share/click' AFTER `referer`,
  ADD COLUMN IF NOT EXISTS `share_channel` varchar(20) DEFAULT '' COMMENT '分享渠道: wechat/weibo/qq/copy' AFTER `event_type`,
  ADD KEY IF NOT EXISTS `idx_event_type` (`event_type`);

-- 8. AI生成统计相关配置
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('ai_stat_enabled', '1', 'ai', 'switch', '是否启用AI生成统计', 15),
('ai_stat_retention_days', '30', 'ai', 'number', 'AI统计保留天数', 16)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
