-- AI-CMS V2.9.27 Sprint U 数据库变更脚本
-- 主题：模板商店商业化

SET NAMES utf8mb4;
SET @db = DATABASE();

-- 1. 模板定价表 (U-1)
CREATE TABLE IF NOT EXISTS `i8j_template_pricing` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `template_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `billing_type` VARCHAR(20) NOT NULL DEFAULT 'one_time' COMMENT 'one_time/recurring/free/trial',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `original_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `recurring_period` VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'monthly/yearly',
    `trial_days` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `sort` INT UNSIGNED NOT NULL DEFAULT 0,
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_template_billing` (`template_id`, `billing_type`),
    KEY `idx_template_id` (`template_id`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板定价';

-- 2. 模板授权表 (U-2)
CREATE TABLE IF NOT EXISTS `i8j_template_license` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `license_code` VARCHAR(64) NOT NULL DEFAULT '',
    `template_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `member_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `license_type` VARCHAR(20) NOT NULL DEFAULT 'permanent' COMMENT 'permanent/yearly/lifetime',
    `domains` TEXT,
    `expires_at` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_license_code` (`license_code`),
    KEY `idx_template_id` (`template_id`),
    KEY `idx_member_id` (`member_id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板授权';

-- 3. 模板购物车表 (U-2)
CREATE TABLE IF NOT EXISTS `i8j_template_cart` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `member_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `template_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_member_template` (`member_id`, `template_id`),
    KEY `idx_member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板购物车';

-- 4. i8j_template_order 增加字段 (U-2)
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='i8j_template_order' AND COLUMN_NAME='original_amount');
SET @sql = IF(@col=0,'ALTER TABLE `i8j_template_order` ADD COLUMN `original_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT \'原始金额\' AFTER `amount`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='i8j_template_order' AND COLUMN_NAME='discount_amount');
SET @sql = IF(@col=0,'ALTER TABLE `i8j_template_order` ADD COLUMN `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT \'优惠金额\' AFTER `original_amount`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='i8j_template_order' AND COLUMN_NAME='pay_amount');
SET @sql = IF(@col=0,'ALTER TABLE `i8j_template_order` ADD COLUMN `pay_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT \'实付金额\' AFTER `discount_amount`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='i8j_template_order' AND COLUMN_NAME='coupon_code');
SET @sql = IF(@col=0,'ALTER TABLE `i8j_template_order` ADD COLUMN `coupon_code` VARCHAR(50) NOT NULL DEFAULT \'\' COMMENT \'优惠码\' AFTER `pay_amount`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='i8j_template_order' AND COLUMN_NAME='promotion_id');
SET @sql = IF(@col=0,'ALTER TABLE `i8j_template_order` ADD COLUMN `promotion_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT \'促销ID\' AFTER `coupon_code`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='i8j_template_order' AND COLUMN_NAME='license_id');
SET @sql = IF(@col=0,'ALTER TABLE `i8j_template_order` ADD COLUMN `license_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT \'授权ID\' AFTER `promotion_id`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='i8j_template_order' AND COLUMN_NAME='pay_method');
SET @sql = IF(@col=0,'ALTER TABLE `i8j_template_order` ADD COLUMN `pay_method` VARCHAR(20) NOT NULL DEFAULT \'\' COMMENT \'支付方式\' AFTER `license_id`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='i8j_template_order' AND COLUMN_NAME='pay_time');
SET @sql = IF(@col=0,'ALTER TABLE `i8j_template_order` ADD COLUMN `pay_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT \'支付时间\' AFTER `pay_method`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='i8j_template_order' AND COLUMN_NAME='refund_time');
SET @sql = IF(@col=0,'ALTER TABLE `i8j_template_order` ADD COLUMN `refund_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT \'退款时间\' AFTER `pay_time`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='i8j_template_order' AND COLUMN_NAME='refund_reason');
SET @sql = IF(@col=0,'ALTER TABLE `i8j_template_order` ADD COLUMN `refund_reason` VARCHAR(500) NOT NULL DEFAULT \'\' COMMENT \'退款原因\' AFTER `refund_time`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. 菜单项
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(530, 3, 0, '模板定价', '/admin/template_pricing/index', 'template_pricing.*', 'template_pricing', 'bi bi-tag', 91, 1),
(531, 3, 0, '模板订单', '/admin/template_order_admin/index', 'template_order_admin.*', 'template_order_admin', 'bi bi-receipt', 92, 1);

-- 6. 系统设置
INSERT IGNORE INTO `i8j_config` (`name`, `value`, `group`) VALUES
('template_store_payment_enabled', '1', 'template'),
('template_store_alipay_enabled', '0', 'template'),
('template_store_license_enabled', '1', 'template');
