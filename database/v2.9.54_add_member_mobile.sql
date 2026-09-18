-- V2.9.54: member 表新增 mobile 字段（手机号+短信验证码注册，全局唯一）
-- 幂等说明：重复执行时 ADD COLUMN 报 1060 / ADD UNIQUE KEY 报 1061，
-- UpgradeService::executeSqlContent 的跳过列表（already exists/duplicate/1050/1060/1061/1062/1091）会自动跳过。
-- NULL 存储未绑定状态：MySQL 唯一索引对 NULL 不去重，存量会员（未绑手机号）不受影响。
ALTER TABLE `{prefix}member` ADD COLUMN `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '手机号（唯一，手机号验证码注册；NULL=未绑定）' AFTER `email`;
ALTER TABLE `{prefix}member` ADD UNIQUE KEY `uk_mobile` (`mobile`);
