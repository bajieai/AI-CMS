SET NAMES utf8mb4;
-- V2.9 数据库迁移脚本
-- 执行日期: 2026-05-08
-- 说明: AI模板高度定制化(字段映射+质量检测) + 优惠券系统 + 评价评分 + visit_log补全

-- ============================================================
-- 1. i8j_ai_template 表新增字段映射和质量检测配置字段
-- ============================================================
-- ALTER TABLE `i8j_ai_template`
--   ADD COLUMN `field_mapping` text DEFAULT NULL COMMENT '字段映射规则JSON(含mappings/variables/image_config_override)' AFTER `image_config`;
-- ALTER TABLE `i8j_ai_template`
--   ADD COLUMN `quality_config` text DEFAULT NULL COMMENT '质量检测配置JSON(min_score/max_retry/action_on_low_quality/check_items)' AFTER `field_mapping`;

-- ============================================================
-- 2. i8j_visit_log 表补全 event_type 字段（如v2.8未执行）
-- ============================================================
SET @dbname = DATABASE();
SET @exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'i8j_visit_log'
    AND COLUMN_NAME = 'event_type'
);
SET @sql = IF(@exists = 0,
  'ALTER TABLE `i8j_visit_log`
    ADD COLUMN `event_type` varchar(20) DEFAULT \'visit\' COMMENT \'事件类型: visit/share/click\' AFTER `referrer`,
    ADD COLUMN `share_channel` varchar(20) DEFAULT \'\' COMMENT \'分享渠道: wechat/weibo/qq/copy\' AFTER `event_type`,
    ADD KEY `idx_event_type` (`event_type`)',
  'SELECT \"event_type column already exists\" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 3. 优惠券模板表
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_coupon_template` (
  `id` int UNSIGNED AUTO_INCREMENT,
  `coupon_name` varchar(100) NOT NULL COMMENT '券名称，如"满100减20"',
  `coupon_type` enum('reduce','discount','free_shipping') NOT NULL COMMENT '满减/discount/免邮',
  `condition_amount` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '门槛金额(免邮券填0)',
  `reduce_amount` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '满减券:减免金额; 折扣券:折扣率(0.9=9折); 免邮券:0',
  `total_stock` int NOT NULL DEFAULT 0 COMMENT '发行总量',
  `remain_stock` int NOT NULL DEFAULT 0 COMMENT '剩余库存',
  `per_user_limit` int NOT NULL DEFAULT 1 COMMENT '每人限领数量',
  `start_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '有效期开始时间',
  `end_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '有效期结束时间',
  `scope_type` enum('all','category','content') NOT NULL DEFAULT 'all' COMMENT '适用范围:全部/指定分类/指定商品',
  `scope_value` text DEFAULT NULL COMMENT '适用范围值(分类ID/商品ID,JSON数组)',
  `status` tinyint NOT NULL DEFAULT 0 COMMENT '状态:0草稿/1启用/2停用/3已过期',
  `create_time` int UNSIGNED DEFAULT 0,
  `update_time` int UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_type_status` (`coupon_type`, `status`),
  KEY `idx_time` (`start_time`, `end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='优惠券模板表';

-- ============================================================
-- 4. 用户优惠券表
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_user_coupon` (
  `id` int UNSIGNED AUTO_INCREMENT,
  `member_id` int UNSIGNED NOT NULL,
  `template_id` int UNSIGNED NOT NULL,
  `code` varchar(32) NOT NULL COMMENT '优惠券码',
  `coupon_type` enum('reduce','discount','free_shipping') NOT NULL COMMENT '冗余:券类型',
  `condition_amount` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '冗余:门槛金额',
  `reduce_amount` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '冗余:减免金额/折扣率',
  `status` tinyint DEFAULT 0 COMMENT '0未使用/1已使用/2已过期/3已作废/4已退还',
  `used_at` int UNSIGNED DEFAULT 0 COMMENT '使用时间',
  `used_order_id` int UNSIGNED DEFAULT 0 COMMENT '使用的订单ID',
  `expire_at` int UNSIGNED DEFAULT 0 COMMENT '过期时间',
  `create_time` int UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_member_status` (`member_id`, `status`),
  KEY `idx_expire` (`expire_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户优惠券表';

-- ============================================================
-- 5. 内容评价评分表
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_content_rating` (
  `id` int UNSIGNED AUTO_INCREMENT,
  `content_id` int UNSIGNED NOT NULL COMMENT '内容ID',
  `member_id` int UNSIGNED NOT NULL COMMENT '评价用户ID',
  `rating` tinyint NOT NULL COMMENT '评分 1-5',
  `title` varchar(255) DEFAULT '' COMMENT '评价标题',
  `content` text DEFAULT NULL COMMENT '评价内容',
  `has_media` tinyint DEFAULT 0 COMMENT '是否有图片/视频 0否1是',
  `media_urls` text DEFAULT NULL COMMENT '图片/视频URL列表JSON',
  `is_anonymous` tinyint DEFAULT 0 COMMENT '是否匿名 0否1是',
  `reply_count` int DEFAULT 0 COMMENT '回复数',
  `like_count` int DEFAULT 0 COMMENT '点赞数',
  `status` tinyint DEFAULT 1 COMMENT '状态:0待审/1通过/2拒绝',
  `create_time` int UNSIGNED DEFAULT 0,
  `update_time` int UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_content_member` (`content_id`, `member_id`),
  KEY `idx_content_rating` (`content_id`, `rating`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='内容评价评分表';

-- ============================================================
-- 6. 优惠券相关配置项
-- ============================================================
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('coupon_enabled', '1', 'coupon', 'switch', '是否启用优惠券系统', 1),
('coupon_newbie_enabled', '1', 'coupon', 'switch', '是否启用新人券', 2),
('coupon_newbie_days', '7', 'coupon', 'number', '注册后多少天内可领新人券', 3),
('coupon_newbie_template_id', '0', 'coupon', 'number', '新人券模板ID', 4),
('coupon_refund_return', '1', 'coupon', 'switch', '全额退款时是否退还优惠券', 5)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- ============================================================
-- 7. 评价评分相关配置项
-- ============================================================
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('rating_enabled', '1', 'rating', 'switch', '是否启用评价评分系统', 1),
('rating_require_purchase', '1', 'rating', 'switch', '是否要求购买后才能评价', 2),
('rating_anonymous_allowed', '1', 'rating', 'switch', '是否允许匿名评价', 3),
('rating_auto_approve', '0', 'rating', 'switch', '是否自动审核通过评价', 4),
('rating_media_max', '5', 'rating', 'number', '评价最多上传图片数', 5)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- ============================================================
-- 8. V2.9功能模块注册
-- ============================================================
INSERT INTO `i8j_module` (`code`, `name`, `description`, `icon`, `category`, `is_system`, `is_enabled`, `sort`, `menu_ids`) VALUES
('coupon', '优惠券系统', '满减/折扣/免邮券管理', 'bi-ticket-perforated', 'marketing', 0, 1, 60, '[]'),
('content_rating', '评价评分', '内容评价与评分管理', 'bi-star', 'interaction', 0, 1, 61, '[]'),
('template_design', '模板设计器', '前台模板可视化配置与AI配色', 'bi-palette', 'extension', 0, 1, 62, '[]')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

-- ============================================================
-- 8.5. FLUX/DALL-E AI配图配置项
-- ============================================================
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('ai_image_default_provider', 'tongyi_wanxiang', 'ai', 'select', '默认AI配图Provider(tongyi_wanxiang/flux/dalle)', 21),
('ai_image_fallback_provider', 'flux', 'ai', 'select', '备用AI配图Provider', 22),
('ai_image_flux_enabled', '0', 'ai', 'switch', '是否启用FLUX配图', 23),
('ai_image_flux_api_key', '', 'ai', 'text', 'FLUX API Key', 24),
('ai_image_flux_model', 'flux-pro', 'ai', 'text', 'FLUX模型名称', 25),
('ai_image_dalle_enabled', '0', 'ai', 'switch', '是否启用DALL-E配图', 26),
('ai_image_dalle_api_key', '', 'ai', 'text', 'DALL-E API Key', 27),
('ai_image_dalle_model', 'dall-e-3', 'ai', 'text', 'DALL-E模型名称', 28)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `group` = VALUES(`group`), `type` = VALUES(`type`), `remark` = VALUES(`remark`);

-- ============================================================
-- 9. 前台主题配置表（V2.9 M11 模板可视化配置持久化）
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_theme_config` (
  `id` int UNSIGNED AUTO_INCREMENT,
  `theme` varchar(50) NOT NULL DEFAULT 'default' COMMENT '主题标识',
  `scope` enum('global','page','component') NOT NULL DEFAULT 'global' COMMENT '配置范围',
  `scope_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '范围ID（页面ID或组件ID，global时为0）',
  `config_key` varchar(100) NOT NULL COMMENT '配置键名',
  `config_value` text DEFAULT NULL COMMENT '配置值（JSON或纯文本）',
  `config_type` enum('color','text','number','image','select','boolean','json') NOT NULL DEFAULT 'text' COMMENT '值类型',
  `label` varchar(100) DEFAULT '' COMMENT '显示标签',
  `description` varchar(255) DEFAULT '' COMMENT '配置说明',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `create_time` int UNSIGNED DEFAULT 0,
  `update_time` int UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_theme_scope_key` (`theme`, `scope`, `scope_id`, `config_key`),
  KEY `idx_theme_scope` (`theme`, `scope`, `scope_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='前台主题配置表';

-- ============================================================
-- 10. i8j_member 表补全 invite_code 字段（V2.9邀请功能需要）
-- ============================================================
SET @exists3 = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'i8j_member'
    AND COLUMN_NAME = 'invite_code'
);
SET @sql3 = IF(@exists3 = 0,
  'ALTER TABLE `i8j_member`
    ADD COLUMN `invite_code` varchar(32) DEFAULT "" COMMENT "邀请码" AFTER `avatar`,
    ADD COLUMN `inviter_id` int UNSIGNED DEFAULT 0 COMMENT "邀请人ID" AFTER `invite_code`,
    ADD UNIQUE KEY `uk_invite_code` (`invite_code`)',
  'SELECT "invite_code column already exists" AS info'
);
PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

-- ============================================================
-- 11. i8j_content 表补全 quality_score 字段（如v2.8未执行）
-- ============================================================
SET @exists2 = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'i8j_content'
    AND COLUMN_NAME = 'quality_score'
);
SET @sql2 = IF(@exists2 = 0,
  'ALTER TABLE `i8j_content` ADD COLUMN `quality_score` tinyint DEFAULT 0 COMMENT \'AI质量评分(0-100)\' AFTER `chapter_title`',
  'SELECT \"quality_score column already exists\" AS info'
);
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- ============================================================
-- 12. V2.9 邀请奖励三阶段积分配置
-- ============================================================
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('invite_reward_register', '10', 'invite', 'number', '邀请注册阶段奖励积分', 1),
('invite_reward_signin', '20', 'invite', 'number', '邀请签到阶段奖励积分', 2),
('invite_reward_pay', '50', 'invite', 'number', '邀请付费阶段奖励积分', 3),
('invite_enabled', '1', 'invite', 'switch', '是否启用邀请奖励系统', 4)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `group` = VALUES(`group`), `type` = VALUES(`type`), `remark` = VALUES(`remark`);
