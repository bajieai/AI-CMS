SET NAMES utf8mb4;
-- ============================================================
-- AI-CMS V2.4 数据库迁移脚本
-- 日期: 2026-04-29
-- 前置版本: V2.3.1
-- 说明: 所有时间字段使用int UNSIGNED，与V2.3保持一致
-- ============================================================

-- ============================================================
-- 一、新增表
-- ============================================================

-- 1. AI模型配置表
CREATE TABLE IF NOT EXISTS `{prefix}ai_model` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL COMMENT '模型名称',
    `provider` varchar(50) NOT NULL COMMENT '供应商: deepseek/qwen/ernie/glm/openai',
    `model_id` varchar(100) NOT NULL COMMENT '模型ID（API调用用）',
    `api_base` varchar(255) DEFAULT '' COMMENT 'API Base URL',
    `api_key` text COMMENT 'API密钥（加密存储）',
    `capabilities` varchar(255) DEFAULT 'write,seo,translate' COMMENT '能力标签，逗号分隔',
    `is_default` tinyint DEFAULT 0 COMMENT '是否默认模型',
    `is_enabled` tinyint DEFAULT 1 COMMENT '启用状态',
    `max_tokens` int DEFAULT 2000 COMMENT '最大输出token数',
    `temperature` float DEFAULT 0.7 COMMENT '温度参数',
    `sort` int DEFAULT 0 COMMENT '排序（故障降级顺序）',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    `update_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_provider_model` (`provider`, `model_id`),
    KEY `idx_enabled_default` (`is_enabled`, `is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI模型配置表';

-- 2. AI调用日志表
CREATE TABLE IF NOT EXISTS `{prefix}ai_log` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `model_id` int NOT NULL COMMENT '使用的模型ID',
    `task_type` varchar(50) NOT NULL COMMENT '任务类型: write/seo/translate/summarize',
    `prompt_length` int DEFAULT 0 COMMENT '输入长度',
    `response_length` int DEFAULT 0 COMMENT '输出长度',
    `tokens_used` int DEFAULT 0 COMMENT '消耗token数',
    `duration_ms` int DEFAULT 0 COMMENT '耗时（毫秒）',
    `status` tinyint DEFAULT 1 COMMENT '状态: 1成功 2失败 3降级',
    `error_msg` varchar(500) DEFAULT '' COMMENT '错误信息',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_model_time` (`model_id`, `create_time`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI调用日志表';

-- 3. 会员等级表
CREATE TABLE IF NOT EXISTS `{prefix}member_level` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL COMMENT '等级名称',
    `min_points` int DEFAULT 0 COMMENT '所需最低积分',
    `discount` tinyint DEFAULT 100 COMMENT '付费内容折扣百分比（100=无折扣）',
    `allow_download` tinyint DEFAULT 0 COMMENT '是否允许下载附件',
    `allow_comment_no_review` tinyint DEFAULT 0 COMMENT '是否评论免审核',
    `icon` varchar(255) DEFAULT '' COMMENT '等级图标',
    `sort` int DEFAULT 0 COMMENT '排序',
    `is_default` tinyint DEFAULT 0 COMMENT '是否默认等级',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    `update_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_min_points` (`min_points`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员等级表';

-- 4. 积分变动记录表
CREATE TABLE IF NOT EXISTS `{prefix}points_log` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `member_id` int NOT NULL,
    `points` int NOT NULL COMMENT '变动积分（正增负减）',
    `type` varchar(50) NOT NULL COMMENT '类型: signin/comment/like/favorite/purchase/register/admin_adjust',
    `source_id` int DEFAULT 0 COMMENT '来源ID',
    `note` varchar(255) DEFAULT '' COMMENT '备注',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_member` (`member_id`),
    KEY `idx_type_time` (`type`, `create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分变动记录表';

-- 5. 签到记录表
CREATE TABLE IF NOT EXISTS `{prefix}signin_log` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `member_id` int NOT NULL,
    `signin_date` date NOT NULL,
    `points` int DEFAULT 0 COMMENT '签到获得积分',
    `consecutive_days` int DEFAULT 1 COMMENT '连续签到天数',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_member_date` (`member_id`, `signin_date`),
    KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='签到记录表';

-- 6. 付费订单表
CREATE TABLE IF NOT EXISTS `{prefix}paid_order` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_sn` varchar(64) NOT NULL COMMENT '订单号',
    `member_id` int NOT NULL COMMENT '购买会员ID',
    `content_id` int NOT NULL COMMENT '购买内容ID',
    `type` varchar(20) DEFAULT 'content' COMMENT '类型: content内容',
    `price` decimal(10,2) NOT NULL COMMENT '实付金额/积分',
    `pay_type` varchar(20) DEFAULT 'points' COMMENT '支付方式: points积分 wechat微信 alipay支付宝',
    `status` tinyint DEFAULT 0 COMMENT '状态: 0待支付 1已支付 2已退款 3已关闭',
    `paid_at` int UNSIGNED DEFAULT 0 COMMENT '支付时间',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    `update_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_sn` (`order_sn`),
    UNIQUE KEY `uk_member_content` (`member_id`, `content_id`, `type`),
    KEY `idx_member` (`member_id`),
    KEY `idx_content` (`content_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='付费订单表';

-- 7. 表单定义表
CREATE TABLE IF NOT EXISTS `{prefix}form` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL COMMENT '表单名称',
    `code` varchar(50) NOT NULL COMMENT '表单唯一标识',
    `fields` json NOT NULL COMMENT '字段配置（JSON数组）',
    `submit_text` varchar(255) DEFAULT '提交' COMMENT '提交按钮文案',
    `success_msg` varchar(255) DEFAULT '提交成功' COMMENT '提交成功提示',
    `success_action` varchar(20) DEFAULT 'message' COMMENT '提交后动作: message消息 redirect跳转',
    `redirect_url` varchar(255) DEFAULT '' COMMENT '跳转URL',
    `anti_spam` tinyint DEFAULT 0 COMMENT '防刷: 0无 1验证码 2IP限制',
    `is_enabled` tinyint DEFAULT 1,
    `sort` int DEFAULT 0,
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    `update_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='表单定义表';

-- 8. 表单提交数据表
CREATE TABLE IF NOT EXISTS `{prefix}form_data` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `form_id` int NOT NULL,
    `fields_data` json NOT NULL COMMENT '提交数据（JSON）',
    `ip` varchar(45) DEFAULT '' COMMENT '提交者IP',
    `user_agent` varchar(500) DEFAULT '',
    `is_read` tinyint DEFAULT 0 COMMENT '是否已读',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_form` (`form_id`),
    KEY `idx_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='表单提交数据表';

-- 9. SEO关键词表
CREATE TABLE IF NOT EXISTS `{prefix}seo_keyword` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `keyword` varchar(200) NOT NULL COMMENT '关键词',
    `group_id` int DEFAULT 0 COMMENT '分组ID',
    `search_volume` int DEFAULT 0 COMMENT '搜索量',
    `difficulty` tinyint DEFAULT 50 COMMENT '难度指数（0-100）',
    `is_sensitive` tinyint DEFAULT 0 COMMENT '是否敏感词',
    `status` tinyint DEFAULT 1 COMMENT '状态: 1启用 0禁用',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    `update_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_keyword` (`keyword`),
    KEY `idx_group` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEO关键词表';

-- 10. SEO关键词分组表
CREATE TABLE IF NOT EXISTS `{prefix}seo_keyword_group` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL COMMENT '分组名称',
    `sort` int DEFAULT 0,
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEO关键词分组表';

-- 11. 访问日志归档表
CREATE TABLE IF NOT EXISTS `{prefix}visit_log_archive` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `period` varchar(7) NOT NULL COMMENT '归档周期 如:2026-04',
    `period_type` varchar(10) NOT NULL DEFAULT 'month' COMMENT '周期类型',
    `pv` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '月PV',
    `uv` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '月UV',
    `content_stats` text COMMENT '内容访问排行(JSON)',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_period` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='访问日志归档表';

-- ============================================================
-- 二、修改表（ALTER）
-- ============================================================

-- 会员表新增等级/积分字段
ALTER TABLE `{prefix}member` ADD COLUMN `level_id` int DEFAULT 1 COMMENT '会员等级ID';
ALTER TABLE `{prefix}member` ADD COLUMN `points` int DEFAULT 0 COMMENT '当前积分';
ALTER TABLE `{prefix}member` ADD COLUMN `total_points` int DEFAULT 0 COMMENT '累计获得积分';
ALTER TABLE `{prefix}member` ADD COLUMN `signin_count` int DEFAULT 0 COMMENT '连续签到天数';
ALTER TABLE `{prefix}member` ADD COLUMN `last_signin_date` date DEFAULT NULL COMMENT '最后签到日期';

-- 内容表新增付费字段
ALTER TABLE `{prefix}content` ADD COLUMN `is_paid` tinyint DEFAULT 0 COMMENT '是否付费: 0免费 1付费';
ALTER TABLE `{prefix}content` ADD COLUMN `paid_price` decimal(10,2) DEFAULT 0.00 COMMENT '付费价格';
ALTER TABLE `{prefix}content` ADD COLUMN `paid_type` varchar(20) DEFAULT 'points' COMMENT '付费类型: points积分 money金额';
ALTER TABLE `{prefix}content` ADD COLUMN `preview_length` int DEFAULT 500 COMMENT '试读字数';

-- ============================================================
-- 三、初始化数据
-- ============================================================

-- 会员等级初始化
INSERT INTO `{prefix}member_level` (`name`, `min_points`, `discount`, `allow_comment_no_review`, `icon`, `sort`, `is_default`, `create_time`, `update_time`) VALUES
('注册会员', 0, 100, 0, 'badge-lv1', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('正式会员', 100, 95, 1, 'badge-lv2', 2, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('高级会员', 500, 90, 1, 'badge-lv3', 3, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('VIP会员', 2000, 80, 1, 'badge-lv4', 4, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('至尊会员', 5000, 70, 1, 'badge-lv5', 5, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- AI模型初始化（DeepSeek，从.env读取api_key）
-- 注意：DeepSeek官方API目前仅提供 deepseek-chat（V3/V4通用端点）和 deepseek-reasoner（R1）
-- 如需配置多个版本，请使用不同的api_base或api_key区分，或移除uk_provider_model唯一键约束
INSERT INTO `{prefix}ai_model` (`name`, `provider`, `model_id`, `api_base`, `api_key`, `capabilities`, `is_default`, `is_enabled`, `max_tokens`, `temperature`, `sort`, `create_time`, `update_time`) VALUES
('DeepSeek V4-Flash', 'deepseek', 'deepseek-chat', 'https://api.deepseek.com/v1', '', 'write,seo,translate,summarize', 1, 1, 2000, 0.7, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 积分规则配置
INSERT INTO `{prefix}config` (`name`, `value`, `group`, `remark`) VALUES
('points_signin', '5', 'points', '每日签到积分'),
('points_signin_3days', '10', 'points', '连续签到3天额外积分'),
('points_signin_7days', '30', 'points', '连续签到7天额外积分'),
('points_comment', '2', 'points', '发表评论积分'),
('points_comment_liked', '1', 'points', '评论被点赞积分'),
('points_content_liked', '3', 'points', '内容被点赞积分'),
('points_content_favorited', '5', 'points', '内容被收藏积分'),
('points_daily_login', '1', 'points', '每日首次登录积分'),
('points_register', '50', 'points', '注册奖励积分'),
('points_comment_liked_daily_limit', '10', 'points', '评论被点赞每日上限'),
('points_content_liked_daily_limit', '20', 'points', '内容被点赞每日上限'),
('points_content_favorited_daily_limit', '10', 'points', '内容被收藏每日上限');

-- OAuth配置项
INSERT INTO `{prefix}config` (`name`, `value`, `group`, `remark`) VALUES
('wechat_open_appid', '', 'oauth', '微信开放平台AppID（扫码登录）'),
('wechat_open_secret', '', 'oauth', '微信开放平台AppSecret'),
('qq_appid', '', 'oauth', 'QQ互联AppID'),
('qq_appkey', '', 'oauth', 'QQ互联AppKey');

-- 12. 邮件订阅表
CREATE TABLE IF NOT EXISTS `{prefix}email_subscriber` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` varchar(255) NOT NULL COMMENT '订阅邮箱',
    `status` tinyint DEFAULT 1 COMMENT '状态: 1订阅 0退订',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    `update_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件订阅表';

-- 模板配置项（V2.4 多模板风格：前台主题 + 后台主题）
INSERT INTO `{prefix}config` (`group`, `name`, `value`, `type`, `sort`, `remark`)
VALUES ('site', 'frontend_theme', 'default', 'string', 50, '前台主题');

INSERT INTO `{prefix}config` (`group`, `name`, `value`, `type`, `sort`, `remark`)
VALUES ('site', 'admin_theme', 'default', 'string', 51, '后台主题');

-- ============================================================
-- 四、功能模块注册
-- ============================================================

INSERT INTO `{prefix}module` (`code`, `name`, `is_enabled`, `menu_ids`, `create_time`, `update_time`) VALUES
('ai_model', 'AI模型管理', 1, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('member_level', '会员等级', 1, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('points', '积分体系', 1, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('paid_content', '付费阅读', 1, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('form_builder', '表单生成器', 1, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('seo_keyword', 'SEO关键词库', 1, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('dashboard', '数据看板', 1, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('oauth_manage', 'OAuth管理', 1, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
