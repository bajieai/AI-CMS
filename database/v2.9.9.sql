-- ============================================================
-- V2.9.9 "模力收官" 数据库迁移脚本
-- 幂等DDL：可重复执行不报错
-- ============================================================

-- -----------------------------------------------------------
-- 1. visit_log 新增 session_id 字段（跳出率计算必需）
-- -----------------------------------------------------------
SET @dbname = DATABASE();
SET @tablename = 'i8j_visit_log';
SET @columnname = 'session_id';

SET @sql = CONCAT(
    'ALTER TABLE ', @tablename,
    ' ADD COLUMN session_id VARCHAR(64) NULL DEFAULT NULL AFTER visitor_id,',
    ' ADD INDEX idx_session_id (session_id)'
);

SET @exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
);

SET @sqlExec = IF(@exists = 0, @sql, 'SELECT "session_id already exists, skipping" AS msg');
PREPARE stmt FROM @sqlExec;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------
-- 2. 确认 visit_time 索引存在（加速时间范围查询）
-- -----------------------------------------------------------
SET @idxExists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'i8j_visit_log'
      AND INDEX_NAME = 'idx_visit_time'
);
SET @sqlIdx = IF(@idxExists = 0,
    'ALTER TABLE i8j_visit_log ADD INDEX idx_visit_time (visit_time)',
    'SELECT "idx_visit_time already exists, skipping" AS msg'
);
PREPARE stmtIdx FROM @sqlIdx;
EXECUTE stmtIdx;
DEALLOCATE PREPARE stmtIdx;
