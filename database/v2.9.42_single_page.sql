-- V2.9.42 单页面功能优化迁移脚本
-- 1. cate表新增content_id字段
-- 2. 预置3个单页分类及对应content记录

-- 1. cate表新增content_id字段
ALTER TABLE `{prefix}cate` ADD COLUMN `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '单页内容ID(type=6时关联content表)' AFTER `default_style`;
ALTER TABLE `{prefix}cate` ADD KEY `idx_content_id` (`content_id`);

-- 2. 插入3条单页content记录（ID从2开始，因为ID 1已被占用）
INSERT INTO `{prefix}content` (`id`,`title`,`content`,`excerpt`,`type`,`status`,`cate_id`,`create_time`,`update_time`,`seo_title`,`seo_keywords`,`seo_description`) VALUES
(2,'关于我们','<p>八界AI-CMS是一款由AI驱动的内容管理系统...</p>','',6,2,6,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'关于我们 - 八界AI-CMS','关于我们,公司介绍','八界AI-CMS是一款由AI驱动的内容管理系统'),
(3,'联系方式','<p>电话：xxx-xxxx-xxxx<br>邮箱：contact@example.com<br>地址：xx省xx市xx区xx路xx号</p>','',6,2,7,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'联系方式 - 八界AI-CMS','联系方式,电话,邮箱','联系我们'),
(4,'公司简介','<p>湖北八界智能技术有限公司成立于xxxx年...</p>','',6,2,8,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'公司简介 - 八界AI-CMS','公司简介,企业介绍','湖北八界智能技术有限公司简介');

-- 3. 插入3个单页分类（如果不存在）
INSERT INTO `{prefix}cate` (`id`,`name`,`type`,`parent_id`,`sort`,`status`,`create_time`,`update_time`,`default_style`,`content_id`) VALUES
(6,'关于我们',6,0,6,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'formal',2),
(7,'联系方式',6,0,7,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'formal',3),
(8,'公司简介',6,0,8,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'formal',4)
ON DUPLICATE KEY UPDATE `content_id` = VALUES(`content_id`);
