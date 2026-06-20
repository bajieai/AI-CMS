-- AI-CMS V2.9.26 R-1/R-2: AI content log + translation memory + glossary
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `i8j_ai_content_log`;
CREATE TABLE `i8j_ai_content_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `content_id` int(11) NOT NULL DEFAULT 0,
  `mode` varchar(20) NOT NULL DEFAULT '',
  `style` varchar(20) NOT NULL DEFAULT '',
  `input_text` text NULL,
  `output_text` text NULL,
  `provider` varchar(30) NOT NULL DEFAULT '',
  `tokens_used` int(11) NOT NULL DEFAULT 0,
  `elapsed_ms` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_mode` (`mode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI content log';

DROP TABLE IF EXISTS `i8j_ai_translation_cache`;
CREATE TABLE `i8j_ai_translation_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_text_hash` varchar(64) NOT NULL DEFAULT '',
  `source_text` text NULL,
  `source_lang` varchar(10) NOT NULL DEFAULT 'zh-CN',
  `target_lang` varchar(10) NOT NULL DEFAULT 'en',
  `translated_text` text NULL,
  `provider` varchar(30) NOT NULL DEFAULT '',
  `quality_score` decimal(3,1) NOT NULL DEFAULT 0.0,
  `hit_count` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_hash_lang` (`source_text_hash`, `source_lang`, `target_lang`),
  KEY `idx_langs` (`source_lang`, `target_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI translation memory';

DROP TABLE IF EXISTS `i8j_ai_translation_glossary`;
CREATE TABLE `i8j_ai_translation_glossary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_term` varchar(200) NOT NULL DEFAULT '',
  `target_term` varchar(200) NOT NULL DEFAULT '',
  `source_lang` varchar(10) NOT NULL DEFAULT 'zh-CN',
  `target_lang` varchar(10) NOT NULL DEFAULT 'en',
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_langs` (`source_lang`, `target_lang`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI translation glossary';

SET FOREIGN_KEY_CHECKS = 1;
