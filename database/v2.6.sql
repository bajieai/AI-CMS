SET NAMES utf8mb4;
-- ============================================================
-- 八界AI-CMS V2.6 数据库升级脚本
-- 从 V2.5.1 升级至 V2.6.x
-- 执行方式: 在已安装V2.5.1的数据库中按顺序执行
-- ============================================================

-- -----------------------------------------------------------
-- 遗留问题修复 (阶段零)
-- -----------------------------------------------------------

-- TD10: collect_log表补全content和pub_time字段
ALTER TABLE `{prefix}collect_log`
    ADD COLUMN `content` LONGTEXT COMMENT '采集内容' AFTER `url_hash`,
    ADD COLUMN `pub_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '原文发布时间' AFTER `content`;

-- TD16: paid_order表增加复合索引优化canAccess查询
ALTER TABLE `{prefix}paid_order`
    ADD INDEX `idx_member_content_status` (`member_id`, `content_id`, `type`, `status`);

-- TD13: member表增加VIP到期时间字段
ALTER TABLE `{prefix}member`
    ADD COLUMN `vip_expire_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT 'VIP权益到期时间(0表示非VIP)' AFTER `level_id`;

-- -----------------------------------------------------------
-- 冲刺1: CDN集成 + 付费章节 + VIP权益
-- -----------------------------------------------------------

-- content表增加付费章节相关字段 (V2.5预留is_chapter已存在)
ALTER TABLE `{prefix}content`
    ADD COLUMN `parent_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属父内容ID(付费章节专用)' AFTER `is_chapter`,
    ADD COLUMN `chapter_sort` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '章节排序号' AFTER `parent_id`,
    ADD COLUMN `chapter_title` varchar(255) DEFAULT '' COMMENT '章节标题(冗余显示)' AFTER `chapter_sort`,
    ADD COLUMN `is_free_chapter` tinyint NOT NULL DEFAULT 0 COMMENT '是否免费试读章节:0否1是' AFTER `chapter_title`,
    ADD COLUMN `download_url` varchar(500) DEFAULT '' COMMENT '付费下载文件URL' AFTER `is_free_chapter`,
    ADD COLUMN `download_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '下载单独售价(0表示跟随内容)' AFTER `download_url`,
    ADD INDEX `idx_parent_chapter` (`parent_id`, `chapter_sort`);

-- media表增加storage_driver字段(CDN集成)
ALTER TABLE `{prefix}media`
    ADD COLUMN `storage_driver` varchar(50) DEFAULT 'local' COMMENT '存储驱动:local/oss/cos/s3' AFTER `url`,
    ADD COLUMN `storage_path` varchar(500) DEFAULT '' COMMENT '对象存储路径' AFTER `storage_driver`,
    ADD COLUMN `cdn_url` varchar(500) DEFAULT '' COMMENT 'CDN加速URL' AFTER `storage_path`,
    ADD COLUMN `file_hash` varchar(64) DEFAULT '' COMMENT '文件MD5哈希(去重)' AFTER `cdn_url`;

-- member_level表增加权益字段
ALTER TABLE `{prefix}member_level`
    ADD COLUMN `discount` decimal(3,2) NOT NULL DEFAULT '1.00' COMMENT '购买折扣(1.00表示无折扣,0表示免费)' AFTER `price`,
    ADD COLUMN `points_rate` decimal(3,2) NOT NULL DEFAULT '1.00' COMMENT '积分倍率(1.00表示1倍)' AFTER `discount`,
    ADD COLUMN `daily_ai_quota` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '每日AI生成配额(0表示无限制)' AFTER `points_rate`;

-- -----------------------------------------------------------
-- 冲刺2: 会员私信系统 + 内容工作流审批
-- -----------------------------------------------------------

-- 私信会话表
CREATE TABLE IF NOT EXISTS `{prefix}message_conversation` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id_1` int UNSIGNED NOT NULL COMMENT '用户1ID(较小值)',
    `user_id_2` int UNSIGNED NOT NULL COMMENT '用户2ID(较大值)',
    `last_message_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后一条消息ID',
    `last_message_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后消息时间',
    `unread_count_1` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户1的未读数',
    `unread_count_2` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户2的未读数',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    `update_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users` (`user_id_1`, `user_id_2`),
    KEY `idx_user1_time` (`user_id_1`, `last_message_time`),
    KEY `idx_user2_time` (`user_id_2`, `last_message_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='私信会话表';

