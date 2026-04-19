-- AI-CMS 菜单名称更新迁移脚本
-- 将"文章管理"统一重命名为"信息管理"
-- 执行日期: 2026-04-20

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 更新权限表中的菜单名称
-- ----------------------------

-- 更新主菜单
UPDATE `i8j_aicms_permissions` 
SET `name` = '信息管理', 
    `description` = '信息管理模块' 
WHERE `name` = '文章管理' 
  AND `type` = 'menu';

-- 更新子菜单 - 文章列表改为信息列表
UPDATE `i8j_aicms_permissions` 
SET `name` = '信息列表', 
    `description` = '信息列表' 
WHERE `name` = '文章列表' 
  AND `type` = 'menu';

-- 更新操作权限中的描述
UPDATE `i8j_aicms_permissions` 
SET `description` = '查看信息详情' 
WHERE `description` = '查看文章详情';

UPDATE `i8j_aicms_permissions` 
SET `description` = '创建新信息' 
WHERE `description` = '创建新文章';

UPDATE `i8j_aicms_permissions` 
SET `description` = '编辑信息' 
WHERE `description` = '编辑文章';

UPDATE `i8j_aicms_permissions` 
SET `description` = '删除信息' 
WHERE `description` = '删除文章';

-- ----------------------------
-- 验证更新结果
-- ----------------------------
SELECT `id`, `name`, `slug`, `description`, `type` 
FROM `i8j_aicms_permissions` 
WHERE `slug` LIKE 'article%'
ORDER BY `id`;

SET FOREIGN_KEY_CHECKS = 1;
