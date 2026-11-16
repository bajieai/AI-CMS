-- =============================================================
-- V2.9.64 Sprint M：菜单双源对齐（AI主题安装 936）
-- 背景：V2.9.63 起该菜单只由迁移 SQL 写入 936，config/menu.php 与 install.sql 缺失，
--       导致 menu:sync / 全新安装 / 存量升级三条路径的 id 不一致。
-- 本脚本幂等：已存在 936 时删除可能存在的 952 重复行；两者都缺时补 936。
-- =============================================================

SET NAMES utf8mb4;

DELETE FROM `i8j_menu_item`
WHERE `id` = 952
  AND `url` = '/admin/theme_generator/index'
  AND EXISTS (SELECT 1 FROM (SELECT `id` FROM `i8j_menu_item` WHERE `id` = 936) t);

UPDATE `i8j_menu_item`
SET `id` = 936
WHERE `id` = 952
  AND `url` = '/admin/theme_generator/index';

INSERT IGNORE INTO `i8j_menu_item`
(`id`,`group_id`,`parent_id`,`name`,`url`,`permission`,`active`,`icon`,`sort`,`status`,`module`,`create_time`,`update_time`)
VALUES
(936,7,0,'AI主题安装','/admin/theme_generator/index','theme_generator.*','theme_generator','bi bi-box-seam',790,1,NULL,0,0);
