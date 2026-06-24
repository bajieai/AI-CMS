-- ============================================================
-- AI-CMS V2.9.29 Sprint D 数据库变更脚本
-- 主题：开发者生态启动
-- 变更：5新表 (developer + webhook_endpoint + webhook_log + api_key + api_log)
-- ============================================================

SET NAMES utf8mb4;
SET @db = DATABASE();

-- ------------------------------------------------------------
-- 1. 开发者信息表 (D-1)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_developer` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `real_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '真实姓名',
    `contact_phone` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '联系电话',
    `contact_email` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '联系邮箱',
    `introduction` TEXT COMMENT '开发经验介绍',
    `level` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '认证等级:1初级2认证3专业',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态:0待审1通过2驳回3禁用',
    `audit_remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '审核备注',
    `total_templates` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '已发布模板数',
    `total_revenue` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '累计收益',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user` (`user_id`),
    KEY `idx_level` (`level`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='开发者信息表';

-- ------------------------------------------------------------
-- 2. Webhook端点表 (D-4)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_webhook_endpoint` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '端点名称',
    `url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '推送URL',
    `secret` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '签名密钥',
    `events` TEXT NOT NULL COMMENT '监听事件列表(JSON数组)',
    `is_active` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否激活',
    `retry_count` TINYINT UNSIGNED NOT NULL DEFAULT 3 COMMENT '最大重试次数',
    `timeout_seconds` INT UNSIGNED NOT NULL DEFAULT 10 COMMENT '超时时间(秒)',
    `fail_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '连续失败次数',
    `last_sent_at` INT UNSIGNED DEFAULT NULL COMMENT '最后推送时间',
    `last_status` TINYINT DEFAULT NULL COMMENT '最后状态:1成功0失败',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Webhook端点表';

-- ------------------------------------------------------------
-- 3. Webhook推送日志表 (D-4)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_webhook_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `endpoint_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '端点ID',
    `event_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '事件名称',
    `payload` TEXT NOT NULL COMMENT '推送数据(JSON)',
    `response_code` INT NOT NULL DEFAULT 0 COMMENT '响应状态码',
    `response_body` TEXT COMMENT '响应内容',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态:0待推送1推送中2成功3失败',
    `attempt` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '第几次重试',
    `duration_ms` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '耗时(毫秒)',
    `error_message` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '错误消息',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_endpoint` (`endpoint_id`),
    KEY `idx_status` (`status`),
    KEY `idx_event` (`event_name`),
    KEY `idx_create` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Webhook推送日志表';

-- ------------------------------------------------------------
-- 4. API密钥表 (D-5)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_api_key` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '密钥名称',
    `api_key` VARCHAR(64) NOT NULL DEFAULT '' COMMENT 'API密钥',
    `api_secret` VARCHAR(64) NOT NULL DEFAULT '' COMMENT 'API密钥密钥',
    `scopes` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '权限范围(JSON数组)',
    `ip_whitelist` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'IP白名单(逗号分隔)',
    `rate_limit` INT UNSIGNED NOT NULL DEFAULT 100 COMMENT '每分钟限制次数',
    `is_active` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否启用',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_api_key` (`api_key`),
    KEY `idx_status` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='API密钥表';

-- ------------------------------------------------------------
-- 5. API调用日志表 (D-5)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_api_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `api_key_id` INT UNSIGNED DEFAULT NULL COMMENT '密钥ID',
    `endpoint` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '接口路径',
    `method` VARCHAR(10) NOT NULL DEFAULT 'GET' COMMENT '请求方法',
    `ip_address` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '请求IP',
    `status_code` INT NOT NULL DEFAULT 0 COMMENT '状态码',
    `duration_ms` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '耗时(毫秒)',
    `user_agent` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'UA标识',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_key` (`api_key_id`),
    KEY `idx_endpoint` (`endpoint`),
    KEY `idx_status` (`status_code`),
    KEY `idx_create` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='API调用日志表';
