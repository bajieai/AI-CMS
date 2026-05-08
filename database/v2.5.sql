SET NAMES utf8mb4;
-- ============================================================
-- AI-CMS V2.5 数据库迁移脚本
-- 日期: 2026-05-03
-- 前置版本: V2.4.0
-- 说明: V2.5新增12张表、4组ALTER、7项配置、9项模块注册
-- ============================================================

-- ============================================================
-- 一、新增表（12张）
-- ============================================================

-- 1. 支付日志表
CREATE TABLE IF NOT EXISTS `{prefix}payment_log` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_sn` varchar(64) NOT NULL COMMENT '订单号',
    `type` varchar(20) NOT NULL COMMENT '类型: request/notify/refund',
    `request_data` text COMMENT '请求数据(JSON)',
    `response_data` text COMMENT '响应数据(JSON)',
    `status` tinyint DEFAULT 1 COMMENT '状态: 1成功 0失败',
    `error_msg` varchar(500) DEFAULT '' COMMENT '错误信息',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_order` (`order_sn`),
    KEY `idx_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付日志表';

-- 2. AI批量生成任务表
CREATE TABLE IF NOT EXISTS `{prefix}ai_batch_task` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` varchar(200) NOT NULL COMMENT '任务名称',
    `keywords` text NOT NULL COMMENT '关键词列表（换行分隔）',
    `style` varchar(20) DEFAULT 'default' COMMENT '写作风格: default/formal/casual/marketing/technical',
    `cate_id` int DEFAULT 0 COMMENT '目标分类',
    `model_id` int DEFAULT 0 COMMENT '使用的AI模型ID',
    `total` int DEFAULT 0 COMMENT '总数量',
    `completed` int DEFAULT 0 COMMENT '已完成数量',
    `status` tinyint DEFAULT 0 COMMENT '状态: 0排队 1进行中 2完成 3失败',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    `update_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI批量生成任务表';

-- 3. 插件注册表
CREATE TABLE IF NOT EXISTS `{prefix}plugin` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL COMMENT '插件名称',
    `code` varchar(50) NOT NULL COMMENT '插件唯一标识',
    `version` varchar(20) DEFAULT '1.0.0' COMMENT '版本号',
    `author` varchar(100) DEFAULT '' COMMENT '作者',
    `description` varchar(500) DEFAULT '' COMMENT '描述',
    `hooks` text COMMENT '注册的Hook列表(JSON)',
    `config` text COMMENT '插件配置(JSON)',
    `is_enabled` tinyint DEFAULT 0 COMMENT '启用状态: 0禁用 1启用',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    `update_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件注册表';

