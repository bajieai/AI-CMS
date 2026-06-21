-- AI-CMS V2.9.27 Sprint V 数据库变更脚本
SET NAMES utf8mb4;
SET @db = DATABASE();

INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(540, 1, 0, '系统健康检查', '/admin/system_health/index', 'system_health.*', 'system_health', 'bi bi-heart-pulse', 95, 1);

INSERT IGNORE INTO `i8j_config` (`name`, `value`, `group`) VALUES
('rss_enabled', '1', 'system'),
('rss_cache_ttl', '600', 'system'),
('oauth_github_enabled', '0', 'oauth'),
('email_service_unified', '1', 'email');
