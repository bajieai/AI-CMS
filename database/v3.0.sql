-- ============================================================
-- AI-CMS V3.0 Phase 1 数据库迁移脚本（幂等执行）
-- 适用版本：V2.9.4 / V2.9.5 → V3.0 Phase 1
-- 执行方式：后台 系统设置 → 数据库 → 执行SQL
--           或命令行：php think migrate:run
-- 特性：完全幂等，重复执行不会报错
-- ============================================================

SET NAMES utf8mb4;

-- 获取当前数据库名
SET @dbname = DATABASE();
SET @tbl_prefix = 'i8j_';

-- ============================================================
-- 1. paid_order.type 字段扩展（兼容打赏/下载）
-- ============================================================

-- 1.1 检查字段是否存在并修改
SET @exists_type_col = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = CONCAT(@tbl_prefix, 'paid_order')
    AND COLUMN_NAME = 'type'
);

SET @sql_modify_type = IF(@exists_type_col = 0,
  CONCAT('ALTER TABLE `', @tbl_prefix, 'paid_order` ADD COLUMN `type` varchar(20) DEFAULT \'content_purchase\' COMMENT \'类型: content_purchase内容购买, reward打赏, download下载付费\''),
  CONCAT('ALTER TABLE `', @tbl_prefix, 'paid_order` MODIFY `type` varchar(20) DEFAULT \'content_purchase\' COMMENT \'类型: content_purchase内容购买, reward打赏, download下载付费\'')
);
PREPARE stmt_modify_type FROM @sql_modify_type;
EXECUTE stmt_modify_type;
DEALLOCATE PREPARE stmt_modify_type;

-- 1.2 旧数据兼容升级（content → content_purchase）
UPDATE `i8j_paid_order` SET `type` = 'content_purchase' WHERE `type` = 'content' OR `type` = '' OR `type` IS NULL;

-- 1.3 确保 status=0 的未支付订单有正确的 type 默认值
UPDATE `i8j_paid_order` SET `type` = 'content_purchase' WHERE `type` IS NULL;

-- ============================================================
-- 2. notification 表 type 字段长度检查（确保支持新枚举值）
-- ============================================================

SET @exists_notif_type = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = CONCAT(@tbl_prefix, 'notification')
    AND COLUMN_NAME = 'type'
    AND CHARACTER_MAXIMUM_LENGTH >= 20
);

SET @sql_notif_type = IF(@exists_notif_type = 0,
  CONCAT('ALTER TABLE `', @tbl_prefix, 'notification` MODIFY `type` varchar(20) NOT NULL DEFAULT \'system\' COMMENT \'类型: system/review/publish/title/comment_reply/content_approve/content_reject/reward_receive\''),
  'SELECT "notification.type already varchar(20+)" AS info'
);
PREPARE stmt_notif_type FROM @sql_notif_type;
EXECUTE stmt_notif_type;
DEALLOCATE PREPARE stmt_notif_type;

-- ============================================================
-- 3. config 配置项更新与新增
-- ============================================================

-- 3.1 版本号更新为 3.0.0
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`)
VALUES ('app_version', '3.0.0', 'system', 'text', '当前系统版本号', 0)
ON DUPLICATE KEY UPDATE `value` = '3.0.0', `remark` = '当前系统版本号';

-- 3.2 CSP 相关配置（可选，供后台管理界面使用）
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`)
VALUES ('csp_mode', 'report_only', 'security', 'select', 'CSP模式: report_only仅报告 / enforce强制阻断', 10)
ON DUPLICATE KEY UPDATE `value` = COALESCE(`value`, 'report_only'), `remark` = 'CSP模式: report_only仅报告 / enforce强制阻断';

-- 3.3 模板共享片段目录标记
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`)
VALUES ('template_shared_enabled', '1', 'template', 'switch', '是否启用模板共享片段(template/themes/shared/)', 20)
ON DUPLICATE KEY UPDATE `value` = COALESCE(`value`, '1'), `remark` = '是否启用模板共享片段(template/themes/shared/)';

-- ============================================================
-- 4. 清理已废弃配置项（如有）
-- ============================================================

-- V3.0 无废弃配置项，保留此区域供后续版本使用

-- ============================================================
-- 5. 索引优化
-- ============================================================

-- 5.1 paid_order 按 type 查询的索引（打赏记录/购买记录筛选）
SET @exists_idx_type = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = CONCAT(@tbl_prefix, 'paid_order')
    AND INDEX_NAME = 'idx_type_status'
);

SET @sql_add_idx = IF(@exists_idx_type = 0,
  CONCAT('CREATE INDEX `idx_type_status` ON `', @tbl_prefix, 'paid_order`(`type`, `status`, `create_time`)'),
  'SELECT "idx_type_status already exists" AS info'
);
PREPARE stmt_add_idx FROM @sql_add_idx;
EXECUTE stmt_add_idx;
DEALLOCATE PREPARE stmt_add_idx;

-- ============================================================
-- 6. 数据一致性校验与修复
-- ============================================================

-- 6.1 修复 paid_order 中 member_id 为 0 但 status=1 的异常数据（如有）
-- UPDATE `i8j_paid_order` SET `status` = -1 WHERE `member_id` = 0 AND `status` = 1;

-- ============================================================
-- 7. 版本标记
-- ============================================================

SELECT CONCAT('数据库升级完成，当前版本: V3.0 Phase 1 (', NOW(), ')') AS `升级结果`;