-- 4. 发布平台配置表
CREATE TABLE IF NOT EXISTS `{prefix}publish_platform` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL COMMENT '平台标识: wechat_mp/toutiao',
    `display_name` varchar(100) NOT NULL COMMENT '显示名称',
    `config_json` text COMMENT '平台配置(JSON: appid/secret/token等)',
    `is_enabled` tinyint DEFAULT 0 COMMENT '启用状态',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    `update_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='发布平台配置表';

-- 5. 发布记录表
CREATE TABLE IF NOT EXISTS `{prefix}publish_log` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `content_id` int NOT NULL COMMENT '内容ID',
    `platform_id` int NOT NULL COMMENT '平台ID',
    `platform_content_id` varchar(100) DEFAULT '' COMMENT '外部平台内容ID',
    `status` tinyint DEFAULT 0 COMMENT '状态: 0待发布 1已发布 2失败',
    `error_msg` varchar(500) DEFAULT '' COMMENT '错误信息',
    `publish_time` int UNSIGNED DEFAULT 0 COMMENT '发布时间',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_content` (`content_id`),
    KEY `idx_platform` (`platform_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='发布记录表';

-- 6. 邮件模板表
CREATE TABLE IF NOT EXISTS `{prefix}email_template` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` varchar(50) NOT NULL COMMENT '模板标识: register/forgot_password/comment_notify/payment_success',
    `name` varchar(100) NOT NULL COMMENT '模板名称',
    `subject` varchar(200) NOT NULL COMMENT '邮件主题（支持变量）',
    `body` text NOT NULL COMMENT '邮件正文HTML（支持变量）',
    `vars` varchar(500) DEFAULT '' COMMENT '可用变量(逗号分隔): username,site_name,content_title等',
    `is_enabled` tinyint DEFAULT 1 COMMENT '启用状态',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    `update_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件模板表';

-- 7. 邮件发送日志表
CREATE TABLE IF NOT EXISTS `{prefix}email_log` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `template_code` varchar(50) DEFAULT '' COMMENT '模板标识',
    `to_email` varchar(255) NOT NULL COMMENT '收件人',
    `subject` varchar(200) NOT NULL COMMENT '实际主题',
    `status` tinyint DEFAULT 0 COMMENT '状态: 0排队 1成功 2失败',
    `error_msg` varchar(500) DEFAULT '' COMMENT '错误信息',
    `send_time` int UNSIGNED DEFAULT 0 COMMENT '发送时间',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_to` (`to_email`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件发送日志表';

-- 8. 采集源表
CREATE TABLE IF NOT EXISTS `{prefix}collect_source` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL COMMENT '来源名称',
    `type` varchar(20) NOT NULL DEFAULT 'rss' COMMENT '类型: rss/webpage',
    `url` varchar(500) NOT NULL COMMENT '源URL',
    `rules` text COMMENT '采集规则(JSON: title_selector/content_selector等)',
    `cate_id` int DEFAULT 0 COMMENT '默认分类ID',
    `interval_minutes` int DEFAULT 60 COMMENT '采集间隔(分钟)',
    `is_enabled` tinyint DEFAULT 0 COMMENT '启用状态',
    `last_collect_time` int UNSIGNED DEFAULT 0 COMMENT '最后采集时间',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='采集源表';

-- 9. 采集日志表
CREATE TABLE IF NOT EXISTS `{prefix}collect_log` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `source_id` int NOT NULL COMMENT '采集源ID',
    `title` varchar(500) DEFAULT '' COMMENT '采集标题',
    `url` varchar(500) DEFAULT '' COMMENT '采集URL',
    `url_hash` varchar(32) DEFAULT '' COMMENT 'URL MD5去重',
    `status` tinyint DEFAULT 0 COMMENT '状态: 0新采集 1已导入 2跳过(重复) 3失败',
    `content_id` int DEFAULT 0 COMMENT '导入后的内容ID',
    `error_msg` varchar(500) DEFAULT '' COMMENT '错误信息',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_source` (`source_id`),
    UNIQUE KEY `uk_url_hash` (`url_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='采集日志表';

-- 10. 语言表
CREATE TABLE IF NOT EXISTS `{prefix}language` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` varchar(10) NOT NULL COMMENT '语言代码: zh-CN/en-US',
    `name` varchar(50) NOT NULL COMMENT '语言名称',
    `is_default` tinyint DEFAULT 0 COMMENT '是否默认',
    `is_enabled` tinyint DEFAULT 1 COMMENT '启用状态',
    `sort` int DEFAULT 0 COMMENT '排序',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='语言表';

-- 11. 翻译表
CREATE TABLE IF NOT EXISTS `{prefix}translation` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `lang_code` varchar(10) NOT NULL COMMENT '语言代码',
    `group` varchar(50) NOT NULL DEFAULT 'common' COMMENT '分组: common/admin/frontend',
    `key` varchar(255) NOT NULL COMMENT '原文/翻译键',
    `translation` text COMMENT '译文',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    `update_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_lang_key` (`lang_code`, `group`, `key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='翻译表';

-- 12. 主题信息表
CREATE TABLE IF NOT EXISTS `{prefix}theme_info` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` varchar(50) NOT NULL COMMENT '主题标识',
    `type` varchar(20) NOT NULL DEFAULT 'frontend' COMMENT '类型: frontend/admin',
    `name` varchar(100) NOT NULL COMMENT '主题名称',
    `version` varchar(20) DEFAULT '1.0.0' COMMENT '版本号',
    `author` varchar(100) DEFAULT '' COMMENT '作者',
    `description` text COMMENT '描述',
    `thumbnail` varchar(255) DEFAULT '' COMMENT '缩略图',
    `is_installed` tinyint DEFAULT 1 COMMENT '是否已安装',
    `installed_version` varchar(20) DEFAULT '1.0.0' COMMENT '已安装版本',
    `update_available` tinyint DEFAULT 0 COMMENT '有可用更新',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    `update_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code_type` (`code`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='主题信息表';

-- ============================================================
-- 二、修改表（ALTER）
-- ============================================================

-- ai_model表：加密标记 + 速率限制
ALTER TABLE `{prefix}ai_model` ADD COLUMN `api_key_encrypted` tinyint DEFAULT 0 COMMENT 'API密钥是否已加密';
ALTER TABLE `{prefix}ai_model` ADD COLUMN `rate_limit_rpm` int DEFAULT 60 COMMENT '每分钟最大请求数';
ALTER TABLE `{prefix}ai_model` ADD COLUMN `rate_limit_rph` int DEFAULT 1000 COMMENT '每小时最大请求数';

-- paid_order表：微信支付退款字段
ALTER TABLE `{prefix}paid_order` ADD COLUMN `transaction_id` varchar(64) DEFAULT '' COMMENT '微信交易号';
ALTER TABLE `{prefix}paid_order` ADD COLUMN `refund_sn` varchar(64) DEFAULT '' COMMENT '退款单号';
ALTER TABLE `{prefix}paid_order` ADD COLUMN `refund_amount` decimal(10,2) DEFAULT 0.00 COMMENT '退款金额';
ALTER TABLE `{prefix}paid_order` ADD COLUMN `refund_time` int UNSIGNED DEFAULT 0 COMMENT '退款时间';
ALTER TABLE `{prefix}paid_order` ADD COLUMN `refund_reason` varchar(255) DEFAULT '' COMMENT '退款原因';

-- content表：会员等级限制 + 章节付费 + 多语言
ALTER TABLE `{prefix}content` ADD COLUMN `min_level_id` int DEFAULT 0 COMMENT '最低访问等级(0=无限制)';
ALTER TABLE `{prefix}content` ADD COLUMN `is_chapter` tinyint DEFAULT 0 COMMENT '是否启用章节付费';
ALTER TABLE `{prefix}content` ADD COLUMN `lang` varchar(10) DEFAULT 'zh-CN' COMMENT '内容语言';
ALTER TABLE `{prefix}content` ADD COLUMN `translation_of` int DEFAULT 0 COMMENT '翻译源内容ID';

-- visit_log表：来源分析字段
ALTER TABLE `{prefix}visit_log` ADD COLUMN `referrer` varchar(500) DEFAULT '' COMMENT '来源URL';
ALTER TABLE `{prefix}visit_log` ADD COLUMN `source_type` varchar(20) DEFAULT 'direct' COMMENT '来源类型: direct/search/social/referral/other';

-- ============================================================
-- 三、初始化数据
-- ============================================================

-- 安全配置
INSERT INTO `{prefix}config` (`name`, `value`, `group`, `remark`) VALUES
('encrypt_cipher', 'AES-256-CBC', 'security', '加密算法'),
('captcha_type', 'math', 'security', '验证码类型: math算术/turnstile腾讯验证码'),
('captcha_enabled_forms', '', 'security', '需要验证码的表单code(逗号分隔)');

-- 微信支付配置
INSERT INTO `{prefix}config` (`name`, `value`, `group`, `remark`) VALUES
('wechat_pay_appid', '', 'payment', '微信支付AppID'),
('wechat_pay_mchid', '', 'payment', '微信支付商户号'),
('wechat_pay_v3_key', '', 'payment', 'APIv3密钥'),
('wechat_pay_serial_no', '', 'payment', '证书序列号'),
('wechat_pay_notify_url', '/api/payment/wechat/notify', 'payment', '回调地址'),
('wechat_pay_enabled', '0', 'payment', '微信支付是否启用');

-- QQ回调配置
INSERT INTO `{prefix}config` (`name`, `value`, `group`, `remark`) VALUES
('qq_redirect', '/oauth/qq/callback', 'oauth', 'QQ回调地址'),
('oauth_wechat_enabled', '0', 'oauth', '微信登录启用'),
('oauth_qq_enabled', '0', 'oauth', 'QQ登录启用');

-- SMTP邮件配置
INSERT INTO `{prefix}config` (`name`, `value`, `group`, `remark`) VALUES
('smtp_host', '', 'email', 'SMTP服务器'),
('smtp_port', '465', 'email', 'SMTP端口'),
('smtp_username', '', 'email', 'SMTP账号'),
('smtp_password', '', 'email', 'SMTP密码'),
('smtp_from_email', '', 'email', '发件人邮箱'),
('smtp_from_name', '', 'email', '发件人名称'),
('smtp_ssl', '1', 'email', '是否SSL');

-- AI批量生成配置
INSERT INTO `{prefix}config` (`name`, `value`, `group`, `remark`) VALUES
('ai_batch_max_count', '10', 'ai', '批量生成最大篇数'),
('ai_batch_default_model', '0', 'ai', '批量生成默认模型(0=系统默认)'),
('ai_long_article_threshold', '2000', 'ai', '长文阈值(字数)');

-- 新增AI Provider初始数据
INSERT INTO `{prefix}ai_model` (`name`, `provider`, `model_id`, `api_base`, `api_key`, `capabilities`, `is_default`, `is_enabled`, `max_tokens`, `temperature`, `sort`, `api_key_encrypted`, `rate_limit_rpm`, `rate_limit_rph`, `create_time`, `update_time`) VALUES
('GLM-4-Flash', 'glm', 'glm-4-flash', 'https://open.bigmodel.cn/api/paas/v4', '', 'write,seo,translate', 0, 1, 2000, 0.7, 3, 0, 60, 1000, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('ERNIE-Speed', 'ernie', 'ernie-speed-128k', 'https://qianfan.baidubce.com/v2', '', 'write,seo', 0, 1, 2000, 0.7, 4, 0, 60, 1000, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('OpenAI兼容', 'openai', 'gpt-3.5-turbo', 'https://api.openai.com/v1', '', 'write,seo,translate,summarize', 0, 0, 2000, 0.7, 5, 0, 60, 1000, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 发布平台初始数据
INSERT INTO `{prefix}publish_platform` (`name`, `display_name`, `config_json`, `is_enabled`, `create_time`, `update_time`) VALUES
('wechat_mp', '微信公众号', '{"appid":"","secret":""}', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('toutiao', '头条号', '{"client_key":"","client_secret":""}', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 邮件模板初始数据
INSERT INTO `{prefix}email_template` (`code`, `name`, `subject`, `body`, `vars`, `is_enabled`, `create_time`, `update_time`) VALUES
('register', '注册欢迎', '欢迎注册{{site_name}}', '<h2>欢迎加入{{site_name}}！</h2><p>亲爱的{{username}}，恭喜您成功注册。</p><p>您的账号：{{email}}</p>', 'username,site_name,email', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('forgot_password', '密码找回', '{{site_name}} - 密码找回', '<h2>密码找回</h2><p>您好 {{username}}，请点击以下链接重置密码：</p><p><a href="{{reset_url}}">重置密码</a></p><p>链接有效期30分钟。</p>', 'username,site_name,reset_url', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('comment_notify', '评论通知', '{{site_name}} - 您的文章有新评论', '<h2>新评论通知</h2><p>您的文章《{{content_title}}》收到一条新评论：</p><blockquote>{{comment_content}}</blockquote><p>评论者：{{comment_author}}</p>', 'username,site_name,content_title,comment_content,comment_author', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('payment_success', '付费成功', '{{site_name}} - 付费成功通知', '<h2>付费成功</h2><p>您已成功购买《{{content_title}}》，支付金额：{{amount}}元</p><p>感谢您的支持！</p>', 'username,site_name,content_title,amount', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 语言初始数据
INSERT INTO `{prefix}language` (`code`, `name`, `is_default`, `is_enabled`, `sort`) VALUES
('zh-CN', '简体中文', 1, 1, 1),
('en-US', 'English', 0, 1, 2);

-- ============================================================
-- 四、功能模块注册
-- ============================================================

INSERT INTO `{prefix}module` (`code`, `name`, `is_enabled`, `menu_ids`, `create_time`, `update_time`) VALUES
('payment', '微信支付', 1, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('ai_batch', 'AI批量生成', 1, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('plugin', '插件管理', 1, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('publish', '多平台发布', 1, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('email', '邮件系统', 1, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('collect', '内容采集', 1, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('i18n', '多语言', 1, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('captcha', '验证码', 1, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('theme_market', '模板市场', 1, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
