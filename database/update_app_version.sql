-- ============================================================
-- 更新 app_version 配置为 V2.9.9
-- 此脚本幂等，可重复执行
-- ============================================================
INSERT INTO `i8j_config` (`name`, `value`, `group`, `type`, `remark`, `sort`) VALUES
('app_version', '2.9.9', 'system', 'text', '当前系统版本号', 0)
ON DUPLICATE KEY UPDATE `value` = '2.9.9';

-- 同时更新 config 缓存（可选）
-- 系统会在下次请求时自动刷新缓存