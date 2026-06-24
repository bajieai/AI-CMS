-- ============================================================
-- AI-CMS V2.9.29 全版本菜单SQL
-- ============================================================

SET NAMES utf8mb4;

-- Sprint C 菜单
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(520, 4, 0, '内容模型管理', '/admin/content_model/index', 'content_model.*', 'content_model', 'bi bi-diagram-2', 80, 1);

-- Sprint D 菜单
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(530, 4, 0, '开发者管理', '/admin/developer/index', 'developer.*', 'developer', 'bi bi-person-badge', 91, 1),
(531, 4, 0, 'Webhook管理', '/admin/webhook/index', 'webhook.*', 'webhook', 'bi bi-broadcast', 92, 1),
(532, 4, 0, 'API密钥管理', '/admin/api_key/index', 'api_key.*', 'api_key', 'bi bi-key', 93, 1),
(533, 4, 0, 'API文档', '/admin/api_key/doc', 'api_key.doc', 'api_key', 'bi bi-file-earmark-code', 94, 1);

-- Sprint T 菜单
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(540, 4, 0, '模板分类管理', '/admin/template_category_v2/index', 'template_category_v2.*', 'template_category_v2', 'bi bi-tags', 85, 1),
(541, 4, 0, '模板审核报告', '/admin/template_audit_report/index', 'template_audit_report.*', 'template_audit_report', 'bi bi-shield-check', 86, 1),
(542, 4, 0, '模板统计详情', '/admin/template_stats_detail/index', 'template_stats_detail.*', 'template_stats_detail', 'bi bi-graph-up', 87, 1);

-- Sprint I 菜单
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(550, 4, 0, '内容关系图谱', '/admin/content_relation/index', 'content_relation.*', 'content_relation', 'bi bi-diagram-3', 75, 1),
(551, 4, 0, '行动计划', '/admin/content_action_plan/index', 'content_action_plan.*', 'content_action_plan', 'bi bi-clock-history', 76, 1),
(552, 4, 0, '评论管理', '/admin/comment_admin/index', 'comment_admin.*', 'comment_admin', 'bi bi-chat-dots', 77, 1),
(553, 4, 0, '操作审计', '/admin/content_audit_log/index', 'content_audit_log.*', 'content_audit_log', 'bi bi-journal-text', 78, 1);
