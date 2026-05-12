-- ============================================================
-- AI-CMS V2.9.5 数据库升级脚本
-- 日期: 2026-05-12
-- ============================================================

-- ----------------------------------------------------------
-- 1. i8j_paid_order 新增 payment_order_no 字段（双订单桥接）
--    注：如字段已存在则忽略报错
-- ----------------------------------------------------------
-- ALTER TABLE `i8j_paid_order`
--     ADD COLUMN `payment_order_no` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '关联PaymentService订单号(i8j_orders.order_no)，真钱支付时填充' AFTER `order_sn`,
--     ADD INDEX `idx_payment_order_no` (`payment_order_no`);
-- 字段与索引已由代码迁移自动添加，此处跳过

-- ----------------------------------------------------------
-- 2. i8j_orders 订单来源扩展（覆盖3个官方场景）
--    source 字段已有: plugin/template/member/content
--    无需修改，content 已支持付费文章/章节
-- ----------------------------------------------------------

-- ----------------------------------------------------------
-- 3. 系统配置项扩展
-- ----------------------------------------------------------
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `options`, `sort`, `remark`) VALUES
('csp_mode', 'report_only', 'security', 'select', 'report_only=仅报告,enforce=强制拦截', 1, 'CSP策略模式'),
('csrf_front_enabled', '1', 'security', 'switch', '', 2, '启用后前台写操作需携带Token'),
('cache_warm_enabled', '1', 'performance', 'switch', '', 3, '内容变更后自动清除相关缓存'),
('xss_log_enabled', '1', 'security', 'switch', '', 4, '响应中包含潜在XSS特征时记录日志')
ON DUPLICATE KEY UPDATE `remark` = VALUES(`remark`), `options` = VALUES(`options`);

-- ----------------------------------------------------------
-- 4. 版本标记
-- ----------------------------------------------------------
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `options`, `sort`, `remark`) VALUES
('version', '2.9.5', 'system', 'text', '', 0, 'AI-CMS版本号')
ON DUPLICATE KEY UPDATE `value` = '2.9.5';

-- ----------------------------------------------------------
-- 5. 慢查询建议索引（幂等操作，重复执行安全）
-- ----------------------------------------------------------
DROP PROCEDURE IF EXISTS `add_index_if_not_exists`;
DELIMITER $$
CREATE PROCEDURE `add_index_if_not_exists`(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_columns VARCHAR(255)
)
BEGIN
    DECLARE idx_count INT;
    SELECT COUNT(*) INTO idx_count
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table
      AND INDEX_NAME = p_index;
    IF idx_count = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_columns, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL `add_index_if_not_exists`('i8j_content', 'idx_status_cate_sort', '`status`, `cate_id`, `sort`');
CALL `add_index_if_not_exists`('i8j_paid_order', 'idx_member_status', '`member_id`, `status`');
CALL `add_index_if_not_exists`('i8j_member_downgrade_log', 'idx_user_time', '`user_id`, `create_time`');

DROP PROCEDURE IF EXISTS `add_index_if_not_exists`;
