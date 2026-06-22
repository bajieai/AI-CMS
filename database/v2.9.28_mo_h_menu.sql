-- AI-CMS V2.9.28 Sprint MO + Sprint H 菜单和配置
SET NAMES utf8mb4;

-- 菜单项
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(570, 1, 0, 'Hook事件文档', '/admin/hook_doc/index', 'hook_doc.*', 'hook_doc', 'bi bi-book', 85, 1),
(571, 5, 0, 'PWA配置', '/admin/pwa_config/index', 'pwa_config.*', 'pwa_config', 'bi bi-phone', 90, 1);

-- PWA配置
INSERT IGNORE INTO `i8j_config` (`name`, `value`, `group`) VALUES
('pwa_enabled', '1', 'pwa'),
('pwa_app_name', 'AI-CMS', 'pwa'),
('pwa_app_short_name', 'AI-CMS', 'pwa'),
('pwa_theme_color', '#0d6efd', 'pwa'),
('pwa_bg_color', '#ffffff', 'pwa'),
('pwa_push_enabled', '0', 'pwa');
