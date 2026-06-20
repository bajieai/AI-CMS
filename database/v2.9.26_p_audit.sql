-- AI-CMS V2.9.26 Sprint P-3: 审核流程增强
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `i8j_template_audit_log`;
CREATE TABLE `i8j_template_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL DEFAULT 0,
  `auditor_id` int(11) NOT NULL DEFAULT 0,
  `auditor_name` varchar(50) NOT NULL DEFAULT '',
  `action` varchar(20) NOT NULL DEFAULT '',
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `reason` text NULL,
  `reason_id` int(11) NOT NULL DEFAULT 0,
  `prev_status` varchar(20) NOT NULL DEFAULT '',
  `new_status` varchar(20) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_auditor` (`auditor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板审核日志';

DROP TABLE IF EXISTS `i8j_template_reject_reason`;
CREATE TABLE `i8j_template_reject_reason` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reason` varchar(500) NOT NULL DEFAULT '',
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `sort` int(11) NOT NULL DEFAULT 100,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板驳回理由模板';

INSERT INTO `i8j_template_reject_reason` (`reason`, `category`, `sort`) VALUES
('模板设计不完整，缺少必要的页面文件', 'quality', 10),
('模板存在明显的兼容性问题', 'quality', 20),
('模板代码质量不达标，存在安全风险', 'quality', 30),
('模板截图与实际效果不符', 'quality', 40),
('模板涉及版权问题，请提供授权证明', 'copyright', 50),
('模板描述信息不完整', 'general', 60),
('模板分类选择不正确', 'general', 70),
('模板定价不合理', 'general', 80),
('模板存在重复内容', 'quality', 90),
('其他原因（请在审核意见中说明）', 'other', 100);

SET FOREIGN_KEY_CHECKS = 1;