-- 私信消息表
CREATE TABLE IF NOT EXISTS `{prefix}message` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `conversation_id` int UNSIGNED NOT NULL COMMENT '会话ID',
    `from_user_id` int UNSIGNED NOT NULL COMMENT '发送者ID',
    `to_user_id` int UNSIGNED NOT NULL COMMENT '接收者ID',
    `content` text NOT NULL COMMENT '消息内容',
    `is_read` tinyint NOT NULL DEFAULT 0 COMMENT '是否已读:0否1是',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_conversation` (`conversation_id`, `create_time`),
    KEY `idx_to_user` (`to_user_id`, `is_read`, `create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='私信消息表';

-- 系统通知表
CREATE TABLE IF NOT EXISTS `{prefix}message_system` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL DEFAULT '' COMMENT '通知标题',
    `content` text COMMENT '通知内容',
    `type` varchar(50) DEFAULT 'system' COMMENT '类型:system/vip/ai/order',
    `target_url` varchar(500) DEFAULT '' COMMENT '跳转链接',
    `send_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送时间',
    `expire_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '过期时间(0永不过期)',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_type_time` (`type`, `send_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统通知表';

-- 系统通知已读记录表
CREATE TABLE IF NOT EXISTS `{prefix}message_system_read` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `message_id` int UNSIGNED NOT NULL COMMENT '通知ID',
    `user_id` int UNSIGNED NOT NULL COMMENT '用户ID',
    `read_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '阅读时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_message_user` (`message_id`, `user_id`),
    KEY `idx_user` (`user_id`, `read_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统通知已读记录表';

-- 审核工作流定义表
CREATE TABLE IF NOT EXISTS `{prefix}review_workflow` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL DEFAULT '' COMMENT '流程名称',
    `module` varchar(50) DEFAULT 'content' COMMENT '适用模块:content/member/comment',
    `steps` text COMMENT '流程步骤JSON [{step:1,role_id:0,name:"一审"},...]',
    `is_default` tinyint NOT NULL DEFAULT 0 COMMENT '是否默认流程:0否1是',
    `is_enabled` tinyint NOT NULL DEFAULT 1 COMMENT '是否启用',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    `update_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_module` (`module`, `is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审核工作流定义表';

-- 审核记录表
CREATE TABLE IF NOT EXISTS `{prefix}review_record` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `workflow_id` int UNSIGNED NOT NULL COMMENT '流程ID',
    `target_id` int UNSIGNED NOT NULL COMMENT '目标对象ID(如内容ID)',
    `target_type` varchar(50) DEFAULT 'content' COMMENT '目标类型',
    `current_step` tinyint NOT NULL DEFAULT 1 COMMENT '当前步骤序号',
    `total_steps` tinyint NOT NULL DEFAULT 1 COMMENT '总步骤数',
    `status` tinyint NOT NULL DEFAULT 0 COMMENT '状态:0待审核 1审核中 2已通过 3已拒绝 4已撤回',
    `submitter_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '提交者ID',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    `update_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_target` (`target_id`, `target_type`, `workflow_id`),
    KEY `idx_status` (`status`, `current_step`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审核记录表';

-- 审核日志表
CREATE TABLE IF NOT EXISTS `{prefix}review_log` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `record_id` int UNSIGNED NOT NULL COMMENT '审核记录ID',
    `step` tinyint NOT NULL DEFAULT 1 COMMENT '步骤序号',
    `reviewer_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核人ID',
    `action` varchar(20) DEFAULT '' COMMENT '动作:pass/reject/withdraw/transfer',
    `comment` varchar(500) DEFAULT '' COMMENT '审核意见',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_record` (`record_id`, `step`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审核日志表';

-- -----------------------------------------------------------
-- 冲刺3: 全站搜索 + 微信小程序
-- -----------------------------------------------------------

-- 搜索关键词统计表 (Meilisearch搜索热词/补全用)
CREATE TABLE IF NOT EXISTS `{prefix}search_keyword` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `keyword` varchar(100) NOT NULL DEFAULT '' COMMENT '搜索关键词',
    `count` int UNSIGNED NOT NULL DEFAULT 1 COMMENT '搜索次数',
    `last_search_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后搜索时间',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_keyword` (`keyword`),
    KEY `idx_count` (`count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='搜索关键词统计表';

-- -----------------------------------------------------------
-- 冲刺4: 积分商城 + 报表 + 插件市场 + OAuth配置页 + 表单编辑器MVP
-- -----------------------------------------------------------

-- 积分商品表
CREATE TABLE IF NOT EXISTS `{prefix}points_product` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL DEFAULT '' COMMENT '商品名称',
    `description` text COMMENT '商品描述',
    `image` varchar(500) DEFAULT '' COMMENT '商品图片',
    `points` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '所需积分',
    `stock` int NOT NULL DEFAULT 0 COMMENT '库存(-1表示无限)',
    `type` varchar(50) DEFAULT 'virtual' COMMENT '类型:virtual/physical/coupon',
    `config` text COMMENT '类型配置JSON',
    `sort` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `is_enabled` tinyint NOT NULL DEFAULT 1 COMMENT '是否上架',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    `update_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_enabled_sort` (`is_enabled`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分商品表';

-- 积分兑换记录表
CREATE TABLE IF NOT EXISTS `{prefix}points_exchange` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` int UNSIGNED NOT NULL COMMENT '用户ID',
    `product_id` int UNSIGNED NOT NULL COMMENT '商品ID',
    `points` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '消耗积分',
    `status` tinyint NOT NULL DEFAULT 0 COMMENT '状态:0待处理 1已发放 2已拒绝',
    `delivery_info` text COMMENT '发货信息JSON',
    `remark` varchar(500) DEFAULT '' COMMENT '备注',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    `update_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`, `create_time`),
    KEY `idx_status` (`status`, `create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分兑换记录表';

-- 插件评分表
CREATE TABLE IF NOT EXISTS `{prefix}plugin_rating` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `plugin_id` int UNSIGNED NOT NULL COMMENT '插件ID',
    `user_id` int UNSIGNED NOT NULL COMMENT '用户ID',
    `rating` tinyint NOT NULL DEFAULT 5 COMMENT '评分1-5',
    `comment` varchar(500) DEFAULT '' COMMENT '评价内容',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_plugin_user` (`plugin_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件评分表';

-- plugin表增加插件市场字段
ALTER TABLE `{prefix}plugin`
    ADD COLUMN `author_name` varchar(100) DEFAULT '' COMMENT '作者名称' AFTER `name`,
    ADD COLUMN `author_url` varchar(255) DEFAULT '' COMMENT '作者链接' AFTER `author_name`,
    ADD COLUMN `version_required` varchar(50) DEFAULT '' COMMENT '要求系统最低版本' AFTER `version`,
    ADD COLUMN `download_count` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '下载次数' AFTER `is_installed`,
    ADD COLUMN `rating_avg` decimal(2,1) NOT NULL DEFAULT '5.0' COMMENT '平均评分' AFTER `download_count`;

-- 主题信息表增加字段
ALTER TABLE `{prefix}theme_info`
    ADD COLUMN `author_name` varchar(100) DEFAULT '' COMMENT '作者名称' AFTER `name`,
    ADD COLUMN `author_url` varchar(255) DEFAULT '' COMMENT '作者链接' AFTER `author_name`,
    ADD COLUMN `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '售价' AFTER `author_url`,
    ADD COLUMN `sales_count` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '销量' AFTER `is_system`,
    ADD COLUMN `preview_images` text COMMENT '预览图JSON数组' AFTER `sales_count`;

-- 表单表增加可视化编辑器字段
ALTER TABLE `{prefix}form`
    ADD COLUMN `design_json` longtext COMMENT '可视化设计JSON' AFTER `fields`,
    ADD COLUMN `layout_mode` varchar(20) DEFAULT 'classic' COMMENT '布局模式:classic/grid/card' AFTER `design_json`,
    ADD COLUMN `theme_config` text COMMENT '主题配置JSON' AFTER `layout_mode`,
    ADD COLUMN `submit_btn_text` varchar(50) DEFAULT '提交' COMMENT '提交按钮文字' AFTER `theme_config`;
