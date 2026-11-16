-- =============================================================
-- V2.9.64 Sprint T：主题安装验收闭环（状态机字段）
-- 说明：默认值 1（已验收），保证存量主题升级后不被隐藏；
--       新安装/AI 生成的主题由代码写入 0（待验收），验收通过才进前台可选列表。
-- =============================================================

SET NAMES utf8mb4;

ALTER TABLE `i8j_theme_info`
  ADD COLUMN `review_status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '验收状态:0待验收,1已验收,2已驳回' AFTER `is_installed`,
  ADD COLUMN `review_by` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '验收人ID' AFTER `review_status`,
  ADD COLUMN `review_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '验收时间' AFTER `review_by`,
  ADD COLUMN `review_remark` varchar(255) NOT NULL DEFAULT '' COMMENT '驳回原因' AFTER `review_time`;
