-- +----------------------------------------------------------------------
-- | AI-CMS V2.9.44 在线升级系统数据库迁移
-- +----------------------------------------------------------------------
-- | 创建升级日志表和SQL补丁执行记录表，并添加后台菜单项
-- +----------------------------------------------------------------------

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 升级日志表
DROP TABLE IF EXISTS `{prefix}upgrade_log`;
CREATE TABLE `{prefix}upgrade_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `from_version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '升级前版本',
  `to_version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '目标版本',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态:0待执行/1成功/2失败/3已回滚',
  `backup_db_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '数据库备份路径',
  `backup_files_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件备份路径',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '错误信息',
  `upgrade_steps` json DEFAULT NULL COMMENT '升级步骤JSON',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统升级日志表';

-- SQL补丁执行记录表（幂等执行）
DROP TABLE IF EXISTS `{prefix}upgrade_patch`;
CREATE TABLE `{prefix}upgrade_patch` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '版本号',
  `patch_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '补丁文件名',
  `checksum` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '校验值',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态:1成功/2失败',
  `executed_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '执行时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_version_patch` (`version`,`patch_file`),
  KEY `idx_version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SQL升级补丁执行记录表';

-- 后台菜单：在线升级（系统设置组 group_id=4）
INSERT INTO `{prefix}menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`)
VALUES (934, 4, 0, '在线升级', '/admin/online_upgrade/index', 'online_upgrade.*', 'online_upgrade', 'bi bi-cloud-arrow-up', 95, 1)
ON DUPLICATE KEY UPDATE `name`='在线升级', `url`='/admin/online_upgrade/index', `permission`='online_upgrade.*', `active`='online_upgrade', `icon`='bi bi-cloud-arrow-up', `sort`=95, `status`=1;

SET FOREIGN_KEY_CHECKS = 1;
