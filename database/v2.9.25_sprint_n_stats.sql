-- =====================================================
-- V2.9.25 Sprint N: 模板商店运营数据统计增强
-- 4张新表：使用日志、日统计汇总、订单、结算
-- =====================================================

SET @dbname = DATABASE();
SET @tbl_prefix = 'i8j_';

-- 1. 模板使用日志表（埋点）N-2
SET @t1 = CONCAT(@tbl_prefix, 'template_usage_log');
SET @e1 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t1);
SET @s1 = IF(@e1 = 0, CONCAT('CREATE TABLE `', @t1, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "模板ID",
  `member_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "用户ID",
  `event_type` varchar(32) NOT NULL DEFAULT "" COMMENT "事件类型: view/preview/install/activate/custom",
  `device` varchar(16) NOT NULL DEFAULT "pc" COMMENT "设备: pc/mobile/tablet",
  `ip` varchar(45) NOT NULL DEFAULT "" COMMENT "IP地址",
  `user_agent` varchar(500) NOT NULL DEFAULT "" COMMENT "UA",
  `referer` varchar(500) NOT NULL DEFAULT "" COMMENT "来源页",
  `extra` json DEFAULT NULL COMMENT "额外信息JSON",
  `create_date` date NOT NULL COMMENT "日期（用于汇总）",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_template_date` (`template_id`, `create_date`),
  KEY `idx_event_date` (`event_type`, `create_date`),
  KEY `idx_member` (`member_id`),
  KEY `idx_date` (`create_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="模板使用日志表(V2.9.25 N-2)"'), 'SELECT 1');
PREPARE stmt FROM @s1; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. 模板日统计汇总表 N-2
SET @t2 = CONCAT(@tbl_prefix, 'template_daily_stats');
SET @e2 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t2);
SET @s2 = IF(@e2 = 0, CONCAT('CREATE TABLE `', @t2, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "模板ID（0=全站汇总）",
  `stats_date` date NOT NULL COMMENT "统计日期",
  `view_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "浏览次数",
  `unique_visitors` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "独立访客数",
  `install_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "安装次数",
  `uninstall_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "卸载次数",
  `activate_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "激活次数",
  `dau` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "DAU",
  `mau` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "MAU（月活，仅每月1日计算）",
  `revenue` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT "当日收入",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_date` (`template_id`, `stats_date`),
  KEY `idx_date` (`stats_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="模板日统计汇总表(V2.9.25 N-2)"'), 'SELECT 1');
PREPARE stmt FROM @s2; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. 模板订单表 N-3
SET @t3 = CONCAT(@tbl_prefix, 'template_order');
SET @e3 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t3);
SET @s3 = IF(@e3 = 0, CONCAT('CREATE TABLE `', @t3, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL DEFAULT "" COMMENT "订单号",
  `template_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "模板ID",
  `template_name` varchar(128) NOT NULL DEFAULT "" COMMENT "模板名称（冗余）",
  `member_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "购买用户ID",
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT "订单金额",
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT "优惠金额",
  `pay_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT "实付金额",
  `pay_method` varchar(16) NOT NULL DEFAULT "" COMMENT "支付方式: wechat/alipay/balance",
  `pay_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT "支付状态: 0待支付/1已支付/2已退款/3已取消",
  `pay_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "支付时间",
  `refund_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "退款时间",
  `refund_reason` varchar(255) NOT NULL DEFAULT "" COMMENT "退款原因",
  `settlement_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT "结算状态: 0未结算/1已结算",
  `settlement_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "结算批次ID",
  `ip` varchar(45) NOT NULL DEFAULT "" COMMENT "下单IP",
  `extra` json DEFAULT NULL COMMENT "额外信息",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_template` (`template_id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_pay_status` (`pay_status`),
  KEY `idx_settlement` (`settlement_status`, `settlement_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="模板订单表(V2.9.25 N-3)"'), 'SELECT 1');
PREPARE stmt FROM @s3; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. 结算报表表 N-3
SET @t4 = CONCAT(@tbl_prefix, 'template_settlement');
SET @e4 = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @t4);
SET @s4 = IF(@e4 = 0, CONCAT('CREATE TABLE `', @t4, '` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `batch_no` varchar(32) NOT NULL DEFAULT "" COMMENT "结算批次号",
  `period_start` date NOT NULL COMMENT "结算周期开始",
  `period_end` date NOT NULL COMMENT "结算周期结束",
  `total_orders` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "订单总数",
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT "订单总金额",
  `commission_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT "平台佣金",
  `settlement_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT "应结金额",
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT "状态: 0待审核/1已审核/2已打款/3已关闭",
  `auditor` varchar(64) NOT NULL DEFAULT "" COMMENT "审核人",
  `audit_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT "审核时间",
  `remark` varchar(500) NOT NULL DEFAULT "" COMMENT "备注",
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_batch_no` (`batch_no`),
  KEY `idx_period` (`period_start`, `period_end`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="结算报表表(V2.9.25 N-3)"'), 'SELECT 1');
PREPARE stmt FROM @s4; EXECUTE stmt; DEALLOCATE PREPARE stmt;
