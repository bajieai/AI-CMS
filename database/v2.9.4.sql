-- ============================================================
-- AI-CMS V2.9.4 数据库增量脚本
-- 执行环境：MySQL 8.0+
-- ============================================================
SET NAMES utf8mb4;

-- 获取当前数据库名（用于动态条件判断）
SET @dbname = DATABASE();

-- ============================================================
-- 1. 发布状态日志表（模块1.1）
-- 注意：i8j_publish_log 在V2.5已存在，V2.9.4需要增加字段
-- ============================================================
-- 添加新字段（如不存在）
SET @exists_platform = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'i8j_publish_log'
    AND COLUMN_NAME = 'platform'
);
SET @sql_add_platform = IF(@exists_platform = 0,
  'ALTER TABLE `i8j_publish_log` ADD COLUMN `platform` varchar(50) NOT NULL DEFAULT '''' COMMENT ''发布平台: weixin/toutiao/zhihu'' AFTER `platform_id`',
  'SELECT "platform column already exists" AS info'
);
PREPARE stmt_platform FROM @sql_add_platform;
EXECUTE stmt_platform;
DEALLOCATE PREPARE stmt_platform;

SET @exists_action = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'i8j_publish_log'
    AND COLUMN_NAME = 'action'
);
SET @sql_add_action = IF(@exists_action = 0,
  'ALTER TABLE `i8j_publish_log` ADD COLUMN `action` varchar(20) NOT NULL DEFAULT ''publish'' COMMENT ''操作: publish/update/delete/retry'' AFTER `platform`',
  'SELECT "action column already exists" AS info'
);
PREPARE stmt_action FROM @sql_add_action;
EXECUTE stmt_action;
DEALLOCATE PREPARE stmt_action;

SET @exists_error_msg = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'i8j_publish_log'
    AND COLUMN_NAME = 'error_msg'
);
SET @sql_add_error = IF(@exists_error_msg = 0,
  'ALTER TABLE `i8j_publish_log` ADD COLUMN `error_msg` varchar(1000) NOT NULL DEFAULT '''' COMMENT ''错误信息'' AFTER `status`',
  'SELECT "error_msg column already exists" AS info'
);
PREPARE stmt_error FROM @sql_add_error;
EXECUTE stmt_error;
DEALLOCATE PREPARE stmt_error;

SET @exists_retry = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'i8j_publish_log'
    AND COLUMN_NAME = 'retry_count'
);
SET @sql_add_retry = IF(@exists_retry = 0,
  'ALTER TABLE `i8j_publish_log` ADD COLUMN `retry_count` tinyint UNSIGNED NOT NULL DEFAULT 0 COMMENT ''重试次数'' AFTER `error_msg`',
  'SELECT "retry_count column already exists" AS info'
);
PREPARE stmt_retry FROM @sql_add_retry;
EXECUTE stmt_retry;
DEALLOCATE PREPARE stmt_retry;

SET @exists_update_time = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'i8j_publish_log'
    AND COLUMN_NAME = 'update_time'
);
SET @sql_add_update = IF(@exists_update_time = 0,
  'ALTER TABLE `i8j_publish_log` ADD COLUMN `update_time` int UNSIGNED NOT NULL DEFAULT 0 AFTER `create_time`',
  'SELECT "update_time column already exists" AS info'
);
PREPARE stmt_update FROM @sql_add_update;
EXECUTE stmt_update;
DEALLOCATE PREPARE stmt_update;

-- 添加索引（如不存在）
SET @idx_platform = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'i8j_publish_log' AND INDEX_NAME = 'platform');
SET @sql_idx_platform = IF(@idx_platform = 0, 'ALTER TABLE `i8j_publish_log` ADD KEY `platform` (`platform`)', 'SELECT "index platform already exists" AS info');
PREPARE stmt_idx FROM @sql_idx_platform;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;

