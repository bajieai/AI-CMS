-- ============================================================
-- V2.9.24 H-3: 模板商店运营(G-1~G-5) 缺失建表补丁
-- 幂等DDL：可重复执行不报错
-- ============================================================

SET NAMES utf8mb4;
SET @dbname = DATABASE();
SET @tbl_prefix = (SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME LIKE '%template_store' LIMIT 1);
SET @tbl_prefix = IFNULL(@tbl_prefix, 'i8j_template_store');
SET @tbl_prefix = SUBSTRING(@tbl_prefix, 1, LENGTH(@tbl_prefix) - LENGTH('template_store'));

-- ============================================================
-- 1. 模板商店Banner表 (G-1)
-- ============================================================
SET @t1 = CONCAT(@tbl_prefix, 'template_banner');
SET @e1 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t1);
SET @s1 = IF(@e1 = 0, CONCAT('CREATE TABLE `', @t1, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(128) NOT NULL DEFAULT "" COMMENT "Banner标题",
  `image` varchar(255) NOT NULL DEFAULT "" COMMENT "Banner图片URL",
  `target_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT "跳转类型:1外部URL/2模板详情/3分类页面",
  `target_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "跳转目标ID",
  `target_url` varchar(255) NOT NULL DEFAULT "" COMMENT "外部跳转URL",
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT "排序号",
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT "状态:0禁用/1启用",
  `start_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "开始展示时间",
  `end_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "结束展示时间",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`, `sort`),
  KEY `idx_time` (`start_time`, `end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="模板商店Banner表(V2.9.24)"'), 'SELECT "template_banner already exists" AS msg');
PREPARE stmt1 FROM @s1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

-- ============================================================
-- 2. 模板推荐位表 (G-2)
-- ============================================================
SET @t2 = CONCAT(@tbl_prefix, 'template_recommend');
SET @e2 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t2);
SET @s2 = IF(@e2 = 0, CONCAT('CREATE TABLE `', @t2, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `position` tinyint(1) NOT NULL DEFAULT 1 COMMENT "推荐位置:1首页顶部/2热门/3新品/4精选",
  `recommend_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT "推荐类型:1手动指定/2自动热门/3自动最新",
  `template_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "关联模板ID(手动指定时)",
  `title` varchar(128) NOT NULL DEFAULT "" COMMENT "推荐位标题",
  `description` varchar(255) DEFAULT NULL COMMENT "推荐位描述",
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT "排序号",
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT "状态:0禁用/1启用",
  `start_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "开始展示时间",
  `end_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "结束展示时间",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_position_status` (`position`, `status`, `sort`),
  KEY `idx_template` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="模板推荐位表(V2.9.24)"'), 'SELECT "template_recommend already exists" AS msg');
PREPARE stmt2 FROM @s2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- ============================================================
-- 3. 模板安装日志表 (G-4)
-- ============================================================
SET @t3 = CONCAT(@tbl_prefix, 'template_install_log');
SET @e3 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t3);
SET @s3 = IF(@e3 = 0, CONCAT('CREATE TABLE `', @t3, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "模板ID",
  `member_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "用户ID",
  `action` tinyint(1) NOT NULL DEFAULT 1 COMMENT "动作:1安装/2卸载/3切换/4基线迁移",
  `source` tinyint(1) NOT NULL DEFAULT 1 COMMENT "来源:1商店/2上传/3恢复",
  `ip` varchar(45) NOT NULL DEFAULT "" COMMENT "操作IP",
  `extra` json DEFAULT NULL COMMENT "额外信息JSON",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_template_action` (`template_id`, `action`),
  KEY `idx_member` (`member_id`),
  KEY `idx_action_time` (`action`, `create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="模板安装日志表(V2.9.24)"'), 'SELECT "template_install_log already exists" AS msg');
PREPARE stmt3 FROM @s3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

-- ============================================================
-- 4. template_review 表补充缺失字段 (G-5 评论批量管理)
-- ============================================================

-- 4.1 添加 status 字段（兼容控制器查询）
SET @col_status = 'status';
SET @exists_status = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = CONCAT(@tbl_prefix, 'template_review') AND COLUMN_NAME = @col_status);
SET @sql_status = IF(@exists_status = 0,
  CONCAT('ALTER TABLE `', @tbl_prefix, 'template_review` ADD COLUMN `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT "审核状态:0待审核/1通过/2拒绝(兼容字段)" AFTER `is_audited`'),
  'SELECT "status column already exists" AS msg'
);
PREPARE stmt_status FROM @sql_status;
EXECUTE stmt_status;
DEALLOCATE PREPARE stmt_status;

-- 4.2 添加 images 字段（V2.9.13遗漏的）
SET @col_images = 'images';
SET @exists_images = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = CONCAT(@tbl_prefix, 'template_review') AND COLUMN_NAME = @col_images);
SET @sql_images = IF(@exists_images = 0,
  CONCAT('ALTER TABLE `', @tbl_prefix, 'template_review` ADD COLUMN `images` json DEFAULT NULL COMMENT "评论图片JSON数组(V2.9.13)" AFTER `content`'),
  'SELECT "images column already exists" AS msg'
);
PREPARE stmt_images FROM @sql_images;
EXECUTE stmt_images;
DEALLOCATE PREPARE stmt_images;

-- 4.3 添加 reply 字段
SET @col_reply = 'reply';
SET @exists_reply = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = CONCAT(@tbl_prefix, 'template_review') AND COLUMN_NAME = @col_reply);
SET @sql_reply = IF(@exists_reply = 0,
  CONCAT('ALTER TABLE `', @tbl_prefix, 'template_review` ADD COLUMN `reply` varchar(500) NOT NULL DEFAULT "" COMMENT "管理员回复(V2.9.24)" AFTER `images`'),
  'SELECT "reply column already exists" AS msg'
);
PREPARE stmt_reply FROM @sql_reply;
EXECUTE stmt_reply;
DEALLOCATE PREPARE stmt_reply;

-- 4.4 添加 reply_time 字段
SET @col_reply_time = 'reply_time';
SET @exists_reply_time = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = CONCAT(@tbl_prefix, 'template_review') AND COLUMN_NAME = @col_reply_time);
SET @sql_reply_time = IF(@exists_reply_time = 0,
  CONCAT('ALTER TABLE `', @tbl_prefix, 'template_review` ADD COLUMN `reply_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "回复时间(V2.9.24)" AFTER `reply`'),
  'SELECT "reply_time column already exists" AS msg'
);
PREPARE stmt_reply_time FROM @sql_reply_time;
EXECUTE stmt_reply_time;
DEALLOCATE PREPARE stmt_reply_time;

SELECT 'V2.9.24 H-3 migration completed' AS result;
