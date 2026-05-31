-- ============================================================
-- AI-CMS V2.9.14 数据库变更脚本
-- 主题：体验精修·异步升级
-- 说明：本脚本可重复执行（幂等保护）
-- 执行方式：bin\migrate.bat database\v2.9.14_alter.sql
-- ============================================================

SET NAMES utf8mb4;

-- 前置检查：确保已选择数据库
SET @db = DATABASE();
SELECT CONCAT('当前数据库: ', @db) AS info;

-- ------------------------------------------------------------
-- 新增：AI任务队列表 i8j_ai_task_queue
-- 用于配图生成、批量SEO等异步AI操作的统一任务调度
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_ai_task_queue` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '任务ID',
    `task_type`     VARCHAR(50)     NOT NULL DEFAULT '' COMMENT '任务类型：ai_image_generate / batch_seo_optimize / single_seo_optimize',
    `biz_id`        INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '业务ID：content_id / batch_id 等',
    `biz_key`       VARCHAR(100)    NOT NULL DEFAULT '' COMMENT '业务标识：用于分组，如batch_seo:20260531',
    `payload`       JSON            NULL COMMENT '任务参数（JSON），含prompt/options/extra等',
    `status`        TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态：0=pending, 1=running, 2=completed, 3=failed, 4=paused, 5=cancelled',
    `progress`      TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '进度百分比 0-100',
    `result`        JSON            NULL COMMENT '执行结果（JSON），成功时包含urls/task_ids等',
    `error_msg`     VARCHAR(500)    NOT NULL DEFAULT '' COMMENT '失败原因',
    `retry_count`   TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '重试次数',
    `max_retries`   TINYINT UNSIGNED NOT NULL DEFAULT 3 COMMENT '最大重试次数',
    `priority`      TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '优先级：0=普通, 1=高',
    `scheduled_at`  INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '计划执行时间（时间戳，0=立即）',
    `started_at`    INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '开始执行时间',
    `completed_at`  INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '完成时间',
    `create_time`   INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time`   INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    INDEX `idx_biz` (`biz_id`, `task_type`),
    INDEX `idx_biz_key` (`biz_key`),
    INDEX `idx_status` (`status`, `priority`, `scheduled_at`),
    INDEX `idx_type_status` (`task_type`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI任务队列表';
