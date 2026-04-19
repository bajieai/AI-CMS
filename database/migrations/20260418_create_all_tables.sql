-- AI-CMS 数据库迁移脚本
-- 创建日期: 2026-04-18
-- 数据库: ai_cms
-- 字符集: utf8mb4
-- 排序规则: utf8mb4_unicode_ci

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+08:00';

-- ----------------------------
-- 1. 用户表 (i8j_aicms_users)
-- ----------------------------
DROP TABLE IF EXISTS `i8j_aicms_users`;
CREATE TABLE `i8j_aicms_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `email` varchar(100) NOT NULL COMMENT '邮箱',
  `password` varchar(255) NOT NULL COMMENT '密码(bcrypt)',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像URL',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态: 0=禁用, 1=正常',
  `last_login_ip` varchar(45) DEFAULT NULL COMMENT '最后登录IP',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `login_count` int unsigned NOT NULL DEFAULT '0' COMMENT '登录次数',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- ----------------------------
-- 2. 角色表 (i8j_aicms_roles)
-- ----------------------------
DROP TABLE IF EXISTS `i8j_aicms_roles`;
CREATE TABLE `i8j_aicms_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '角色ID',
  `name` varchar(50) NOT NULL COMMENT '角色名称',
  `slug` varchar(50) NOT NULL COMMENT '角色标识',
  `description` varchar(255) DEFAULT NULL COMMENT '角色描述',
  `is_system` tinyint NOT NULL DEFAULT '0' COMMENT '是否系统内置: 0=否, 1=是',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态: 0=禁用, 1=正常',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表';

