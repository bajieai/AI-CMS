-- AI-CMS 设置页面 Bug 修复 - 数据库补丁
-- 用于在已运行的系统中插入缺失的设置项
-- 执行日期: 2026-04-19
--
-- 修复问题:
-- 1. basic 组缺少 timezone/language 字段
-- 2. seo/smtp 分组无种子数据
-- 3. 前端保存后刷新回退

SET NAMES utf8mb4;

-- 插入基础设置中缺失的字段（如果不存在则忽略，因为已有唯一约束）
INSERT IGNORE INTO `i8j_aicms_configs` (`group_name`, `key`, `value`, `value_type`, `title`, `description`, `is_public`, `sort_order`, `created_at`, `updated_at`) VALUES
('basic', 'timezone', 'Asia/Shanghai', 'string', '时区', '系统时区设置', 1, 6, NOW(), NOW()),
('basic', 'language', 'zh-CN', 'string', '语言', '系统语言设置', 1, 7, NOW(), NOW());

-- 插入 SEO 设置分组
INSERT IGNORE INTO `i8j_aicms_configs` (`group_name`, `key`, `value`, `value_type`, `title`, `description`, `is_public`, `sort_order`, `created_at`, `updated_at`) VALUES
('seo', 'title_format', '{title} - {site_name}', 'string', '标题格式', '页面标题显示格式', 1, 30, NOW(), NOW()),
('seo', 'keyword_separator', ',', 'string', '关键词分隔符', 'SEO关键词分隔字符', 1, 31, NOW(), NOW()),
('seo', 'sitemap_enabled', '1', 'boolean', '生成Sitemap', '是否自动生成站点地图', 1, 32, NOW(), NOW()),
('seo', 'baidu_push_api', '', 'string', '百度推送API', '百度主动推送接口地址', 1, 33, NOW(), NOW());

-- 插入 SMTP 邮件设置分组
INSERT IGNORE INTO `i8j_aicms_configs` (`group_name`, `key`, `value`, `value_type`, `title`, `description`, `is_public`, `sort_order`, `created_at`, `updated_at`) VALUES
('smtp', 'host', '', 'string', 'SMTP服务器', '邮件发送服务器地址', 0, 40, NOW(), NOW()),
('smtp', 'port', '465', 'number', '端口', 'SMTP服务端口', 0, 41, NOW(), NOW()),
('smtp', 'username', '', 'string', '用户名', 'SMTP登录用户名', 0, 42, NOW(), NOW()),
('smtp', 'password', '', 'string', '密码', 'SMTP登录密码(加密存储)', 0, 43, NOW(), NOW()),
('smtp', 'from_email', '', 'string', '发件人邮箱', '默认发件人邮箱地址', 0, 44, NOW(), NOW()),
('smtp', 'from_name', 'AI-CMS', 'string', '发件人名称', '默认发件人名称', 0, 45, NOW(), NOW()),
('smtp', 'use_ssl', '1', 'boolean', '使用SSL', '是否启用SSL加密连接', 0, 46, NOW(), NOW());

-- 验证插入结果
SELECT group_name, COUNT(*) as config_count FROM `i8j_aicms_configs` GROUP BY group_name;
