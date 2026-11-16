-- =====================================================================
-- V2.9.63 问题反馈 · 表结构与后台菜单补齐
-- 说明: feedback 表在 install.sql 中已定义，本脚本用于已安装站点升级（幂等）
--       菜单 id 936/937 与 config/menu.php 保持一致，避免 menu:sync 产生重复
-- 编码: UTF-8 无 BOM；表名前缀 {prefix} 由导入器替换为 i8j_
-- =====================================================================

CREATE TABLE IF NOT EXISTS `{prefix}feedback` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bug' COMMENT '类型:bug程序缺陷/enhancement功能建议/question使用疑问',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '反馈标题',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '问题描述',
  `contact_email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '回信邮箱',
  `contact_mobile` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '联系电话',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '提交人后台用户ID',
  `admin_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '提交人名称',
  `attachments` text COLLATE utf8mb4_unicode_ci COMMENT '截图路径JSON数组',
  `env` text COLLATE utf8mb4_unicode_ci COMMENT '环境信息JSON',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态:0待处理1处理中2已解决3已答复4忽略',
  `reply_content` text COLLATE utf8mb4_unicode_ci COMMENT '官方答复内容',
  `reply_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '答复时间',
  `gateway_no` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '中心网关受理编号',
  `gateway_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '上报状态:0待上报1已上报2失败',
  `gateway_error` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '上报失败原因',
  `notify_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '通知状态:0未通知1已通知2失败',
  `site_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '站点地址',
  `app_version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '提交时程序版本',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_gateway_status` (`gateway_status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台问题反馈表';

INSERT IGNORE INTO `{prefix}menu_item`
(`id`,`group_id`,`parent_id`,`name`,`url`,`permission`,`active`,`icon`,`sort`,`status`,`module`,`create_time`,`update_time`) VALUES
(950,4,0,'问题反馈','/admin/feedback/index','feedback.*','feedback','bi bi-chat-left-dots',97,1,NULL,0,0),
(951,4,0,'反馈设置','/admin/feedback/config','feedback.*','feedback_config','bi bi-sliders',98,1,NULL,0,0);
