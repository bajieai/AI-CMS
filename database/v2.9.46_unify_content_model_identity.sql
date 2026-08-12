-- V2.9.46 内容模型身份统一迁移
-- 目标：model.id = model.type = cate.type = content.type
-- 1信息资讯、2单页介绍、3产品信息、4企业案例、5软件下载、6人才招聘、7图片图集、8视频内容
-- 执行前务必备份数据库；脚本会保留一份迁移前快照表。

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `i8j_content_model_identity_backup_20260812` AS
SELECT * FROM `i8j_content_model`;
CREATE TABLE IF NOT EXISTS `i8j_cate_identity_backup_20260812` AS
SELECT * FROM `i8j_cate`;
CREATE TABLE IF NOT EXISTS `i8j_content_identity_backup_20260812` AS
SELECT * FROM `i8j_content`;
CREATE TABLE IF NOT EXISTS `i8j_content_model_field_identity_backup_20260812` AS
SELECT * FROM `i8j_content_model_field`;
CREATE TABLE IF NOT EXISTS `i8j_content_field_identity_backup_20260812` AS
SELECT * FROM `i8j_content_field`;
CREATE TABLE IF NOT EXISTS `i8j_content_model_template_map_identity_backup_20260812` AS
SELECT * FROM `i8j_content_model_template_map`;
CREATE TABLE IF NOT EXISTS `i8j_content_model_stats_identity_backup_20260812` AS
SELECT * FROM `i8j_content_model_stats`;
CREATE TABLE IF NOT EXISTS `i8j_content_model_migration_log_identity_backup_20260812` AS
SELECT * FROM `i8j_content_model_migration_log`;

-- 旧ID：4下载、5招聘、6案例；临时移动后改为：4案例、5下载、6招聘。
UPDATE `i8j_content_model` SET `id` = CASE `id`
    WHEN 4 THEN 104 WHEN 5 THEN 105 WHEN 6 THEN 106 ELSE `id` END
WHERE `id` IN (4, 5, 6);
UPDATE `i8j_content_model` SET `id` = CASE `id`
    WHEN 104 THEN 5 WHEN 105 THEN 6 WHEN 106 THEN 4 ELSE `id` END
WHERE `id` IN (104, 105, 106);

-- 同步所有内容模型ID外键式引用；AI模型表不属于内容模型，禁止修改。
UPDATE `i8j_cate` SET `model_id` = CASE `model_id`
    WHEN 4 THEN 5 WHEN 5 THEN 6 WHEN 6 THEN 4 ELSE `model_id` END
WHERE `model_id` IN (4, 5, 6);
UPDATE `i8j_content` SET `model_id` = CASE `model_id`
    WHEN 4 THEN 5 WHEN 5 THEN 6 WHEN 6 THEN 4 ELSE `model_id` END
WHERE `model_id` IN (4, 5, 6);
UPDATE `i8j_content_model_field` SET `model_id` = CASE `model_id`
    WHEN 4 THEN 5 WHEN 5 THEN 6 WHEN 6 THEN 4 ELSE `model_id` END
WHERE `model_id` IN (4, 5, 6);
UPDATE `i8j_content_field` SET `model_id` = CASE `model_id`
    WHEN 4 THEN 5 WHEN 5 THEN 6 WHEN 6 THEN 4 ELSE `model_id` END
WHERE `model_id` IN (4, 5, 6);
UPDATE `i8j_content_model_template_map` SET `model_id` = CASE `model_id`
    WHEN 4 THEN 5 WHEN 5 THEN 6 WHEN 6 THEN 4 ELSE `model_id` END
WHERE `model_id` IN (4, 5, 6);
UPDATE `i8j_content_model_stats` SET `model_id` = CASE `model_id`
    WHEN 4 THEN 5 WHEN 5 THEN 6 WHEN 6 THEN 4 ELSE `model_id` END
WHERE `model_id` IN (4, 5, 6);
UPDATE `i8j_content_model_migration_log` SET `model_id` = CASE `model_id`
    WHEN 4 THEN 5 WHEN 5 THEN 6 WHEN 6 THEN 4 ELSE `model_id` END
WHERE `model_id` IN (4, 5, 6);

-- 将旧业务类型转换为目标统一编号。
-- 旧：1产品、2案例、3信息、4下载、5招聘、6单页
-- 新：1信息、2单页、3产品、4案例、5下载、6招聘
UPDATE `i8j_content_model` SET `type` = `id`
WHERE `id` BETWEEN 1 AND 8;
UPDATE `i8j_cate` SET `type` = CASE `type`
    WHEN 1 THEN 3 WHEN 2 THEN 4 WHEN 3 THEN 1
    WHEN 4 THEN 5 WHEN 5 THEN 6 WHEN 6 THEN 2 ELSE `type` END
WHERE `type` BETWEEN 1 AND 6;
UPDATE `i8j_content` SET `type` = CASE `type`
    WHEN 1 THEN 3 WHEN 2 THEN 4 WHEN 3 THEN 1
    WHEN 4 THEN 5 WHEN 5 THEN 6 WHEN 6 THEN 2 ELSE `type` END
WHERE `type` BETWEEN 1 AND 6;

-- 历史单页正文曾未绑定模型（model_id=0）；按所属分类补齐模型关联。
UPDATE `i8j_content` AS `content`
INNER JOIN `i8j_cate` AS `cate` ON `cate`.`id` = `content`.`cate_id`
SET `content`.`model_id` = `cate`.`model_id`,
    `content`.`model_identifier` = 'model_page'
WHERE `content`.`model_id` = 0
  AND `content`.`type` = 2
  AND `cate`.`type` = 2
  AND `cate`.`model_id` = 2;
UPDATE `i8j_content` SET `model_id` = 2, `model_identifier` = 'model_page'
WHERE `model_id` = 0 AND `type` = 2;

-- 将预置分类绑定到对应模型，并保留既有的SEO别名。
UPDATE `i8j_cate` SET `model_id` = `type`
WHERE `type` BETWEEN 1 AND 8 AND `model_id` IN (0, 1, 2, 3, 4, 5, 6, 7, 8);

COMMIT;

-- 验收：以下三列应完全相等，且模型字段按新ID归属。
SELECT `id`, `name`, `model_identifier`, `type`, `sort`
FROM `i8j_content_model` WHERE `id` BETWEEN 1 AND 8 ORDER BY `id`;
SELECT `id`, `name`, `type`, `model_id`, `content_model_code`
FROM `i8j_cate` ORDER BY `id`;
SELECT `model_id`, COUNT(*) AS `field_count`
FROM `i8j_content_model_field` GROUP BY `model_id` ORDER BY `model_id`;
