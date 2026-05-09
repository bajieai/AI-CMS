-- ============================================
-- AI-CMS V2.9.1 数据库迁移脚本
-- 执行方式: docker exec aicms_mysql mysql -uroot -proot123456 aicms_v2 -e "source /var/sql/v2.9.1.sql"
-- 特性: 幂等执行(IF NOT EXISTS)，可重复运行不报错
-- 编码警告: 此文件包含中文，执行前必须 SET NAMES utf8mb4，否则中文将双倍编码产生乱码!
-- ============================================
SET NAMES utf8mb4;

-- --------------------------------------------
-- M14a: 配图异步任务表
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_image_task` (
  `id` int UNSIGNED AUTO_INCREMENT,
  `task_id` varchar(64) NOT NULL COMMENT '外部任务ID(FLUX返回的id)',
  `provider` varchar(20) NOT NULL DEFAULT 'flux' COMMENT 'Provider标识(flux/dalle/tongyi_wanxiang)',
  `poll_url` varchar(500) DEFAULT '' COMMENT '轮询URL',
  `status` tinyint NOT NULL DEFAULT 0 COMMENT '0pending/1processing/2completed/3failed',
  `prompt` varchar(500) DEFAULT '' COMMENT '生成提示词',
  `result` json DEFAULT NULL COMMENT '生成结果JSON',
  `attempts` tinyint DEFAULT 0 COMMENT '轮询尝试次数',
  `max_attempts` tinyint DEFAULT 30 COMMENT '最大尝试次数(30次≈90秒超时)',
  `related_type` varchar(30) DEFAULT '' COMMENT '关联类型(content/batch)',
  `related_id` int UNSIGNED DEFAULT 0 COMMENT '关联ID',
  `error_msg` varchar(500) DEFAULT '' COMMENT '错误信息',
  `retry_count` tinyint DEFAULT 0 COMMENT '失败重试次数(最多3次)',
  `local_path` varchar(500) DEFAULT '' COMMENT '本地存储路径(M17 AI配图URL本地化)',
  `create_time` int UNSIGNED DEFAULT 0,
  `update_time` int UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_task_id` (`task_id`),
  KEY `idx_status` (`status`),
  KEY `idx_related` (`related_type`, `related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='配图异步任务表';

-- --------------------------------------------
-- M9: AI分析报告表
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_ai_report` (
  `id` int UNSIGNED AUTO_INCREMENT,
  `type` varchar(20) NOT NULL COMMENT 'daily/weekly/monthly/manual',
  `title` varchar(200) NOT NULL COMMENT '报告标题',
  `period_start` int UNSIGNED NOT NULL COMMENT '统计开始时间戳',
  `period_end` int UNSIGNED NOT NULL COMMENT '统计结束时间戳',
  `raw_data` json DEFAULT NULL COMMENT '原始数据快照',
  `summary` text DEFAULT NULL COMMENT '一句话总结',
  `findings` json DEFAULT NULL COMMENT '关键发现列表',
  `anomalies` json DEFAULT NULL COMMENT '异常检测列表',
  `recommendations` json DEFAULT NULL COMMENT '建议列表',
  `sections` json DEFAULT NULL COMMENT '详细章节',
  `status` tinyint DEFAULT 0 COMMENT '0生成中/1已完成/2发布/3失败',
  `create_time` int UNSIGNED DEFAULT 0,
  `update_time` int UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_period` (`period_start`, `period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI分析报告';

-- --------------------------------------------
-- M15b: 评价回复独立表
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_rating_reply` (
  `id` int UNSIGNED AUTO_INCREMENT,
  `rating_id` int UNSIGNED NOT NULL COMMENT '关联评价ID',
  `user_id` int UNSIGNED DEFAULT 0 COMMENT '回复用户(管理员ID)',
  `member_id` int UNSIGNED DEFAULT 0 COMMENT '回复会员ID',
  `content` text NOT NULL COMMENT '回复内容',
  `create_time` int UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_rating` (`rating_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评价回复记录';

-- --------------------------------------------
-- M16a: 免邮券虚拟类型配置
-- --------------------------------------------
INSERT IGNORE INTO `i8j_config` (`name`, `value`, `type`, `group`, `options`, `sort`, `remark`)
VALUES
('shipping_coupon_type', 'free_shipping', 'text', 'coupon', '用于CouponTemplate识别免邮券', 50, '免邮券类型标识'),
('shipping_free_threshold', '0', 'number', 'shipping', '订单金额超过此值免邮，0表示全部免邮', 10, '免邮阈值(元)'),
('shipping_default_fee', '10', 'number', 'shipping', '未触发免邮时的默认运费', 20, '默认运费(元)');

-- --------------------------------------------
-- V2.9.1: 多语言开关配置
-- --------------------------------------------
INSERT IGNORE INTO `i8j_config` (`name`, `value`, `type`, `group`, `options`, `sort`, `remark`)
VALUES
('language_switcher_enabled', '1', 'switch', 'basic', '关闭后前台顶部不显示语言切换下拉菜单', 95, '前台显示语言切换器'),
('language_sitewide', '0', 'switch', 'basic', '开启后所有内容按语言隔离，仅显示当前语言的内容', 96, '多语言全站生效');

-- --------------------------------------------
-- M18: 批量内容管理字段扩展(如需要)
-- --------------------------------------------
-- 暂无新增字段，复用content现有status/is_top/is_recommend字段
