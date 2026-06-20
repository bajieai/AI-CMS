-- ============================================================
-- AI-CMS V2.9.26 Sprint P-1: AI模板智能推荐系统
-- 新增表: i8j_template_recommend_rule (推荐规则)
-- 新增表: i8j_template_recommend_stats (推荐效果统计)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------
-- 表1: i8j_template_recommend_rule — 推荐规则表
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `i8j_template_recommend_rule`;
CREATE TABLE `i8j_template_recommend_rule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '规则名称',
  `rule_type` varchar(30) NOT NULL DEFAULT 'manual' COMMENT '规则类型: manual=手动置顶, ai=AI推荐, category=分类热门, festival=节日特推, new_release=新品首发',
  `template_ids` text NULL COMMENT '手动指定的模板ID列表(JSON数组)',
  `category_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联分类ID(分类热门时有效)',
  `priority` int(11) NOT NULL DEFAULT 10 COMMENT '优先级(数字越大越靠前)',
  `ab_group` varchar(10) NOT NULL DEFAULT 'A' COMMENT 'A/B测试分组: A/B/ALL',
  `conditions` text NULL COMMENT '触发条件(JSON: 用户标签/时间段/设备等)',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态: 0=禁用 1=启用',
  `start_time` datetime NULL COMMENT '生效开始时间',
  `end_time` datetime NULL COMMENT '生效结束时间',
  `sort` int(11) NOT NULL DEFAULT 100 COMMENT '排序',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rule_type` (`rule_type`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category_id`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板推荐规则表';

-- -----------------------------------------------------------
-- 表2: i8j_template_recommend_stats — 推荐效果统计表
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `i8j_template_recommend_stats`;
CREATE TABLE `i8j_template_recommend_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL DEFAULT 0 COMMENT '模板ID',
  `rule_id` int(11) NOT NULL DEFAULT 0 COMMENT '触发的规则ID',
  `position` varchar(50) NOT NULL DEFAULT '' COMMENT '推荐位: home/sidebar/detail/search',
  `ab_group` varchar(10) NOT NULL DEFAULT 'A' COMMENT 'A/B测试分组',
  `impression_count` int(11) NOT NULL DEFAULT 0 COMMENT '曝光次数',
  `click_count` int(11) NOT NULL DEFAULT 0 COMMENT '点击次数',
  `install_count` int(11) NOT NULL DEFAULT 0 COMMENT '安装次数',
  `stat_date` date NOT NULL COMMENT '统计日期',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_rule_pos_date` (`template_id`, `rule_id`, `position`, `stat_date`),
  KEY `idx_template` (`template_id`),
  KEY `idx_stat_date` (`stat_date`),
  KEY `idx_position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板推荐效果统计表';

SET FOREIGN_KEY_CHECKS = 1;
