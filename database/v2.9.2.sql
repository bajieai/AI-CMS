-- AI-CMS V2.9.2 数据库变更
-- 执行时间: 2026-05-10

-- 1. 会员等级权益扩展
ALTER TABLE `{prefix}member_level`
    ADD COLUMN `vip_badge_icon` varchar(100) DEFAULT '' COMMENT 'VIP标识图标CSS类名' AFTER `daily_ai_quota`,
    ADD COLUMN `exclusive_content_ids` text COMMENT '专享内容ID列表JSON' AFTER `vip_badge_icon`,
    ADD COLUMN `auto_downgrade_days` int DEFAULT 0 COMMENT '自动降级天数(0=不降级,预留V3.0)' AFTER `exclusive_content_ids`;

-- 2. 系统配置项
INSERT INTO `{prefix}config` (`group`, `name`, `value`, `type`, `comment`) VALUES
('language', 'auto_translate_enabled', '0', 'switch', '发布时自动AI翻译'),
('language', 'auto_translate_targets', 'en,ja,ko', 'text', '自动翻译目标语言(逗号分隔)'),
('language', 'translate_source_lang', 'zh-CN', 'text', '翻译源语言'),
('plugin', 'market_url', 'https://market.aicms.com/api/v1', 'text', '插件市场API地址'),
('plugin', 'market_enabled', '1', 'switch', '启用插件市场'),
('pwa', 'pwa_enabled', '0', 'switch', '启用PWA离线支持'),
('seo', 'sitemap_includes_cate', '1', 'switch', 'Sitemap包含分类页'),
('seo', 'sitemap_includes_tag', '1', 'switch', 'Sitemap包含标签页'),
('seo', 'schema_enabled', '1', 'switch', '启用结构化数据(JSON-LD)'),
('seo', 'og_enabled', '1', 'switch', '启用Open Graph标签'),
('member', 'level_manual_downgrade', '1', 'switch', '允许手动降级会员等级'),
('member', 'level_change_notify', '1', 'switch', '等级变更时发送通知');
