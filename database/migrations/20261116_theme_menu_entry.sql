-- +----------------------------------------------------------------------
-- | 八界AI-CMS 内容管理系统
-- +----------------------------------------------------------------------
-- | 迁移：为存量库补充「AI主题安装」后台菜单入口
-- | 适用：已安装的存量库（新装请直接使用 database/install.sql，其中已含该菜单）
-- | 特性：可重复执行。按 url 判重，已存在则不插入；分组不存在时同样跳过，不报错。
-- | 前缀：与 install.sql 一致使用 {prefix} 占位符，执行前请替换为实际表前缀（默认 i8j_）。
-- | 切分：升级执行器按行累积、以行尾分号断句，因此整条语句用多行书写，仅末行以分号结尾。
-- +----------------------------------------------------------------------

INSERT INTO `{prefix}menu_item`
  (`group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`, `module`, `create_time`, `update_time`)
SELECT 7, 0, 'AI主题安装', '/admin/theme_generator/index', 'theme_generator.*', 'theme_generator', 'bi bi-box-seam', 790, 1, NULL, 0, 0
FROM DUAL
WHERE EXISTS (SELECT 1 FROM `{prefix}menu_group` WHERE `id` = 7)
  AND NOT EXISTS (SELECT 1 FROM `{prefix}menu_item` WHERE `url` = '/admin/theme_generator/index');