-- ----------------------------
-- 3. 权限表 (i8j_aicms_permissions)
-- ----------------------------
DROP TABLE IF EXISTS `i8j_aicms_permissions`;
CREATE TABLE `i8j_aicms_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '权限ID',
  `name` varchar(50) NOT NULL COMMENT '权限名称',
  `slug` varchar(100) NOT NULL COMMENT '权限标识',
  `module` varchar(50) NOT NULL COMMENT '所属模块',
  `type` enum('menu','action','data') NOT NULL DEFAULT 'action' COMMENT '权限类型',
  `parent_id` bigint unsigned DEFAULT NULL COMMENT '父级权限ID',
  `path` varchar(255) DEFAULT NULL COMMENT '路由路径',
  `icon` varchar(50) DEFAULT NULL COMMENT '菜单图标',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '排序',
  `description` varchar(255) DEFAULT NULL COMMENT '描述',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态: 0=禁用, 1=正常',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_module` (`module`),
  KEY `idx_type` (`type`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='权限表';

-- ----------------------------
-- 4. 角色权限关联表 (i8j_aicms_role_permissions)
-- ----------------------------
DROP TABLE IF EXISTS `i8j_aicms_role_permissions`;
CREATE TABLE `i8j_aicms_role_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `role_id` bigint unsigned NOT NULL COMMENT '角色ID',
  `permission_id` bigint unsigned NOT NULL COMMENT '权限ID',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_permission` (`role_id`,`permission_id`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_permission_id` (`permission_id`),
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `i8j_aicms_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `i8j_aicms_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色权限关联表';

-- ----------------------------
-- 5. 用户角色关联表 (i8j_aicms_user_roles)
-- ----------------------------
DROP TABLE IF EXISTS `i8j_aicms_user_roles`;
CREATE TABLE `i8j_aicms_user_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `role_id` bigint unsigned NOT NULL COMMENT '角色ID',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_role` (`user_id`,`role_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_role_id` (`role_id`),
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `i8j_aicms_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `i8j_aicms_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户角色关联表';

-- ----------------------------
-- 6. 操作日志表 (i8j_aicms_operation_logs)
-- ----------------------------
DROP TABLE IF EXISTS `i8j_aicms_operation_logs`;
CREATE TABLE `i8j_aicms_operation_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `user_id` bigint unsigned DEFAULT NULL COMMENT '操作用户ID',
  `username` varchar(50) DEFAULT NULL COMMENT '操作用户名',
  `action` varchar(50) NOT NULL COMMENT '操作类型',
  `module` varchar(50) DEFAULT NULL COMMENT '模块',
  `description` varchar(255) DEFAULT NULL COMMENT '操作描述',
  `request_method` varchar(10) DEFAULT NULL COMMENT '请求方法',
  `request_url` varchar(500) DEFAULT NULL COMMENT '请求URL',
  `request_ip` varchar(45) DEFAULT NULL COMMENT '请求IP',
  `request_params` text COMMENT '请求参数',
  `response_code` int DEFAULT NULL COMMENT '响应码',
  `response_data` longtext COMMENT '响应数据',
  `user_agent` varchar(500) DEFAULT NULL COMMENT 'User-Agent',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_module` (`module`),
  KEY `idx_request_ip` (`request_ip`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表';

-- ----------------------------
-- 7. 分类表 (i8j_aicms_categories)
-- ----------------------------
DROP TABLE IF EXISTS `i8j_aicms_categories`;
CREATE TABLE `i8j_aicms_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `parent_id` bigint unsigned DEFAULT NULL COMMENT '父级分类ID',
  `name` varchar(50) NOT NULL COMMENT '分类名称',
  `slug` varchar(50) NOT NULL COMMENT '分类别名',
  `description` varchar(255) DEFAULT NULL COMMENT '分类描述',
  `image` varchar(255) DEFAULT NULL COMMENT '分类图片',
  `template` varchar(50) DEFAULT NULL COMMENT '模板',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '排序',
  `seo_title` varchar(100) DEFAULT NULL COMMENT 'SEO标题',
  `seo_keywords` varchar(255) DEFAULT NULL COMMENT 'SEO关键词',
  `seo_description` varchar(500) DEFAULT NULL COMMENT 'SEO描述',
  `content_count` int unsigned NOT NULL DEFAULT '0' COMMENT '信息数量',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态: 0=禁用, 1=正常',
  `level` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '层级',
  `path` varchar(255) DEFAULT NULL COMMENT '层级路径',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分类表';

-- ----------------------------
-- 8. 信息表/文章表 (i8j_aicms_articles)
-- ----------------------------
DROP TABLE IF EXISTS `i8j_aicms_articles`;
CREATE TABLE `i8j_aicms_articles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '信息ID',
  `title` varchar(200) NOT NULL COMMENT '信息标题',
  `slug` varchar(200) DEFAULT NULL COMMENT '信息别名',
  `excerpt` text COMMENT '信息摘要',
  `content` longtext COMMENT '信息内容',
  `content_type` varchar(20) NOT NULL DEFAULT 'html' COMMENT '内容类型: html/markdown',
  `cover_image` varchar(255) DEFAULT NULL COMMENT '封面图片',
  `category_id` bigint unsigned DEFAULT NULL COMMENT '分类ID',
  `author_id` bigint unsigned DEFAULT NULL COMMENT '作者ID',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态: 0=草稿, 1=待审核, 2=已发布, 3=已下架, 4=回收站',
  `is_top` tinyint NOT NULL DEFAULT '0' COMMENT '是否置顶: 0=否, 1=是',
  `is_featured` tinyint NOT NULL DEFAULT '0' COMMENT '是否推荐: 0=否, 1=是',
  `allow_comment` tinyint NOT NULL DEFAULT '1' COMMENT '是否允许评论: 0=否, 1=是',
  `view_count` int unsigned NOT NULL DEFAULT '0' COMMENT '浏览次数',
  `like_count` int unsigned NOT NULL DEFAULT '0' COMMENT '点赞次数',
  `comment_count` int unsigned NOT NULL DEFAULT '0' COMMENT '评论次数',
  `seo_title` varchar(200) DEFAULT NULL COMMENT 'SEO标题',
  `seo_keywords` varchar(255) DEFAULT NULL COMMENT 'SEO关键词',
  `seo_description` varchar(500) DEFAULT NULL COMMENT 'SEO描述',
  `ai_summary` text COMMENT 'AI摘要',
  `ai_tags` varchar(255) DEFAULT NULL COMMENT 'AI标签',
  `ai_generated_at` datetime DEFAULT NULL COMMENT 'AI生成时间',
  `published_at` datetime DEFAULT NULL COMMENT '发布时间',
  `version` int unsigned NOT NULL DEFAULT '1' COMMENT '版本号',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  FULLTEXT KEY `ft_title_content` (`title`, `content`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_author_id` (`author_id`),
  KEY `idx_status` (`status`),
  KEY `idx_is_top` (`is_top`),
  KEY `idx_is_featured` (`is_featured`),
  KEY `idx_published_at` (`published_at`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_articles_category` FOREIGN KEY (`category_id`) REFERENCES `i8j_aicms_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_articles_author` FOREIGN KEY (`author_id`) REFERENCES `i8j_aicms_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='信息表';

-- ----------------------------
-- 9. 标签表 (i8j_aicms_tags)
-- ----------------------------
DROP TABLE IF EXISTS `i8j_aicms_tags`;
CREATE TABLE `i8j_aicms_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '标签ID',
  `name` varchar(50) NOT NULL COMMENT '标签名称',
  `slug` varchar(50) NOT NULL COMMENT '标签别名',
  `description` varchar(255) DEFAULT NULL COMMENT '标签描述',
  `color` varchar(20) DEFAULT NULL COMMENT '标签颜色',
  `content_count` int unsigned NOT NULL DEFAULT '0' COMMENT '信息数量',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态: 0=禁用, 1=正常',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='标签表';

-- ----------------------------
-- 10. 信息标签关联表 (i8j_aicms_article_tags)
-- ----------------------------
DROP TABLE IF EXISTS `i8j_aicms_article_tags`;
CREATE TABLE `i8j_aicms_article_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `article_id` bigint unsigned NOT NULL COMMENT '信息ID',
  `tag_id` bigint unsigned NOT NULL COMMENT '标签ID',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_article_tag` (`article_id`,`tag_id`),
  KEY `idx_article_id` (`article_id`),
  KEY `idx_tag_id` (`tag_id`),
  CONSTRAINT `fk_article_tags_article` FOREIGN KEY (`article_id`) REFERENCES `i8j_aicms_articles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_article_tags_tag` FOREIGN KEY (`tag_id`) REFERENCES `i8j_aicms_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章标签关联表';

-- ----------------------------
-- 11. 信息状态变更日志表 (i8j_aicms_article_status_logs)
-- ----------------------------
DROP TABLE IF EXISTS `i8j_aicms_article_status_logs`;
CREATE TABLE `i8j_aicms_article_status_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `article_id` bigint unsigned NOT NULL COMMENT '信息ID',
  `operator_id` bigint unsigned DEFAULT NULL COMMENT '操作人ID',
  `operator_name` varchar(50) DEFAULT NULL COMMENT '操作人名称',
  `old_status` tinyint DEFAULT NULL COMMENT '原状态',
  `new_status` tinyint NOT NULL COMMENT '新状态',
  `reason` varchar(255) DEFAULT NULL COMMENT '变更原因',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_article_id` (`article_id`),
  KEY `idx_operator_id` (`operator_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_status_logs_article` FOREIGN KEY (`article_id`) REFERENCES `i8j_aicms_articles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章状态变更日志表';

-- ----------------------------
-- 12. AI任务表 (i8j_aicms_ai_tasks)
-- ----------------------------
DROP TABLE IF EXISTS `i8j_aicms_ai_tasks`;
CREATE TABLE `i8j_aicms_ai_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '任务ID',
  `task_no` varchar(32) NOT NULL COMMENT '任务编号',
  `type` varchar(50) NOT NULL COMMENT '任务类型: summarize/generate/translate/rewrite/chat',
  `prompt` text NOT NULL COMMENT '用户提示词',
  `system_prompt` text COMMENT '系统提示词',
  `model` varchar(100) DEFAULT NULL COMMENT '使用的模型',
  `parameters` json DEFAULT NULL COMMENT '模型参数(JSON)',
  `input_tokens` int unsigned DEFAULT NULL COMMENT '输入token数',
  `output_tokens` int unsigned DEFAULT NULL COMMENT '输出token数',
  `result_content` longtext COMMENT '结果内容',
  `result_meta` json DEFAULT NULL COMMENT '结果元数据(JSON)',
  `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending' COMMENT '状态',
  `error_message` varchar(500) DEFAULT NULL COMMENT '错误信息',
  `retry_count` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '重试次数',
  `priority` tinyint NOT NULL DEFAULT '5' COMMENT '优先级: 1-10',
  `started_at` datetime DEFAULT NULL COMMENT '开始时间',
  `completed_at` datetime DEFAULT NULL COMMENT '完成时间',
  `user_id` bigint unsigned DEFAULT NULL COMMENT '用户ID',
  `rel_type` varchar(50) DEFAULT NULL COMMENT '关联类型',
  `rel_id` bigint unsigned DEFAULT NULL COMMENT '关联ID',
  `cost` decimal(10,4) DEFAULT NULL COMMENT '费用',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_task_no` (`task_no`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_rel_type_id` (`rel_type`,`rel_id`),
  KEY `idx_priority` (`priority`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI任务表';

-- ----------------------------
-- 13. AI提示词模板表 (i8j_aicms_ai_prompts)
-- ----------------------------
DROP TABLE IF EXISTS `i8j_aicms_ai_prompts`;
CREATE TABLE `i8j_aicms_ai_prompts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(100) NOT NULL COMMENT '模板名称',
  `description` varchar(255) DEFAULT NULL COMMENT '模板描述',
  `category` varchar(50) DEFAULT NULL COMMENT '分类',
  `system_prompt` text COMMENT '系统提示词',
  `user_prompt_template` text COMMENT '用户提示词模板',
  `variables` json DEFAULT NULL COMMENT '变量定义(JSON)',
  `model` varchar(100) DEFAULT NULL COMMENT '推荐模型',
  `default_parameters` json DEFAULT NULL COMMENT '默认参数(JSON)',
  `tags` varchar(255) DEFAULT NULL COMMENT '标签',
  `usage_count` int unsigned NOT NULL DEFAULT '0' COMMENT '使用次数',
  `is_public` tinyint NOT NULL DEFAULT '1' COMMENT '是否公开: 0=私有, 1=公开',
  `is_builtin` tinyint NOT NULL DEFAULT '0' COMMENT '是否内置: 0=否, 1=是',
  `creator_id` bigint unsigned DEFAULT NULL COMMENT '创建者ID',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态: 0=禁用, 1=正常',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_is_public` (`is_public`),
  KEY `idx_is_builtin` (`is_builtin`),
  KEY `idx_creator_id` (`creator_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI提示词模板表';

-- ----------------------------
-- 14. AI模型配置表 (i8j_aicms_ai_models)
-- ----------------------------
DROP TABLE IF EXISTS `i8j_aicms_ai_models`;
CREATE TABLE `i8j_aicms_ai_models` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `provider` varchar(50) NOT NULL COMMENT '服务商: deepseek/openai/baidu',
  `model_code` varchar(100) NOT NULL COMMENT '模型代码',
  `model_name` varchar(100) NOT NULL COMMENT '模型名称',
  `api_endpoint` varchar(255) DEFAULT NULL COMMENT 'API地址',
  `api_key_encrypted` varchar(500) DEFAULT NULL COMMENT 'API密钥(加密)',
  `max_tokens` int unsigned DEFAULT NULL COMMENT '最大token数',
  `input_price_per_1k` decimal(10,4) DEFAULT NULL COMMENT '输入价格(元/1K token)',
  `output_price_per_1k` decimal(10,4) DEFAULT NULL COMMENT '输出价格(元/1K token)',
  `supports_streaming` tinyint NOT NULL DEFAULT '1' COMMENT '是否支持流式: 0=否, 1=是',
  `rate_limit_rpm` int unsigned DEFAULT NULL COMMENT '速率限制(每分钟请求数)',
  `timeout` int unsigned DEFAULT NULL COMMENT '超时时间(秒)',
  `is_default` tinyint NOT NULL DEFAULT '0' COMMENT '是否默认: 0=否, 1=是',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态: 0=禁用, 1=正常',
  `last_test_at` datetime DEFAULT NULL COMMENT '最后测试时间',
  `test_result` text COMMENT '测试结果',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provider_model` (`provider`,`model_code`),
  KEY `idx_status` (`status`),
  KEY `idx_is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI模型配置表';

-- ----------------------------
-- 15. AI使用统计表 (i8j_aicms_ai_usage_stats)
-- ----------------------------
DROP TABLE IF EXISTS `i8j_aicms_ai_usage_stats`;
CREATE TABLE `i8j_aicms_ai_usage_stats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `stat_date` date NOT NULL COMMENT '统计日期',
  `provider` varchar(50) DEFAULT NULL COMMENT '服务商',
  `model` varchar(100) DEFAULT NULL COMMENT '模型',
  `user_id` bigint unsigned DEFAULT NULL COMMENT '用户ID',
  `task_count` int unsigned NOT NULL DEFAULT '0' COMMENT '任务数',
  `success_count` int unsigned NOT NULL DEFAULT '0' COMMENT '成功数',
  `failed_count` int unsigned NOT NULL DEFAULT '0' COMMENT '失败数',
  `total_input_tokens` bigint unsigned NOT NULL DEFAULT '0' COMMENT '总输入token',
  `total_output_tokens` bigint unsigned NOT NULL DEFAULT '0' COMMENT '总输出token',
  `total_cost` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '总费用',
  `avg_response_time` decimal(10,2) DEFAULT NULL COMMENT '平均响应时间(秒)',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_date_provider_model_user` (`stat_date`,`provider`,`model`,`user_id`),
  KEY `idx_stat_date` (`stat_date`),
  KEY `idx_provider` (`provider`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI使用统计表';

-- ----------------------------
-- 16. 系统配置表 (i8j_aicms_configs)
-- ----------------------------
DROP TABLE IF EXISTS `i8j_aicms_configs`;
CREATE TABLE `i8j_aicms_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `group_name` varchar(50) NOT NULL DEFAULT 'basic' COMMENT '配置分组',
  `key` varchar(100) NOT NULL COMMENT '配置键',
  `value` text COMMENT '配置值',
  `value_type` varchar(20) NOT NULL DEFAULT 'string' COMMENT '值类型: string/number/boolean/json',
  `title` varchar(100) DEFAULT NULL COMMENT '配置标题',
  `description` varchar(255) DEFAULT NULL COMMENT '配置描述',
  `is_public` tinyint NOT NULL DEFAULT '1' COMMENT '是否公开: 0=否, 1=是',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_group_key` (`group_name`,`key`),
  KEY `idx_group_name` (`group_name`),
  KEY `idx_is_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

-- ----------------------------
-- 17. 附件表 (i8j_aicms_attachments)
-- ----------------------------
DROP TABLE IF EXISTS `i8j_aicms_attachments`;
CREATE TABLE `i8j_aicms_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `original_name` varchar(255) NOT NULL COMMENT '原始文件名',
  `saved_path` varchar(255) NOT NULL COMMENT '保存路径',
  `saved_name` varchar(100) NOT NULL COMMENT '保存文件名',
  `mime_type` varchar(100) DEFAULT NULL COMMENT 'MIME类型',
  `size` bigint unsigned NOT NULL DEFAULT '0' COMMENT '文件大小(字节)',
  `extension` varchar(20) DEFAULT NULL COMMENT '文件扩展名',
  `storage_type` varchar(20) NOT NULL DEFAULT 'local' COMMENT '存储类型: local/oss/cos/s3',
  `url` varchar(500) DEFAULT NULL COMMENT '访问URL',
  `thumb_url` varchar(500) DEFAULT NULL COMMENT '缩略图URL',
  `uploader_id` bigint unsigned DEFAULT NULL COMMENT '上传者ID',
  `rel_type` varchar(50) DEFAULT NULL COMMENT '关联类型',
  `rel_id` bigint unsigned DEFAULT NULL COMMENT '关联ID',
  `width` int unsigned DEFAULT NULL COMMENT '图片宽度',
  `height` int unsigned DEFAULT NULL COMMENT '图片高度',
  `alt_text` varchar(255) DEFAULT NULL COMMENT 'Alt文本',
  `download_count` int unsigned NOT NULL DEFAULT '0' COMMENT '下载次数',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_uploader_id` (`uploader_id`),
  KEY `idx_rel_type_id` (`rel_type`,`rel_id`),
  KEY `idx_mime_type` (`mime_type`),
  KEY `idx_storage_type` (`storage_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='附件表';

-- ----------------------------
-- 种子数据
-- ----------------------------

-- 插入角色数据
INSERT INTO `i8j_aicms_roles` (`id`, `name`, `slug`, `description`, `is_system`, `status`, `created_at`, `updated_at`) VALUES
(1, '超级管理员', 'super_admin', '拥有系统所有权限', 1, 1, NOW(), NOW()),
(2, '管理员', 'admin', '系统管理员', 0, 1, NOW(), NOW()),
(3, '编辑', 'editor', '内容编辑', 0, 1, NOW(), NOW()),
(4, '作者', 'author', '内容作者', 0, 1, NOW(), NOW()),
(5, '访客', 'visitor', '普通访客', 0, 1, NOW(), NOW());

-- 插入权限数据
INSERT INTO `i8j_aicms_permissions` (`id`, `name`, `slug`, `module`, `type`, `parent_id`, `path`, `icon`, `sort_order`, `description`, `status`, `created_at`, `updated_at`) VALUES
-- 系统管理模块
(1, '系统管理', 'system', 'system', 'menu', NULL, NULL, 'fa-cog', 100, '系统管理模块', 1, NOW(), NOW()),
(2, '系统设置', 'system.config', 'system', 'menu', 1, '/system/config', 'fa-sliders-h', 101, '系统设置', 1, NOW(), NOW()),
(3, '查看设置', 'system.config.list', 'system', 'action', 2, NULL, NULL, 0, '查看系统设置', 1, NOW(), NOW()),
(4, '编辑设置', 'system.config.edit', 'system', 'action', 2, NULL, NULL, 0, '编辑系统设置', 1, NOW(), NOW()),

-- 用户管理模块
(10, '用户管理', 'user', 'user', 'menu', NULL, NULL, 'fa-users', 90, '用户管理模块', 1, NOW(), NOW()),
(11, '用户列表', 'user.list', 'user', 'menu', 10, '/user/list', 'fa-list', 91, '用户列表', 1, NOW(), NOW()),
(12, '查看用户', 'user.show', 'user', 'action', 11, NULL, NULL, 0, '查看用户详情', 1, NOW(), NOW()),
(13, '创建用户', 'user.create', 'user', 'action', 11, NULL, NULL, 0, '创建新用户', 1, NOW(), NOW()),
(14, '编辑用户', 'user.edit', 'user', 'action', 11, NULL, NULL, 0, '编辑用户', 1, NOW(), NOW()),
(15, '删除用户', 'user.delete', 'user', 'action', 11, NULL, NULL, 0, '删除用户', 1, NOW(), NOW()),
(16, '角色管理', 'user.role', 'user', 'menu', 10, '/user/role', 'fa-user-shield', 92, '角色管理', 1, NOW(), NOW()),
(17, '权限管理', 'user.permission', 'user', 'menu', 10, '/user/permission', 'fa-key', 93, '权限管理', 1, NOW(), NOW()),

-- 文章管理模块（信息管理）
(20, '信息管理', 'article', 'article', 'menu', NULL, NULL, 'fa-newspaper', 80, '信息管理模块', 1, NOW(), NOW()),
(21, '信息列表', 'article.list', 'article', 'menu', 20, '/article/list', 'fa-list', 81, '信息列表', 1, NOW(), NOW()),
(22, '查看信息', 'article.show', 'article', 'action', 21, NULL, NULL, 0, '查看信息详情', 1, NOW(), NOW()),
(23, '创建信息', 'article.create', 'article', 'action', 21, NULL, NULL, 0, '创建新信息', 1, NOW(), NOW()),
(24, '编辑信息', 'article.edit', 'article', 'action', 21, NULL, NULL, 0, '编辑信息', 1, NOW(), NOW()),
(25, '删除信息', 'article.delete', 'article', 'action', 21, NULL, NULL, 0, '删除信息', 1, NOW(), NOW()),
(26, '分类管理', 'article.category', 'article', 'menu', 20, '/article/category', 'fa-folder', 82, '分类管理', 1, NOW(), NOW()),
(27, '标签管理', 'article.tag', 'article', 'menu', 20, '/article/tag', 'fa-tags', 83, '标签管理', 1, NOW(), NOW()),

-- AI功能模块
(30, 'AI功能', 'ai', 'ai', 'menu', NULL, NULL, 'fa-robot', 70, 'AI功能模块', 1, NOW(), NOW()),
(31, 'AI任务', 'ai.task', 'ai', 'menu', 30, '/ai/task', 'fa-tasks', 71, 'AI任务列表', 1, NOW(), NOW()),
(32, 'AI助手', 'ai.chat', 'ai', 'menu', 30, '/ai/chat', 'fa-comment-dots', 72, 'AI对话', 1, NOW(), NOW()),
(33, 'AI模板', 'ai.prompt', 'ai', 'menu', 30, '/ai/prompt', 'fa-file-alt', 73, 'AI提示词模板', 1, NOW(), NOW()),
(34, 'AI模型', 'ai.model', 'ai', 'menu', 30, '/ai/model', 'fa-brain', 74, 'AI模型配置', 1, NOW(), NOW()),
(35, 'AI统计', 'ai.stats', 'ai', 'menu', 30, '/ai/stats', 'fa-chart-bar', 75, 'AI使用统计', 1, NOW(), NOW()),

-- 媒体管理模块
(40, '媒体管理', 'media', 'media', 'menu', NULL, NULL, 'fa-photo-video', 60, '媒体管理模块', 1, NOW(), NOW()),
(41, '媒体库', 'media.library', 'media', 'menu', 40, '/media/library', 'fa-images', 61, '媒体库', 1, NOW(), NOW()),
(42, '上传文件', 'media.upload', 'media', 'action', 41, NULL, NULL, 0, '上传文件', 1, NOW(), NOW()),
(43, '删除文件', 'media.delete', 'media', 'action', 41, NULL, NULL, 0, '删除文件', 1, NOW(), NOW());

-- 角色权限关联 - 超级管理员拥有所有权限
INSERT INTO `i8j_aicms_role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT 1, `id`, NOW() FROM `i8j_aicms_permissions` WHERE `status` = 1;

-- 管理员拥有系统设置和大部分权限
INSERT INTO `i8j_aicms_role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT 2, `id`, NOW() FROM `i8j_aicms_permissions` WHERE `status` = 1 AND `id` NOT IN (4, 15); -- 排除系统设置编辑和删除用户

-- 编辑拥有信息管理权限
INSERT INTO `i8j_aicms_role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT 3, `id`, NOW() FROM `i8j_aicms_permissions` 
WHERE `module` IN ('article', 'media') OR `slug` LIKE 'user.list' OR `slug` LIKE 'user.show';

-- 作者拥有信息列表和创建权限
INSERT INTO `i8j_aicms_role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT 4, `id`, NOW() FROM `i8j_aicms_permissions` 
WHERE `id` IN (20, 21, 22, 23, 24, 26, 27, 40, 41, 42);

-- 插入默认管理员用户 (密码: password, bcrypt hash)
-- 此哈希为 Laravel 默认测试密码 'password' 的标准 bcrypt 哈希值
-- 如需使用其他密码，请执行: UPDATE i8j_aicms_users SET password = '$2y$10$新哈希' WHERE id = 1;
INSERT INTO `i8j_aicms_users` (`id`, `username`, `email`, `password`, `nickname`, `avatar`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '管理员', NULL, 1, NOW(), NOW());

-- 管理员分配超级管理员角色
INSERT INTO `i8j_aicms_user_roles` (`user_id`, `role_id`, `created_at`) VALUES
(1, 1, NOW());

-- 插入内置AI Prompt模板
INSERT INTO `i8j_aicms_ai_prompts` (`name`, `description`, `category`, `system_prompt`, `user_prompt_template`, `variables`, `model`, `default_parameters`, `tags`, `is_public`, `is_builtin`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
('内容摘要', '快速提取内容核心要点', 'content', '你是一个专业的内容编辑，擅长提炼内容的核心要点。', '请为以下内容生成一个简洁的摘要（不超过200字）：\n\n{{content}}', '["content"]', 'deepseek-chat', '{"temperature": 0.7, "max_tokens": 500}', '摘要,内容,AI', 1, 1, 1, 1, NOW(), NOW()),
('内容续写', '根据已有内容继续创作', 'content', '你是一个才华横溢的作家，擅长续写各种风格的内容。', '请根据以下内容继续写作，保持相同的风格和语气：\n\n{{existing_content}}\n\n请续写大约{{word_count}}字。', '["existing_content","word_count"]', 'deepseek-chat', '{"temperature": 0.8, "max_tokens": 2000}', '续写,创作,AI', 1, 1, 2, 1, NOW(), NOW()),
('内容优化', '优化内容质量和可读性', 'content', '你是一个资深的内容编辑，专注于提升内容质量和可读性。', '请优化以下内容，提升其表达清晰度和可读性：\n\n{{content}}\n\n优化要点：\n1. 改善段落结构\n2. 优化用词\n3. 增强逻辑性', '["content"]', 'deepseek-chat', '{"temperature": 0.7, "max_tokens": 3000}', '优化,编辑,AI', 1, 1, 3, 1, NOW(), NOW()),
('标签生成', '为内容生成相关标签', 'content', '你是一个专业的SEO专家，擅长为内容生成精准的标签。', '请为以下内容生成5-8个相关标签（用逗号分隔）：\n\n标题：{{title}}\n内容：{{content}}', '["title","content"]', 'deepseek-chat', '{"temperature": 0.5, "max_tokens": 200}', '标签,SEO,AI', 1, 1, 4, 1, NOW(), NOW()),
('智能问答', '回答用户问题', 'chat', '你是一个知识渊博的AI助手，可以回答各种问题。', '{{question}}', '["question"]', 'deepseek-chat', '{"temperature": 0.9, "max_tokens": 1000}', '问答,助手,AI', 1, 1, 5, 1, NOW(), NOW());

-- 插入默认AI模型配置
INSERT INTO `i8j_aicms_ai_models` (`provider`, `model_code`, `model_name`, `api_endpoint`, `max_tokens`, `input_price_per_1k`, `output_price_per_1k`, `supports_streaming`, `rate_limit_rpm`, `timeout`, `is_default`, `status`, `created_at`, `updated_at`) VALUES
('deepseek', 'deepseek-chat', 'DeepSeek Chat', 'https://api.deepseek.com/chat/completions', 16384, 0.0010, 0.0020, 1, 60, 120, 1, 1, NOW(), NOW()),
('deepseek', 'deepseek-coder', 'DeepSeek Coder', 'https://api.deepseek.com/chat/completions', 16384, 0.0014, 0.0028, 1, 60, 120, 0, 1, NOW(), NOW());

-- 插入默认系统配置
INSERT INTO `i8j_aicms_configs` (`group_name`, `key`, `value`, `value_type`, `title`, `description`, `is_public`, `sort_order`, `created_at`, `updated_at`) VALUES
('basic', 'site_name', '八界AI-CMS', 'string', '网站名称', '网站显示名称', 1, 1, NOW(), NOW()),
('basic', 'site_url', 'https://example.com', 'string', '网站地址', '网站访问URL', 1, 2, NOW(), NOW()),
('basic', 'site_logo', '/uploads/logo.png', 'string', '网站Logo', '网站Logo图片地址', 1, 3, NOW(), NOW()),
('basic', 'site_keywords', 'AI-CMS,内容管理系统,人工智能', 'string', '网站关键词', 'SEO关键词', 1, 4, NOW(), NOW()),
('basic', 'site_description', '八界AI-CMS - 智能内容管理系统', 'string', '网站描述', '网站描述信息', 1, 5, NOW(), NOW()),
('basic', 'timezone', 'Asia/Shanghai', 'string', '时区', '系统时区设置', 1, 6, NOW(), NOW()),
('basic', 'language', 'zh-CN', 'string', '语言', '系统语言设置', 1, 7, NOW(), NOW()),
('basic', 'icp_number', '', 'string', 'ICP备案号', '网站ICP备案号', 1, 8, NOW(), NOW()),
('basic', 'police_number', '', 'string', '公安备案号', '网站公安备案号', 1, 9, NOW(), NOW()),
('upload', 'upload_max_size', '20971520', 'number', '最大上传大小', '文件最大上传大小(字节)', 1, 10, NOW(), NOW()),
('upload', 'upload_allowed_ext', 'jpg,jpeg,png,gif,zip,rar,pdf,doc,docx,xls,xlsx', 'string', '允许上传扩展名', '允许上传的文件扩展名', 1, 11, NOW(), NOW()),
('ai', 'default_model', 'deepseek-chat', 'string', '默认AI模型', '系统默认使用的AI模型', 1, 20, NOW(), NOW()),
('ai', 'ai_temperature', '0.7', 'number', 'AI温度参数', 'AI生成的随机性(0-1)', 1, 21, NOW(), NOW()),
('ai', 'ai_max_tokens', '2000', 'number', 'AI最大Token', 'AI单次生成最大Token数', 1, 22, NOW(), NOW()),
('seo', 'title_format', '{title} - {site_name}', 'string', '标题格式', '页面标题显示格式', 1, 30, NOW(), NOW()),
('seo', 'keyword_separator', ',', 'string', '关键词分隔符', 'SEO关键词分隔字符', 1, 31, NOW(), NOW()),
('seo', 'sitemap_enabled', '1', 'boolean', '生成Sitemap', '是否自动生成站点地图', 1, 32, NOW(), NOW()),
('seo', 'baidu_push_api', '', 'string', '百度推送API', '百度主动推送接口地址', 1, 33, NOW(), NOW()),
('smtp', 'host', '', 'string', 'SMTP服务器', '邮件发送服务器地址', 0, 40, NOW(), NOW()),
('smtp', 'port', '465', 'number', '端口', 'SMTP服务端口', 0, 41, NOW(), NOW()),
('smtp', 'username', '', 'string', '用户名', 'SMTP登录用户名', 0, 42, NOW(), NOW()),
('smtp', 'password', '', 'string', '密码', 'SMTP登录密码(加密存储)', 0, 43, NOW(), NOW()),
('smtp', 'from_email', '', 'string', '发件人邮箱', '默认发件人邮箱地址', 0, 44, NOW(), NOW()),
('smtp', 'from_name', 'AI-CMS', 'string', '发件人名称', '默认发件人名称', 0, 45, NOW(), NOW()),
('smtp', 'use_ssl', '1', 'boolean', '使用SSL', '是否启用SSL加密连接', 0, 46, NOW(), NOW());

SET FOREIGN_KEY_CHECKS = 1;
