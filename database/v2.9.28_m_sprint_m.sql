-- AI-CMS V2.9.28 Sprint M 数据库变更脚本
-- 主题：模板商店后台管理完善（M-1~M-8）
-- 包含：退款/发票/评价举报/统计聚合/模板包/审核配置/推荐位/结算规则/提现/SEO配置

SET NAMES utf8mb4;
SET @db = DATABASE();

-- ============================================================
-- M-1a: 退款记录表
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_template_refund` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '申请用户ID',
    `reason` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '退款原因',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '退款金额',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态:0待审1通过2拒绝',
    `admin_remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '管理员备注',
    `process_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_order` (`order_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板退款记录表(V2.9.28 M-1)';

-- ============================================================
-- M-1b: 发票申请表
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_template_invoice` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '申请用户ID',
    `title` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '发票抬头',
    `tax_no` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '税号',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '开票金额',
    `email` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '接收邮箱',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态:0待开1已开2拒绝',
    `invoice_no` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '发票号码',
    `invoice_file` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '发票文件路径',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_order` (`order_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板发票申请表(V2.9.28 M-1)';

-- ============================================================
-- M-2: 评价举报表
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_template_review_report` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `review_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '被举报评价ID',
    `reporter_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '举报人ID',
    `reason` VARCHAR(300) NOT NULL DEFAULT '' COMMENT '举报原因',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态:0待处理1已通过(隐藏)2已驳回',
    `admin_remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '处理备注',
    `process_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_review` (`review_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板评价举报表(V2.9.28 M-2)';

-- M-2: template_review 表增加 reply 和 reply_time 字段（幂等）
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='i8j_template_review' AND COLUMN_NAME='reply');
SET @sql = IF(@col=0,'ALTER TABLE `i8j_template_review` ADD COLUMN `reply` TEXT COMMENT \'管理员回复\' AFTER `content`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='i8j_template_review' AND COLUMN_NAME='reply_time');
SET @sql = IF(@col=0,'ALTER TABLE `i8j_template_review` ADD COLUMN `reply_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT \'回复时间\' AFTER `reply`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- M-3: 模板日常统计聚合表（如不存在则创建，已有则跳过）
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_template_daily_stats` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `stats_date` DATE NOT NULL COMMENT '统计日期',
    `template_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '模板ID(0=全站汇总)',
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '浏览数',
    `download_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '下载数',
    `order_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单数',
    `revenue` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '收入',
    `refund_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款数',
    `refund_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '退款金额',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_date_template` (`stats_date`, `template_id`),
    KEY `idx_date` (`stats_date`),
    KEY `idx_template` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板日常统计聚合表(V2.9.28 M-3)';

-- ============================================================
-- M-4: 模板包组合表 + 模板包关联表
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_template_pack` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '包名称',
    `description` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '包描述',
    `cover` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '封面图',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '打包价格',
    `original_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '原价合计',
    `sort` INT UNSIGNED NOT NULL DEFAULT 99 COMMENT '排序',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板包组合表(V2.9.28 M-4)';

CREATE TABLE IF NOT EXISTS `i8j_template_pack_item` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `pack_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '包ID',
    `template_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '模板ID',
    `sort` INT UNSIGNED NOT NULL DEFAULT 99 COMMENT '排序',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pack_template` (`pack_id`, `template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板包关联表(V2.9.28 M-4)';

-- ============================================================
-- M-5: 审核配置表
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_template_audit_config` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `template_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '模板ID(0=全局默认)',
    `audit_level` TINYINT NOT NULL DEFAULT 2 COMMENT '审核层级:1单级2两级3三级',
    `first_reviewer_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '初审人ID',
    `final_reviewer_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '终审人ID',
    `need_file_diff` TINYINT NOT NULL DEFAULT 1 COMMENT '是否需要版本对比',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_template` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审核配置表(V2.9.28 M-5)';

-- M-5: 驳回原因模板表（如不存在则创建）
CREATE TABLE IF NOT EXISTS `i8j_template_reject_reason` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '分类(code/design/safety/other)',
    `reason` VARCHAR(300) NOT NULL DEFAULT '' COMMENT '驳回原因',
    `sort` INT UNSIGNED NOT NULL DEFAULT 99 COMMENT '排序',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='驳回原因模板表(V2.9.28 M-5)';

-- M-5: 初始化默认驳回原因（幂等，兼容V2.9.26已有表的created_at字段）
INSERT IGNORE INTO `i8j_template_reject_reason` (`category`, `reason`, `sort`, `status`) VALUES
('code', '代码存在语法错误，无法通过编译', 1, 1),
('code', '代码不符合PHP编码规范(PSR-12)', 2, 1),
('code', '存在硬编码的数据库连接信息', 3, 1),
('design', '页面布局在移动端显示异常', 1, 1),
('design', '颜色对比度不符合无障碍标准', 2, 1),
('design', '页面加载速度过慢(>3秒)', 3, 1),
('safety', '存在SQL注入风险', 1, 1),
('safety', '存在XSS跨站脚本风险', 2, 1),
('safety', '文件上传缺少安全校验', 3, 1),
('other', '模板描述与实际功能不符', 1, 1),
('other', '缺少使用文档或README', 2, 1),
('other', '与其他已上架模板重复度过高', 3, 1);

-- ============================================================
-- M-6: 推荐位定义表 + 推荐位模板关联表
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_template_recommend_position` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '推荐位名称',
    `code` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '标识(home_banner/home_featured/home_hot/guess_like)',
    `type` TINYINT NOT NULL DEFAULT 1 COMMENT '类型:1人工2规则3AI',
    `max_count` INT UNSIGNED NOT NULL DEFAULT 10 COMMENT '最大展示数',
    `config` JSON COMMENT '规则配置(JSON)',
    `sort` INT UNSIGNED NOT NULL DEFAULT 99 COMMENT '排序',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推荐位定义表(V2.9.28 M-6)';

CREATE TABLE IF NOT EXISTS `i8j_template_recommend_item` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `position_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '推荐位ID',
    `template_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '模板ID',
    `sort` INT UNSIGNED NOT NULL DEFAULT 99 COMMENT '排序',
    `start_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '生效时间',
    `end_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '失效时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_position_template` (`position_id`, `template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推荐位模板关联表(V2.9.28 M-6)';

-- M-6: 初始化默认推荐位
INSERT IGNORE INTO `i8j_template_recommend_position` (`name`, `code`, `type`, `max_count`, `config`, `sort`, `status`, `create_time`, `update_time`) VALUES
('首页轮播', 'home_banner', 1, 5, '{"desc":"首页顶部轮播展示"}', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('精品推荐', 'home_featured', 1, 10, '{"desc":"首页精品推荐区域"}', 2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('热门排行', 'home_hot', 2, 10, '{"rule":"order_by","field":"install_count","desc":true}', 3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('猜你喜欢', 'guess_like', 3, 10, '{"desc":"基于用户行为的AI推荐(预留)"}', 4, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ============================================================
-- M-7: 结算规则表 + 提现申请表
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_template_settlement_rule` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `developer_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '开发者用户ID',
    `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 30.00 COMMENT '平台抽成比例(%)',
    `min_withdraw` DECIMAL(10,2) NOT NULL DEFAULT 100.00 COMMENT '最低提现金额',
    `settle_cycle` TINYINT NOT NULL DEFAULT 1 COMMENT '结算周期:1月结2季结3年结',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_developer` (`developer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='结算规则表(V2.9.28 M-7)';

CREATE TABLE IF NOT EXISTS `i8j_template_withdraw` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `developer_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '开发者ID',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '提现金额',
    `fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '手续费',
    `actual_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '实际到账金额',
    `account_info` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '收款账户信息(JSON)',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态:0待审1打款中2已完成3已驳回',
    `admin_remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '管理员备注',
    `process_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
    `confirm_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '到账确认时间',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_developer` (`developer_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='提现申请表(V2.9.28 M-7)';

-- ============================================================
-- M-8: template_store 表增加 SEO 字段（幂等）
-- ============================================================
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='i8j_template_store' AND COLUMN_NAME='seo_title');
SET @sql = IF(@col=0,'ALTER TABLE `i8j_template_store` ADD COLUMN `seo_title` VARCHAR(200) NOT NULL DEFAULT \'\' COMMENT \'SEO标题\' AFTER `description`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='i8j_template_store' AND COLUMN_NAME='seo_description');
SET @sql = IF(@col=0,'ALTER TABLE `i8j_template_store` ADD COLUMN `seo_description` VARCHAR(500) NOT NULL DEFAULT \'\' COMMENT \'SEO描述\' AFTER `seo_title`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='i8j_template_store' AND COLUMN_NAME='seo_keywords');
SET @sql = IF(@col=0,'ALTER TABLE `i8j_template_store` ADD COLUMN `seo_keywords` VARCHAR(200) NOT NULL DEFAULT \'\' COMMENT \'SEO关键词\' AFTER `seo_description`','SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 菜单项（幂等插入）
-- ============================================================
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(540, 3, 0, '订单管理', '/admin/template_order_admin/index', 'template_order_admin.*', 'template_order_admin', 'bi bi-receipt', 90, 1),
(541, 3, 0, '评价管理', '/admin/template_review_admin/index', 'template_review_admin.*', 'template_review_admin', 'bi bi-star', 93, 1),
(542, 3, 0, '统计看板', '/admin/template_store_stats/index', 'template_store_stats.*', 'template_store_stats', 'bi bi-bar-chart', 94, 1),
(543, 3, 0, '模板包管理', '/admin/template_pack/index', 'template_pack.*', 'template_pack', 'bi bi-box-seam', 95, 1),
(544, 3, 0, '审核工作流', '/admin/template_audit_workflow/index', 'template_audit_workflow.*', 'template_audit_workflow', 'bi bi-shield-check', 96, 1),
(545, 3, 0, '推荐位管理', '/admin/template_recommend_position/index', 'template_recommend_position.*', 'template_recommend_position', 'bi bi-megaphone', 97, 1),
(546, 3, 0, '结算管理', '/admin/template_settlement_admin/index', 'template_settlement_admin.*', 'template_settlement_admin', 'bi bi-cash-coin', 98, 1),
(547, 3, 0, '商店SEO', '/admin/template_store_seo/index', 'template_store_seo.*', 'template_store_seo', 'bi bi-search', 99, 1);

-- ============================================================
-- 系统配置（幂等）
-- ============================================================
INSERT IGNORE INTO `i8j_config` (`name`, `value`, `group`) VALUES
('template_store_refund_enabled', '1', 'template'),
('template_store_refund_days', '7', 'template'),
('template_store_invoice_enabled', '1', 'template'),
('template_store_commission_rate', '30', 'template'),
('template_store_min_withdraw', '100', 'template'),
('template_store_settle_cycle', '1', 'template'),
('template_store_seo_title', '模板商店 - 八界AI-CMS', 'template'),
('template_store_seo_description', '专业CMS模板商店，提供海量优质网站模板', 'template'),
('template_store_seo_keywords', 'CMS模板,网站模板,响应式模板', 'template');
