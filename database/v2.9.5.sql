-- ============================================================
-- V2.9.5 数据库迁移脚本（幂等执行）
-- 执行方式：在后台 系统设置 → 数据库 → 执行SQL 中逐条运行
-- 或命令行：php think migrate:run（如已配置迁移系统）
-- ============================================================

-- 1. 扩展 paid_order.type 枚举值，默认改为 content_purchase 兼容新逻辑
ALTER TABLE `{prefix}paid_order` MODIFY `type` varchar(20) DEFAULT 'content_purchase' COMMENT '类型: content_purchase内容购买, reward打赏, download下载付费';

-- 2. 将旧数据 type='content' 升级为 'content_purchase'
UPDATE `{prefix}paid_order` SET `type` = 'content_purchase' WHERE `type` = 'content';

-- 3. 确保 member_purchased 列表视图能正确显示（如有自定义视图可在此处维护）
