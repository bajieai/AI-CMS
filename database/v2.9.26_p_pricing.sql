-- AI-CMS V2.9.26 Sprint P-4: 定价与促销管理
SET NAMES utf8mb4;

ALTER TABLE `i8j_template_store`
  ADD COLUMN `billing_type` varchar(20) NOT NULL DEFAULT 'free' COMMENT '计费类型: free/one_time/subscription' AFTER `price`,
  ADD COLUMN `price_original` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '原价' AFTER `billing_type`,
  ADD COLUMN `price_sale` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '促销价' AFTER `price_original`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `i8j_template_promotion`;
CREATE TABLE `i8j_template_promotion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `type` varchar(20) NOT NULL DEFAULT 'discount',
  `discount_type` varchar(10) NOT NULL DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `template_ids` text NULL,
  `category_id` int(11) NOT NULL DEFAULT 0,
  `start_time` datetime NULL,
  `end_time` datetime NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `sort` int(11) NOT NULL DEFAULT 100,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板促销活动';

DROP TABLE IF EXISTS `i8j_template_coupon`;
CREATE TABLE `i8j_template_coupon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL DEFAULT '',
  `discount_type` varchar(10) NOT NULL DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_uses` int(11) NOT NULL DEFAULT 0,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `template_ids` text NULL,
  `start_time` datetime NULL,
  `end_time` datetime NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板优惠码';

DROP TABLE IF EXISTS `i8j_template_price_log`;
CREATE TABLE `i8j_template_price_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL DEFAULT 0,
  `operator_id` int(11) NOT NULL DEFAULT 0,
  `operator_name` varchar(50) NOT NULL DEFAULT '',
  `action` varchar(20) NOT NULL DEFAULT '',
  `old_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `new_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(200) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板价格变更日志';

SET FOREIGN_KEY_CHECKS = 1;
