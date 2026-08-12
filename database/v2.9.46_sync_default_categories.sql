-- V2.9.46 默认分类列表同步
-- 适用：内容模型已采用统一编号的运行库。
-- 分类 type、model_id 必须与 content_model.id 一致。

START TRANSACTION;

UPDATE `i8j_cate`
SET `name` = CASE `id`
        WHEN 1 THEN '新闻动态'
        WHEN 2 THEN '产品中心'
        WHEN 3 THEN '成功案例'
        WHEN 4 THEN '资料下载'
        WHEN 5 THEN '人才招聘'
        WHEN 6 THEN '关于我们'
        WHEN 7 THEN '联系方式'
        WHEN 8 THEN '公司简介'
    END,
    `type` = CASE `id`
        WHEN 1 THEN 1
        WHEN 2 THEN 3
        WHEN 3 THEN 4
        WHEN 4 THEN 5
        WHEN 5 THEN 6
        WHEN 6 THEN 2
        WHEN 7 THEN 2
        WHEN 8 THEN 2
    END,
    `model_id` = CASE `id`
        WHEN 1 THEN 1
        WHEN 2 THEN 3
        WHEN 3 THEN 4
        WHEN 4 THEN 5
        WHEN 5 THEN 6
        WHEN 6 THEN 2
        WHEN 7 THEN 2
        WHEN 8 THEN 2
    END,
    `sort` = `id`
WHERE `id` BETWEEN 1 AND 8;

UPDATE `i8j_content`
SET `type` = 2,
    `model_id` = 2,
    `model_identifier` = 'model_page'
WHERE `cate_id` IN (6, 7, 8)
  AND `id` IN (2, 3, 4);

COMMIT;

SELECT `id`, `name`, `type`, `model_id`, `sort`
FROM `i8j_cate`
WHERE `id` BETWEEN 1 AND 8
ORDER BY `sort`, `id`;
