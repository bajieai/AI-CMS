-- AI-CMS V2.9.26 P-5/P-6: 质量标签 + 版本记录
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `i8j_template_quality_tag`;
CREATE TABLE `i8j_template_quality_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL DEFAULT 0,
  `tag_name` varchar(50) NOT NULL DEFAULT '',
  `tag_type` varchar(20) NOT NULL DEFAULT 'auto',
  `score` decimal(3,1) NOT NULL DEFAULT 0.0,
  `weight` int(11) NOT NULL DEFAULT 100,
  `auditor_id` int(11) NOT NULL DEFAULT 0,
  `auditor_name` varchar(50) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_tag_type` (`tag_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板质量标签';

DROP TABLE IF EXISTS `i8j_template_version_record`;
CREATE TABLE `i8j_template_version_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL DEFAULT 0,
  `version` varchar(20) NOT NULL DEFAULT '',
  `changelog` text NULL,
  `file_snapshot` longtext NULL,
  `file_diff` longtext NULL,
  `grayscale_percent` tinyint(3) NOT NULL DEFAULT 100,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `operator_id` int(11) NOT NULL DEFAULT 0,
  `operator_name` varchar(50) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_version` (`version`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板版本记录';

SET FOREIGN_KEY_CHECKS = 1;