-- ============================================================
-- 2. 插件评分评价表（模块1.2）
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_plugin_rating` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `plugin_code` varchar(100) NOT NULL DEFAULT '' COMMENT '插件标识',
  `user_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `rating` tinyint UNSIGNED NOT NULL DEFAULT 5 COMMENT '评分1-5',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '评价内容',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `plugin_code` (`plugin_code`),
  UNIQUE KEY `uk_plugin_user` (`plugin_code`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='插件评分评价';

-- ============================================================
-- 3. 订单表（模块3.1）
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_orders` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL DEFAULT '' COMMENT '订单号',
  `user_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `source` varchar(50) NOT NULL DEFAULT '' COMMENT '来源: plugin/template/member/content',
  `source_id` varchar(100) NOT NULL DEFAULT '' COMMENT '来源ID(插件code/模板code/会员等级ID/内容ID)',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '订单金额',
  `status` tinyint UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态: 0=待支付 1=已支付 2=已退款 3=已关闭',
  `pay_method` varchar(20) NOT NULL DEFAULT '' COMMENT '支付方式: wechat/alipay',
  `pay_trade_no` varchar(64) NOT NULL DEFAULT '' COMMENT '第三方交易号',
  `paid_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付时间',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `user_id` (`user_id`),
  KEY `source` (`source`, `source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';

-- ============================================================
-- 4. 许可证表（模块3.2）
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_licenses` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `license_code` varchar(64) NOT NULL DEFAULT '' COMMENT '许可证编码(唯一)',
  `product_type` varchar(20) NOT NULL DEFAULT '' COMMENT '产品类型: plugin/template',
  `product_code` varchar(100) NOT NULL DEFAULT '' COMMENT '产品编码',
  `user_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属用户',
  `license_type` varchar(20) NOT NULL DEFAULT 'standard' COMMENT '类型: standard/pro/lifetime',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT '状态: active/suspended/revoked/expired',
  `bind_domain` varchar(255) NOT NULL DEFAULT '' COMMENT '绑定域名',
  `valid_from` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '有效期开始',
  `valid_until` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '有效期结束',
  `last_verified` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后验证时间',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `license_code` (`license_code`),
  KEY `user_id` (`user_id`),
  KEY `product_type_code` (`product_type`, `product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='许可证表';

-- ============================================================
-- 5. 备份操作日志表（模块5.1）
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_backup_log` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `backup_type` varchar(20) NOT NULL DEFAULT '' COMMENT '类型: database/files',
  `file_name` varchar(255) NOT NULL DEFAULT '' COMMENT '备份文件名',
  `file_size` bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT '文件大小(bytes)',
  `status` tinyint UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态: 0=进行中 1=成功 2=失败',
  `error_msg` varchar(500) NOT NULL DEFAULT '' COMMENT '错误信息',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `backup_type` (`backup_type`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='备份操作日志';

-- ============================================================
-- 6. 会员降级日志表（模块5.2）
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_member_downgrade_log` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `from_level` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '原等级ID',
  `to_level` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '目标等级ID',
  `action` varchar(20) NOT NULL DEFAULT '' COMMENT '操作: auto_downgrade/auto_upgrade/manual',
  `trigger_condition` varchar(100) NOT NULL DEFAULT '' COMMENT '触发条件: points_insufficient/grace_expired/admin_manual',
  `notified` tinyint UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已通知',
  `create_time` int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员降级日志';

-- ============================================================
-- 7. 栏目默认写作风格字段（模块2.2）
-- ============================================================
SET @exists_default_style = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'i8j_cate'
    AND COLUMN_NAME = 'default_style'
);
SET @sql_add_style = IF(@exists_default_style = 0,
  'ALTER TABLE `i8j_cate` ADD COLUMN `default_style` varchar(20) NOT NULL DEFAULT ''formal'' COMMENT ''默认写作风格: formal/relaxed/professional/warm''',
  'SELECT "default_style column already exists" AS info'
);
PREPARE stmt_style FROM @sql_add_style;
EXECUTE stmt_style;
DEALLOCATE PREPARE stmt_style;

-- ============================================================
-- 8. 内容表增加付费阅读字段（模块3.3）
-- ============================================================
SET @exists_is_paid = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'i8j_content'
    AND COLUMN_NAME = 'is_paid'
);
SET @sql_add_paid = IF(@exists_is_paid = 0,
  'ALTER TABLE `i8j_content` ADD COLUMN `is_paid` tinyint UNSIGNED NOT NULL DEFAULT 0 COMMENT ''是否付费阅读: 0=否 1=是'' AFTER `status`',
  'SELECT "is_paid column already exists" AS info'
);
PREPARE stmt_paid FROM @sql_add_paid;
EXECUTE stmt_paid;
DEALLOCATE PREPARE stmt_paid;

SET @exists_pay_price = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'i8j_content'
    AND COLUMN_NAME = 'pay_price'
);
SET @sql_add_price = IF(@exists_pay_price = 0,
  'ALTER TABLE `i8j_content` ADD COLUMN `pay_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT ''付费价格'' AFTER `is_paid`',
  'SELECT "pay_price column already exists" AS info'
);
PREPARE stmt_price FROM @sql_add_price;
EXECUTE stmt_price;
DEALLOCATE PREPARE stmt_price;

-- ============================================================
-- 9. 配置项
-- ============================================================
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('content_quality_check_enabled', '1', 'content', 'switch', '启用AI内容质量检测(可读性/SEO/敏感词)', 18),
('sensitive_words_check_enabled', '1', 'content', 'switch', '启用敏感词过滤检测', 19),
('pay_enabled', '0', 'pay', 'switch', '启用支付功能(需先配置支付参数)', 1),
('pay_wechat_enabled', '0', 'pay', 'switch', '启用微信支付', 2),
('pay_alipay_enabled', '0', 'pay', 'switch', '启用支付宝支付', 3),
('license_verify_enabled', '0', 'plugin', 'switch', '启用许可证远程验证(插件商店)', 15),
('paid_content_enabled', '0', 'content', 'switch', '启用付费阅读功能', 21)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- ============================================================
-- 10. 版本标记
-- ============================================================
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('app_version', '2.9.4', 'system', 'text', '当前系统版本号', 0)
ON DUPLICATE KEY UPDATE `value` = '2.9.4';
