-- =============================================================
-- V2.9.64 Sprint M：菜单双源对齐（AI主题安装）
-- 背景：V2.9.63 起该菜单由迁移脚本「自增 id + 按 url 判重」写入，
--       不同站点 id 不同（本机为 936），而 config/menu.php 与 install.sql 缺失/写死其它 id，
--       导致 menu:sync 按 id 判重时插入重复行，或覆盖掉占用同 id 的其他菜单。
-- 本脚本按 url 判重，幂等：优先归一为 936（config 身份），已存在其他 id 且 936 空闲则改号，
--       完全没有时才补 936；936 被别的菜单占用时不强插、不覆盖。
-- =============================================================

SET NAMES utf8mb4;

-- 1) 仅有一条该菜单、且 936 空闲时，改号为 936（便于 menu:sync 按 id 直接命中）
UPDATE `i8j_menu_item`
SET `id` = 936
WHERE `url` = '/admin/theme_generator/index'
  AND `id` <> 936
  AND NOT EXISTS (SELECT 1 FROM (SELECT `id` FROM `i8j_menu_item` WHERE `id` = 936) t)
  AND (SELECT COUNT(*) FROM (SELECT `id` FROM `i8j_menu_item` WHERE `url` = '/admin/theme_generator/index') t2) = 1;

-- 2) 已存在 936 版本时，清掉同 url 的重复行（仅保留 936）
DELETE FROM `i8j_menu_item`
WHERE `url` = '/admin/theme_generator/index'
  AND `id` <> 936
  AND EXISTS (
      SELECT 1 FROM (
          SELECT `id` FROM `i8j_menu_item` WHERE `id` = 936 AND `url` = '/admin/theme_generator/index'
      ) t
  );

-- 3) 完全没有该菜单时补 936（936 已被其他菜单占用则跳过，不覆盖）
INSERT IGNORE INTO `i8j_menu_item`
(`id`,`group_id`,`parent_id`,`name`,`url`,`permission`,`active`,`icon`,`sort`,`status`,`module`,`create_time`,`update_time`)
SELECT 936, 7, 0, 'AI主题安装', '/admin/theme_generator/index', 'theme_generator.*', 'theme_generator', 'bi bi-box-seam', 790, 1, NULL, 0, 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `id` FROM `i8j_menu_item` WHERE `id` = 936) t1)
  AND NOT EXISTS (SELECT 1 FROM (SELECT `id` FROM `i8j_menu_item` WHERE `url` = '/admin/theme_generator/index') t2);
