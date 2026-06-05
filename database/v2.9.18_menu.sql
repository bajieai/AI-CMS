-- ============================================
-- AI-CMS V2.9.18 菜单数据插入
-- ============================================
INSERT INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`)
VALUES
(481, 4, 0, '内容推送', '/admin/push/channel', 'push.*', 'push_channel', 'bi bi-send', 72, 1),
(482, 4, 0, '推送日志', '/admin/push/log', 'push.*', 'push_log', 'bi bi-journal-code', 73, 1),
(483, 4, 0, '订阅管理', '/admin/subscriber/index', 'subscriber.*', 'subscriber', 'bi bi-envelope-plus', 74, 1),
(484, 4, 0, '邮件日志', '/admin/mail_log/index', 'mail_log.*', 'mail_log', 'bi bi-envelope-check', 75, 1)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `url` = VALUES(`url`),
  `permission` = VALUES(`permission`),
  `active` = VALUES(`active`),
  `icon` = VALUES(`icon`),
  `sort` = VALUES(`sort`),
  `status` = VALUES(`status`);
