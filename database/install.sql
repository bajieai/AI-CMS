-- ============================================
-- AI-CMS Install SQL
-- Version: V2.9.41
-- Prefix: {prefix}
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET UNIQUE_CHECKS = 0;
SET AUTOCOMMIT = 0;

DROP TABLE IF EXISTS `{prefix}ab_test`;
CREATE TABLE `{prefix}ab_test` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `test_name` varchar(100) NOT NULL DEFAULT '' COMMENT '测试名称',
  `test_type` varchar(30) NOT NULL DEFAULT 'content' COMMENT '类型: content/template/feature/price',
  `description` text COMMENT '测试描述',
  `version_a_config` json DEFAULT NULL COMMENT '版本A配置',
  `version_b_config` json DEFAULT NULL COMMENT '版本B配置',
  `traffic_ratio` int(11) NOT NULL DEFAULT '50' COMMENT '版本B流量占比(%)',
  `primary_metric` varchar(50) NOT NULL DEFAULT 'click_rate' COMMENT '主要指标: click_rate/conversion_rate/bounce_rate/avg_duration/revenue',
  `target_audience` json DEFAULT NULL COMMENT '目标受众条件',
  `status` varchar(20) NOT NULL DEFAULT 'draft' COMMENT '状态: draft/running/paused/completed/archived',
  `winner` varchar(5) NOT NULL DEFAULT '' COMMENT '获胜版本: A/B/none',
  `confidence` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '置信度(%)',
  `start_time` datetime DEFAULT NULL COMMENT '开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '结束时间',
  `version_a_visitors` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '版本A访客数',
  `version_a_conversions` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '版本A转化数',
  `version_b_visitors` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '版本B访客数',
  `version_b_conversions` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '版本B转化数',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建者ID',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_test_type` (`test_type`),
  KEY `idx_status` (`status`),
  KEY `idx_start_time` (`start_time`),
  KEY `idx_end_time` (`end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='A/B测试表';

DROP TABLE IF EXISTS `{prefix}ad`;
CREATE TABLE `{prefix}ad` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `position_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '广告位ID',
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '广告标题',
  `image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '图片地址',
  `link` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '链接地址',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '代码/富文本内容',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_position_status` (`position_id`,`status`),
  KEY `idx_status_sort` (`status`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='广告表';

DROP TABLE IF EXISTS `{prefix}ad_position`;
CREATE TABLE `{prefix}ad_position` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '广告位名称',
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '广告位标识(唯一)',
  `width` int(11) NOT NULL DEFAULT '0' COMMENT '宽度(px)',
  `height` int(11) NOT NULL DEFAULT '0' COMMENT '高度(px)',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '描述',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='广告位表';

DROP TABLE IF EXISTS `{prefix}ad_stat`;
CREATE TABLE `{prefix}ad_stat` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ad_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '广告ID',
  `stat_date` date NOT NULL COMMENT '统计日期',
  `views` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '展示次数',
  `clicks` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点击次数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ad_date` (`ad_id`,`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='广告统计表';

DROP TABLE IF EXISTS `{prefix}admin_user`;
CREATE TABLE `{prefix}admin_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}ai_batch_task`;
CREATE TABLE `{prefix}ai_batch_task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(10) unsigned DEFAULT '0' COMMENT '关联AI模板ID',
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '任务名称',
  `keywords` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '关键词列表（换行分隔）',
  `style` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'default' COMMENT '写作风格: default/formal/casual/marketing/technical',
  `cate_id` int(11) DEFAULT '0' COMMENT '目标分类',
  `model_id` int(11) DEFAULT '0' COMMENT '使用的AI模型ID',
  `total` int(11) DEFAULT '0' COMMENT '总数量',
  `completed` int(11) DEFAULT '0' COMMENT '已完成数量',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态: 0排队 1进行中 2完成 3失败',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_template` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI批量生成任务表';

DROP TABLE IF EXISTS `{prefix}ai_config`;
CREATE TABLE `{prefix}ai_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}ai_content_log`;
CREATE TABLE `{prefix}ai_content_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `content_id` int(11) NOT NULL DEFAULT '0',
  `mode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `style` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `output_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `provider` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tokens_used` int(11) NOT NULL DEFAULT '0',
  `elapsed_ms` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_mode` (`mode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI content log';

DROP TABLE IF EXISTS `{prefix}ai_dialog`;
CREATE TABLE `{prefix}ai_dialog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) NOT NULL COMMENT '对话会话ID',
  `title` varchar(200) DEFAULT '' COMMENT '对话标题',
  `dialog_type` varchar(30) DEFAULT 'general' COMMENT '对话类型(general/content/analysis/ops/tech/user/education/creative/translation)',
  `dialog_style` varchar(30) DEFAULT 'formal' COMMENT '对话风格(formal/concise/detailed/friendly/tech)',
  `member_id` int(11) DEFAULT '0' COMMENT '用户ID(0=匿名)',
  `context_summary` text COMMENT '上下文摘要',
  `message_count` int(11) DEFAULT '0' COMMENT '消息数量',
  `tokens_used` int(11) DEFAULT '0' COMMENT '消耗Token数',
  `is_active` tinyint(4) DEFAULT '1' COMMENT '是否活跃:1是0否',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_type` (`dialog_type`),
  KEY `idx_active` (`is_active`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='AI对话会话表';

DROP TABLE IF EXISTS `{prefix}ai_dialog_message`;
CREATE TABLE `{prefix}ai_dialog_message` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) NOT NULL COMMENT '对话会话ID',
  `role` varchar(20) NOT NULL COMMENT '角色(user/assistant/system)',
  `content` longtext NOT NULL COMMENT '消息内容',
  `content_type` varchar(20) DEFAULT 'text' COMMENT '内容类型(text/markdown/html/code/image)',
  `metadata` json DEFAULT NULL COMMENT '元数据(JSON: tokens/模型/耗时/引用等)',
  `parent_id` bigint(20) DEFAULT '0' COMMENT '父消息ID(用于分支对话)',
  `is_deleted` tinyint(4) DEFAULT '0' COMMENT '是否删除:1是0否',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_role` (`role`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='AI对话消息表';

DROP TABLE IF EXISTS `{prefix}ai_editor_conversation`;
CREATE TABLE `{prefix}ai_editor_conversation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '会话标识',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联内容ID(0=未关联内容，仅允许临时对话，不支持跨content对话)',
  `role` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '角色(user/assistant)',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '对话内容',
  `token_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '本轮Token数量',
  `session_token_total` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会话累计Token总数',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI编辑器对话记录表(V2.9.28 A-2)';

DROP TABLE IF EXISTS `{prefix}ai_editor_snapshot`;
CREATE TABLE `{prefix}ai_editor_snapshot` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `version` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '版本号(自增)',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '内容快照',
  `content_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '内容哈希(sha256)',
  `operation_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作类型(continue/rewrite/expand/translate/optimize)',
  `operation_desc` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作描述',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_content_version` (`content_id`,`version`),
  KEY `idx_content` (`content_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI编辑器版本快照表(V2.9.28 A-7)';

DROP TABLE IF EXISTS `{prefix}ai_editor_template`;
CREATE TABLE `{prefix}ai_editor_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板名称',
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板描述',
  `prompt` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Prompt模板(含变量占位符)',
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类',
  `industry` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '行业标签',
  `tags` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标签(逗号分隔)',
  `example_output` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '示例输出',
  `sort` int(10) unsigned NOT NULL DEFAULT '99' COMMENT '排序',
  `is_system` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否系统预制',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建用户(0=系统)',
  `use_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '使用次数',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI编辑器模板库(V2.9.28 A-5)';

INSERT INTO `{prefix}ai_editor_template` VALUES (1,'营销文案生成','生成吸引人的营销文案','请根据以下信息生成一段营销文案：\n产品名称：{product_name}\n目标受众：{target_audience}\n核心卖点：{selling_points}\n文案风格：吸引眼球、简洁有力','marketing','ecommerce','营销,文案,广告','【限时特惠】{product_name}，专为{target_audience}打造！{selling_points}，让每一次选择都物超所值。',1,1,0,0,1,1782111123,1782111123),(2,'产品描述优化','优化产品描述使其更具吸引力','请优化以下产品描述，使其更专业、更有吸引力：\n{content}\n要求：突出产品优势、使用场景化描述','marketing','ecommerce','产品,描述,优化','这款产品采用XX工艺，精选优质材料...',2,1,0,0,1,1782111123,1782111123),(3,'新闻稿撰写','撰写标准格式的新闻稿','请撰写一篇新闻稿：\n标题：{title}\n事件：{event}\n时间：{date}\n地点：{location}\n要求：客观、正式、信息完整','news','enterprise','新闻,稿件,公关','{date}，{location}讯——{title}。据悉，{event}...',3,1,0,0,1,1782111123,1782111123),(4,'博客文章生成','生成博客文章框架','请围绕以下主题撰写一篇博客文章：\n主题：{topic}\n字数：{word_count}\n风格：{style}\n要求：开头吸引人、内容有价值、结尾有总结','blog','blog','博客,文章,内容','在这个信息爆炸的时代，{topic}成为了热门话题...',4,1,0,0,1,1782111123,1782111123),(5,'邮件营销模板','生成邮件营销内容','请撰写一封营销邮件：\n收件人：{recipient}\n产品：{product}\n目的：{purpose}\n要求：标题吸引人、正文简洁、CTA明确','email','enterprise','邮件,营销,EDM','亲爱的{recipient}，\n\n我们很高兴向您介绍{product}...',5,1,0,0,1,1782111123,1782111123),(6,'SEO摘要生成','生成SEO友好的内容摘要','请为以下内容生成SEO友好的摘要(150字以内)：\n{content}\n要求：包含核心关键词、吸引点击、适合搜索引擎','seo','enterprise','SEO,摘要,优化','本文深入探讨{topic}，为您揭示...',6,1,0,0,1,1782111123,1782111123),(7,'社交媒体文案','生成社交媒体发布文案','请为以下内容生成社交媒体文案：\n平台：{platform}\n内容：{content}\n要求：符合平台调性、带话题标签、互动性强','social','ecommerce','社交媒体,文案,互动','刚刚了解到{content}，太赞了！#话题标签',7,1,0,0,1,1782111123,1782111123),(8,'产品说明书','撰写产品使用说明书','请撰写产品使用说明书：\n产品：{product}\n功能：{features}\n要求：步骤清晰、语言简洁、安全提示','manual','enterprise','产品,说明书,文档','一、产品概述\n{product}是一款{features}的产品...',8,1,0,0,1,1782111123,1782111123),(9,'教育课程大纲','生成教育培训课程大纲','请生成课程大纲：\n课程名称：{course_name}\n目标学员：{target}\n课时数：{hours}\n要求：循序渐进、知识点清晰','education','education','教育,课程,培训','第一讲：{course_name}基础\n第二讲：进阶知识...',9,1,0,0,1,1782111123,1782111123),(10,'医疗科普文章','撰写通俗易懂的医疗科普','请撰写医疗科普文章：\n主题：{topic}\n读者：普通大众\n要求：科学准确、通俗易懂、有实用建议','article','medical','医疗,科普,健康','关于{topic}，很多人都有疑问...',10,1,0,0,1,1782111123,1782111123),(11,'金融分析报告','生成金融数据分析报告','请撰写金融分析报告：\n分析对象：{target}\n数据：{data}\n要求：客观分析、数据支撑、有结论建议','report','finance','金融,分析,报告','一、市场概况\n根据数据，{target}近期表现...',11,1,0,0,1,1782111123,1782111123),(12,'旅游攻略生成','生成旅游目的地攻略','请生成旅游攻略：\n目的地：{destination}\n天数：{days}\n要求：行程合理、必去景点、美食推荐','guide','tourism','旅游,攻略,出行','第一天：抵达{destination}，建议游览...',12,1,0,0,1,1782111123,1782111123),(13,'电商详情页文案','生成电商产品详情页文案','请为电商产品生成详情页文案：\n产品：{product}\n卖点：{features}\n要求：分模块展示、图文并茂、转化率高','ecommerce','ecommerce','电商,详情页,转化','【产品亮点】\n{features}\n【使用场景】\n...',13,1,0,0,1,1782111123,1782111123),(14,'技术文档撰写','撰写技术API文档','请撰写技术文档：\n功能：{feature}\n接口：{api}\n要求：参数说明、示例代码、注意事项','tech','enterprise','技术,文档,API','## 接口说明\n{api}\n\n## 请求参数\n...',14,1,0,0,1,1782111123,1782111123),(15,'品牌故事撰写','撰写品牌故事','请撰写品牌故事：\n品牌：{brand}\n历史：{history}\n价值观：{values}\n要求：感人、真实、有记忆点','brand','enterprise','品牌,故事,营销','{brand}的故事始于{history}...',15,1,0,0,1,1782111123,1782111123),(16,'FAQ常见问题','生成FAQ问答','请生成FAQ：\n主题：{topic}\n常见问题数：{count}\n要求：问题典型、答案简洁','faq','enterprise','FAQ,问答,帮助','Q1: {topic}是什么？\nA: ...',16,1,0,0,1,1782111123,1782111123),(17,'视频脚本撰写','生成短视频脚本','请撰写短视频脚本：\n主题：{topic}\n时长：{duration}秒\n平台：{platform}\n要求：开头3秒抓眼球、节奏紧凑','video','ecommerce','视频,脚本,短视频','【0-3秒】开场白\n【3-15秒】核心内容...',17,1,0,0,1,1782111123,1782111123),(18,'活动策划方案','生成活动策划方案','请生成活动策划方案：\n活动类型：{type}\n预算：{budget}\n人数：{participants}\n要求：创意、可执行、有ROI分析','event','enterprise','活动,策划,方案','一、活动概述\n{type}活动，预计{participants}人参加...',18,1,0,0,1,1782111123,1782111123),(19,'用户评测文案','生成用户评测文案','请撰写产品评测文案：\n产品：{product}\n使用体验：{experience}\n要求：真实客观、优缺点对比','review','ecommerce','评测,产品,体验','用了{product}一周后，我的真实感受...',19,1,0,0,1,1782111123,1782111123),(20,'招聘JD撰写','生成招聘职位描述','请撰写招聘JD：\n职位：{position}\n要求：{requirements}\n公司：{company}\n要求：吸引人、职责清晰、要求合理','hr','enterprise','招聘,JD,HR','我们正在寻找{position}！\n在{company}，你将...',20,1,0,0,1,1782111123,1782111123);
DROP TABLE IF EXISTS `{prefix}ai_image_task`;
CREATE TABLE `{prefix}ai_image_task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联内容ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0排队中/1生成中/2完成/3失败',
  `prompt` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '生成提示词',
  `image_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '生成图片URL',
  `provider` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '图片提供商',
  `error_msg` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '错误信息',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_status` (`status`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI配图任务表(V2.9.12)';

DROP TABLE IF EXISTS `{prefix}ai_knowledge_base`;
CREATE TABLE `{prefix}ai_knowledge_base` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}ai_knowledge_chunk`;
CREATE TABLE `{prefix}ai_knowledge_chunk` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}ai_knowledge_doc`;
CREATE TABLE `{prefix}ai_knowledge_doc` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}ai_log`;
CREATE TABLE `{prefix}ai_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `model_id` int(11) NOT NULL COMMENT '使用的模型ID',
  `task_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '任务类型: write/seo/translate/summarize',
  `prompt_length` int(11) DEFAULT '0' COMMENT '输入长度',
  `response_length` int(11) DEFAULT '0' COMMENT '输出长度',
  `tokens_used` int(11) DEFAULT '0' COMMENT '消耗token数',
  `duration_ms` int(11) DEFAULT '0' COMMENT '耗时（毫秒）',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态: 1成功 2失败 3降级',
  `error_msg` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '错误信息',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_model_time` (`model_id`,`create_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI调用日志表';

INSERT INTO `{prefix}ai_log` VALUES (1,0,'seoOptimize',41,3547,0,5387,1,'',1779246923),(2,0,'seoOptimize',41,4811,0,8057,1,'',1779248613);
DROP TABLE IF EXISTS `{prefix}ai_model`;
CREATE TABLE `{prefix}ai_model` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模型名称',
  `provider` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '供应商: deepseek/qwen/ernie/glm/openai',
  `model_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模型ID（API调用用）',
  `api_base` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'API Base URL',
  `api_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'API密钥（加密存储）',
  `capabilities` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'write,seo,translate' COMMENT '能力标签，逗号分隔',
  `is_default` tinyint(4) DEFAULT '0' COMMENT '是否默认模型',
  `is_enabled` tinyint(4) DEFAULT '1' COMMENT '启用状态',
  `max_tokens` int(11) DEFAULT '2000' COMMENT '最大输出token数',
  `temperature` float DEFAULT '0.7' COMMENT '温度参数',
  `sort` int(11) DEFAULT '0' COMMENT '排序（故障降级顺序）',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  `rate_limit_rpm` int(11) DEFAULT '60' COMMENT '每分钟最大请求数',
  `rate_limit_rph` int(11) DEFAULT '1000' COMMENT '每小时最大请求数',
  `api_key_encrypted` tinyint(4) DEFAULT '0' COMMENT 'API密钥是否已加密',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态: 1启用 0禁用',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provider_model` (`provider`,`model_id`),
  KEY `idx_enabled_default` (`is_enabled`,`is_default`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI模型配置表';

INSERT INTO `{prefix}ai_model` VALUES (1,'DeepSeek V4-Flash','deepseek','deepseek-chat','https://api.deepseek.com/v1','','write,seo,translate,summarize',1,1,2000,0.7,1,1777457479,1778141229,60,1000,0,1),(4,'GLM-4-Flash','glm','glm-4-flash','https://open.bigmodel.cn/api/paas/v4','','write,seo,translate',0,1,2000,0.7,3,1777774128,1777774128,60,1000,0,1),(5,'ERNIE-Speed','ernie','ernie-speed-128k','https://qianfan.baidubce.com/v2','','write,seo',0,0,2000,0.7,4,1777774128,1779255885,60,1000,0,1),(6,'OpenAI兼容','openai','gpt-3.5-turbo','https://api.openai.com/v1','','write,seo,translate,summarize',0,0,2000,0.7,5,1777774128,1777774128,60,1000,0,1);
DROP TABLE IF EXISTS `{prefix}ai_prompt_template`;
CREATE TABLE `{prefix}ai_prompt_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板标识',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板名称',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '描述',
  `template` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '提示词模板',
  `variables` json DEFAULT NULL COMMENT '变量列表JSON',
  `is_system` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否系统内置:1=是,0=否',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态:0=禁用,1=启用',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`key`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI Prompt模板表 - V2.9.31';

DROP TABLE IF EXISTS `{prefix}ai_quality_check`;
CREATE TABLE `{prefix}ai_quality_check` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(11) NOT NULL COMMENT '内容ID',
  `content_type` varchar(20) DEFAULT 'article' COMMENT '内容类型',
  `ai_generated` tinyint(4) DEFAULT '1' COMMENT '是否AI生成',
  `quality_score` decimal(4,2) DEFAULT '0.00' COMMENT '总质量评分(0-100)',
  `dimension_scores` json DEFAULT NULL COMMENT '各维度评分(JSON)',
  `check_rules` json DEFAULT NULL COMMENT '检查规则结果(JSON)',
  `issues` json DEFAULT NULL COMMENT '发现的问题(JSON)',
  `suggestions` text COMMENT '改进建议',
  `auto_optimized` tinyint(4) DEFAULT '0' COMMENT '是否已自动优化',
  `optimized_content` longtext COMMENT '优化后内容',
  `check_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '检查时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_type` (`content_type`),
  KEY `idx_ai` (`ai_generated`),
  KEY `idx_score` (`quality_score`),
  KEY `idx_check_time` (`check_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='AI内容质量检查表';

DROP TABLE IF EXISTS `{prefix}ai_report`;
CREATE TABLE `{prefix}ai_report` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL COMMENT 'daily/weekly/monthly/manual',
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '报告标题',
  `period_start` int(10) unsigned NOT NULL COMMENT '统计开始时间戳',
  `period_end` int(10) unsigned NOT NULL COMMENT '统计结束时间戳',
  `raw_data` json DEFAULT NULL COMMENT '原始数据快照',
  `summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT '一句话总结',
  `findings` json DEFAULT NULL COMMENT '关键发现列表',
  `anomalies` json DEFAULT NULL COMMENT '异常检测列表',
  `recommendations` json DEFAULT NULL COMMENT '建议列表',
  `sections` json DEFAULT NULL COMMENT '详细章节',
  `status` tinyint(4) DEFAULT '0' COMMENT '0生成中/1已完成/2发布/3失败',
  `create_time` int(10) unsigned DEFAULT '0',
  `update_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_period` (`period_start`,`period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='AI分析报告';

DROP TABLE IF EXISTS `{prefix}ai_rewrite_log`;
CREATE TABLE `{prefix}ai_rewrite_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '操作人',
  `content_id` int(11) NOT NULL COMMENT '内容ID',
  `rewrite_type` varchar(50) NOT NULL COMMENT '改写类型(title/summary/body/style)',
  `style` varchar(50) DEFAULT '' COMMENT '改写风格',
  `original_content` text COMMENT '原始内容',
  `rewritten_content` text COMMENT '改写后内容',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态:1已生成2已确认3已放弃',
  `token_used` int(11) DEFAULT '0' COMMENT '消耗token数',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='AI改写日志表';

DROP TABLE IF EXISTS `{prefix}ai_task_queue`;
CREATE TABLE `{prefix}ai_task_queue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '任务ID',
  `task_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '任务类型：ai_image_generate / batch_seo_optimize / single_seo_optimize',
  `agent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '智能体ID',
  `agent_session_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '智能体会话ID',
  `agent_plan` json DEFAULT NULL COMMENT '智能体执行计划',
  `agent_memory` json DEFAULT NULL COMMENT '智能体记忆上下文',
  `biz_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '业务ID：content_id / batch_id 等',
  `biz_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '业务标识：用于分组，如batch_seo:20260531',
  `payload` json DEFAULT NULL COMMENT '任务参数（JSON），含prompt/options/extra等',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态：0=pending, 1=running, 2=completed, 3=failed, 4=paused, 5=cancelled',
  `progress` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '进度百分比 0-100',
  `result` json DEFAULT NULL COMMENT '执行结果（JSON），成功时包含urls/task_ids等',
  `error_msg` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '失败原因',
  `retry_count` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '重试次数',
  `max_retries` tinyint(3) unsigned NOT NULL DEFAULT '3' COMMENT '最大重试次数',
  `priority` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '优先级：0=普通, 1=高',
  `scheduled_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '计划执行时间（时间戳，0=立即）',
  `started_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始执行时间',
  `completed_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '完成时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_biz` (`biz_id`,`task_type`),
  KEY `idx_biz_key` (`biz_key`),
  KEY `idx_status` (`status`,`priority`,`scheduled_at`),
  KEY `idx_type_status` (`task_type`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI任务队列表';

DROP TABLE IF EXISTS `{prefix}ai_template`;
CREATE TABLE `{prefix}ai_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模板名称',
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '模板描述',
  `nl_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '自然语言描述(V2.9.9，供AI理解模板意图)',
  `generate_mode` enum('nlp','example') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'nlp' COMMENT '生成模式: nlp自然语言/example参考示例',
  `cate_id` int(10) unsigned DEFAULT '0' COMMENT '默认内容分类ID',
  `model_id` int(10) unsigned DEFAULT '0' COMMENT '默认AI模型ID',
  `style` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'default' COMMENT '写作风格: default/formal/casual/marketing/technical',
  `title_rule` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '标题生成规则(NL描述)',
  `content_rule` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '内容生成规则(NL描述)',
  `keyword_hint` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '默认关键词提示',
  `fields_config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '自定义字段配置JSON',
  `image_config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '配图配置JSON',
  `field_mapping` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '字段映射规则JSON(含mappings/variables/image_config_override)',
  `quality_config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '质量检测配置JSON(min_score/max_retry/action_on_low_quality/check_items)',
  `publisher` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '默认作者',
  `contact` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '默认联系方式',
  `example_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '示例标题',
  `example_content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '示例正文内容（用于风格学习）',
  `default_batch` smallint(5) unsigned DEFAULT '10' COMMENT '默认批量数量(1-100)',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态: 0禁用 1启用',
  `source` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'custom' COMMENT '模板来源:system官方/custom自建/imported导入(V2.9.9)',
  `sort` int(11) DEFAULT '0' COMMENT '排序权重',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_cate` (`cate_id`),
  KEY `idx_mode` (`generate_mode`),
  KEY `idx_source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI内容生成模板表(V2.6)';

DROP TABLE IF EXISTS `{prefix}ai_theme_chat_log`;
CREATE TABLE `{prefix}ai_theme_chat_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联ai_theme_record.id',
  `version` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '修改时的版本号',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作者用户ID（审计用）',
  `role` enum('user','ai','system') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user' COMMENT '消息角色',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '消息内容',
  `changed_files` json DEFAULT NULL COMMENT '本次修改变更的文件列表',
  `prompt_tokens` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '输入Token数',
  `completion_tokens` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '输出Token数',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_record_version` (`record_id`,`version`),
  KEY `idx_record_role` (`record_id`,`role`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI主题多轮对话记录';

DROP TABLE IF EXISTS `{prefix}ai_theme_palette`;
CREATE TABLE `{prefix}ai_theme_palette` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `industry_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '行业标识',
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '调色板名称',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '描述',
  `colors` json NOT NULL COMMENT '色板JSON: {primary,primaryLight,primaryDark,secondary,accent,bg,bgSecondary,bgSection,text,textSecondary,border}',
  `is_system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否系统内置:1是/0否',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_industry_system` (`industry_type`,`is_system`),
  KEY `idx_industry` (`industry_type`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI主题行业调色板(V2.9.11)';

INSERT INTO `{prefix}ai_theme_palette` VALUES (1,'corporate','企业商务','专业、可信、现代简约','{\"bg\": \"#FFFFFF\", \"text\": \"#1E293B\", \"accent\": \"#F59E0B\", \"border\": \"#E2E8F0\", \"primary\": \"#2563EB\", \"bgSection\": \"#F1F5F9\", \"secondary\": \"#64748B\", \"bgSecondary\": \"#F8FAFC\", \"primaryDark\": \"#1E40AF\", \"primaryLight\": \"#DBEAFE\", \"textSecondary\": \"#64748B\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34'),(2,'ecommerce','电商促销','热闹、促销、信任感','{\"bg\": \"#FFFFFF\", \"text\": \"#1F2937\", \"accent\": \"#EF4444\", \"border\": \"#E5E7EB\", \"primary\": \"#F97316\", \"bgSection\": \"#FFFBEB\", \"secondary\": \"#6B7280\", \"bgSecondary\": \"#FFF7ED\", \"primaryDark\": \"#EA580C\", \"primaryLight\": \"#FFEDD5\", \"textSecondary\": \"#6B7280\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34'),(3,'blog','博客文艺','舒适阅读、极简、知识分享','{\"bg\": \"#FFFFFF\", \"text\": \"#111827\", \"accent\": \"#8B5CF6\", \"border\": \"#E5E7EB\", \"primary\": \"#059669\", \"bgSection\": \"#F3F4F6\", \"secondary\": \"#6B7280\", \"bgSecondary\": \"#F9FAFB\", \"primaryDark\": \"#047857\", \"primaryLight\": \"#D1FAE5\", \"textSecondary\": \"#6B7280\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34'),(4,'portal','门户资讯','信息密集、权威、时效','{\"bg\": \"#FFFFFF\", \"text\": \"#0F172A\", \"accent\": \"#0EA5E9\", \"border\": \"#CBD5E1\", \"primary\": \"#1D4ED8\", \"bgSection\": \"#E2E8F0\", \"secondary\": \"#475569\", \"bgSecondary\": \"#F1F5F9\", \"primaryDark\": \"#1E3A8A\", \"primaryLight\": \"#DBEAFE\", \"textSecondary\": \"#475569\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34'),(5,'medical','医疗健康','清洁、专业、信任、安心','{\"bg\": \"#FFFFFF\", \"text\": \"#1E293B\", \"accent\": \"#14B8A6\", \"border\": \"#E2E8F0\", \"primary\": \"#0EA5E9\", \"bgSection\": \"#F0F9FF\", \"secondary\": \"#64748B\", \"bgSecondary\": \"#F8FAFC\", \"primaryDark\": \"#0284C7\", \"primaryLight\": \"#E0F2FE\", \"textSecondary\": \"#64748B\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34'),(6,'education','教育培训','活力、知识、信任、成长','{\"bg\": \"#FFFFFF\", \"text\": \"#1E293B\", \"accent\": \"#F59E0B\", \"border\": \"#E2E8F0\", \"primary\": \"#3B82F6\", \"bgSection\": \"#FFFBEB\", \"secondary\": \"#64748B\", \"bgSecondary\": \"#FEF3C7\", \"primaryDark\": \"#1D4ED8\", \"primaryLight\": \"#DBEAFE\", \"textSecondary\": \"#64748B\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34'),(7,'catering','餐饮美食','食欲、温暖、热闹、品质','{\"bg\": \"#FFFFFF\", \"text\": \"#1F2937\", \"accent\": \"#EF4444\", \"border\": \"#E5E7EB\", \"primary\": \"#F97316\", \"bgSection\": \"#FEF2F2\", \"secondary\": \"#6B7280\", \"bgSecondary\": \"#FFF7ED\", \"primaryDark\": \"#EA580C\", \"primaryLight\": \"#FFEDD5\", \"textSecondary\": \"#6B7280\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34'),(8,'finance','金融理财','稳重、专业、信任、安全','{\"bg\": \"#FFFFFF\", \"text\": \"#0F172A\", \"accent\": \"#D97706\", \"border\": \"#CBD5E1\", \"primary\": \"#1E3A8A\", \"bgSection\": \"#E2E8F0\", \"secondary\": \"#475569\", \"bgSecondary\": \"#F1F5F9\", \"primaryDark\": \"#0F172A\", \"primaryLight\": \"#DBEAFE\", \"textSecondary\": \"#475569\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34'),(9,'technology','科技互联网','创新、前沿、简洁、高效','{\"bg\": \"#FFFFFF\", \"text\": \"#1E293B\", \"accent\": \"#06B6D4\", \"border\": \"#E2E8F0\", \"primary\": \"#6366F1\", \"bgSection\": \"#F1F5F9\", \"secondary\": \"#6B7280\", \"bgSecondary\": \"#F8FAFC\", \"primaryDark\": \"#4338CA\", \"primaryLight\": \"#E0E7FF\", \"textSecondary\": \"#6B7280\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34'),(10,'realestate','房产家居','品质、温馨、稳重、信赖','{\"bg\": \"#FFFFFF\", \"text\": \"#1E293B\", \"accent\": \"#F59E0B\", \"border\": \"#E2E8F0\", \"primary\": \"#0D9488\", \"bgSection\": \"#F8FAFC\", \"secondary\": \"#64748B\", \"bgSecondary\": \"#F0FDFA\", \"primaryDark\": \"#0F766E\", \"primaryLight\": \"#CCFBF1\", \"textSecondary\": \"#64748B\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34');
DROP TABLE IF EXISTS `{prefix}ai_theme_record`;
CREATE TABLE `{prefix}ai_theme_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `theme_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '生成的主题名',
  `source_theme_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '源骨架主题ID，骨架模式时记录复制来源(V2.9.11)',
  `generate_mode` enum('full','skeleton') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'full' COMMENT '生成模式:full从零生成/skeleton基于骨架(V2.9.11)',
  `layout_type` enum('showcase','content') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '布局类型:showcase展示型/content内容型(V2.9.11)',
  `industry_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '行业类型:corporate/ecommerce/blog/portal/medical/education/catering/finance(V2.9.11)',
  `batch_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '批量生成批次ID(S14)',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '用户输入描述',
  `options` json DEFAULT NULL COMMENT '生成选项（风格/色系等）',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态: 0生成中 1待审核 2校验通过 3已发布 4已拒绝 -1生成失败 -2校验失败',
  `prompt_log` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '使用的完整Prompt（审计用）',
  `validate_result` json DEFAULT NULL COMMENT '校验结果JSON',
  `quality_score` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '质量评分(0-100,S14)',
  `quality_detail` json DEFAULT NULL COMMENT '质量评分明细(S14)',
  `files_tree` json DEFAULT NULL COMMENT '生成的文件树结构',
  `version` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '版本号（Phase 3预埋）',
  `token_cost` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Token消耗',
  `cost` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '成本估算',
  `retry_count` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '已重试次数',
  `error_msg` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '错误信息（失败时记录）',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_created` (`status`,`created_at`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_theme_name` (`theme_name`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_industry_type` (`industry_type`),
  KEY `idx_generate_mode` (`generate_mode`,`layout_type`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI主题生成记录';

DROP TABLE IF EXISTS `{prefix}ai_translation_cache`;
CREATE TABLE `{prefix}ai_translation_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_text_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `source_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `source_lang` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'zh-CN',
  `target_lang` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `translated_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `provider` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `quality_score` decimal(3,1) NOT NULL DEFAULT '0.0',
  `hit_count` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_hash_lang` (`source_text_hash`,`source_lang`,`target_lang`),
  KEY `idx_langs` (`source_lang`,`target_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI translation memory';

DROP TABLE IF EXISTS `{prefix}ai_translation_glossary`;
CREATE TABLE `{prefix}ai_translation_glossary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_term` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `target_term` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `source_lang` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'zh-CN',
  `target_lang` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_langs` (`source_lang`,`target_lang`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI translation glossary';

DROP TABLE IF EXISTS `{prefix}ai_workflow`;
CREATE TABLE `{prefix}ai_workflow` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '工作流名称',
  `description` text COMMENT '工作流描述',
  `workflow_type` varchar(30) NOT NULL DEFAULT 'custom' COMMENT '类型: content_gen/translation/quality/recommend/agent_template/custom',
  `workflow_definition` json DEFAULT NULL COMMENT '工作流定义(节点+连线)',
  `trigger_type` varchar(20) NOT NULL DEFAULT 'manual' COMMENT '触发类型: manual/scheduled/event/condition',
  `trigger_config` json DEFAULT NULL COMMENT '触发配置(cron表达式/事件名/条件表达式)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
  `is_template` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为模板(市场发布)',
  `category` varchar(50) NOT NULL DEFAULT '' COMMENT '分类',
  `tags` varchar(255) NOT NULL DEFAULT '' COMMENT '标签(逗号分隔)',
  `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '图标',
  `sort_order` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `exec_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '执行次数',
  `success_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '成功次数',
  `fail_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
  `avg_duration` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '平均耗时(毫秒)',
  `cost_budget` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '成本预算(元)',
  `total_cost` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '累计成本(元)',
  `creator_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建者ID',
  `install_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '市场安装次数',
  `avg_rating` decimal(2,1) NOT NULL DEFAULT '0.0' COMMENT '平均评分',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT '状态: active/draft/archived/pending_audit/rejected',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_workflow_type` (`workflow_type`),
  KEY `idx_trigger_type` (`trigger_type`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_is_template` (`is_template`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='AI工作流定义表';

INSERT INTO `{prefix}ai_workflow` VALUES (1,'内容生成工作流','标题生成→正文写作→AI配图→多语言翻译→质量检测→SEO优化→自动发布','content_gen','{\"edges\": [{\"to\": \"content\", \"from\": \"title\"}, {\"to\": \"image\", \"from\": \"content\"}, {\"to\": \"translate\", \"from\": \"image\"}, {\"to\": \"quality\", \"from\": \"translate\"}, {\"to\": \"seo\", \"from\": \"quality\"}, {\"to\": \"publish\", \"from\": \"seo\"}], \"nodes\": [{\"id\": \"title\", \"type\": \"ai_write\", \"label\": \"生成标题\", \"config\": {\"prompt\": \"根据关键词生成吸引人的标题\"}}, {\"id\": \"content\", \"type\": \"ai_write\", \"label\": \"正文写作\", \"config\": {\"prompt\": \"根据标题生成高质量正文\"}}, {\"id\": \"image\", \"type\": \"ai_image\", \"label\": \"AI配图\", \"config\": {\"style\": \"auto\"}}, {\"id\": \"translate\", \"type\": \"ai_translate\", \"label\": \"多语言翻译\", \"config\": {\"target_langs\": [\"en\", \"ja\"]}}, {\"id\": \"quality\", \"type\": \"ai_qa\", \"label\": \"质量检测\", \"config\": {\"threshold\": 70}}, {\"id\": \"seo\", \"type\": \"ai_seo\", \"label\": \"SEO优化\", \"config\": {\"auto_fix\": true}}, {\"id\": \"publish\", \"type\": \"publish\", \"label\": \"自动发布\", \"config\": {\"channel\": \"default\"}}]}','manual',NULL,1,1,'内容生产','','',1,0,0,0,0,0.00,0.00,0,0,0.0,'active','2026-07-15 13:32:26','2026-07-15 13:32:26'),(2,'翻译工作流','内容提取→多语言翻译→术语统一→格式校验→发布','translation','{\"edges\": [{\"to\": \"translate\", \"from\": \"extract\"}, {\"to\": \"glossary\", \"from\": \"translate\"}, {\"to\": \"format\", \"from\": \"glossary\"}, {\"to\": \"publish\", \"from\": \"format\"}], \"nodes\": [{\"id\": \"extract\", \"type\": \"ai_write\", \"label\": \"内容提取\", \"config\": {}}, {\"id\": \"translate\", \"type\": \"ai_translate\", \"label\": \"多语言翻译\", \"config\": {\"target_langs\": [\"en\", \"ja\", \"ko\"]}}, {\"id\": \"glossary\", \"type\": \"ai_qa\", \"label\": \"术语统一\", \"config\": {}}, {\"id\": \"format\", \"type\": \"ai_qa\", \"label\": \"格式校验\", \"config\": {}}, {\"id\": \"publish\", \"type\": \"publish\", \"label\": \"发布\", \"config\": {}}]}','manual',NULL,1,1,'翻译','','',2,0,0,0,0,0.00,0.00,0,0,0.0,'active','2026-07-15 13:32:26','2026-07-15 13:32:26'),(3,'质量检测工作流','内容评分→问题识别→AI修复→复检→报告生成','quality','{\"edges\": [{\"to\": \"identify\", \"from\": \"score\"}, {\"to\": \"fix\", \"from\": \"identify\"}, {\"to\": \"recheck\", \"from\": \"fix\"}, {\"to\": \"report\", \"from\": \"recheck\"}], \"nodes\": [{\"id\": \"score\", \"type\": \"ai_qa\", \"label\": \"内容评分\", \"config\": {\"dimensions\": [\"completeness\", \"readability\", \"seo\", \"image_match\", \"tag_accuracy\"]}}, {\"id\": \"identify\", \"type\": \"ai_qa\", \"label\": \"问题识别\", \"config\": {}}, {\"id\": \"fix\", \"type\": \"ai_write\", \"label\": \"AI修复\", \"config\": {\"max_retries\": 3}}, {\"id\": \"recheck\", \"type\": \"ai_qa\", \"label\": \"复检\", \"config\": {\"threshold\": 80}}, {\"id\": \"report\", \"type\": \"ai_write\", \"label\": \"报告生成\", \"config\": {\"format\": \"markdown\"}}]}','manual',NULL,1,1,'质量','','',3,0,0,0,0,0.00,0.00,0,0,0.0,'active','2026-07-15 13:32:26','2026-07-15 13:32:26'),(4,'推荐优化工作流','行为分析→标签匹配→内容推荐→效果统计','recommend','{\"edges\": [{\"to\": \"match\", \"from\": \"analyze\"}, {\"to\": \"recommend\", \"from\": \"match\"}, {\"to\": \"stats\", \"from\": \"recommend\"}], \"nodes\": [{\"id\": \"analyze\", \"type\": \"ai_recommend\", \"label\": \"行为分析\", \"config\": {}}, {\"id\": \"match\", \"type\": \"ai_recommend\", \"label\": \"标签匹配\", \"config\": {\"strategy\": \"tfidf\"}}, {\"id\": \"recommend\", \"type\": \"ai_recommend\", \"label\": \"内容推荐\", \"config\": {\"count\": 10}}, {\"id\": \"stats\", \"type\": \"ai_write\", \"label\": \"效果统计\", \"config\": {}}]}','manual',NULL,1,1,'推荐','','',4,0,0,0,0,0.00,0.00,0,0,0.0,'active','2026-07-15 13:32:26','2026-07-15 13:32:26');
DROP TABLE IF EXISTS `{prefix}ai_workflow_exec`;
CREATE TABLE `{prefix}ai_workflow_exec` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '工作流ID',
  `exec_status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT '状态: pending/running/success/failed/cancelled/paused',
  `trigger_type` varchar(20) NOT NULL DEFAULT 'manual' COMMENT '触发类型',
  `trigger_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '触发者ID',
  `target_ids` json DEFAULT NULL COMMENT '目标内容ID列表',
  `target_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '目标数量',
  `current_node` varchar(50) NOT NULL DEFAULT '' COMMENT '当前节点ID',
  `node_results` json DEFAULT NULL COMMENT '各节点执行结果',
  `total_duration` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '总耗时(毫秒)',
  `ai_call_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'AI调用次数',
  `ai_call_cost` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT 'AI调用成本(元)',
  `error_message` text COMMENT '错误信息',
  `started_at` datetime DEFAULT NULL COMMENT '开始时间',
  `completed_at` datetime DEFAULT NULL COMMENT '完成时间',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_workflow_id` (`workflow_id`),
  KEY `idx_exec_status` (`exec_status`),
  KEY `idx_trigger_by` (`trigger_by`),
  KEY `idx_started_at` (`started_at`),
  KEY `idx_workflow_status` (`workflow_id`,`exec_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='AI工作流执行记录表';

DROP TABLE IF EXISTS `{prefix}ai_workflow_review`;
CREATE TABLE `{prefix}ai_workflow_review` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}api_call_log`;
CREATE TABLE `{prefix}api_call_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}api_doc_changelog`;
CREATE TABLE `{prefix}api_doc_changelog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}api_key`;
CREATE TABLE `{prefix}api_key` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密钥名称',
  `api_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'API密钥',
  `api_secret` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'API密钥密钥',
  `scopes` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '权限范围(JSON数组)',
  `ip_whitelist` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP白名单(逗号分隔)',
  `rate_limit` int(10) unsigned NOT NULL DEFAULT '100' COMMENT '每分钟限制次数',
  `is_active` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否启用',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_api_key` (`api_key`),
  KEY `idx_status` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='API密钥表';

DROP TABLE IF EXISTS `{prefix}api_log`;
CREATE TABLE `{prefix}api_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `api_key_id` int(10) unsigned DEFAULT NULL COMMENT '密钥ID',
  `endpoint` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '接口路径',
  `method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GET' COMMENT '请求方法',
  `ip_address` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '请求IP',
  `status_code` int(11) NOT NULL DEFAULT '0' COMMENT '状态码',
  `duration_ms` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '耗时(毫秒)',
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'UA标识',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_key` (`api_key_id`),
  KEY `idx_endpoint` (`endpoint`),
  KEY `idx_status` (`status_code`),
  KEY `idx_create` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='API调用日志表';

DROP TABLE IF EXISTS `{prefix}api_token`;
CREATE TABLE `{prefix}api_token` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '令牌名称',
  `auth_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bearer' COMMENT '认证类型:bearer/hmac',
  `token_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '令牌哈希(sha256)',
  `secret_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'HMAC密钥(仅auth_type=hmac时有效)',
  `scopes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '*' COMMENT '权限范围(*/content.read/content.write等)',
  `rate_limit` int(11) NOT NULL DEFAULT '60' COMMENT '速率限制(次/小时)',
  `last_used_time` int(10) unsigned NOT NULL DEFAULT '0',
  `expire_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '过期时间(0永不过期)',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token_hash` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='API令牌表';

DROP TABLE IF EXISTS `{prefix}attachment`;
CREATE TABLE `{prefix}attachment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}audit_log`;
CREATE TABLE `{prefix}audit_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}auth_group`;
CREATE TABLE `{prefix}auth_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '组名',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `rules` text COLLATE utf8mb4_unicode_ci COMMENT '权限规则ID列表(逗号分隔)',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色组表';

INSERT INTO `{prefix}auth_group` VALUES (1,'超级管理员','拥有全部权限',1,'*',1784691333,1784691333);
DROP TABLE IF EXISTS `{prefix}auth_rule`;
CREATE TABLE `{prefix}auth_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '规则标识',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'menu' COMMENT 'menu/button/api',
  `condition` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `sort` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='权限规则表';

DROP TABLE IF EXISTS `{prefix}backup_log`;
CREATE TABLE `{prefix}backup_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `backup_type` varchar(20) NOT NULL DEFAULT '' COMMENT '类型: database/files',
  `file_name` varchar(255) NOT NULL DEFAULT '' COMMENT '备份文件名',
  `file_size` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小(bytes)',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态: 0=进行中 1=成功 2=失败',
  `error_msg` varchar(500) NOT NULL DEFAULT '' COMMENT '错误信息',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `backup_type` (`backup_type`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='备份操作日志';

DROP TABLE IF EXISTS `{prefix}backup_record`;
CREATE TABLE `{prefix}backup_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_name` varchar(100) NOT NULL COMMENT '备份名称',
  `backup_type` varchar(20) DEFAULT 'full' COMMENT '备份类型(full/incremental/differential)',
  `backup_content` varchar(50) NOT NULL COMMENT '备份内容(database/files/config/code/logs/all)',
  `backup_size` bigint(20) DEFAULT '0' COMMENT '备份大小(字节)',
  `backup_path` varchar(500) NOT NULL COMMENT '备份存储路径',
  `storage_type` varchar(20) DEFAULT 'local' COMMENT '存储类型(local/remote/ftp/s3/oss/cos)',
  `storage_config` json DEFAULT NULL COMMENT '存储配置(JSON)',
  `encryption_type` varchar(20) DEFAULT 'none' COMMENT '加密类型(none/aes256)',
  `checksum` varchar(64) DEFAULT '' COMMENT '校验和(SHA256)',
  `compression_type` varchar(20) DEFAULT 'gzip' COMMENT '压缩类型(none/gzip/zstd)',
  `is_auto` tinyint(4) DEFAULT '0' COMMENT '是否为自动备份:1是0否',
  `status` varchar(20) DEFAULT 'pending' COMMENT '状态(pending/running/completed/failed/verifying/verified)',
  `error_message` text COMMENT '错误信息',
  `verified_at` datetime DEFAULT NULL COMMENT '验证时间',
  `verification_result` tinyint(4) DEFAULT '0' COMMENT '验证结果:1通过0未通过',
  `monitor_alert_id` int(11) DEFAULT '0' COMMENT '监控告警ID',
  `is_monitor_triggered` tinyint(4) DEFAULT '0' COMMENT '是否监控触发',
  `retention_days` int(11) DEFAULT '30' COMMENT '保留天数',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`backup_type`),
  KEY `idx_content` (`backup_content`),
  KEY `idx_status` (`status`),
  KEY `idx_verified` (`verification_result`),
  KEY `idx_auto` (`is_auto`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='备份记录表';

DROP TABLE IF EXISTS `{prefix}banner`;
CREATE TABLE `{prefix}banner` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标题',
  `image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图片地址',
  `link` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '链接地址',
  `target` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '_self' COMMENT '打开方式:_self/_blank',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='轮播图表';

DROP TABLE IF EXISTS `{prefix}cache_stats`;
CREATE TABLE `{prefix}cache_stats` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stat_date` date NOT NULL COMMENT '统计日期',
  `cache_key` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '缓存键名/分组',
  `hit_count` bigint(20) NOT NULL DEFAULT '0' COMMENT '命中次数',
  `miss_count` bigint(20) NOT NULL DEFAULT '0' COMMENT '未命中次数',
  `hit_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '命中率(%)',
  `size_bytes` bigint(20) NOT NULL DEFAULT '0' COMMENT '缓存大小(字节)',
  `level` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '缓存级别: L1/L2/L3',
  `prewarm_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '预热状态: 0=未预热 1=已预热',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_date_key` (`stat_date`,`cache_key`),
  KEY `idx_stat_date` (`stat_date`),
  KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='V2.9.35 缓存统计';

DROP TABLE IF EXISTS `{prefix}cate`;
CREATE TABLE `{prefix}cate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '分类类型:1产品/2案例/3新闻/4下载/5招聘/6单页',
  `model_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容模型ID(0=通用分类)',
  `content_model_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '内容模型code(留空=通用/article)',
  `list_template` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '自定义列表模板(留空=使用模型默认)',
  `detail_template` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '自定义详情模板(留空=使用模型默认)',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `default_style` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'formal' COMMENT '默认写作风格: formal/relaxed/professional/warm',
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_type` (`type`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_content_model_code` (`content_model_code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分类表';

INSERT INTO `{prefix}cate` VALUES (1,'产品中心',1,0,'','','',0,1,1,1776933035,1776933035,'formal'),(2,'成功案例',2,0,'','','',0,2,1,1776933035,1776933035,'formal'),(3,'新闻动态',3,0,'','','',0,3,1,1776933035,1776933035,'formal'),(4,'资料下载',4,0,'','','',0,4,1,1776933035,1776933035,'formal'),(5,'人才招聘',5,0,'','','',0,5,1,1776933035,1776933035,'formal');
DROP TABLE IF EXISTS `{prefix}category`;
CREATE TABLE `{prefix}category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}channel_platform`;
CREATE TABLE `{prefix}channel_platform` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform_type` varchar(20) NOT NULL COMMENT '平台类型(toutiao/zhihu/weibo)',
  `platform_name` varchar(100) NOT NULL COMMENT '平台账号名称',
  `platform_uid` varchar(100) DEFAULT '' COMMENT '平台用户ID',
  `access_token` text COMMENT '访问Token',
  `refresh_token` text COMMENT '刷新Token',
  `token_expire_time` int(10) unsigned DEFAULT '0' COMMENT '过期时间戳',
  `config` json DEFAULT NULL COMMENT '平台配置(JSON)',
  `is_default` tinyint(4) DEFAULT '0' COMMENT '是否默认账号',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态:1启用0禁用',
  `create_time` int(10) unsigned DEFAULT '0',
  `update_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_platform` (`platform_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='第三方平台账号配置表';

DROP TABLE IF EXISTS `{prefix}channel_wechat`;
CREATE TABLE `{prefix}channel_wechat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_id` varchar(50) NOT NULL COMMENT '微信公众号AppID',
  `app_secret` varchar(100) NOT NULL COMMENT 'AppSecret',
  `account_name` varchar(100) NOT NULL COMMENT '公众号名称',
  `account_type` varchar(20) DEFAULT 'subscription' COMMENT '类型(subscription/service)',
  `access_token` text COMMENT '当前access_token',
  `token_expire_time` int(10) unsigned DEFAULT '0' COMMENT '过期时间戳',
  `is_default` tinyint(4) DEFAULT '0' COMMENT '是否默认公众号',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态:1启用0禁用',
  `create_time` int(10) unsigned DEFAULT '0',
  `update_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_appid` (`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='微信公众号配置表';

DROP TABLE IF EXISTS `{prefix}collect_log`;
CREATE TABLE `{prefix}collect_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_id` int(11) NOT NULL COMMENT '采集源ID',
  `title` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '采集标题',
  `url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '采集URL',
  `url_hash` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'URL MD5去重',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态: 0新采集 1已导入 2跳过(重复) 3失败',
  `content_id` int(11) DEFAULT '0' COMMENT '导入后的内容ID',
  `error_msg` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '错误信息',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_url_hash` (`url_hash`),
  KEY `idx_source` (`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='采集日志表';

DROP TABLE IF EXISTS `{prefix}collect_source`;
CREATE TABLE `{prefix}collect_source` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '来源名称',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rss' COMMENT '类型: rss/webpage',
  `url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '源URL',
  `rules` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '采集规则(JSON: title_selector/content_selector等)',
  `cate_id` int(11) DEFAULT '0' COMMENT '默认分类ID',
  `interval_minutes` int(11) DEFAULT '60' COMMENT '采集间隔(分钟)',
  `is_enabled` tinyint(4) DEFAULT '0' COMMENT '启用状态',
  `last_collect_time` int(10) unsigned DEFAULT '0' COMMENT '最后采集时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='采集源表';

DROP TABLE IF EXISTS `{prefix}comment`;
CREATE TABLE `{prefix}comment` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `member_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID(0为游客)',
  `nickname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '邮箱',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '评论内容',
  `parent_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '父评论ID(0为顶级)',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态:0待审/1已通过/-1已拒绝',
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'IP地址',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_content_status` (`content_id`,`status`),
  KEY `idx_member` (`member_id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_parent_status` (`parent_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='评论表';

DROP TABLE IF EXISTS `{prefix}compliance_report`;
CREATE TABLE `{prefix}compliance_report` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}config`;
CREATE TABLE `{prefix}config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `group` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分组',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置名',
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '配置值',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text' COMMENT '类型:text/textarea/number/switch/select',
  `options` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '选项(JSON,select/switch用)',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '说明',
  `mini_config` json DEFAULT NULL COMMENT '小程序配置',
  `app_market_config` json DEFAULT NULL COMMENT '应用市场配置',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_group` (`group`)
) ENGINE=InnoDB AUTO_INCREMENT=359 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

INSERT INTO `{prefix}config` VALUES (1,'basic','site_name','AI-CMS v2.9.40','text','',1,'网站名称',NULL,NULL),(2,'basic','site_keywords','AI,CMS,内容管理','text','',2,'网站关键词',NULL,NULL),(3,'basic','site_description','AI驱动的企业信息管理系统','textarea','',3,'网站描述',NULL,NULL),(4,'basic','site_logo','/assets/images/logo_ico.png','text','',4,'网站Logo',NULL,NULL),(5,'basic','site_icp','','text','',5,'ICP备案号',NULL,NULL),(6,'upload','upload_max_size','10','number','',1,'上传大小限制(MB)',NULL,NULL),(7,'upload','upload_image_ext','jpg,jpeg,png,gif,webp,svg','text','',2,'允许的图片格式',NULL,NULL),(8,'ai','ai_enabled','1','switch','',1,'启用AI功能',NULL,NULL),(9,'ai','ai_default_model','deepseek-chat','text','',2,'默认AI模型',NULL,NULL),(10,'upload','upload_video_ext','mp4,webm,ogg','text','',3,'允许的视频格式',NULL,NULL),(11,'upload','upload_file_ext','pdf,doc,docx,xls,xlsx,zip,rar','text','',4,'允许的文件格式',NULL,NULL),(12,'basic','site_copyright','','text','',6,'版权信息',NULL,NULL),(13,'basic','site_stat_code','','textarea','',7,'统计代码',NULL,NULL),(14,'seo','seo_sitemap_enabled','1','switch','',1,'启用Sitemap自动生成',NULL,NULL),(15,'seo','seo_sitemap_frequency','daily','select','',2,'Sitemap更新频率',NULL,NULL),(16,'seo','seo_robots_txt','User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /member/\nDisallow: /api/\n\nSitemap: /sitemap.xml.html\n\n# AI-CMS v2.9.2','textarea','',3,'robots.txt内容',NULL,NULL),(17,'comment','comment_enabled','1','switch','',1,'启用评论功能',NULL,NULL),(18,'comment','comment_auto_approve','0','switch','',2,'评论自动审核通过',NULL,NULL),(19,'comment','comment_captcha','1','switch','',3,'评论验证码',NULL,NULL),(20,'notification','notification_enabled','1','switch','',1,'启用消息通知',NULL,NULL),(21,'member','member_register_enabled','1','switch','',1,'启用前台注册',NULL,NULL),(22,'member','member_oauth_gitee_enabled','1','switch','',2,'启用Gitee登录',NULL,NULL),(23,'ad','ad_enabled','1','switch','',1,'启用广告系统',NULL,NULL),(27,'points','points_comment','2','text','',0,'发表评论积分',NULL,NULL),(28,'points','points_comment_liked','1','switch','',0,'评论被点赞积分',NULL,NULL),(29,'points','points_content_liked','3','text','',0,'内容被点赞积分',NULL,NULL),(30,'points','points_content_favorited','5','text','',0,'内容被收藏积分',NULL,NULL),(31,'points','points_daily_login','1','switch','',0,'每日首次登录积分',NULL,NULL),(32,'points','points_register','50','text','',0,'注册奖励积分',NULL,NULL),(33,'points','points_comment_liked_daily_limit','10','text','',0,'评论被点赞每日上限',NULL,NULL),(34,'points','points_content_liked_daily_limit','20','text','',0,'内容被点赞每日上限',NULL,NULL),(35,'points','points_content_favorited_daily_limit','10','text','',0,'内容被收藏每日上限',NULL,NULL),(36,'oauth','wechat_open_appid','','text','',0,'微信开放平台AppID（扫码登录）',NULL,NULL),(37,'oauth','wechat_open_secret','','text','',0,'微信开放平台AppSecret',NULL,NULL),(38,'oauth','qq_appid','','text','',0,'QQ互联AppID',NULL,NULL),(39,'oauth','qq_appkey','','text','',0,'QQ互联AppKey',NULL,NULL),(40,'site','home_template','default','text','',0,'前台模板选择',NULL,NULL),(41,'site','frontend_theme','default','string','',50,'前台主题',NULL,NULL),(42,'site','admin_theme','default','string','',51,'后台主题',NULL,NULL),(43,'security','encrypt_cipher','AES-256-CBC','text','',0,'加密算法',NULL,NULL),(44,'security','captcha_type','math','text','',0,'验证码类型',NULL,NULL),(45,'security','captcha_enabled_forms','','text','',0,'需要验证码的表单code(逗号分隔)',NULL,NULL),(46,'payment','wechat_pay_appid','','text','',0,'微信支付AppID',NULL,NULL),(47,'payment','wechat_pay_mchid','','text','',0,'微信支付商户号',NULL,NULL),(48,'payment','wechat_pay_v3_key','','text','',0,'APIv3密钥',NULL,NULL),(49,'payment','wechat_pay_serial_no','','text','',0,'证书序列号',NULL,NULL),(50,'payment','wechat_pay_notify_url','/api/payment/wechat/notify','text','',0,'回调地址',NULL,NULL),(51,'payment','wechat_pay_enabled','0','switch','',0,'微信支付是否启用',NULL,NULL),(52,'oauth','qq_redirect','/oauth/qq/callback','text','',0,'QQ回调地址',NULL,NULL),(53,'oauth','oauth_wechat_enabled','0','switch','',0,'微信登录启用',NULL,NULL),(54,'oauth','oauth_qq_enabled','0','switch','',0,'QQ登录启用',NULL,NULL),(55,'email','smtp_host','','text','',0,'SMTP服务器',NULL,NULL),(56,'email','smtp_port','465','text','',0,'SMTP端口',NULL,NULL),(57,'email','smtp_username','','text','',0,'SMTP账号',NULL,NULL),(58,'email','smtp_password','','text','',0,'SMTP密码',NULL,NULL),(59,'email','smtp_from_email','','text','',0,'发件人邮箱',NULL,NULL),(60,'email','smtp_from_name','','text','',0,'发件人名称',NULL,NULL),(61,'email','smtp_ssl','1','switch','',0,'是否SSL',NULL,NULL),(62,'ai','ai_batch_max_count','10','text','',0,'批量生成最大篇数',NULL,NULL),(63,'ai','ai_batch_default_model','0','switch','',0,'批量生成默认模型(0=系统默认)',NULL,NULL),(64,'ai','ai_long_info_threshold','2000','text','',0,'长文阈值(字数)',NULL,NULL),(65,'member','member_register_audit','0','switch','',1,'会员注册需管理员审核',NULL,NULL),(69,'system','search_engine','mysql','select','',10,'搜索引擎',NULL,NULL),(130,'member','vip_free_read_mode','0','select','',30,'VIP免费阅读范围: 0=不免费 1=全部免费',NULL,NULL),(131,'points','points_invite_register','50','number','',5,'邀请注册奖励积分',NULL,NULL),(132,'points','points_invite_signin','20','number','',6,'被邀请人首次签到奖励邀请人积分',NULL,NULL),(133,'points','points_invite_pay','100','number','',7,'被邀请人首次付费奖励邀请人积分',NULL,NULL),(135,'social','wechat_share_appid','','text','',1,'微信JS-SDK AppID',NULL,NULL),(136,'social','wechat_share_secret','','password','',2,'微信JS-SDK Secret',NULL,NULL),(137,'social','social_share_enabled','1','switch','',3,'是否启用社交分享',NULL,NULL),(138,'ai','image_provider','tongyi_wanxiang','select','',10,'AI配图Provider',NULL,NULL),(139,'ai','image_api_key','','password','',11,'AI配图API Key',NULL,NULL),(140,'ai','image_default_count','1','number','',12,'默认生成配图数(1-5)',NULL,NULL),(141,'ai','image_default_style','realistic','select','',13,'默认配图风格',NULL,NULL),(142,'ai','image_timeout','15','number','',14,'配图API超时(秒)',NULL,NULL),(143,'ai','ai_stat_enabled','1','switch','',15,'是否启用AI生成统计',NULL,NULL),(144,'ai','ai_stat_retention_days','30','number','',16,'AI统计保留天数',NULL,NULL),(145,'security','captcha_driver','local','select','',10,'验证码驱动',NULL,NULL),(146,'security','captcha_tencent_appid','','text','',11,'腾讯验证码AppID',NULL,NULL),(147,'security','captcha_tencent_secret','','password','',12,'腾讯验证码Secret',NULL,NULL),(148,'system','cdn_enabled','0','switch','',20,'是否启用CDN',NULL,NULL),(149,'system','cdn_domain','','text','',21,'CDN域名(如 https://cdn.example.com)',NULL,NULL),(150,'points','points_signin','5','number','',1,'每日签到基础积分',NULL,NULL),(151,'points','points_signin_3days','10','number','',2,'连续签到3天额外奖励',NULL,NULL),(152,'points','points_signin_7days','30','number','',3,'连续签到7天额外奖励',NULL,NULL),(153,'points','points_consume_ratio','0','number','',4,'消费返积分比例(0=不返, 0.1=返10%)',NULL,NULL),(154,'coupon','coupon_enabled','1','switch','',1,'是否启用优惠券系统',NULL,NULL),(155,'coupon','coupon_newbie_enabled','1','switch','',2,'是否启用新人券',NULL,NULL),(156,'coupon','coupon_newbie_days','7','number','',3,'注册后多少天内可领新人券',NULL,NULL),(157,'coupon','coupon_newbie_template_id','0','number','',4,'新人券模板ID',NULL,NULL),(158,'coupon','coupon_refund_return','1','switch','',5,'全额退款时是否退还优惠券',NULL,NULL),(159,'rating','rating_enabled','1','switch','',1,'是否启用评价评分系统',NULL,NULL),(160,'rating','rating_require_purchase','1','switch','',2,'是否要求购买后才能评价',NULL,NULL),(161,'rating','rating_anonymous_allowed','1','switch','',3,'是否允许匿名评价',NULL,NULL),(162,'rating','rating_auto_approve','0','switch','',4,'是否自动审核通过评价',NULL,NULL),(163,'rating','rating_media_max','5','number','',5,'评价最多上传图片数',NULL,NULL),(171,'ai','ai_image_default_provider','tongyi_wanxiang','select','',21,'默认AI配图Provider(tongyi_wanxiang/flux/dalle)',NULL,NULL),(172,'ai','ai_image_fallback_provider','flux','select','',22,'备用AI配图Provider',NULL,NULL),(173,'ai','ai_image_flux_enabled','0','switch','',23,'是否启用FLUX配图',NULL,NULL),(174,'ai','ai_image_flux_api_key','','text','',24,'FLUX API Key',NULL,NULL),(175,'ai','ai_image_flux_model','flux-pro','text','',25,'FLUX模型名称',NULL,NULL),(176,'ai','ai_image_dalle_enabled','0','switch','',26,'是否启用DALL-E配图',NULL,NULL),(177,'ai','ai_image_dalle_api_key','','text','',27,'DALL-E API Key',NULL,NULL),(178,'ai','ai_image_dalle_model','dall-e-3','text','',28,'DALL-E模型名称',NULL,NULL),(215,'invite','invite_reward_register','10','number','',1,'邀请注册奖励积分',NULL,NULL),(216,'invite','invite_reward_signin','20','number','',2,'邀请签到奖励积分',NULL,NULL),(217,'invite','invite_reward_pay','50','number','',3,'邀请付费奖励积分',NULL,NULL),(218,'invite','invite_enabled','1','switch','',4,'是否启用邀请奖励系统',NULL,NULL),(219,'coupon','shipping_coupon_type','free_shipping','text','用于CouponTemplate识别免邮券',50,'免邮券类型标识',NULL,NULL),(220,'shipping','shipping_free_threshold','0','number','订单金额超过此值免邮，0表示全部免邮',10,'免邮阈值(元)',NULL,NULL),(221,'shipping','shipping_default_fee','10','number','未触发免邮时的默认运费',20,'默认运费(元)',NULL,NULL),(222,'basic','language_switcher_enabled','0','switch','关闭后前台顶部不显示语言切换下拉菜单',95,'前台显示语言切换器',NULL,NULL),(223,'basic','language_sitewide','0','switch','开启后所有内容按语言隔离，仅显示当前语言的内容',96,'多语言全站生效',NULL,NULL),(224,'basic','logo_icon_only','1','switch','',0,'仅使用Logo图标(勾选:仅替换图标保留文字/不勾选:完整替换)',NULL,NULL),(225,'basic','logo_name','','text','',0,'后台品牌名称(留空则使用默认名称)',NULL,NULL),(226,'publish','publish_auto_sync_enabled','0','switch','',1,'内容发布后自动同步到已启用平台',NULL,NULL),(227,'member','member_auto_downgrade_grace_days','7','number','',5,'自动降级缓冲期天数(0=直接降级)',NULL,NULL),(228,'system','backup_keep_count','10','number','',30,'自动备份保留最近N个',NULL,NULL),(229,'system','app_version','2.9.40','text','',0,'当前系统版本号',NULL,NULL),(230,'content','content_quality_check_enabled','1','switch','',18,'启用AI内容质量检测(可读性/SEO/敏感词)',NULL,NULL),(231,'content','sensitive_words_check_enabled','1','switch','',19,'启用敏感词过滤检测',NULL,NULL),(232,'pay','pay_enabled','0','switch','',1,'启用支付功能(需先配置支付参数)',NULL,NULL),(233,'pay','pay_wechat_enabled','0','switch','',2,'启用微信支付',NULL,NULL),(234,'pay','pay_alipay_enabled','0','switch','',3,'启用支付宝支付',NULL,NULL),(235,'plugin','license_verify_enabled','0','switch','',15,'启用许可证远程验证(插件商店)',NULL,NULL),(236,'content','paid_content_enabled','0','switch','',21,'启用付费阅读功能',NULL,NULL),(238,'security','csp_mode','report_only','select','report_only=仅报告,enforce=强制拦截',1,'CSP策略模式',NULL,NULL),(239,'security','csrf_front_enabled','1','switch','',2,'启用后前台写操作需携带Token',NULL,NULL),(240,'performance','cache_warm_enabled','1','switch','',3,'内容变更后自动清除相关缓存',NULL,NULL),(241,'security','xss_log_enabled','1','switch','',4,'响应中包含潜在XSS特征时记录日志',NULL,NULL),(242,'system','version','V2.9.40','text','',0,'AI-CMS版本号',NULL,NULL),(273,'ai','image_daily_limit','50','number','',80,'AI配图每日限额',NULL,NULL),(274,'ai','image_max_batch','5','number','',81,'AI批量配图最大数量',NULL,NULL),(276,'ai','writing_styles','{\"formal\":{\"name\":\"正式风格\",\"system_prompt\":\"你是一位专业的内容编辑。请使用正式、严谨、权威的语言风格撰写内容。\"},\"casual\":{\"name\":\"轻松风格\",\"system_prompt\":\"你是一位亲切的内容创作者。请使用轻松、自然、口语化的语言风格撰写内容。\"},\"professional\":{\"name\":\"专业风格\",\"system_prompt\":\"你是一位行业专家。请使用专业、深度、有洞察力的语言风格撰写内容。\"},\"humorous\":{\"name\":\"幽默风格\",\"system_prompt\":\"你是一位幽默风趣的作家。请使用幽默、有趣、富有创意的语言风格撰写内容。\"},\"concise\":{\"name\":\"简洁风格\",\"system_prompt\":\"你是一位高效的内容编辑。请使用简洁、精炼、直切要点的语言风格撰写内容。\"}}','json','',83,'AI写作风格配置',NULL,NULL),(277,'ai','ai_theme_generate_daily_limit','50','number','',30,'每日AI主题生成上限次数',NULL,NULL),(278,'ai','ai_theme_generate_timeout','300','number','',31,'AI主题生成单次超时时间（秒）',NULL,NULL),(279,'ai','ai_theme_generate_max_tokens','8192','number','',32,'AI主题生成LLM最大Token数',NULL,NULL),(280,'ai','ai_theme_generate_temperature','0.5','number','',33,'AI主题生成LLM温度参数(0-1)',NULL,NULL),(282,'ai','ai_theme_chat_max_rounds','10','number','',40,'AI主题对话最大轮数',NULL,NULL),(283,'ai','ai_theme_chat_timeout','60','number','',41,'AI主题对话同步调用超时（秒）',NULL,NULL),(284,'ai','ai_theme_chat_context_budget','15000','number','',42,'AI主题对话上下文Token预算',NULL,NULL),(286,'ai','ai_image_default_size','1024x1024','select','<option value=\"1024x1024\">1:1 正方形 (1024x1024)</option><option value=\"1024x576\">16:9 宽屏 (1024x576)</option><option value=\"1024x768\">4:3 标准 (1024x768)</option><option value=\"768x1024\">3:4 竖屏 (768x1024)</option>',0,'AI配图默认尺寸',NULL,NULL),(287,'ai','ai_image_default_style','realistic','select','<option value=\"realistic\">写实</option><option value=\"illustration\">插画</option><option value=\"watercolor\">水彩</option><option value=\"3d_render\">3D</option><option value=\"pixel_art\">像素</option>',0,'AI配图默认风格',NULL,NULL),(288,'ai','ai_image_candidate_count','4','select','<option value=\"1\">1张</option><option value=\"2\">2张</option><option value=\"4\">4张</option>',0,'AI配图候选图数量',NULL,NULL),(289,'ai','ai_image_auto_on_publish','0','switch','',0,'发布时自动AI配图(开启后发布内容时自动为无封面文章生成封面图)',NULL,NULL),(291,'security','captcha_enabled','1','switch','',0,'是否启用验证码',NULL,NULL),(292,'security','captcha_login','1','switch','',0,'登录时需要验证码',NULL,NULL),(293,'security','captcha_register','1','switch','',0,'注册时需要验证码',NULL,NULL),(294,'security','captcha_comment','0','switch','',0,'评论时需要验证码',NULL,NULL),(295,'security','captcha_form','0','switch','',0,'表单提交时需要验证码',NULL,NULL),(296,'security','captcha_fail_limit','3','number','',0,'验证码失败重试次数限制',NULL,NULL),(297,'points','points_shop_enabled','1','switch','',10,'积分商城开关',NULL,NULL),(300,'push','push_global_timeout','60','text','',0,'æŽ¨é€å…¨å±€è¶…æ—¶(ç§’)',NULL,NULL),(301,'notification','notify_default_settings','{\"system\":1,\"review\":1,\"publish\":1,\"comment_reply\":1,\"content_approve\":1,\"content_reject\":1,\"reward_receive\":1,\"level_upgrade\":1,\"level_downgrade\":1,\"level_grace_warning\":1}','text','',0,'通知默认设置(JSON)',NULL,NULL),(306,'content','content_model_enabled','1','switch','',0,'启用内容模型差异化',NULL,NULL),(307,'template','template_store_category_enabled','1','switch','',0,'æ¨¡æ¿å•†åº—å¯ç”¨åˆ†ç±»',NULL,NULL),(308,'content','content_model_seo_enabled','1','switch','',0,'å†…å®¹æ¨¡åž‹SEOä¼˜åŒ–',NULL,NULL),(309,'content','content_model_relation_enabled','1','switch','',0,'å†…å®¹æ¨¡åž‹å…³ç³»å›¾è°±',NULL,NULL),(310,'content','content_model_template_map_enabled','1','switch','',0,'å†…å®¹æ¨¡åž‹æ¨¡æ¿æ˜ å°„',NULL,NULL),(311,'system','sse_max_connections_per_ip','5','text','',0,'SSE每IP最大连接数',NULL,NULL),(312,'system','sse_max_connections_per_user','3','text','',0,'SSE每用户最大连接数',NULL,NULL),(313,'system','sse_connection_timeout','1800','text','',0,'SSE连接超时时间(秒)',NULL,NULL),(314,'system','sse_heartbeat_interval','30','text','',0,'SSE心跳间隔(秒)',NULL,NULL),(315,'system','sse_message_ttl','3600','text','',0,'SSE消息存活时间(秒)',NULL,NULL),(316,'system','sse_offline_message_limit','100','text','',0,'SSE离线消息保留条数',NULL,NULL),(317,'template','template_store_payment_enabled','1','switch','',0,'æ¨¡æ¿å•†åº—å¯ç”¨æ”¯ä»˜',NULL,NULL),(318,'template','template_store_alipay_enabled','0','switch','',0,'æ¨¡æ¿å•†åº—å¯ç”¨æ”¯ä»˜å®',NULL,NULL),(319,'template','template_store_license_enabled','1','switch','',0,'æ¨¡æ¿å•†åº—å¯ç”¨æŽˆæƒ',NULL,NULL),(320,'system','rss_enabled','1','switch','',0,'启用RSS订阅',NULL,NULL),(321,'system','rss_cache_ttl','600','text','',0,'RSS缓存时间(秒)',NULL,NULL),(322,'oauth','oauth_github_enabled','0','switch','',0,'启用GitHub登录',NULL,NULL),(323,'email','email_service_unified','1','switch','',0,'启用统一邮件服务',NULL,NULL),(324,'template','template_store_refund_enabled','1','switch','',0,'æ¨¡æ¿å•†åº—å¯ç”¨é€€æ¬¾',NULL,NULL),(325,'template','template_store_refund_days','7','text','',0,'æ¨¡æ¿å•†åº—é€€æ¬¾å¤©æ•°',NULL,NULL),(326,'template','template_store_invoice_enabled','1','switch','',0,'æ¨¡æ¿å•†åº—å¯ç”¨å‘ç¥¨',NULL,NULL),(327,'template','template_store_commission_rate','30','text','',0,'æ¨¡æ¿å•†åº—ä½£é‡‘æ¯”ä¾‹(%)',NULL,NULL),(328,'template','template_store_min_withdraw','100','text','',0,'æ¨¡æ¿å•†åº—æœ€ä½ŽæçŽ°é‡‘é¢',NULL,NULL),(329,'template','template_store_settle_cycle','1','text','',0,'æ¨¡æ¿å•†åº—ç»“ç®—å‘¨æœŸ(å¤©)',NULL,NULL),(330,'template','template_store_seo_title','模板商店 - 八界AI-CMS','text','',0,'æ¨¡æ¿å•†åº—SEOæ ‡é¢˜',NULL,NULL),(331,'template','template_store_seo_description','专业CMS模板商店，提供海量优质网站模板','text','',0,'æ¨¡æ¿å•†åº—SEOæè¿°',NULL,NULL),(332,'template','template_store_seo_keywords','CMS模板,网站模板,响应式模板','text','',0,'æ¨¡æ¿å•†åº—SEOå…³é”®è¯',NULL,NULL),(333,'ai','ai_editor_paragraph_optimize','1','switch','',0,'AI编辑器段落优化',NULL,NULL),(334,'ai','ai_editor_conversation','1','switch','',0,'AI编辑器多轮对话',NULL,NULL),(335,'ai','ai_editor_conversation_timeout','1800','text','',0,'多轮对话超时时间(秒)',NULL,NULL),(336,'ai','ai_editor_conversation_max_token','4096','text','',0,'多轮对话最大Token数',NULL,NULL),(337,'ai','ai_editor_format_preserve','1','switch','',0,'AI编辑器格式保留',NULL,NULL),(338,'ai','ai_editor_translate','1','switch','',0,'AI编辑器翻译功能',NULL,NULL),(339,'ai','ai_editor_template_library','1','switch','',0,'AI编辑器模板库',NULL,NULL),(340,'ai','ai_editor_snapshot','1','switch','',0,'AI编辑器快照功能',NULL,NULL),(341,'ai','ai_editor_snapshot_max','50','text','',0,'快照最大保留数',NULL,NULL),(342,'ai','ai_editor_shortcut_menu','alt+space','text','',0,'AI编辑器快捷菜单',NULL,NULL),(343,'ai','ai_editor_shortcut_optimize','alt+shift+o','text','',0,'快捷键优化',NULL,NULL),(344,'ai','ai_editor_shortcut_translate','alt+shift+t','text','',0,'快捷键翻译',NULL,NULL),(345,'plugin','plugin_market_url','https://market.aicms.io/api','text','',0,'æ’ä»¶å¸‚åœºURL',NULL,NULL),(346,'plugin','plugin_auto_update_check','1','switch','',0,'æ’ä»¶è‡ªåŠ¨æ›´æ–°æ£€æŸ¥',NULL,NULL),(347,'plugin','plugin_security_scan','1','switch','',0,'æ’ä»¶å®‰å…¨æ‰«æ',NULL,NULL),(348,'plugin','plugin_max_filesize','52428800','text','',0,'æ’ä»¶æœ€å¤§æ–‡ä»¶å¤§å°(MB)',NULL,NULL),(349,'pwa','pwa_enabled','1','switch','',0,'启用PWA离线支持',NULL,NULL),(350,'pwa','pwa_app_name','AI-CMS','text','',0,'PWAåº”ç”¨åç§°',NULL,NULL),(351,'pwa','pwa_app_short_name','AI-CMS','text','',0,'PWAåº”ç”¨çŸ­åç§°',NULL,NULL),(352,'pwa','pwa_theme_color','#0d6efd','text','',0,'PWAä¸»é¢˜è‰²',NULL,NULL),(353,'pwa','pwa_bg_color','#ffffff','text','',0,'PWAèƒŒæ™¯è‰²',NULL,NULL),(354,'pwa','pwa_push_enabled','0','switch','',0,'å¯ç”¨PWAæŽ¨é€',NULL,NULL),(355,'content','content_model_diff_enabled','1','switch','',0,'启用内容模型差异化功能',NULL,NULL),(356,'content','content_model_fallback_enabled','1','switch','',0,'内容模型降级渲染',NULL,NULL),(357,'plugin','plugin_payout_config','{\"default_platform_ratio\":30,\"default_developer_ratio\":70,\"tiers\":[{\"min\":0,\"max\":1000,\"developer_ratio\":70},{\"min\":1000,\"max\":5000,\"developer_ratio\":75},{\"min\":5000,\"max\":999999999,\"developer_ratio\":80}]}','json','',0,'插件分成配置',NULL,NULL),(358,'security','admin_captcha_enabled','1','switch','',10,'后台登录验证码',NULL,NULL);
DROP TABLE IF EXISTS `{prefix}content`;
CREATE TABLE `{prefix}content` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标题',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '内容',
  `excerpt` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '摘要',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '类型:1产品/2案例/3新闻/4下载/5招聘/6单页',
  `model_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容模型ID(0=未分配/使用旧逻辑)',
  `model_identifier` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'article' COMMENT '内容模型标识',
  `template` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '前台展示模板(空=使用模型默认)',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态:0草稿/1待审/2已发布/-1已删除',
  `publish_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '定时发布时间(0立即)',
  `seo_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `custom_fields` json DEFAULT NULL COMMENT '自定义字段值(JSON)',
  `recommend_weight` decimal(5,2) DEFAULT '0.00' COMMENT '推荐权重(0-100)',
  `recommend_score` decimal(5,2) DEFAULT '0.00' COMMENT '推荐综合评分',
  `seo_description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `ai_seo_json` json DEFAULT NULL COMMENT 'AI SEO优化数据JSON(V2.9.12)',
  `cate_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '作者ID',
  `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '封面图',
  `ai_image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'AI配图URL(V2.9.12)',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `is_top` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否置顶:0否/1是',
  `views` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '浏览量',
  `play_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '视频/音频播放量（V2.9.21 D-1）',
  `download_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '下载次数（V2.9.20 A-4）',
  `hotness` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '热度值',
  `is_recommend` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否推荐:0否/1是',
  `like_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点赞数',
  `comment_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `is_paid` tinyint(4) DEFAULT '0' COMMENT '是否付费: 0免费 1付费',
  `pay_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '付费价格',
  `paid_price` decimal(10,2) DEFAULT '0.00' COMMENT '付费价格',
  `paid_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'points' COMMENT '付费类型: points积分 money金额',
  `preview_length` int(11) DEFAULT '500' COMMENT '试读字数',
  `is_chapter` tinyint(4) DEFAULT '0' COMMENT '是否启用章节付费',
  `lang` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'zh-CN' COMMENT '内容语言',
  `translation_of` int(11) DEFAULT '0' COMMENT '翻译源内容ID',
  `min_level_id` int(11) DEFAULT '0' COMMENT '最低访问等级(0=无限制)',
  `chapter_price` decimal(10,2) DEFAULT '0.00' COMMENT '章节单购价格',
  `chapter_count` int(10) unsigned DEFAULT '0' COMMENT '总章节数(父记录)',
  `chapter_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '章节标题',
  `quality_score` tinyint(4) DEFAULT '0' COMMENT 'AI质量评分(0-100)',
  `quality_level` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'unscored' COMMENT '质量等级(excellent/good/fair/poor/unscored)',
  `seo_score` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'SEO评分(0-100, V3.1)',
  `lang_site_id` int(11) DEFAULT '0' COMMENT '所属语言站点ID',
  `is_auto_translated` tinyint(4) DEFAULT '0' COMMENT '是否AI自动翻译:1是0否',
  `slug` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '当前生效URL别名',
  `paid_points` int(11) DEFAULT '0' COMMENT '付费所需积分',
  `paid_preview_ratio` int(11) DEFAULT '20' COMMENT '付费预览比例(%)',
  `paid_download_limit` int(11) DEFAULT '3' COMMENT '付费下载次数限制',
  `paid_author_ratio` int(11) DEFAULT '0' COMMENT '作者分成比例(%)',
  `ab_test_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'AB测试ID',
  `ab_version` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'AB测试版本: A/B',
  `ab_metrics` json DEFAULT NULL COMMENT 'AB测试指标数据',
  PRIMARY KEY (`id`),
  KEY `idx_type_status` (`type`,`status`),
  KEY `idx_cate` (`cate_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_publish_time` (`publish_time`),
  KEY `idx_hotness` (`hotness`),
  KEY `idx_is_recommend` (`is_recommend`),
  KEY `idx_status_create_time` (`status`,`create_time`),
  KEY `idx_status_cate` (`status`,`cate_id`),
  KEY `idx_status_cate_sort` (`status`,`cate_id`,`sort`),
  KEY `idx_seo_score` (`seo_score`),
  KEY `idx_lang` (`lang`),
  KEY `idx_play_count` (`play_count`),
  KEY `idx_download_count` (`download_count`),
  FULLTEXT KEY `ft_title_excerpt` (`title`,`excerpt`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容表';

INSERT INTO `{prefix}content` VALUES (1,'1111273','<p>test测试222</p>\n<p>test测试</p>\n<p>test测试</p>\n<p>test测试</p>\n<p>test测试</p>','',3,0,'article','',0,0,'','',NULL,0.00,0.00,'',NULL,0,1,'',NULL,0,1,0,0,0,0,0,0,0,1776944895,1783917512,1,0.00,0.00,'points',500,0,'zh-CN',0,0,0.00,0,'',0,'unscored',18,0,0,'',0,20,3,0,0,'',NULL);
DROP TABLE IF EXISTS `{prefix}content_action_plan`;
CREATE TABLE `{prefix}content_action_plan` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(10) unsigned NOT NULL DEFAULT '0',
  `action` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'publish/unpublish/archive',
  `execute_time` int(10) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0待执行1已执行2已取消3失败',
  `execute_log` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_action` (`action`),
  KEY `idx_time` (`execute_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容行动计划表';

DROP TABLE IF EXISTS `{prefix}content_archive`;
CREATE TABLE `{prefix}content_archive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_id` int(11) NOT NULL COMMENT '内容ID',
  `archived_by` int(11) DEFAULT '0' COMMENT '归档操作人',
  `archive_reason` varchar(200) DEFAULT '' COMMENT '归档原因',
  `original_status` varchar(20) DEFAULT '' COMMENT '归档前状态',
  `content_snapshot` json DEFAULT NULL COMMENT '内容快照(JSON)',
  `create_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='内容归档记录表';

DROP TABLE IF EXISTS `{prefix}content_audit_log`;
CREATE TABLE `{prefix}content_audit_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `operation` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'create/update/delete/audit/publish/unpublish/restore',
  `diff_summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '变更摘要(JSON)',
  `ip_address` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_operation` (`operation`),
  KEY `idx_create` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容操作日志表';

DROP TABLE IF EXISTS `{prefix}content_ext`;
CREATE TABLE `{prefix}content_ext` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `content_id` bigint(20) unsigned NOT NULL COMMENT '内容ID',
  `type` tinyint(4) NOT NULL COMMENT '内容类型',
  `data` json DEFAULT NULL COMMENT '扩展数据(JSON)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_content_type` (`content_id`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容扩展表';

INSERT INTO `{prefix}content_ext` VALUES (1,1,1,'{\"product_price\": \"\", \"product_specs\": \"\", \"product_params\": \"\"}'),(2,1,3,'{\"news_author\": \"\", \"news_source\": \"\"}');
DROP TABLE IF EXISTS `{prefix}content_field`;
CREATE TABLE `{prefix}content_field` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model_id` int(11) NOT NULL COMMENT '内容模型ID',
  `field_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '字段标识',
  `field_label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '字段标签',
  `field_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '字段类型',
  `field_options` json DEFAULT NULL COMMENT '字段选项',
  `field_validation` json DEFAULT NULL COMMENT '验证规则',
  `field_layout` json DEFAULT NULL COMMENT '布局设置',
  `default_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT '默认值',
  `placeholder` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT '' COMMENT '占位符',
  `help_text` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT '' COMMENT '帮助说明',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `is_required` tinyint(4) DEFAULT '0' COMMENT '是否必填',
  `is_unique` tinyint(4) DEFAULT '0' COMMENT '是否唯一',
  `is_searchable` tinyint(4) DEFAULT '0' COMMENT '是否可搜索',
  `is_list_show` tinyint(4) DEFAULT '0' COMMENT '是否列表展示',
  `is_system` tinyint(4) DEFAULT '0' COMMENT '系统字段',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_model` (`model_id`),
  KEY `idx_field` (`field_name`),
  KEY `idx_sort` (`sort_order`),
  KEY `idx_type` (`field_type`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='内容模型字段表';

INSERT INTO `{prefix}content_field` VALUES (1,10,'title','标题','text','{\"maxlength\": 200}','{\"maxlength\": 200}',NULL,NULL,'请输入标题','',1,1,0,1,1,1,1,'2026-07-24 17:17:56','2026-07-24 17:17:56'),(2,10,'author','作者','text','{\"maxlength\": 50}','{\"maxlength\": 50}',NULL,NULL,'请输入作者','',2,0,0,0,1,0,1,'2026-07-24 17:17:56','2026-07-24 17:17:56'),(3,10,'cover_image','封面图','image','{\"max_size\": 5, \"thumb_width\": 300, \"thumb_height\": 200}',NULL,NULL,NULL,NULL,'',3,0,0,0,1,0,1,'2026-07-24 17:17:56','2026-07-24 17:17:56'),(4,10,'summary','摘要','textarea','{\"rows\": 3, \"maxlength\": 500}',NULL,NULL,NULL,'请输入摘要','',4,0,0,1,0,0,1,'2026-07-24 17:17:56','2026-07-24 17:17:56'),(5,10,'content','正文','editor','{\"height\": 400, \"editor_type\": \"rich\"}',NULL,NULL,NULL,NULL,'',5,1,0,1,0,1,1,'2026-07-24 17:17:56','2026-07-24 17:17:56'),(6,10,'category','分类','select','{\"source\": \"category\", \"multiple\": 0}',NULL,NULL,NULL,NULL,'',6,0,0,1,1,0,1,'2026-07-24 17:17:56','2026-07-24 17:17:56'),(7,10,'tags','标签','tag','{\"max_count\": 5}',NULL,NULL,NULL,NULL,'',7,0,0,1,1,0,1,'2026-07-24 17:17:56','2026-07-24 17:17:56'),(8,10,'source','来源','text','{\"maxlength\": 100}','{\"maxlength\": 100}',NULL,NULL,'请输入来源','',8,0,0,0,0,0,1,'2026-07-24 17:17:56','2026-07-24 17:17:56'),(9,10,'pub_time','发布时间','datetime','{\"format\": \"Y-m-d H:i:s\"}',NULL,NULL,NULL,NULL,'',9,0,0,1,1,1,1,'2026-07-24 17:17:56','2026-07-24 17:17:56'),(10,10,'status','状态','radio','{\"options\": [{\"label\": \"草稿\", \"value\": \"0\"}, {\"label\": \"发布\", \"value\": \"1\"}]}','{\"required\": 1}',NULL,NULL,NULL,'',10,1,0,1,1,1,1,'2026-07-24 17:17:56','2026-07-24 17:17:56');
DROP TABLE IF EXISTS `{prefix}content_image`;
CREATE TABLE `{prefix}content_image` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图片URL',
  `image_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'body' COMMENT '图片类型(header/body/thumbnail)',
  `style` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配图风格',
  `quality_score` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '质量评分(0-100)',
  `ai_generated` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否AI生成:1=是,0=否',
  `auto_triggered` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否自动触发:1=是,0=否',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_type` (`image_type`),
  KEY `idx_quality` (`quality_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容配图表 - V2.9.32';

DROP TABLE IF EXISTS `{prefix}content_lang`;
CREATE TABLE `{prefix}content_lang` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `lang` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '语言代码(en/ja/ko/...)',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '翻译标题',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '翻译正文',
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '翻译摘要',
  `seo_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_desc` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `keywords` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '翻译关键词',
  `image_alt` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '图片ALT翻译(JSON格式)',
  `error_msg` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '翻译失败错误信息',
  `translate_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '翻译状态(0=PENDING,1=PROCESSING,2=COMPLETED,3=FAILED)',
  `translate_provider` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '翻译Provider',
  `translate_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '翻译耗时(秒)',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_content_id_lang` (`content_id`,`lang`),
  KEY `idx_lang` (`lang`),
  KEY `idx_status` (`translate_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容多语言翻译版本表';

DROP TABLE IF EXISTS `{prefix}content_like`;
CREATE TABLE `{prefix}content_like` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}content_model`;
CREATE TABLE `{prefix}content_model` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型名称',
  `model_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型名称',
  `model_identifier` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型标识',
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型标识(unique)',
  `mobile_partial_suffix` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '移动端详情模板片段后缀(E-1)',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '描述',
  `model_description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型描述',
  `seo_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO标题模板',
  `seo_keywords` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词模板',
  `seo_description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO描述模板',
  `template_file` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '前台专属模板文件名',
  `default_list_template` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '默认列表模板(list_{code}.html)',
  `default_detail_template` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '默认详情模板(detail_{code}.html)',
  `icon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图标CSS class或URL',
  `model_icon` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型图标',
  `model_config` text COLLATE utf8mb4_unicode_ci COMMENT '模型配置JSON',
  `template_config` text COLLATE utf8mb4_unicode_ci COMMENT '模板配置JSON',
  `url_rule` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'URL规则',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分组ID',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '关联内容类型(1产品/2案例/3新闻/4下载/5招聘/6单页)',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态(1启用/0禁用)',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `sort_order` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `is_system` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否系统模型',
  `is_enabled` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否启用',
  `form_layout` text COLLATE utf8mb4_unicode_ci COMMENT '表单布局JSON',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  `delete_time` int(10) unsigned DEFAULT '0' COMMENT '软删除时间',
  `is_deleted` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '软删除',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_type` (`type`),
  KEY `idx_status_sort` (`status`,`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容模型定义';

INSERT INTO `{prefix}content_model` VALUES (1,'产品信息','产品信息','model_product','model_product','','用于展示产品详情，支持价格、库存、规格等字段','用于展示产品详情，支持价格、库存、规格等字段','','','','','','','bi bi-box-seam','bi bi-box-seam',NULL,NULL,'',0,1,1,10,10,0,1,NULL,1780903524,1780903524,0,0),(2,'企业案例','企业案例','model_case','model_case','','用于展示企业案例/项目，支持客户名称、项目周期等字段','用于展示企业案例/项目，支持客户名称、项目周期等字段','','','','','','','bi bi-briefcase','bi bi-briefcase',NULL,NULL,'',0,2,1,20,20,0,1,NULL,1780903524,1780903524,0,0),(3,'新闻资讯','新闻资讯','model_news','model_news','','用于发布新闻文章，支持来源、作者等字段','用于发布新闻文章，支持来源、作者等字段','','','','','','','bi bi-newspaper','bi bi-newspaper',NULL,NULL,'',0,3,1,30,30,0,1,NULL,1780903524,1780903524,0,0),(4,'软件下载','软件下载','model_download','model_download','','用于软件/资源下载，支持版本号、文件大小、下载次数等字段','用于软件/资源下载，支持版本号、文件大小、下载次数等字段','','','','','','','bi bi-download','bi bi-download',NULL,NULL,'',0,4,1,40,40,0,1,NULL,1780903524,1780903524,0,0),(5,'人才招聘','人才招聘','model_job','model_job','','用于发布招聘信息，支持薪资范围、工作地点、学历要求等字段','用于发布招聘信息，支持薪资范围、工作地点、学历要求等字段','','','','','','','bi bi-people','bi bi-people',NULL,NULL,'',0,5,1,50,50,0,1,NULL,1780903524,1780903524,0,0),(6,'单页介绍','单页介绍','model_page','model_page','','用于单页内容展示，支持副标题、封面图等字段','用于单页内容展示，支持副标题、封面图等字段','','','','','','','bi bi-file-earmark-text','bi bi-file-earmark-text',NULL,NULL,'',0,6,1,60,60,0,1,NULL,1780903524,1780903524,0,0),(7,'图片图集','图片图集','model_image','model_image','','用于图片画廊、作品集展示，支持多图轮播、图片说明等字段','用于图片画廊、作品集展示，支持多图轮播、图片说明等字段','{$title} - 图集 - {$site_name}','{$title},图片,图集,作品集','{$title}图片图集展示页面','content/image_show','list_image','detail_image','bi bi-images','bi bi-images',NULL,NULL,'',0,3,1,35,35,0,1,NULL,1782034032,1782034032,0,0),(8,'视频内容','视频内容','model_video','model_video','','用于视频内容展示与播放，支持视频链接、时长、封面等字段','用于视频内容展示与播放，支持视频链接、时长、封面等字段','{$title} - 视频 - {$site_name}','{$title},视频,播放','{$title}视频播放页面','content/video_show','list_video','detail_video','bi bi-play-btn','bi bi-play-btn',NULL,NULL,'',0,3,1,36,36,0,1,NULL,1782034032,1782034032,0,0),(10,'文章模型','文章模型','article','article','','标准文章模型，适用于新闻、博客、资讯等内容类型','标准文章模型，适用于新闻、博客、资讯等内容类型','{$title} - {$site_name}','{$title},文章,资讯','{$title}文章详情页','content/article_show','list_article','detail_article','bi bi-file-text','bi bi-file-text','{\"layout\":\"single\",\"containers\":[],\"fields\":[]}',NULL,'',0,3,1,10,10,0,1,NULL,1782292684,2026,0,0),(11,'图片模型','图片模型','image','image','','图片图集模型，适用于画廊、作品集、相册等视觉内容','图片图集模型，适用于画廊、作品集、相册等视觉内容','{$title} - 图集 - {$site_name}','{$title},图片,图集','{$title}图片展示页','content/image_show','list_image','detail_image','bi bi-images','bi bi-images',NULL,NULL,'',0,3,1,20,20,0,1,NULL,1782292684,1782292684,0,0),(12,'下载模型','下载模型','download','download','','下载资源模型，适用于软件、文档、模板等资源下载','下载资源模型，适用于软件、文档、模板等资源下载','{$title} - 下载 - {$site_name}','{$title},下载,资源','{$title}资源下载页','content/download_show','list_download','detail_download','bi bi-download','bi bi-download',NULL,NULL,'',0,4,1,30,30,0,1,NULL,1782292684,1782292684,0,0),(13,'产品模型','产品模型','product','product','','产品展示模型，适用于商品、服务展示等电商场景','产品展示模型，适用于商品、服务展示等电商场景','{$title} - 产品 - {$site_name}','{$title},产品,商品','{$title}产品详情页','content/product_show','list_product','detail_product','bi bi-box','bi bi-box',NULL,NULL,'',0,1,1,40,40,0,1,NULL,1782292684,1782292684,0,0),(14,'视频模型','视频模型','video','video','','视频播放模型，适用于视频站、课程、演示等多媒体内容','视频播放模型，适用于视频站、课程、演示等多媒体内容','{$title} - 视频 - {$site_name}','{$title},视频,播放','{$title}视频播放页','content/video_show','list_video','detail_video','bi bi-play-btn','bi bi-play-btn',NULL,NULL,'',0,3,1,50,50,0,1,NULL,1782292684,1782292684,0,0);
DROP TABLE IF EXISTS `{prefix}content_model_field`;
CREATE TABLE `{prefix}content_model_field` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `model_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '模型ID',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字段名(英文标识)',
  `label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字段标签(中文显示名)',
  `type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text' COMMENT '字段类型(text/textarea/rich_text/number/select/radio/checkbox/date/datetime/image/file/color/tags/location)',
  `options` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '选项(JSON,用于select/radio/checkbox)',
  `default_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '默认值',
  `placeholder` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '占位提示',
  `validation` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '验证规则(JSON)',
  `is_searchable` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否可搜索(1是/0否)',
  `is_list_show` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '列表页是否显示(1是/0否)',
  `required` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否必填(1是/0否)',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态(1启用/0禁用)',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_model_status_sort` (`model_id`,`status`,`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容模型扩展字段';

INSERT INTO `{prefix}content_model_field` VALUES (1,1,'price','价格','number',NULL,'0','请输入产品价格','',0,0,1,10,1,1780903524,1780903524),(2,1,'stock','库存数量','number',NULL,'0','请输入库存数量','',0,0,1,20,1,1780903524,1780903524),(3,1,'spec','产品规格','textarea',NULL,'','请输入产品规格参数','',0,0,0,30,1,1780903524,1780903524),(4,1,'brand','品牌','text',NULL,'','请输入品牌名称','',0,0,0,40,1,1780903524,1780903524),(5,2,'client_name','客户名称','text',NULL,'','请输入客户/公司名称','',0,0,1,10,1,1780903524,1780903524),(6,2,'project_period','项目周期','text',NULL,'','如：2024.01-2024.06','',0,0,0,20,1,1780903524,1780903524),(7,2,'industry','所属行业','select','[\"互联网\",\"金融\",\"教育\",\"医疗\",\"制造\",\"其他\"]','互联网','请选择所属行业','',0,0,0,30,1,1780903524,1780903524),(8,3,'source','文章来源','text',NULL,'','请输入文章来源','',0,0,0,10,1,1780903524,1780903524),(9,3,'author','作者','text',NULL,'','请输入作者姓名','',0,0,0,20,1,1780903524,1780903524),(10,3,'is_top','是否置顶','radio','[\"否\",\"是\"]','0','','',0,0,0,30,1,1780903524,1780903524),(11,4,'version','版本号','text',NULL,'1.0.0','如：1.0.0','',0,0,1,10,1,1780903524,1780903524),(12,4,'file_size','文件大小','text',NULL,'','如：15.6 MB','',0,0,0,20,1,1780903524,1780903524),(13,4,'download_url','下载链接','text',NULL,'','请输入下载链接','',0,0,1,30,1,1780903524,1780903524),(14,5,'salary_range','薪资范围','text',NULL,'','如：15K-25K','',0,0,1,10,1,1780903524,1780903524),(15,5,'location','工作地点','text',NULL,'','如：北京市海淀区','',0,0,1,20,1,1780903524,1780903524),(16,5,'education','学历要求','select','[\"不限\",\"大专\",\"本科\",\"硕士\",\"博士\"]','本科','请选择学历要求','',0,0,1,30,1,1780903524,1780903524),(17,6,'subtitle','副标题','text',NULL,'','请输入副标题','',0,0,0,10,1,1780903524,1780903524),(18,6,'cover_image','封面图','image',NULL,'','请上传封面图片','',0,0,0,20,1,1780903524,1780903524),(19,7,'gallery','图集','image',NULL,'','请上传图片(可多选)','',0,0,1,10,1,1782034032,1782034032),(20,7,'image_description','图片说明','textarea',NULL,'','请输入图片描述','',0,0,0,20,1,1782034032,1782034032),(21,7,'photographer','摄影师','text',NULL,'','请输入摄影师姓名','',0,0,0,30,1,1782034032,1782034032),(22,8,'video_url','视频链接','text',NULL,'','请输入视频播放链接','',0,0,1,10,1,1782034032,1782034032),(23,8,'video_cover','视频封面','image',NULL,'','请上传视频封面图','',0,0,0,20,1,1782034032,1782034032),(24,8,'duration','视频时长','text',NULL,'','如：12:30','',0,0,0,30,1,1782034032,1782034032);
DROP TABLE IF EXISTS `{prefix}content_model_migration_log`;
CREATE TABLE `{prefix}content_model_migration_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `model_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '模型ID',
  `migration_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '迁移类型(batch_assign/import_from_type/init_fields)',
  `total_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '处理总数',
  `success_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '成功数',
  `fail_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '失败数',
  `error_detail` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '错误详情(JSON)',
  `operator` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作人',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_migration_type` (`migration_type`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容模型迁移日志';

DROP TABLE IF EXISTS `{prefix}content_model_stats`;
CREATE TABLE `{prefix}content_model_stats` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `model_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容模型ID',
  `stat_date` date NOT NULL COMMENT '统计日期',
  `total_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容总数',
  `published_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '已发布数',
  `draft_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '草稿数',
  `pending_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '待审核数',
  `new_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '当日新增数',
  `total_views` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '总浏览量',
  `avg_quality_score` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '平均质量分',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_model_date` (`model_id`,`stat_date`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容模型数据统计';

DROP TABLE IF EXISTS `{prefix}content_model_template_map`;
CREATE TABLE `{prefix}content_model_template_map` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `model_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容模型ID',
  `template_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '模板ID(template_store)',
  `tag_match` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '标签匹配规则(JSON)',
  `priority` tinyint(3) unsigned NOT NULL DEFAULT '50' COMMENT '优先级(1-100,越大越优先)',
  `is_default` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否默认模板(1是/0否)',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态(1启用/0禁用)',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_model_template` (`model_id`,`template_id`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_status_priority` (`status`,`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容模型-模板映射';

DROP TABLE IF EXISTS `{prefix}content_quality`;
CREATE TABLE `{prefix}content_quality` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}content_quality_score`;
CREATE TABLE `{prefix}content_quality_score` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(11) NOT NULL COMMENT '内容ID',
  `completeness_score` int(11) DEFAULT '0' COMMENT '完整性评分(0-100)',
  `readability_score` int(11) DEFAULT '0' COMMENT '可读性评分(0-100)',
  `seo_score` int(11) DEFAULT '0' COMMENT 'SEO优化评分(0-100)',
  `image_match_score` int(11) DEFAULT '0' COMMENT '配图匹配评分(0-100)',
  `tag_accuracy_score` int(11) DEFAULT '0' COMMENT '标签准确评分(0-100)',
  `total_score` int(11) DEFAULT '0' COMMENT '综合评分(0-100)',
  `suggestions` json DEFAULT NULL COMMENT '改进建议(JSON数组)',
  `score_source` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'auto' COMMENT '评分来源(auto/manual/batch)',
  `repair_count` int(11) DEFAULT '0' COMMENT '修复次数',
  `last_repair_time` int(10) unsigned DEFAULT '0' COMMENT '最近修复时间',
  `repair_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'none' COMMENT '修复状态(none/auto/suggested/manual/failed/needs_manual)',
  `create_time` int(10) unsigned DEFAULT '0',
  `update_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_content` (`content_id`),
  KEY `idx_total` (`total_score`),
  KEY `idx_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容质量评分表 - V2.9.33';

DROP TABLE IF EXISTS `{prefix}content_rating`;
CREATE TABLE `{prefix}content_rating` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(10) unsigned NOT NULL COMMENT '内容ID',
  `member_id` int(10) unsigned NOT NULL COMMENT '评价用户ID',
  `rating` tinyint(4) NOT NULL COMMENT '评分 1-5',
  `title` varchar(255) DEFAULT '' COMMENT '评价标题',
  `content` text COMMENT '评价内容',
  `has_media` tinyint(4) DEFAULT '0' COMMENT '是否有图片/视频 0否1是',
  `media_urls` text COMMENT '图片/视频URL列表JSON',
  `is_anonymous` tinyint(4) DEFAULT '0' COMMENT '是否匿名 0否1是',
  `reply_count` int(11) DEFAULT '0' COMMENT '回复数',
  `like_count` int(11) DEFAULT '0' COMMENT '点赞数',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态:0待审/1通过/2拒绝',
  `create_time` int(10) unsigned DEFAULT '0',
  `update_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_content_member` (`content_id`,`member_id`),
  KEY `idx_content_rating` (`content_id`,`rating`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='内容评价评分表';

DROP TABLE IF EXISTS `{prefix}content_recommend_log`;
CREATE TABLE `{prefix}content_recommend_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(10) unsigned NOT NULL DEFAULT '0',
  `recommended_content_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned DEFAULT '0',
  `source` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'tag/category/relation',
  `impressed` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `clicked` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_create` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容推荐日志表';

DROP TABLE IF EXISTS `{prefix}content_relation`;
CREATE TABLE `{prefix}content_relation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '主内容ID',
  `relation_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联内容ID',
  `relation_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'related' COMMENT '关系类型(related/previous_next/recommended/similar)',
  `relation_weight` decimal(5,2) NOT NULL DEFAULT '1.00' COMMENT '关联权重(0-1)',
  `is_manual` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否手动关联(1是/0AI自动)',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_content_relation_type` (`content_id`,`relation_id`,`relation_type`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_relation_id` (`relation_id`),
  KEY `idx_relation_type` (`relation_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容关系表';

DROP TABLE IF EXISTS `{prefix}content_slug`;
CREATE TABLE `{prefix}content_slug` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_id` int(11) NOT NULL COMMENT '内容ID',
  `lang_site_id` int(11) NOT NULL COMMENT '语言站点ID',
  `slug` varchar(200) NOT NULL COMMENT 'URL别名',
  `is_active` tinyint(4) DEFAULT '1' COMMENT '是否当前生效',
  `create_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lang_slug` (`lang_site_id`,`slug`),
  KEY `idx_content` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='内容URL别名表';

DROP TABLE IF EXISTS `{prefix}content_subscription`;
CREATE TABLE `{prefix}content_subscription` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `subscribe_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'category/tag/author',
  `subscribe_id` int(10) unsigned NOT NULL DEFAULT '0',
  `notify_email` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `notify_site` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `digest_frequency` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'instant' COMMENT 'instant/daily/weekly',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_subscription` (`user_id`,`subscribe_type`,`subscribe_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_type_id` (`subscribe_type`,`subscribe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容订阅表';

DROP TABLE IF EXISTS `{prefix}content_tag`;
CREATE TABLE `{prefix}content_tag` (
  `content_id` bigint(20) unsigned NOT NULL COMMENT '内容ID',
  `tag_id` int(10) unsigned NOT NULL COMMENT '标签ID',
  PRIMARY KEY (`content_id`,`tag_id`),
  KEY `idx_tag` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容标签关联表';

DROP TABLE IF EXISTS `{prefix}content_version`;
CREATE TABLE `{prefix}content_version` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标题',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '正文内容',
  `excerpt` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '摘要',
  `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '封面图',
  `cate_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态',
  `ext_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '扩展字段数据(JSON)',
  `tag_ids` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '标签ID集合',
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '操作人ID',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '版本创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容版本历史表';

INSERT INTO `{prefix}content_version` VALUES (1,1,'1111223','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"product_price\":\"\",\"product_specs\":\"\",\"product_params\":\"\"}','',1,1779207823),(2,1,'1111223','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"product_price\":\"\",\"product_specs\":\"\",\"product_params\":\"\"}','',1,1779208000),(3,1,'1111223','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"product_price\":\"\",\"product_specs\":\"\",\"product_params\":\"\"}','',1,1779208051),(4,1,'11112234','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779211643),(5,1,'11112234','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779211646),(6,1,'11112234','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779211651),(7,1,'11112234','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779211662),(8,1,'1111223','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779211797),(9,1,'1111223','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779211817),(10,1,'11112235','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779211879),(11,1,'11112235','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779211907),(12,1,'1111223','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779212211),(13,1,'1111223','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779212370),(14,1,'11112239','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779212510),(15,1,'11112233','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779214652),(16,1,'1111269','<p>test测试</p>\n<p>test测试</p>\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779214697),(17,1,'1111261','<p>test测试</p>\n<p>test测试</p>\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779246054);
DROP TABLE IF EXISTS `{prefix}cookie_consent`;
CREATE TABLE `{prefix}cookie_consent` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}cookie_consent_log`;
CREATE TABLE `{prefix}cookie_consent_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}cookie_definition`;
CREATE TABLE `{prefix}cookie_definition` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}coupon_template`;
CREATE TABLE `{prefix}coupon_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `coupon_name` varchar(100) NOT NULL COMMENT '券名称，如"满100减20"',
  `coupon_type` enum('reduce','discount','free_shipping') NOT NULL COMMENT '满减/discount/免邮',
  `condition_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '门槛金额(免邮券填0)',
  `reduce_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '满减券:减免金额; 折扣券:折扣率(0.9=9折); 免邮券:0',
  `total_stock` int(11) NOT NULL DEFAULT '0' COMMENT '发行总量',
  `remain_stock` int(11) NOT NULL DEFAULT '0' COMMENT '剩余库存',
  `per_user_limit` int(11) NOT NULL DEFAULT '1' COMMENT '每人限领数量',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '有效期开始时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '有效期结束时间',
  `scope_type` enum('all','category','content') NOT NULL DEFAULT 'all' COMMENT '适用范围:全部/指定分类/指定商品',
  `scope_value` text COMMENT '适用范围值(分类ID/商品ID,JSON数组)',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态:0草稿/1启用/2停用/3已过期',
  `create_time` int(10) unsigned DEFAULT '0',
  `update_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_type_status` (`coupon_type`,`status`),
  KEY `idx_time` (`start_time`,`end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='优惠券模板表';

DROP TABLE IF EXISTS `{prefix}custom_var`;
CREATE TABLE `{prefix}custom_var` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `sort` int(11) NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='自定义变量表';

DROP TABLE IF EXISTS `{prefix}custom_whitelist`;
CREATE TABLE `{prefix}custom_whitelist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `list_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'css/js',
  `category` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'property/value/selector/function/api/object/event',
  `item_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_pattern` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `security_level` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'safe' COMMENT 'safe/approval/forbidden',
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `creator_id` int(11) DEFAULT '0',
  `create_time` int(10) unsigned DEFAULT '0',
  `update_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type_name` (`list_type`,`category`,`item_name`),
  KEY `idx_type` (`list_type`),
  KEY `idx_security` (`security_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='自定义CSS/JS白名单表';

DROP TABLE IF EXISTS `{prefix}data_alert`;
CREATE TABLE `{prefix}data_alert` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alert_name` varchar(100) NOT NULL COMMENT '预警名称',
  `alert_metric` varchar(50) NOT NULL COMMENT '预警指标(pv/uv/orders/revenue/error_rate/response_time等)',
  `alert_rule` json NOT NULL COMMENT '预警规则(JSON: operator/threshold/window)',
  `alert_level` varchar(10) DEFAULT 'warning' COMMENT '预警级别(info/warning/critical)',
  `alert_channels` json DEFAULT NULL COMMENT '预警通知渠道(JSON: email/sms/webhook/dingtalk/feishu)',
  `alert_recipients` json DEFAULT NULL COMMENT '预警接收人(JSON)',
  `escalation_config` json DEFAULT NULL COMMENT '升级配置(JSON)',
  `cooldown_minutes` int(11) DEFAULT '60' COMMENT '冷却时间(分钟)',
  `is_active` tinyint(4) DEFAULT '1' COMMENT '是否启用',
  `last_triggered` datetime DEFAULT NULL COMMENT '最后触发时间',
  `trigger_count` int(11) DEFAULT '0' COMMENT '触发次数',
  `resolved_count` int(11) DEFAULT '0' COMMENT '已解决次数',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_metric` (`alert_metric`),
  KEY `idx_level` (`alert_level`),
  KEY `idx_active` (`is_active`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='数据预警规则表';

DROP TABLE IF EXISTS `{prefix}data_alert_log`;
CREATE TABLE `{prefix}data_alert_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}data_classification`;
CREATE TABLE `{prefix}data_classification` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `table_name` varchar(100) NOT NULL COMMENT '表名',
  `field_name` varchar(100) NOT NULL COMMENT '字段名',
  `classification_level` varchar(10) NOT NULL COMMENT '分级级别(L0/L1/L2/L3/L4)',
  `classification_method` varchar(30) DEFAULT 'manual' COMMENT '分级方式(manual/auto/regex/ai)',
  `classification_reason` text COMMENT '分级原因',
  `protection_measures` json DEFAULT NULL COMMENT '保护措施(JSON)',
  `is_active` tinyint(4) DEFAULT '1' COMMENT '是否启用',
  `reviewed_by` int(11) DEFAULT '0' COMMENT '审核人ID',
  `reviewed_at` datetime DEFAULT NULL COMMENT '审核时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_table_field` (`table_name`,`field_name`),
  KEY `idx_level` (`classification_level`),
  KEY `idx_active` (`is_active`),
  KEY `idx_method` (`classification_method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='数据安全分级表';

DROP TABLE IF EXISTS `{prefix}data_dashboard`;
CREATE TABLE `{prefix}data_dashboard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '大屏名称',
  `description` text COMMENT '大屏描述',
  `layout` json NOT NULL COMMENT '布局配置(JSON: 模块位置/大小/数据源)',
  `layout_template` varchar(50) DEFAULT 'default' COMMENT '布局模板标识',
  `refresh_interval` int(11) DEFAULT '60' COMMENT '自动刷新间隔(秒)',
  `is_active` tinyint(4) DEFAULT '1' COMMENT '是否启用:1是0否',
  `is_public` tinyint(4) DEFAULT '0' COMMENT '是否公开:1是0否',
  `share_token` varchar(64) DEFAULT '' COMMENT '分享Token',
  `interaction_config` json DEFAULT NULL COMMENT '交互配置(JSON)',
  `custom_charts` json DEFAULT NULL COMMENT '自定义图表(JSON)',
  `is_template` tinyint(4) DEFAULT '0' COMMENT '是否为模版',
  `template_category` varchar(50) DEFAULT '' COMMENT '模版分类',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_public` (`is_public`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='数据大屏配置表';

DROP TABLE IF EXISTS `{prefix}data_dashboard_share`;
CREATE TABLE `{prefix}data_dashboard_share` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}data_dashboard_template`;
CREATE TABLE `{prefix}data_dashboard_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}data_mask_rule`;
CREATE TABLE `{prefix}data_mask_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}data_report`;
CREATE TABLE `{prefix}data_report` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '报表名称',
  `report_type` varchar(30) NOT NULL COMMENT '报表类型(daily/weekly/monthly/quarterly/yearly/custom/compare/trend/anomaly/target)',
  `data_config` json NOT NULL COMMENT '数据配置(JSON: 数据范围/维度/指标/筛选/排序)',
  `chart_config` json DEFAULT NULL COMMENT '图表配置(JSON: 图表类型/颜色/布局)',
  `schedule_config` json DEFAULT NULL COMMENT '定时配置(JSON: cron表达式/时区/启用)',
  `ai_analysis` tinyint(4) DEFAULT '1' COMMENT '是否启用AI分析:1是0否',
  `recipients` json DEFAULT NULL COMMENT '接收人列表(JSON: 用户ID/邮箱)',
  `last_generated` datetime DEFAULT NULL COMMENT '最后生成时间',
  `status` varchar(20) DEFAULT 'active' COMMENT '状态(active/paused/archived)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`report_type`),
  KEY `idx_status` (`status`),
  KEY `idx_last_gen` (`last_generated`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='智能报表配置表';

DROP TABLE IF EXISTS `{prefix}data_report_subscription`;
CREATE TABLE `{prefix}data_report_subscription` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL COMMENT '报表ID',
  `subscriber_id` int(11) NOT NULL COMMENT '订阅者ID',
  `subscriber_type` varchar(20) DEFAULT 'user' COMMENT '订阅者类型(user/role/department)',
  `subscriber_config` json DEFAULT NULL COMMENT '订阅者配置(JSON)',
  `subscription_type` varchar(20) DEFAULT 'daily' COMMENT '订阅类型(daily/weekly/monthly/realtime)',
  `schedule_config` json DEFAULT NULL COMMENT '定时配置(JSON)',
  `push_format` varchar(20) DEFAULT 'pdf' COMMENT '推送格式(pdf/excel/csv/image/html)',
  `push_content` json DEFAULT NULL COMMENT '推送内容配置(JSON)',
  `push_methods` json DEFAULT NULL COMMENT '推送方式(JSON: email/webhook/dingtalk/feishu)',
  `start_date` date DEFAULT NULL COMMENT '开始日期',
  `end_date` date DEFAULT NULL COMMENT '结束日期',
  `status` varchar(20) DEFAULT 'active' COMMENT '状态(active/paused/expired/cancelled)',
  `last_push_time` datetime DEFAULT NULL COMMENT '最后推送时间',
  `push_count` int(11) DEFAULT '0' COMMENT '推送次数',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_report` (`report_id`),
  KEY `idx_subscriber` (`subscriber_id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`subscription_type`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='数据报告订阅表';

DROP TABLE IF EXISTS `{prefix}dev_points_log`;
CREATE TABLE `{prefix}dev_points_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}developer`;
CREATE TABLE `{prefix}developer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `real_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '真实姓名',
  `contact_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '联系电话',
  `contact_email` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '联系邮箱',
  `introduction` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '开发经验介绍',
  `level` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '认证等级:1初级2认证3专业',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态:0待审1通过2驳回3禁用',
  `audit_remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '审核备注',
  `total_templates` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '已发布模板数',
  `total_revenue` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '累计收益',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user` (`user_id`),
  KEY `idx_level` (`level`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='开发者信息表';

DROP TABLE IF EXISTS `{prefix}distribution_auto_rule`;
CREATE TABLE `{prefix}distribution_auto_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}distribution_schedule`;
CREATE TABLE `{prefix}distribution_schedule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}distribution_strategy`;
CREATE TABLE `{prefix}distribution_strategy` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}distribution_template`;
CREATE TABLE `{prefix}distribution_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}email_log`;
CREATE TABLE `{prefix}email_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '模板标识',
  `to_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '收件人',
  `subject` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '实际主题',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态: 0排队 1成功 2失败',
  `error_msg` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '错误信息',
  `send_time` int(10) unsigned DEFAULT '0' COMMENT '发送时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_to` (`to_email`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件发送日志表';

DROP TABLE IF EXISTS `{prefix}email_queue`;
CREATE TABLE `{prefix}email_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_code` varchar(100) NOT NULL DEFAULT '',
  `to_email` varchar(255) NOT NULL,
  `vars` text COMMENT '模板变量JSON',
  `status` tinyint(4) DEFAULT '0' COMMENT '0待发 1已发 2失败',
  `retry_count` tinyint(4) DEFAULT '0',
  `max_retries` tinyint(4) DEFAULT '3' COMMENT '最大重试次数',
  `error_msg` varchar(500) DEFAULT '',
  `create_time` int(10) unsigned DEFAULT '0',
  `sent_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='邮件队列';

DROP TABLE IF EXISTS `{prefix}email_subscriber`;
CREATE TABLE `{prefix}email_subscriber` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱地址',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态:1订阅/0退订',
  `source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '来源页面',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件订阅表';

DROP TABLE IF EXISTS `{prefix}email_template`;
CREATE TABLE `{prefix}email_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模板标识: register/forgot_password/comment_notify/payment_success',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模板名称',
  `subject` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '邮件主题（支持变量）',
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '邮件正文HTML（支持变量）',
  `vars` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '可用变量(逗号分隔): username,site_name,content_title等',
  `is_enabled` tinyint(4) DEFAULT '1' COMMENT '启用状态',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件模板表';

INSERT INTO `{prefix}email_template` VALUES (1,'register','注册欢迎','欢迎注册{{site_name}}','<h2>欢迎加入{{site_name}}！</h2><p>亲爱的{{username}}，恭喜您成功注册。</p><p>您的账号：{{email}}</p>','username,site_name,email',1,1777774069,1777774069),(2,'forgot_password','密码找回','{{site_name}} - 密码找回','<h2>密码找回</h2><p>您好 {{username}}，请点击以下链接重置密码：</p><p><a href=\"{{reset_url}}\">重置密码</a></p><p>链接有效期30分钟。</p>','username,site_name,reset_url',1,1777774069,1777774069),(3,'comment_notify','评论通知','{{site_name}} - 您的文章有新评论','<h2>新评论通知</h2><p>您的文章《{{content_title}}》收到一条新评论：</p><blockquote>{{comment_content}}</blockquote><p>评论者：{{comment_author}}</p>','username,site_name,content_title,comment_content,comment_author',1,1777774069,1777774069),(4,'payment_success','付费成功','{{site_name}} - 付费成功通知','<h2>付费成功</h2><p>您已成功购买《{{content_title}}》，支付金额：{{amount}}元</p><p>感谢您的支持！</p>','username,site_name,content_title,amount',1,1777774069,1777774069),(5,'subscribe_confirm','订阅确认','请确认订阅【{site_name}】','<!DOCTYPE html><html><head><meta charset=\"utf-8\"></head><body style=\"font-family:Arial,sans-serif;background:#f5f5f5;padding:20px\"><div style=\"max-width:600px;margin:0 auto;background:#fff;border-radius:8px;padding:30px\"><h2 style=\"color:#333\">📬 确认订阅</h2><p style=\"color:#666\">您好！感谢您订阅<strong>{site_name}</strong>。</p><p style=\"color:#666\">请点击下方按钮确认：</p><div style=\"text-align:center;margin:30px 0\"><a href=\"{confirm_url}\" style=\"display:inline-block;background:#007bff;color:#fff;padding:12px 32px;border-radius:6px;text-decoration:none;font-size:16px\">确认订阅</a></div></div></body></html>','site_name,confirm_url',1,1780718635,1780718635),(6,'content_notify','新内容通知','【{site_name}】新内容发布：{title}','<!DOCTYPE html><html><head><meta charset=\"utf-8\"></head><body style=\"font-family:Arial,sans-serif;background:#f5f5f5;padding:20px\"><div style=\"max-width:600px;margin:0 auto;background:#fff;border-radius:8px;padding:30px\"><h2 style=\"color:#333\">{title}</h2><p style=\"color:#666;line-height:1.8\">{summary}</p><div style=\"text-align:center;margin:30px 0\"><a href=\"{content_url}\" style=\"display:inline-block;background:#007bff;color:#fff;padding:12px 32px;border-radius:6px;text-decoration:none;font-size:16px\">查看详情</a></div></div></body></html>','site_name,title,summary,content_url',1,1780718635,1780718635),(7,'subscribe_welcome','订阅欢迎','欢迎订阅【{site_name}】','<!DOCTYPE html><html><head><meta charset=\"utf-8\"></head><body style=\"font-family:Arial,sans-serif;background:#f5f5f5;padding:20px\"><div style=\"max-width:600px;margin:0 auto;background:#fff;border-radius:8px;padding:30px\"><h2 style=\"color:#333\">🎉 订阅成功</h2><p style=\"color:#666\">您已成功订阅<strong>{site_name}</strong>，我们将第一时间推送最新内容。</p></div></body></html>','site_name',1,1780718635,1780718635),(14,'content_publish','内容发布通知','【{site_name}】新内容发布：{content_title}','<!DOCTYPE html><html><head><meta charset=\"utf-8\"></head><body style=\"font-family:Arial,sans-serif;background:#f5f5f5;padding:20px\"><div style=\"max-width:600px;margin:0 auto;background:#fff;border-radius:8px;padding:30px\"><h2 style=\"color:#333\">{content_title}</h2><p style=\"color:#666\">{content_summary}</p><div style=\"text-align:center;margin:30px 0\"><a href=\"{content_url}\" style=\"display:inline-block;background:#007bff;color:#fff;padding:12px 32px;border-radius:6px;text-decoration:none;font-size:16px\">查看详情</a></div></div></body></html>','site_name,content_title,content_summary,content_url,content_cover,unsubscribe_url,subscriber_email',1,1780727316,1780727316),(15,'unsubscribe','退订确认','您已成功退订 {site_name} 的邮件通知','<!DOCTYPE html><html><head><meta charset=\"utf-8\"></head><body style=\"font-family:Arial,sans-serif;background:#f5f5f5;padding:20px\"><div style=\"max-width:600px;margin:0 auto;background:#fff;border-radius:8px;padding:30px\"><h2 style=\"color:#333\">退订确认</h2><p style=\"color:#666\">您已成功退订 <strong>{site_name}</strong> 的邮件通知，将不再收到相关内容推送。</p><p style=\"color:#666\">如想重新订阅，请 <a href=\"{subscribe_url}\">点击此处</a>。</p></div></body></html>','site_name,subscribe_url,subscriber_email',1,1780727316,1780727316);
DROP TABLE IF EXISTS `{prefix}encryption_key`;
CREATE TABLE `{prefix}encryption_key` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '密钥标识(唯一)',
  `key_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密钥名称(描述用途)',
  `encrypted_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '加密后的密钥值(用系统主密钥加密)',
  `algorithm` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AES-256-CBC' COMMENT '加密算法',
  `version` int(11) NOT NULL DEFAULT '1' COMMENT '密钥版本号',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1=当前使用 2=已轮换(仅解密) 3=已废弃',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建者用户ID',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rotated_at` datetime DEFAULT NULL COMMENT '轮换时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key_id` (`key_id`),
  KEY `idx_status` (`status`),
  KEY `idx_version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='V2.9.35 加密密钥管理';

DROP TABLE IF EXISTS `{prefix}export_log`;
CREATE TABLE `{prefix}export_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}export_schedule`;
CREATE TABLE `{prefix}export_schedule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}favorite`;
CREATE TABLE `{prefix}favorite` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `folder_id` int(11) DEFAULT '0' COMMENT '收藏夹ID',
  `content_id` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_content` (`user_id`,`content_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_folder_id` (`folder_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='收藏表';

DROP TABLE IF EXISTS `{prefix}favorite_folder`;
CREATE TABLE `{prefix}favorite_folder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `name` varchar(100) NOT NULL COMMENT '收藏夹名称',
  `description` varchar(500) DEFAULT '' COMMENT '收藏夹描述',
  `is_public` tinyint(4) DEFAULT '0' COMMENT '是否公开:0否1是',
  `sort` int(11) DEFAULT '99' COMMENT '排序',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='收藏夹表';

DROP TABLE IF EXISTS `{prefix}feature_registry`;
CREATE TABLE `{prefix}feature_registry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sprint_code` varchar(20) NOT NULL COMMENT '所属Sprint代码(R/Q/T2/AI2/UX/DOC)',
  `feature_code` varchar(50) NOT NULL COMMENT '功能点编码',
  `feature_name` varchar(200) NOT NULL COMMENT '功能点名称',
  `service_class` varchar(200) DEFAULT '' COMMENT '对应Service类',
  `controller_route` varchar(200) DEFAULT '' COMMENT '对应Controller路由',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态:1正常0禁用2异常',
  `health_check_url` varchar(500) DEFAULT '' COMMENT '健康检查URL',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_feature` (`sprint_code`,`feature_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='功能点注册表';

INSERT INTO `{prefix}feature_registry` VALUES (1,'R','R-1','皮肤&模板补全修复','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(2,'R','R-2','代码架构修复','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(3,'R','R-3','插件&API修复','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(4,'R','R-4','模板&市场修复','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(5,'R','R-5','其他零散修复回归','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(6,'Q','Q-1','功能验收脚本框架','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(7,'Q','Q-2','运行时功能看板','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(8,'Q','Q-3','V2.9.29全量功能回归测试脚本','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(9,'Q','Q-4','持续集成验收流水线','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(10,'Q','Q-5','验收报告自动生成','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(11,'Q','Q-6','模板质量自动检测增强','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(12,'T2','T2-1','模板商店个人中心','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(13,'T2','T2-2','模板收藏夹增强','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(14,'T2','T2-3','模板批量管理工具','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(15,'T2','T2-4','模板商店搜索增强','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(16,'T2','T2-5','模板分类管理增强','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(17,'AI2','AI2-1','AI批量内容改写','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(18,'AI2','AI2-2','AI SEO预览与优化','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(19,'AI2','AI2-3','AI智能配图基础版','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(20,'AI2','AI2-4','AI多风格写作扩展','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(21,'UX','UX-1','移动端体验增强','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(22,'UX','UX-2','页面加载性能优化','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(23,'UX','UX-3','后台交互体验优化','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(24,'DOC','DOC-1','系统配置文档','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(25,'DOC','DOC-2','API开放文档完善','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(26,'DOC','DOC-3','部署运维文档','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01');
DROP TABLE IF EXISTS `{prefix}form`;
CREATE TABLE `{prefix}form` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表单名称',
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表单唯一标识',
  `fields` json NOT NULL COMMENT '字段配置（JSON数组）',
  `fields_config` json DEFAULT NULL,
  `submit_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '提交' COMMENT '提交按钮文案',
  `success_msg` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '提交成功' COMMENT '提交成功提示',
  `success_action` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'message' COMMENT '提交后动作: message消息 redirect跳转',
  `redirect_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '跳转URL',
  `anti_spam` tinyint(4) DEFAULT '0' COMMENT '防刷: 0无 1验证码 2IP限制',
  `is_enabled` tinyint(4) DEFAULT '1',
  `sort` int(11) DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='表单定义表';

DROP TABLE IF EXISTS `{prefix}form_data`;
CREATE TABLE `{prefix}form_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `fields_data` json NOT NULL COMMENT '提交数据（JSON）',
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '提交者IP',
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `is_read` tinyint(4) DEFAULT '0' COMMENT '是否已读',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_form` (`form_id`),
  KEY `idx_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='表单提交数据表';

DROP TABLE IF EXISTS `{prefix}gdpr_request`;
CREATE TABLE `{prefix}gdpr_request` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}grayscale_log`;
CREATE TABLE `{prefix}grayscale_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}grayscale_release`;
CREATE TABLE `{prefix}grayscale_release` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}h5_config`;
CREATE TABLE `{prefix}h5_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(50) NOT NULL COMMENT '配置键',
  `config_value` json DEFAULT NULL COMMENT '配置值(JSON)',
  `user_center_config` json DEFAULT NULL COMMENT '用户中心配置(JSON)',
  `config_type` varchar(30) DEFAULT 'general' COMMENT '配置类型(theme/feature/performance/pwa)',
  `is_active` tinyint(4) DEFAULT '1' COMMENT '是否启用:1是0否',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_config_key` (`config_key`),
  KEY `idx_type` (`config_type`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='H5移动端配置表';

INSERT INTO `{prefix}h5_config` VALUES (1,'theme','{\"dark_mode\": \"auto\", \"font_size\": \"medium\", \"primary_color\": \"#1989fa\"}',NULL,'theme',1,'2026-07-22 11:36:27','2026-07-22 11:36:27'),(2,'feature','{\"enable_pwa\": true, \"enable_push\": true, \"enable_offline\": true, \"enable_payment\": true}',NULL,'feature',1,'2026-07-22 11:36:27','2026-07-22 11:36:27'),(3,'performance','{\"cache_ttl\": 300, \"lazy_load\": true, \"cdn_domain\": \"\", \"ssr_enabled\": true}',NULL,'performance',1,'2026-07-22 11:36:27','2026-07-22 11:36:27'),(4,'pwa','{\"name\": \"AI-CMS\", \"display\": \"standalone\", \"short_name\": \"CMS\", \"theme_color\": \"#1989fa\", \"background_color\": \"#ffffff\"}',NULL,'pwa',1,'2026-07-22 11:36:27','2026-07-22 11:36:27');
DROP TABLE IF EXISTS `{prefix}h5_user_config`;
CREATE TABLE `{prefix}h5_user_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL COMMENT '用户ID',
  `config_key` varchar(50) NOT NULL COMMENT '配置键',
  `config_value` json DEFAULT NULL COMMENT '配置值(JSON)',
  `config_type` varchar(30) DEFAULT 'preference' COMMENT '配置类型(preference/notification/security/layout)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_config` (`member_id`,`config_key`),
  KEY `idx_member` (`member_id`),
  KEY `idx_type` (`config_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='H5用户配置表';

DROP TABLE IF EXISTS `{prefix}i18n_content_group`;
CREATE TABLE `{prefix}i18n_content_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}i18n_content_link`;
CREATE TABLE `{prefix}i18n_content_link` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}image_task`;
CREATE TABLE `{prefix}image_task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '外部任务ID(FLUX返回的id)',
  `provider` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'flux' COMMENT 'Provider标识(flux/dalle/tongyi_wanxiang)',
  `poll_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT '' COMMENT '轮询URL',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0pending/1processing/2completed/3failed',
  `prompt` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT '' COMMENT '生成提示词',
  `result` json DEFAULT NULL COMMENT '生成结果JSON',
  `attempts` tinyint(4) DEFAULT '0' COMMENT '轮询尝试次数',
  `max_attempts` tinyint(4) DEFAULT '30' COMMENT '最大尝试次数(30次≈90秒超时)',
  `related_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT '' COMMENT '关联类型(content/batch)',
  `related_id` int(10) unsigned DEFAULT '0' COMMENT '关联ID',
  `error_msg` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT '' COMMENT '错误信息',
  `retry_count` tinyint(4) DEFAULT '0' COMMENT '失败重试次数(最多3次)',
  `local_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT '' COMMENT '本地存储路径(M17 AI配图URL本地化)',
  `create_time` int(10) unsigned DEFAULT '0',
  `update_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_task_id` (`task_id`),
  KEY `idx_status` (`status`),
  KEY `idx_related` (`related_type`,`related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='配图异步任务表';

DROP TABLE IF EXISTS `{prefix}invite_relation`;
CREATE TABLE `{prefix}invite_relation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `inviter_id` int(10) unsigned NOT NULL COMMENT '邀请人',
  `invitee_id` int(10) unsigned NOT NULL COMMENT '被邀请人',
  `invite_code` varchar(32) NOT NULL COMMENT '邀请码',
  `invitee_ip` varchar(45) DEFAULT '' COMMENT '被邀请人IP(防刷审计)',
  `reward_points` int(11) DEFAULT '0' COMMENT '已发放积分',
  `reward_stage` tinyint(4) DEFAULT '0' COMMENT '0注册/1首次签到/2首次付费',
  `create_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_invitee` (`invitee_id`),
  KEY `idx_inviter` (`inviter_id`),
  KEY `idx_code` (`invite_code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='邀请关系表';

INSERT INTO `{prefix}invite_relation` VALUES (1,1,0,'494f3b22','',0,0,1779357427);
DROP TABLE IF EXISTS `{prefix}lang`;
CREATE TABLE `{prefix}lang` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}lang_pack`;
CREATE TABLE `{prefix}lang_pack` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang_code` varchar(10) NOT NULL COMMENT '语言代码(zh-cn/en/jp/ko)',
  `module` varchar(30) DEFAULT 'frontend' COMMENT '模块(frontend/backend/plugin/template)',
  `group_name` varchar(50) DEFAULT 'general' COMMENT '分组名称',
  `entry_key` varchar(200) NOT NULL COMMENT '翻译条目键名',
  `entry_value` text COMMENT '翻译条目值',
  `entry_original` text COMMENT '原始语言值(参考)',
  `is_translated` tinyint(4) DEFAULT '0' COMMENT '是否已翻译:1是0否',
  `is_using_ai` tinyint(4) DEFAULT '0' COMMENT '是否AI翻译:1是0否',
  `is_system` tinyint(4) DEFAULT '0' COMMENT '系统条目:1是0否(不可删除)',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `version` int(11) DEFAULT '1' COMMENT '版本号',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_entry` (`lang_code`,`module`,`group_name`,`entry_key`),
  KEY `idx_lang` (`lang_code`),
  KEY `idx_module` (`module`),
  KEY `idx_group` (`group_name`),
  KEY `idx_translated` (`is_translated`),
  KEY `idx_version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='语言包条目表';

DROP TABLE IF EXISTS `{prefix}lang_pack_snapshot`;
CREATE TABLE `{prefix}lang_pack_snapshot` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang_code` varchar(10) NOT NULL COMMENT '语言代码',
  `module` varchar(30) DEFAULT 'frontend' COMMENT '模块',
  `version` int(11) NOT NULL COMMENT '版本号',
  `snapshot_data` json NOT NULL COMMENT '快照数据(全量条目JSON)',
  `entry_count` int(11) DEFAULT '0' COMMENT '条目数量',
  `translated_count` int(11) DEFAULT '0' COMMENT '已翻译数量',
  `completion_rate` decimal(5,2) DEFAULT '0.00' COMMENT '完成率(%)',
  `created_by` int(11) DEFAULT '0' COMMENT '创建人(管理员ID)',
  `create_reason` varchar(100) DEFAULT '' COMMENT '创建原因(auto_save/manual/publish)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lang_version` (`lang_code`,`module`,`version`),
  KEY `idx_lang` (`lang_code`),
  KEY `idx_module` (`module`),
  KEY `idx_version` (`version`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='语言包版本快照表';

DROP TABLE IF EXISTS `{prefix}lang_site`;
CREATE TABLE `{prefix}lang_site` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang_code` varchar(10) NOT NULL COMMENT '语言代码(zh-CN/en-US/ja-JP等)',
  `site_name` varchar(100) NOT NULL COMMENT '站点名称',
  `site_domain` varchar(200) DEFAULT '' COMMENT '独立域名',
  `url_prefix` varchar(20) DEFAULT '' COMMENT 'URL前缀(如/en/)',
  `url_mode` varchar(10) DEFAULT 'prefix' COMMENT 'URL模式(prefix/subdomain/domain)',
  `template_id` int(11) DEFAULT '0' COMMENT '关联模板ID',
  `timezone` varchar(50) DEFAULT 'Asia/Shanghai' COMMENT '时区',
  `currency` varchar(10) DEFAULT 'CNY' COMMENT '货币',
  `is_default` tinyint(4) DEFAULT '0' COMMENT '是否默认站点',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态:1启用0禁用',
  `site_config` json DEFAULT NULL COMMENT '站点配置(JSON)',
  `create_time` int(10) unsigned DEFAULT '0',
  `update_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lang` (`lang_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='多语言站点表';

INSERT INTO `{prefix}lang_site` VALUES (1,'zh-CN','默认站点','','','prefix',0,'Asia/Shanghai','CNY',1,1,NULL,1783830086,1783830086);
DROP TABLE IF EXISTS `{prefix}language`;
CREATE TABLE `{prefix}language` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '语言代码: zh-CN/en-US',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '语言名称',
  `is_default` tinyint(4) DEFAULT '0' COMMENT '是否默认',
  `is_enabled` tinyint(4) DEFAULT '1' COMMENT '启用状态',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='语言表';

INSERT INTO `{prefix}language` VALUES (1,'zh-CN','简体中文',1,1,1),(2,'en-US','English',0,1,2);
DROP TABLE IF EXISTS `{prefix}licenses`;
CREATE TABLE `{prefix}licenses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `license_code` varchar(64) NOT NULL DEFAULT '' COMMENT '许可证编码(唯一)',
  `product_type` varchar(20) NOT NULL DEFAULT '' COMMENT '产品类型: plugin/template',
  `product_code` varchar(100) NOT NULL DEFAULT '' COMMENT '产品编码',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属用户',
  `license_type` varchar(20) NOT NULL DEFAULT 'standard' COMMENT '类型: standard/pro/lifetime',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT '状态: active/suspended/revoked/expired',
  `bind_domain` varchar(255) NOT NULL DEFAULT '' COMMENT '绑定域名',
  `valid_from` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '有效期开始',
  `valid_until` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '有效期结束',
  `last_verified` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后验证时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `license_code` (`license_code`),
  KEY `user_id` (`user_id`),
  KEY `product_type_code` (`product_type`,`product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='许可证表';

DROP TABLE IF EXISTS `{prefix}like`;
CREATE TABLE `{prefix}like` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_id` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_content` (`user_id`,`content_id`),
  KEY `idx_content` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='点赞记录表';

DROP TABLE IF EXISTS `{prefix}link`;
CREATE TABLE `{prefix}link` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '网站名称',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分组ID',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '网站描述',
  `contact` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '联系人/邮箱',
  `is_apply` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否申请中:0否/1是',
  `url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '链接地址',
  `logo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'Logo地址',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`,`sort`),
  KEY `idx_group_status` (`group_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='友情链接表';

DROP TABLE IF EXISTS `{prefix}link_group`;
CREATE TABLE `{prefix}link_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分组名称',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='友情链接分组表';

DROP TABLE IF EXISTS `{prefix}log`;
CREATE TABLE `{prefix}log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `module` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模块',
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作',
  `target` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '操作对象',
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'IP地址',
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '操作数据',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_time` (`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=390 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表';

INSERT INTO `{prefix}log` VALUES (319,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784700999),(320,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784701012),(321,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784701064),(322,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784701095),(323,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784705174),(324,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784709330),(325,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784732520),(326,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784733174),(327,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784736428),(328,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784737398),(329,1,'LogController','清理日志','清理7天前的日志，共128条','127.0.0.1','',1784737504),(330,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784744989),(331,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784860156),(332,1,'SystemController','保存系统配置','','127.0.0.1','{\"site_name\":\"AI-CMS v2.9.40\",\"site_keywords\":\"AI,CMS,内容管理\",\"site_description\":\"AI驱动的企业信息管理系统\",\"site_logo\":\"\\/assets\\/images\\/logo_ico.png\",\"logo_icon_only\":\"1\",\"logo_name\":\"\",\"site_icp\":\"\",\"site_copyright\":\"\",\"site_stat_code\":\"\",\"language_switcher_enabled\":\"0\",\"language_sitewide\":\"0\",\"upload_max_size\":\"10\",\"upload_image_ext\":\"jpg,jpeg,png,gif,webp,svg\",\"upload_video_ext\":\"mp4,webm,ogg\",\"upload_file_ext\":\"pdf,doc,docx,xls,xlsx,zip,rar\",\"captcha_enabled\":\"1\",\"captcha_login\":\"0\",\"captcha_register\":\"1\",\"captcha_comment\":\"0\",\"captcha_form\":\"0\",\"captcha_fail_limit\":\"3\",\"encrypt_cipher\":\"AES-256-CBC\",\"captcha_type\":\"math\",\"captcha_enabled_forms\":\"\",\"csrf_front_enabled\":\"1\",\"xss_log_enabled\":\"1\",\"admin_captcha_enabled\":\"0\",\"captcha_tencent_appid\":\"\",\"wechat_share_appid\":\"\",\"social_share_enabled\":\"1\",\"__token__\":\"8dbc5cab2ca3e9928a071bb794a1b6ff\"}',1784860228),(333,1,'SystemController','保存系统配置','','127.0.0.1','{\"site_name\":\"AI-CMS v2.9.40\",\"site_keywords\":\"AI,CMS,内容管理\",\"site_description\":\"AI驱动的企业信息管理系统\",\"site_logo\":\"\\/assets\\/images\\/logo_ico.png\",\"logo_icon_only\":\"1\",\"logo_name\":\"\",\"site_icp\":\"\",\"site_copyright\":\"\",\"site_stat_code\":\"\",\"language_switcher_enabled\":\"0\",\"language_sitewide\":\"0\",\"upload_max_size\":\"10\",\"upload_image_ext\":\"jpg,jpeg,png,gif,webp,svg\",\"upload_video_ext\":\"mp4,webm,ogg\",\"upload_file_ext\":\"pdf,doc,docx,xls,xlsx,zip,rar\",\"captcha_enabled\":\"1\",\"captcha_login\":\"0\",\"captcha_register\":\"1\",\"captcha_comment\":\"0\",\"captcha_form\":\"0\",\"captcha_fail_limit\":\"3\",\"encrypt_cipher\":\"AES-256-CBC\",\"captcha_type\":\"math\",\"captcha_enabled_forms\":\"\",\"csrf_front_enabled\":\"1\",\"xss_log_enabled\":\"1\",\"admin_captcha_enabled\":\"0\",\"captcha_tencent_appid\":\"\",\"wechat_share_appid\":\"\",\"social_share_enabled\":\"1\",\"__token__\":\"8dbc5cab2ca3e9928a071bb794a1b6ff\"}',1784860236),(334,1,'SystemController','保存系统配置','','127.0.0.1','{\"sse_max_connections_per_ip\":\"5\",\"sse_max_connections_per_user\":\"3\",\"sse_connection_timeout\":\"1800\",\"sse_heartbeat_interval\":\"30\",\"sse_message_ttl\":\"3600\",\"sse_offline_message_limit\":\"100\",\"rss_enabled\":\"1\",\"rss_cache_ttl\":\"600\",\"app_version\":\"2.9.40\",\"version\":\"V2.9.40\",\"cdn_enabled\":\"0\",\"cdn_domain\":\"\",\"backup_keep_count\":\"10\"}',1784860307),(335,1,'SystemController','保存系统配置','','127.0.0.1','{\"site_name\":\"AI-CMS v2.9.40\",\"site_keywords\":\"AI,CMS,内容管理\",\"site_description\":\"AI驱动的企业信息管理系统\",\"site_logo\":\"\\/assets\\/images\\/logo_ico.png\",\"logo_icon_only\":\"1\",\"logo_name\":\"\",\"site_icp\":\"\",\"site_copyright\":\"\",\"site_stat_code\":\"\",\"language_switcher_enabled\":\"0\",\"language_sitewide\":\"0\",\"upload_max_size\":\"10\",\"upload_image_ext\":\"jpg,jpeg,png,gif,webp,svg\",\"upload_video_ext\":\"mp4,webm,ogg\",\"upload_file_ext\":\"pdf,doc,docx,xls,xlsx,zip,rar\",\"captcha_enabled\":\"1\",\"captcha_login\":\"1\",\"captcha_register\":\"1\",\"captcha_comment\":\"0\",\"captcha_form\":\"0\",\"captcha_fail_limit\":\"3\",\"encrypt_cipher\":\"AES-256-CBC\",\"captcha_type\":\"math\",\"captcha_enabled_forms\":\"\",\"csrf_front_enabled\":\"1\",\"xss_log_enabled\":\"1\",\"admin_captcha_enabled\":\"0\",\"captcha_tencent_appid\":\"\",\"wechat_share_appid\":\"\",\"social_share_enabled\":\"1\"}',1784860558),(336,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784860576),(337,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784860961),(338,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784861200),(339,1,'SystemController','保存系统配置','','127.0.0.1','{\"captcha_enabled\":\"1\",\"captcha_login\":\"1\",\"captcha_register\":\"1\",\"captcha_comment\":\"0\",\"captcha_form\":\"0\",\"captcha_fail_limit\":\"3\",\"encrypt_cipher\":\"AES-256-CBC\",\"captcha_type\":\"math\",\"captcha_enabled_forms\":\"\",\"csrf_front_enabled\":\"1\",\"xss_log_enabled\":\"1\",\"admin_captcha_enabled\":\"1\",\"captcha_tencent_appid\":\"\",\"sse_max_connections_per_ip\":\"5\",\"sse_max_connections_per_user\":\"3\",\"sse_connection_timeout\":\"1800\",\"sse_heartbeat_interval\":\"30\",\"sse_message_ttl\":\"3600\",\"sse_offline_message_limit\":\"100\",\"rss_enabled\":\"1\",\"rss_cache_ttl\":\"600\",\"app_version\":\"2.9.40\",\"version\":\"V2.9.40\",\"cdn_enabled\":\"0\",\"cdn_domain\":\"\",\"backup_keep_count\":\"10\",\"__token__\":\"8a62a2ad93f109c8689f476165cf7080\"}',1784861229),(340,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784861778),(341,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784862087),(342,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784862443),(343,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784863658),(344,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784863736),(345,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784866057),(346,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784867069),(347,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784867935),(348,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784870024),(349,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784870308),(350,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784870412),(351,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784870427),(352,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784870503),(353,1,'SystemController','保存系统配置','','127.0.0.1','{\"captcha_enabled\":\"1\",\"captcha_login\":\"1\",\"captcha_register\":\"1\",\"captcha_comment\":\"0\",\"captcha_form\":\"0\",\"captcha_fail_limit\":\"3\",\"encrypt_cipher\":\"AES-256-CBC\",\"captcha_type\":\"math\",\"captcha_enabled_forms\":\"\",\"csrf_front_enabled\":\"1\",\"xss_log_enabled\":\"1\",\"admin_captcha_enabled\":\"1\",\"captcha_driver\":\"local\",\"captcha_tencent_appid\":\"\",\"captcha_tencent_secret\":\"\",\"sse_max_connections_per_ip\":\"5\",\"sse_max_connections_per_user\":\"3\",\"sse_connection_timeout\":\"1800\",\"sse_heartbeat_interval\":\"30\",\"sse_message_ttl\":\"3600\",\"sse_offline_message_limit\":\"100\",\"rss_enabled\":\"1\",\"rss_cache_ttl\":\"600\",\"app_version\":\"2.9.40\",\"version\":\"V2.9.40\",\"search_engine\":\"mysql\",\"cdn_enabled\":\"0\",\"cdn_domain\":\"\",\"backup_keep_count\":\"10\"}',1784871170),(354,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784871198),(355,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784871704),(356,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784871783),(357,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784872923),(358,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784873646),(359,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784873806),(360,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784873823),(361,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784875392),(362,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784875697),(363,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784875812),(364,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784876347),(365,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784876448),(366,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784877743),(367,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784879518),(368,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784880082),(369,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784880177),(370,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784880421),(371,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784880860),(372,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784881166),(373,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784881907),(374,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784882054),(375,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784882089),(376,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784882240),(377,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784882488),(378,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784882708),(379,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784883697),(380,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784886123),(381,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784886611),(382,1,'LogController','清理日志','清理7天前的日志，共0条','127.0.0.1','',1784889116),(383,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784889308),(384,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784890479),(385,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784890655),(386,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784892092),(387,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784892601),(388,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784892997),(389,1,'cache','清除全部缓存','','127.0.0.1','{\"type\":\"all\"}',1784893254);
DROP TABLE IF EXISTS `{prefix}mail_log`;
CREATE TABLE `{prefix}mail_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `subscriber_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联订阅者ID',
  `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联内容ID(可空)',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '收件人邮箱',
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮件主题',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态: 0=待发送, 1=已发送, 2=失败',
  `error_msg` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '失败原因',
  `sent_at` datetime DEFAULT NULL COMMENT '发送时间',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_subscriber_id` (`subscriber_id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件发送日志';

DROP TABLE IF EXISTS `{prefix}mail_template`;
CREATE TABLE `{prefix}mail_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}media`;
CREATE TABLE `{prefix}media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '上传用户ID',
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '原始文件名',
  `filepath` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件路径',
  `filetype` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'image' COMMENT '文件类型:image/video/file',
  `mimetype` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'MIME类型',
  `filesize` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小(字节)',
  `cate_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `alt_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '替代文本/描述',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_type` (`filetype`),
  KEY `idx_cate` (`cate_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='媒体资源表';

DROP TABLE IF EXISTS `{prefix}member`;
CREATE TABLE `{prefix}member` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `wechat_openid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '微信openid',
  `wechat_unionid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '微信unionid',
  `wechat_nickname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '微信昵称',
  `wechat_avatar` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '微信头像',
  `wechat_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '微信绑定手机号',
  `mini_login_time` datetime DEFAULT NULL COMMENT '最后小程序登录时间',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '头像',
  `invite_code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '邀请码',
  `inviter_id` int(10) unsigned DEFAULT '0' COMMENT '邀请人ID',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `last_login_time` int(10) unsigned NOT NULL DEFAULT '0',
  `last_login_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  `level_id` int(11) DEFAULT '1' COMMENT '会员等级ID',
  `points` int(11) DEFAULT '0' COMMENT '当前积分',
  `total_points` int(11) DEFAULT '0' COMMENT '累计获得积分',
  `signin_count` int(11) DEFAULT '0' COMMENT '连续签到天数',
  `last_signin_date` date DEFAULT NULL COMMENT '最后签到日期',
  `grace_end_time` int(10) unsigned DEFAULT '0' COMMENT '降级缓冲期截止时间(0=无缓冲期)',
  `level_expire_time` int(10) unsigned DEFAULT '0' COMMENT '等级有效期时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  UNIQUE KEY `uk_invite_code` (`invite_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='前台会员表';

INSERT INTO `{prefix}member` VALUES (1,'test','test555@163.com','','','','','',NULL,'$2y$12$8.PWlG3NqW3JLwLv26CSP.Ynqfhe9.pFhqsYwOT7G/.T8ZTiwfXLW','test','','',0,1,1780656570,'172.18.0.1',1777220333,1780930064,2,20,20,1,'2026-05-21',0,0);
DROP TABLE IF EXISTS `{prefix}member_downgrade_log`;
CREATE TABLE `{prefix}member_downgrade_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `from_level` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '原等级ID',
  `to_level` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '目标等级ID',
  `action` varchar(20) NOT NULL DEFAULT '' COMMENT '操作: auto_downgrade/auto_upgrade/manual',
  `trigger_condition` varchar(100) NOT NULL DEFAULT '' COMMENT '触发条件: points_insufficient/grace_expired/admin_manual',
  `notified` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否已通知',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_user_time` (`user_id`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会员降级日志';

DROP TABLE IF EXISTS `{prefix}member_favorite`;
CREATE TABLE `{prefix}member_favorite` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `content_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_content` (`member_id`,`content_id`),
  KEY `idx_content` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员收藏表';

DROP TABLE IF EXISTS `{prefix}member_level`;
CREATE TABLE `{prefix}member_level` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '等级名称',
  `min_points` int(11) DEFAULT '0' COMMENT '所需最低积分',
  `max_points` int(11) DEFAULT '9999999' COMMENT '所需积分(最高值)',
  `discount` tinyint(4) DEFAULT '100' COMMENT '付费内容折扣百分比（100=无折扣）',
  `allow_download` tinyint(4) DEFAULT '0' COMMENT '是否允许下载附件',
  `allow_comment_no_review` tinyint(4) DEFAULT '0' COMMENT '是否评论免审核',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '等级图标',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `level_order` int(11) DEFAULT '1' COMMENT '等级排序',
  `is_default` tinyint(4) DEFAULT '0' COMMENT '是否默认等级',
  `is_vip` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否VIP等级(1=VIP有效期内免费阅读付费内容)',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  `benefits` json DEFAULT NULL COMMENT '等级权益(JSON)',
  `auto_upgrade` tinyint(4) DEFAULT '1' COMMENT '是否自动升级',
  `validity_days` int(11) DEFAULT '0' COMMENT '有效期天数(0为永久)',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态:1启用0禁用',
  PRIMARY KEY (`id`),
  KEY `idx_min_points` (`min_points`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员等级表';

INSERT INTO `{prefix}member_level` VALUES (1,'注册会员',0,99,100,0,0,'badge-lv1',1,1,1,0,1777457479,1777457479,NULL,1,0,1),(2,'正式会员',100,499,95,0,1,'badge-lv2',2,1,0,0,1777457479,1777457479,NULL,1,0,1),(3,'高级会员',500,1999,90,0,1,'badge-lv3',3,1,0,0,1777457479,1777457479,NULL,1,0,1),(4,'VIP会员',2000,4999,80,0,1,'badge-lv4',4,1,0,0,1777457479,1777457479,NULL,1,0,1),(5,'至尊会员',5000,9999999,70,0,1,'badge-lv5',5,1,0,0,1777457479,1777457479,NULL,1,0,1);
DROP TABLE IF EXISTS `{prefix}member_like`;
CREATE TABLE `{prefix}member_like` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `content_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_content` (`member_id`,`content_id`),
  KEY `idx_content` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员点赞表';

DROP TABLE IF EXISTS `{prefix}member_oauth`;
CREATE TABLE `{prefix}member_oauth` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `provider` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '平台:gitee/wechat/qq/weibo',
  `openid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '平台唯一标识',
  `unionid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '平台UnionID',
  `access_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'Access Token',
  `refresh_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'Refresh Token',
  `expire_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Token过期时间',
  `nickname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '平台昵称',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '平台头像',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provider_openid` (`provider`,`openid`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员OAuth绑定表';

DROP TABLE IF EXISTS `{prefix}member_points_log`;
CREATE TABLE `{prefix}member_points_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL COMMENT '会员ID',
  `points` int(11) NOT NULL COMMENT '变动积分数(正增/负减)',
  `balance` int(11) NOT NULL COMMENT '变动后余额',
  `type` varchar(30) NOT NULL COMMENT '类型(login/publish/comment/like/share/pay_read/download/exchange/transfer/admin)',
  `source` varchar(100) DEFAULT '' COMMENT '来源说明',
  `ref_id` int(11) DEFAULT '0' COMMENT '关联ID',
  `create_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_type` (`type`),
  KEY `idx_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会员积分流水表';

DROP TABLE IF EXISTS `{prefix}member_segment_member`;
CREATE TABLE `{prefix}member_segment_member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}menu_group`;
CREATE TABLE `{prefix}menu_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分组名称',
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分组标识',
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '图标类名',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态:0禁用1启用',
  `create_time` int(10) unsigned DEFAULT '0',
  `update_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `sort_status` (`sort`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台菜单分组表';

INSERT INTO `{prefix}menu_group` VALUES (1,'内容管理','group_1','bi bi-file-text',10,1,1779393242,1779393242),(2,'用户管理','group_2','bi bi-people',20,1,1779393242,1779393242),(3,'运营管理','group_3','bi bi-shop',30,1,1779393242,1779393242),(4,'系统设置','group_4','bi bi-gear',40,1,1779393243,1779393243),(5,'互动管理','group_5','bi bi-chat-dots',50,1,1779393242,1779393242),(6,'SEO与数据','group_6','bi bi-bar-chart',60,1,1779393242,1779393242),(7,'AI中心','group_7','bi bi-robot',70,1,1779393242,1779393242),(8,'内容生态','group_8','bi bi-globe2',80,1,1779393242,1779393242),(9,'平台扩展','group_9','bi bi-puzzle',90,1,1779393242,1779393242);
DROP TABLE IF EXISTS `{prefix}menu_item`;
CREATE TABLE `{prefix}menu_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(10) unsigned NOT NULL COMMENT '所属分组ID',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父级ID(0为一级)',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '菜单名称',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '链接地址',
  `permission` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '权限标识',
  `active` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '激活标识',
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '图标类名',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态:0禁用1启用',
  `module` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '所属模块',
  `create_time` int(10) unsigned DEFAULT '0',
  `update_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `group_parent_status` (`group_id`,`parent_id`,`status`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=934 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台菜单项表';

INSERT INTO `{prefix}menu_item` VALUES (11,1,1,'信息管理','/admin/content/index','content.*','content','bi bi-file-text',1,1,NULL,1779393242,1780716034),(12,1,1,'分类管理','/admin/cate/index','cate.*','cate','bi bi-folder2',2,1,NULL,1779393242,1780716034),(13,1,1,'标签管理','/admin/tag/index','tag.*','tag','bi bi-tags',3,1,NULL,1779393242,1780716034),(14,1,1,'回收站','/admin/content/recycleBin','content.recycle','recycle','bi bi-trash3',4,1,NULL,1779393242,1780716034),(15,1,1,'媒体资源库','/admin/media/index','media.*','media','bi bi-images',5,1,NULL,1779393242,1780716034),(16,1,1,'内容审核','/admin/review/index','review.*','review','bi bi-patch-check',6,1,NULL,1779393242,1780716034),(21,2,2,'用户列表','/admin/user/index','user.*','user','bi bi-people',10,1,NULL,1779393242,1780716034),(27,2,2,'会员等级','/admin/member_level/index','member_level.*','member_level','bi bi-award',11,1,NULL,1779393242,1780716034),(28,2,2,'积分规则','/admin/points_rule/index','points.*','points_rule','bi bi-star',14,1,NULL,1779393242,1780716034),(29,2,2,'积分商品','/admin/points_product/index','points_product.*','points_product','bi bi-gift',15,1,NULL,1779393242,1780716034),(33,3,3,'轮播图管理','/admin/banner/index','banner.*','banner','bi bi-images',18,1,NULL,1779393242,1780716034),(34,3,3,'友情链接','/admin/link/index','link.*','link','bi bi-link-45deg',19,1,NULL,1779393242,1780716034),(35,3,3,'友链分组','/admin/link_group/index','link.*','link_group','bi bi-folder2-open',20,1,NULL,1779393242,1780716034),(36,3,3,'广告管理','/admin/ad/index','ad.*','ad','bi bi-badge-ad',21,1,NULL,1779393242,1780716034),(41,4,4,'系统配置','/admin/system/config','system.*','system_config','bi bi-gear',76,1,NULL,1779393243,1780716035),(42,4,4,'操作日志','/admin/log/index','system.log','log','bi bi-journal-text',77,1,NULL,1779393243,1780716035),(43,4,4,'数据库备份','/admin/backup/index','backup.*','backup','bi bi-database',78,1,NULL,1779393243,1780716035),(44,4,4,'通知中心','/admin/notification/index','notification.*','notification','bi bi-bell',79,1,NULL,1779393243,1780716035),(47,3,3,'表单管理','/admin/form/index','form.*','form','bi bi-card-checklist',22,1,NULL,1779393242,1780716034),(48,3,3,'优惠券','/admin/coupon/index','coupon.*','coupon','bi bi-ticket-perforated',23,1,NULL,1779393242,1780716034),(49,4,4,'邮件订阅','/admin/email_subscriber/index','email_subscriber.*','email_subscriber','bi bi-envelope',81,1,NULL,1779393243,1780716035),(50,4,4,'访问归档','/admin/visit_archive/index','visit_archive.*','visit_archive','bi bi-archive',82,1,NULL,1779393243,1780716035),(51,5,5,'评论管理','/admin/comment/index','comment.*','comment','bi bi-chat-left-text',25,1,NULL,1779393242,1780716034),(52,5,5,'前台会员','/admin/member/index','member.*','member','bi bi-person-badge',27,1,NULL,1779393242,1780716034),(53,5,5,'付费订单','/admin/paid_order/index','paid_order.*','paid_order','bi bi-credit-card',29,1,NULL,1779393242,1780716034),(54,5,5,'支付管理','/admin/payment/index','payment.*','payment','bi bi-wallet2',33,1,NULL,1779393242,1780716034),(55,5,5,'收入统计','/admin/payment/revenue','payment.*','payment_revenue','bi bi-cash-stack',34,1,NULL,1779393242,1780716034),(56,5,5,'系统通知','/admin/message/system','message.*','message_system','bi bi-bell',30,1,NULL,1779393242,1780716034),(57,5,5,'发送通知','/admin/message/sendSystem','message.*','message_send','bi bi-send-plus',31,1,NULL,1779393242,1780716034),(58,4,4,'验证码配置','/admin/captcha/config','captcha.*','captcha','bi bi-shield-check',83,1,NULL,1779393243,1780716035),(59,4,4,'存储配置','/admin/storage/config','storage.*','storage_config','bi bi-hdd-network',84,1,NULL,1779393243,1780716035),(60,6,6,'数据看板','/admin/dashboard/index','dashboard.*','dashboard','bi bi-speedometer2',45,1,NULL,1779393242,1780716034),(61,6,6,'SEO管理','/admin/seo/index','seo.*','seo','bi bi-search',46,1,NULL,1779393242,1780716034),(62,6,6,'数据导出','/admin/export/index','export.*','export','bi bi-download',50,1,NULL,1779393242,1780716034),(63,6,6,'API令牌','/admin/token/index','token.*','token','bi bi-key',52,1,NULL,1779393242,1780716034),(64,6,6,'SEO关键词','/admin/seo_keyword/index','seo_keyword.*','seo_keyword','bi bi-hash',48,1,NULL,1779393242,1780716034),(65,6,6,'关键词分组','/admin/seo_keyword/group','seo_keyword.*','seo_keyword_group','bi bi-folder',49,1,NULL,1779393242,1780716034),(66,6,6,'流量分析','/admin/traffic/index','traffic.*','traffic','bi bi-graph-up',54,1,NULL,1779393242,1780716034),(67,6,6,'AI统计','/admin/aiStat/index','ai_stat.*','ai_stat','bi bi-robot',55,1,NULL,1779393242,1780716034),(68,6,6,'数据报告','/admin/report/index','report.*','report','bi bi-graph-up-arrow',56,1,NULL,1779393242,1780716034),(69,6,6,'系统监控','/admin/monitor/index','monitor.*','monitor','bi bi-speedometer2',53,1,NULL,1779393242,1780716034),(70,4,4,'菜单管理','/admin/menu_manager/index','menu_manager.*','menu_manager','bi bi-list-nested',85,1,NULL,1779393243,1780716035),(71,7,7,'AI模型管理','/admin/ai_model/index','ai_model.*','ai_model','bi bi-cpu',36,1,NULL,1779393242,1780716034),(72,7,7,'AI调用日志','/admin/ai_log/index','ai_log.*','ai_log','bi bi-journal-code',37,1,NULL,1779393242,1780716034),(73,7,7,'AI批量生成','/admin/ai_batch/index','ai_batch.*','ai_batch','bi bi-magic',38,1,NULL,1779393242,1780716034),(74,7,7,'AI内容模板','/admin/ai_template/index','ai_template.*','ai_template','bi bi-file-earmark-text',39,1,NULL,1779393242,1780716034),(75,7,7,'模板设计器','/admin/template_design/index','template_design.*','template_design','bi bi-palette',40,1,NULL,1779393242,1780716034),(76,7,7,'AI翻译管理','/admin/ai_translation/index','ai_translation.*','ai_translation','bi bi-translate',41,1,NULL,1779393242,1780716034),(77,7,7,'AI配置','/admin/system/aiConfig','ai_config.*','ai_config','bi bi-sliders',43,1,NULL,1779393242,1780716034),(78,7,0,'AI主题管理','/admin/ai_theme/index','ai_theme.*','ai_theme','bi bi-palette',780,1,NULL,0,0),(81,8,8,'采集源管理','/admin/collect_source/index','collect.*','collect_source','bi bi-cloud-download',60,1,NULL,1779393242,1780716035),(82,8,8,'采集日志','/admin/collect_log/index','collect.*','collect_log','bi bi-journal',61,1,NULL,1779393242,1780716035),(83,8,8,'发布平台','/admin/publish_platform/index','publish.*','publish_platform','bi bi-send',62,1,NULL,1779393242,1780716035),(84,8,8,'发布记录','/admin/publish_log/index','publish.*','publish_log','bi bi-clock-history',63,1,NULL,1779393242,1780716035),(85,8,8,'邮件模板','/admin/email_template/index','email.*','email_template','bi bi-envelope-paper',64,1,NULL,1779393242,1780716035),(86,8,8,'邮件日志','/admin/email_log/index','email.*','email_log','bi bi-envelope-check',65,1,NULL,1779393242,1780716035),(91,9,9,'插件管理','/admin/plugin/index','plugin.*','plugin','bi bi-plug',67,1,NULL,1779393242,1780716035),(92,9,9,'多语言管理','/admin/language/index','language.*','language','bi bi-translate',69,1,NULL,1779393243,1780716035),(93,9,9,'模板市场','/admin/theme_market/index','theme_market.*','theme_market','bi bi-palette2',70,1,NULL,1779393243,1780716035),(94,9,9,'API文档','/admin/api_doc/index','apidoc.*','api_doc','bi bi-file-code',74,1,NULL,1779393243,1780716035),(161,1,1,'审批工作流','/admin/workflow/index','workflow.*','workflow','bi bi-journal-check',7,1,NULL,1779393242,1780716034),(162,1,1,'审批记录','/admin/workflow/records','workflow.*','workflow_records','bi bi-clock-history',8,1,NULL,1779393242,1780716034),(210,2,2,'兑换记录','/admin/points_exchange/index','points_exchange.*','points_exchange','bi bi-arrow-left-right',16,1,NULL,1779393242,1780716034),(271,2,2,'权益配置','/admin/member_benefit/index','member_benefit.*','member_benefit','bi bi-stars',12,1,NULL,1779393242,1780716034),(272,2,2,'会员等级管理','/admin/member_benefit/members','member_benefit.*','member_benefit_members','bi bi-people',13,1,NULL,1779393242,1780716034),(480,4,4,'导入管理','/admin/import/index','import.*','import','bi bi-upload',80,1,NULL,1779393243,1780716035),(481,1,4,'内容推送','/admin/push/channel','push.*','push_channel','bi bi-send',86,1,NULL,0,1780716035),(482,4,4,'推送日志','/admin/push/log','push.*','push_log','bi bi-journal-code',87,1,NULL,0,1780716035),(483,4,4,'订阅管理','/admin/subscriber/index','subscriber.*','subscriber','bi bi-envelope-plus',88,1,NULL,0,1780716035),(484,4,4,'邮件日志','/admin/mail_log/index','mail_log.*','mail_log','bi bi-envelope-check',89,1,NULL,0,1780716035),(491,4,0,'退订分析','/admin/subscriber/analysis','subscriber.*','subscriber','bi bi-graph-down',95,1,NULL,0,0),(500,1,0,'内容模型','/admin/content_model/index','content_model.*','content_model','bi bi-layers',85,1,NULL,0,0),(501,4,0,'模板分类','/admin/template_category/index','template_category.*','template_category','bi bi-tags',86,1,NULL,0,0),(502,4,0,'模板安装','/admin/template_install/index','template_install.*','template_install','bi bi-cloud-arrow-down',87,1,NULL,0,0),(503,4,500,'执行记录','/admin/ai_workflow/logs','ai_workflow.logs','ai_workflow','bi bi-clock-history',3,1,NULL,0,0),(504,4,500,'工作流模板','/admin/ai_workflow/templates','ai_workflow.templates','ai_workflow','bi bi-file-earmark-code',4,1,NULL,0,0),(510,5,5,'OAuth配置','/admin/oauth_config/index','oauth.*','oauth_config','bi bi-key-fill',32,1,NULL,1779393242,1780716034),(511,5,5,'邀请排行','/admin/invite/index','invite.*','invite','bi bi-gift',28,1,NULL,1779393242,1780716034),(512,1,0,'模型迁移工具','/admin/content_model_migration/index','content_model_migration.*','content_model_migration','bi bi-arrow-left-right',90,1,NULL,0,0),(513,5,5,'评价管理','/admin/rating/index','rating.*','rating','bi bi-star',26,1,NULL,1779393242,1780716034),(514,4,510,'执行监控','/admin/ai_agent/monitor','ai_agent.monitor','ai_agent','bi bi-activity',4,1,NULL,0,0),(520,4,0,'商店分类','/admin/template_store_ops/categoryIndex','template_store_ops.*','template_store_ops','bi bi-folder2-open',88,1,NULL,0,0),(521,4,0,'Banner管理','/admin/template_store_ops/bannerIndex','template_store_ops.*','template_store_ops','bi bi-images',89,1,NULL,0,0),(522,4,0,'推荐位配置','/admin/template_store_ops/recommendIndex','template_store_ops.*','template_store_ops','bi bi-star',90,1,NULL,0,0),(523,4,0,'商店统计','/admin/template_store_ops/statsDashboard','template_store_ops.*','template_store_ops','bi bi-bar-chart-line',91,1,NULL,0,0),(524,9,0,'评论批量管理','/admin/template_store_ops/reviewBatch','template_store_ops.*','template_store_ops','bi bi-chat-dots',92,1,NULL,0,0),(530,4,484,'发送趋势','/admin/mail_log/statistics','mail_log.*','mail_log_statistics','bi bi-graph-up',1,1,NULL,0,0),(531,3,0,'模板订单','/admin/template_order_admin/index','template_order_admin.*','template_order_admin','bi bi-receipt',92,1,NULL,0,0),(532,4,0,'API密钥管理','/admin/api_key/index','api_key.*','api_key','bi bi-key',93,1,NULL,0,0),(533,4,0,'API文档','/admin/api_key/doc','api_key.doc','api_key','bi bi-file-earmark-code',94,1,NULL,0,0),(540,1,0,'系统健康检查','/admin/system_health/index','system_health.*','system_health','bi bi-heart-pulse',95,1,NULL,0,0),(541,3,0,'评价管理','/admin/template_review_admin/index','template_review_admin.*','template_review_admin','bi bi-star',93,1,NULL,0,0),(542,3,0,'统计看板','/admin/template_store_stats/index','template_store_stats.*','template_store_stats','bi bi-bar-chart',94,1,NULL,0,0),(543,3,0,'模板包管理','/admin/template_pack/index','template_pack.*','template_pack','bi bi-box-seam',95,1,NULL,0,0),(544,3,0,'审核工作流','/admin/template_audit_workflow/index','template_audit_workflow.*','template_audit_workflow','bi bi-shield-check',96,1,NULL,0,0),(545,3,0,'推荐位管理','/admin/template_recommend_position/index','template_recommend_position.*','template_recommend_position','bi bi-megaphone',97,1,NULL,0,0),(546,3,0,'结算管理','/admin/template_settlement_admin/index','template_settlement_admin.*','template_settlement_admin','bi bi-cash-coin',98,1,NULL,0,0),(547,3,0,'商店SEO','/admin/template_store_seo/index','template_store_seo.*','template_store_seo','bi bi-search',99,1,NULL,0,0),(550,7,0,'AI编辑器配置','/admin/ai_config/index','ai_config.*','ai_config','bi bi-robot',80,1,NULL,0,0),(551,7,0,'AI模板库','/admin/ai_editor_template/index','ai_editor_template.*','ai_editor_template','bi bi-collection',81,1,NULL,0,0),(553,4,0,'操作审计','/admin/content_audit_log/index','content_audit_log.*','content_audit_log','bi bi-journal-text',78,1,NULL,0,0),(554,3,550,'订阅设置','/admin/notify_center/subscriptions','notify_center.subscriptions','notify_center','bi bi-gear',4,1,NULL,0,0),(562,3,560,'发送日志','/admin/sms/logs','sms.logs','sms','bi bi-clock-history',2,1,NULL,0,0),(570,1,0,'Hook事件文档','/admin/hook_doc/index','hook_doc.*','hook_doc','bi bi-book',85,1,NULL,0,0),(571,5,0,'PWA配置','/admin/pwa_config/index','pwa_config.*','pwa_config','bi bi-phone',90,1,NULL,0,0),(572,5,570,'创建测试','/admin/ab_test/create','ab_test.create','ab_test','bi bi-plus-circle',2,1,NULL,0,0),(573,5,570,'测试结果','/admin/ab_test/results','ab_test.results','ab_test','bi bi-graph-up',3,1,NULL,0,0),(580,5,0,'用户分群','/admin/user_segment/index','user_segment.*','user_segment','bi bi-people',111,1,NULL,0,0),(582,5,580,'创建分群','/admin/user_segment/create','user_segment.create','user_segment','bi bi-plus-circle',2,1,NULL,0,0),(590,5,0,'运营自动化','/admin/ops_automation/index','ops_automation.*','ops_automation','bi bi-gear-wide-connected',112,1,NULL,0,0),(592,5,590,'创建流程','/admin/ops_automation/create','ops_automation.create','ops_automation','bi bi-plus-circle',2,1,NULL,0,0),(600,5,0,'质量监控','/admin/quality_monitor/index','quality_monitor.*','quality_monitor','bi bi-shield-check',113,1,NULL,0,0),(610,6,0,'读写分离监控','/admin/db_rw/index','db_rw.*','db_rw','bi bi-database-gear',115,1,NULL,0,0),(611,6,6,'SEO诊断','/admin/seo_diagnose/index','seo.*','seo_diagnose','bi bi-activity',47,1,NULL,1780716034,1780716034),(620,6,0,'队列监控','/admin/queue/index','queue.*','queue','bi bi-list-ol',116,1,NULL,0,0),(621,6,6,'高级导出','/admin/export/dialog','export_advanced.*','export_dialog','bi bi-file-earmark-arrow-down',51,1,NULL,1779393242,1780716034),(622,6,620,'失败任务','/admin/queue/failed','queue.failed','queue','bi bi-exclamation-triangle',2,1,NULL,0,0),(630,6,0,'Redis监控','/admin/redis/index','redis.*','redis','bi bi-lightning',117,1,NULL,0,0),(640,6,0,'静态资源优化','/admin/asset_optimize/index','asset_optimize.*','asset_optimize','bi bi-file-earmark-zip',118,1,NULL,0,0),(690,6,6,'分享追踪','/admin/social_share/index','social_share.*','social_share','bi bi-share',58,1,NULL,1779393242,1780716034),(691,6,6,'运营分析','/admin/data_dashboard/index','data_dashboard.*','data_dashboard','bi bi-bar-chart-line',57,1,NULL,1780716034,1780716034),(761,7,7,'翻译语言管理','/admin/translate/languages','ai_translation.*','translate_language','bi bi-globe2',42,1,NULL,1780716034,1780716034),(911,9,9,'插件市场','/admin/plugin_market/index','plugin_market.*','plugin_market','bi bi-shop',68,1,NULL,1779393243,1780716035),(931,9,9,'模板商店管理','/admin/template_store/index','template_store.*','template_store','bi bi-shop',71,1,NULL,1780716035,1780716035),(932,9,9,'评论审核','/admin/template_store/reviews','template_store.*','template_reviews','bi bi-star-half',72,1,NULL,1780716035,1780716035),(933,9,9,'模板分类','/admin/template_store/categories','template_store.*','template_categories','bi bi-folder2',73,1,NULL,1780716035,1780716035);
DROP TABLE IF EXISTS `{prefix}message`;
CREATE TABLE `{prefix}message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` int(10) unsigned NOT NULL,
  `from_user_id` int(10) unsigned NOT NULL,
  `to_user_id` int(10) unsigned NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(4) NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_conversation` (`conversation_id`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='私信消息表';

DROP TABLE IF EXISTS `{prefix}message_conversation`;
CREATE TABLE `{prefix}message_conversation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id_1` int(10) unsigned NOT NULL,
  `user_id_2` int(10) unsigned NOT NULL,
  `last_message_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_message_time` int(10) unsigned NOT NULL DEFAULT '0',
  `unread_count_1` int(10) unsigned NOT NULL DEFAULT '0',
  `unread_count_2` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users` (`user_id_1`,`user_id_2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='私信会话表';

DROP TABLE IF EXISTS `{prefix}message_system`;
CREATE TABLE `{prefix}message_system` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'system',
  `target_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `send_time` int(10) unsigned NOT NULL DEFAULT '0',
  `expire_time` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_type_time` (`type`,`send_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统通知表';

DROP TABLE IF EXISTS `{prefix}message_system_read`;
CREATE TABLE `{prefix}message_system_read` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `read_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_message_user` (`message_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统通知已读记录表';

DROP TABLE IF EXISTS `{prefix}mini_config`;
CREATE TABLE `{prefix}mini_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(50) NOT NULL,
  `config_value` text,
  `h5_config` json DEFAULT NULL COMMENT 'H5移动端配置(JSON: 主题色/导航/布局/分享等)',
  `analytics_config` json DEFAULT NULL COMMENT '统计配置(JSON: 统计开关/渠道/报告设置等)',
  `config_group` varchar(30) DEFAULT 'basic',
  `config_description` varchar(500) DEFAULT '',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`config_key`),
  KEY `idx_group` (`config_group`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='小程序配置表';

INSERT INTO `{prefix}mini_config` VALUES (1,'appid','',NULL,NULL,'basic','AppID','2026-07-13 02:34:27'),(2,'secret','',NULL,NULL,'basic','AppSecret','2026-07-13 02:34:27'),(3,'mini_name','',NULL,NULL,'basic','小程序名称','2026-07-13 02:34:27'),(4,'theme_color','#0d6efd',NULL,NULL,'theme','主题色','2026-07-13 02:34:27'),(5,'enable_comment','1',NULL,NULL,'function','启用评论','2026-07-13 02:34:27'),(6,'enable_favorite','1',NULL,NULL,'function','启用收藏','2026-07-13 02:34:27'),(7,'enable_like','1',NULL,NULL,'function','启用点赞','2026-07-13 02:34:27'),(8,'api_rate_limit','200',NULL,NULL,'function','API频率限制(次/分)','2026-07-13 02:34:27');
DROP TABLE IF EXISTS `{prefix}mini_error_log`;
CREATE TABLE `{prefix}mini_error_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}mini_message`;
CREATE TABLE `{prefix}mini_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL COMMENT '接收用户ID',
  `msg_type` varchar(20) NOT NULL COMMENT '消息类型(system/comment_reply/like_notify/favorite_remind/audit_notify)',
  `msg_title` varchar(200) NOT NULL COMMENT '消息标题',
  `msg_content` text COMMENT '消息内容',
  `msg_data` json DEFAULT NULL COMMENT '消息附加数据(JSON)',
  `platform` varchar(10) DEFAULT 'mini' COMMENT '推送平台(mini/h5/all)',
  `push_channel` varchar(20) DEFAULT 'template' COMMENT '推送渠道(template/subscribe/station/all)',
  `is_read` tinyint(4) DEFAULT '0' COMMENT '是否已读:1是0否',
  `push_status` varchar(20) DEFAULT 'pending' COMMENT '推送状态(pending/sent/failed)',
  `push_time` datetime DEFAULT NULL COMMENT '推送时间',
  `read_time` datetime DEFAULT NULL COMMENT '阅读时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_type` (`msg_type`),
  KEY `idx_status` (`push_status`),
  KEY `idx_read` (`is_read`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='移动端消息表';

DROP TABLE IF EXISTS `{prefix}mini_page_config`;
CREATE TABLE `{prefix}mini_page_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_type` varchar(20) NOT NULL COMMENT '页面类型(home/list/detail/search/user/about/contact)',
  `page_name` varchar(100) NOT NULL COMMENT '页面名称',
  `page_layout` json NOT NULL COMMENT '页面布局配置(JSON: 组件列表/顺序/属性)',
  `page_style` json DEFAULT NULL COMMENT '页面样式配置(JSON: 主题色/字体/间距等)',
  `page_template` varchar(50) DEFAULT 'default' COMMENT '页面模板标识',
  `platform` varchar(20) DEFAULT 'all' COMMENT '平台(all/mini/h5)',
  `version` int(11) DEFAULT '1' COMMENT '版本号',
  `is_published` tinyint(4) DEFAULT '0' COMMENT '是否已发布:1是0否',
  `publish_time` datetime DEFAULT NULL COMMENT '发布时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_page_type` (`page_type`),
  KEY `idx_platform` (`platform`),
  KEY `idx_version` (`version`),
  KEY `idx_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='移动端页面配置表';

DROP TABLE IF EXISTS `{prefix}mini_stats`;
CREATE TABLE `{prefix}mini_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stats_date` date NOT NULL COMMENT '统计日期',
  `stats_type` varchar(30) NOT NULL COMMENT '统计类型(page_view/visitor/new_user/duration/bounce/conversion)',
  `page_type` varchar(20) DEFAULT '' COMMENT '页面类型',
  `page_path` varchar(200) DEFAULT '' COMMENT '页面路径',
  `platform` varchar(10) DEFAULT 'mini' COMMENT '平台(mini/h5)',
  `metric_name` varchar(50) NOT NULL COMMENT '指标名称',
  `metric_value` int(11) DEFAULT '0' COMMENT '指标值',
  `metric_data` json DEFAULT NULL COMMENT '指标附加数据(JSON)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_date_type` (`stats_date`,`stats_type`,`page_type`,`page_path`,`platform`,`metric_name`),
  KEY `idx_date` (`stats_date`),
  KEY `idx_type` (`stats_type`),
  KEY `idx_platform` (`platform`),
  KEY `idx_page_type` (`page_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='移动端统计表';

DROP TABLE IF EXISTS `{prefix}mobile_nav_tab`;
CREATE TABLE `{prefix}mobile_nav_tab` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Tab名称',
  `icon` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图标类名(Bootstrap Icons)',
  `icon_active` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '激活图标类名',
  `tab_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'custom' COMMENT '类型:home/category/member/message/custom',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '链接URL',
  `require_login` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否需要登录:0否/1是',
  `show_badge` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否显示角标(未读数):0否/1是',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序号',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用:0否/1是',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_enabled_sort` (`is_enabled`,`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='移动端底部导航Tab(V2.9.24)';

INSERT INTO `{prefix}mobile_nav_tab` VALUES (1,'首页','bi bi-house','bi bi-house-fill','home','/',0,0,1,1,0,0),(2,'分类','bi bi-grid','bi bi-grid-fill','category','/product',0,0,2,1,0,0),(3,'我的','bi bi-person','bi bi-person-fill','member','/member/index',1,0,3,1,0,0),(4,'消息','bi bi-bell','bi bi-bell-fill','message','/message/index',1,1,4,1,0,0);
DROP TABLE IF EXISTS `{prefix}model_group`;
CREATE TABLE `{prefix}model_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '分组名称',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='模型分组';

INSERT INTO `{prefix}model_group` VALUES (1,'文章',1,1),(2,'产品',1,2),(3,'下载',1,3),(4,'其他',1,9);
DROP TABLE IF EXISTS `{prefix}module`;
CREATE TABLE `{prefix}module` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `category` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'core',
  `is_system` tinyint(4) NOT NULL DEFAULT '0',
  `is_enabled` tinyint(4) NOT NULL DEFAULT '1',
  `sort` int(11) NOT NULL DEFAULT '0',
  `config_group` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `menu_ids` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_category` (`category`),
  KEY `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='功能模块注册表';

INSERT INTO `{prefix}module` VALUES (1,'content','内容管理','内容发布、分类、标签、回收站，管理网站所有内容','file-text','core',1,1,1,'','[11,12,13,14,15,16]',0,0),(2,'user','用户管理','后台用户管理、角色权限分配、登录日志','people','core',1,1,2,'','[21]',0,0),(3,'banner','轮播图','首页轮播图管理，支持多位置轮播与排序','images','operation',0,1,10,'','[33]',0,0),(4,'link','友情链接','友情链接及分组管理，支持按组分类','link-45deg','operation',0,1,11,'','[34,35]',0,0),(5,'ad','广告系统','广告位与广告内容管理，支持多种广告位','badge-ad','operation',0,1,12,'','[36]',0,0),(6,'comment','评论系统','前台评论与审核，支持回复与敏感词过滤','chat-left-text','interaction',0,1,20,'','[51]',0,0),(7,'member','前台会员','前台会员注册登录、资料管理与互动','person-badge','interaction',0,1,21,'','[52]',0,0),(8,'seo','SEO管理','Sitemap、robots.txt、结构化数据管理','search','seo_data',0,1,30,'','[61]',0,0),(9,'export','数据导出','Excel/CSV导入导出，支持批量数据操作','download','seo_data',0,1,31,'','[62]',0,0),(10,'token','API令牌','RESTful API Token管理，控制接口访问权限','key','seo_data',0,1,32,'','[63]',0,0),(11,'notification','消息通知','站内通知与提醒，支持消息推送','bell','extension',0,1,40,'','[44]',0,0),(12,'backup','数据库备份','数据库备份与恢复，支持定时自动备份','database','extension',0,1,41,'','[43]',0,0),(13,'ai_model','AI模型管理','AI大模型配置与管理，支持多模型切换','robot','extension',0,1,0,'','',1777457624,1777457624),(14,'member_level','会员等级','会员等级与权益管理，支持自动升级','award','interaction',0,1,0,'','',1777457624,1777457624),(15,'points','积分体系','积分规则与兑换管理，签到/消费/奖励','coin','interaction',0,1,0,'','',1777457624,1777457624),(16,'paid_content','付费阅读','内容付费阅读与订单管理','cash-coin','operation',0,1,0,'','',1777457624,1777457624),(17,'form_builder','表单生成器','自定义表单与数据收集，支持多种字段类型','ui-radios','operation',0,1,0,'','',1777457624,1777457624),(18,'seo_keyword','SEO关键词库','SEO关键词挖掘与优化建议','tags','operation',0,1,0,'','',1777457624,1777487966),(19,'dashboard','数据看板','访问统计与数据分析，实时监控网站流量','graph-up','operation',0,1,0,'','',1777457624,1777488132),(20,'oauth_manage','OAuth管理','第三方登录配置(GitHub/微信/QQ)','box-arrow-in-right','extension',0,1,0,'','',1777457624,1777457624),(21,'payment','微信支付','微信支付/支付宝支付配置与订单管理','credit-card','extension',0,1,0,'','',1777774069,1777774069),(22,'ai_batch','AI批量生成','AI批量改写/翻译/生成内容，支持多模式批量处理','lightning-charge','extension',0,1,0,'','',1777774069,1777774069),(23,'plugin','插件管理','插件安装、启用、配置与市场管理','plugin','extension',0,1,0,'','',1777774069,1777774069),(24,'publish','多平台发布','内容一键发布到微信公众号/微博/头条等平台','broadcast','extension',0,1,0,'','',1777774069,1777774069),(25,'email','邮件系统','邮件发送配置、邮件模板与队列管理','envelope','extension',0,1,0,'','',1777774069,1777774069),(26,'collect','内容采集','自动采集外部内容，支持RSS/API/网页抓取','cloud-download','extension',0,1,0,'','',1777774069,1777774069),(27,'i18n','多语言','多语言内容翻译与语言包管理','translate','extension',0,1,0,'','',1777774069,1777774069),(28,'captcha','验证码','图形验证码/滑块验证码配置','shield-check','extension',0,1,0,'','',1777774069,1777774069),(29,'theme_market','模板市场','模板市场浏览、购买与一键安装','shop','extension',0,1,0,'','',1777774069,1777774069),(40,'ai_image','AI配图','AI自动生成文章配图，支持多风格','image','extension',0,1,50,'','[]',0,0),(41,'ai_quality','AI质量检测','AI内容质量评分与改进建议','check-circle','extension',0,1,51,'','[]',0,0),(42,'ai_seo','AI SEO优化','AI自动优化SEO元数据(标题/描述/关键词)','magic','extension',0,1,52,'','[]',0,0),(43,'social_share','社交分享','微信/微博/QQ分享与传播统计','share','interaction',0,1,55,'','[]',0,0),(44,'invite_points','邀请返积分','邀请好友注册返积分，多级奖励机制','gift','interaction',0,1,56,'','[]',0,0),(45,'coupon','优惠券系统','满减/折扣/免邮券管理，支持自动发放','ticket-perforated','extension',0,1,60,'','[]',0,0),(46,'content_rating','评价评分','内容评价与评分管理，支持多维度评分','star','interaction',0,1,61,'','[]',0,0),(47,'template_design','模板设计器','前台模板可视化配置与AI智能配色','palette','extension',0,1,62,'','[]',0,0);
DROP TABLE IF EXISTS `{prefix}monitor_alert`;
CREATE TABLE `{prefix}monitor_alert` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alert_name` varchar(100) NOT NULL COMMENT '告警名称',
  `monitor_type` varchar(30) NOT NULL COMMENT '监控类型(server/app/db/cache/queue)',
  `monitor_metric` varchar(50) NOT NULL COMMENT '监控指标(cpu/memory/disk/network/response_time/error_rate)',
  `alert_rule` json NOT NULL COMMENT '告警规则(JSON: operator/threshold/duration)',
  `alert_level` varchar(10) DEFAULT 'warning' COMMENT '告警级别(info/warning/critical)',
  `alert_channels` json DEFAULT NULL COMMENT '告警渠道(JSON: email/sms/webhook/dingtalk/feishu)',
  `alert_recipients` json DEFAULT NULL COMMENT '告警接收人(JSON)',
  `escalation_config` json DEFAULT NULL COMMENT '升级配置(JSON)',
  `cooldown_minutes` int(11) DEFAULT '30' COMMENT '冷却时间(分钟)',
  `is_active` tinyint(4) DEFAULT '1' COMMENT '是否启用',
  `last_triggered` datetime DEFAULT NULL COMMENT '最后触发时间',
  `trigger_count` int(11) DEFAULT '0' COMMENT '触发次数',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`monitor_type`),
  KEY `idx_metric` (`monitor_metric`),
  KEY `idx_level` (`alert_level`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='监控告警规则表';

DROP TABLE IF EXISTS `{prefix}multilingual_route`;
CREATE TABLE `{prefix}multilingual_route` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}multilingual_tag`;
CREATE TABLE `{prefix}multilingual_tag` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}notice`;
CREATE TABLE `{prefix}notice` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}notification`;
CREATE TABLE `{prefix}notification` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `receiver_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin' COMMENT '接收者类型:admin/member/system',
  `receiver_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '接收者ID',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system' COMMENT '类型:system/review/publish/title',
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '通知标题',
  `content` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '通知内容',
  `link` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '跳转链接',
  `is_read` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否已读:0否/1是',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `notify_channel` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '通知通道: sms/email/in_app/wechat',
  `notify_template_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '通知模板ID',
  `notify_priority` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '优先级: high/normal/low',
  `channel_result` json DEFAULT NULL COMMENT '通道返回结果',
  PRIMARY KEY (`id`),
  KEY `idx_receiver_read` (`receiver_type`,`receiver_id`,`is_read`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='消息通知表';

DROP TABLE IF EXISTS `{prefix}notify_log`;
CREATE TABLE `{prefix}notify_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}notify_template`;
CREATE TABLE `{prefix}notify_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}oauth_user`;
CREATE TABLE `{prefix}oauth_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `oauth_provider` varchar(30) NOT NULL DEFAULT '' COMMENT 'OAuth提供商: wechat/qq/github/weibo',
  `oauth_openid` varchar(128) NOT NULL DEFAULT '' COMMENT 'OpenID',
  `oauth_unionid` varchar(128) NOT NULL DEFAULT '' COMMENT 'UnionID(微信专用)',
  `oauth_nickname` varchar(100) NOT NULL DEFAULT '' COMMENT '第三方昵称',
  `oauth_avatar` varchar(500) NOT NULL DEFAULT '' COMMENT '第三方头像URL',
  `oauth_data` json DEFAULT NULL COMMENT '第三方返回的完整用户数据',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(45) NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `login_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '登录次数',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态: 1正常 0禁用',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provider_openid` (`oauth_provider`,`oauth_openid`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_oauth_unionid` (`oauth_unionid`),
  KEY `idx_last_login_time` (`last_login_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='第三方登录绑定表';

DROP TABLE IF EXISTS `{prefix}operation_task`;
CREATE TABLE `{prefix}operation_task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}ops_automation_flow`;
CREATE TABLE `{prefix}ops_automation_flow` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}order`;
CREATE TABLE `{prefix}order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}orders`;
CREATE TABLE `{prefix}orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL DEFAULT '' COMMENT '订单号',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `source` varchar(50) NOT NULL DEFAULT '' COMMENT '来源: plugin/template/member/content',
  `source_id` varchar(100) NOT NULL DEFAULT '' COMMENT '来源ID(插件code/模板code/会员等级ID/内容ID)',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态: 0=待支付 1=已支付 2=已退款 3=已关闭',
  `pay_method` varchar(20) NOT NULL DEFAULT '' COMMENT '支付方式: wechat/alipay',
  `pay_trade_no` varchar(64) NOT NULL DEFAULT '' COMMENT '第三方交易号',
  `paid_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `user_id` (`user_id`),
  KEY `source` (`source`,`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='订单表';

DROP TABLE IF EXISTS `{prefix}paid_content_record`;
CREATE TABLE `{prefix}paid_content_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}paid_order`;
CREATE TABLE `{prefix}paid_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_sn` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '订单号',
  `payment_order_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '关联PaymentService订单号(orders.order_no)，真钱支付时填充',
  `member_id` int(11) NOT NULL COMMENT '购买会员ID',
  `content_id` int(11) NOT NULL COMMENT '购买内容ID',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'content' COMMENT '类型: content内容',
  `price` decimal(10,2) NOT NULL COMMENT '实付金额/积分',
  `pay_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'points' COMMENT '支付方式: points积分 wechat微信 alipay支付宝',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态: 0待支付 1已支付 2已退款 3已关闭',
  `paid_at` int(10) unsigned DEFAULT '0' COMMENT '支付时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  `refund_sn` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '退款单号',
  `refund_amount` decimal(10,2) DEFAULT '0.00' COMMENT '退款金额',
  `refund_time` int(10) unsigned DEFAULT '0' COMMENT '退款时间',
  `refund_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '退款原因',
  `transaction_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '微信交易号',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_sn` (`order_sn`),
  UNIQUE KEY `uk_member_content` (`member_id`,`content_id`,`type`),
  KEY `idx_member` (`member_id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_order_no` (`payment_order_no`),
  KEY `idx_member_status` (`member_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='付费订单表';

DROP TABLE IF EXISTS `{prefix}payment_log`;
CREATE TABLE `{prefix}payment_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_sn` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '订单号',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '类型: request/notify/refund',
  `request_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '请求数据(JSON)',
  `response_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '响应数据(JSON)',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态: 1成功 0失败',
  `error_msg` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '错误信息',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `pay_channel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '支付渠道: alipay/wechat/unionpay',
  `channel_order_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '渠道订单号',
  `channel_data` json DEFAULT NULL COMMENT '渠道返回数据',
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_sn`),
  KEY `idx_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付日志表';

DROP TABLE IF EXISTS `{prefix}performance_log`;
CREATE TABLE `{prefix}performance_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '请求URL',
  `method` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GET' COMMENT '请求方法',
  `response_time` int(11) NOT NULL DEFAULT '0' COMMENT '响应时间(毫秒)',
  `db_query_count` int(11) NOT NULL DEFAULT '0' COMMENT 'DB查询次数',
  `db_query_time` int(11) NOT NULL DEFAULT '0' COMMENT 'DB查询总耗时(毫秒)',
  `memory_usage` bigint(20) NOT NULL DEFAULT '0' COMMENT '内存使用(字节)',
  `memory_peak` bigint(20) NOT NULL DEFAULT '0' COMMENT '内存峰值(字节)',
  `status_code` int(11) NOT NULL DEFAULT '200' COMMENT 'HTTP状态码',
  `is_slow` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否慢请求(>2s)',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `extra` json DEFAULT NULL COMMENT '扩展数据',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at_date` date GENERATED ALWAYS AS (cast(`created_at` as date)) STORED,
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_created_at_date` (`created_at_date`),
  KEY `idx_is_slow` (`is_slow`),
  KEY `idx_response_time` (`response_time`),
  KEY `idx_url` (`url`(255))
) ENGINE=InnoDB AUTO_INCREMENT=1077 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='V2.9.35 性能日志';

INSERT INTO `{prefix}performance_log` (`id`, `url`, `method`, `response_time`, `db_query_count`, `db_query_time`, `memory_usage`, `memory_peak`, `status_code`, `is_slow`, `user_id`, `ip`, `extra`, `created_at`) VALUES (1,'http://localhost:3000/admin/member_level/index','GET',123,1,0,2033336,2670504,200,0,1,'172.18.0.1',NULL,'2026-07-13 10:32:15'),(2,'http://localhost:3000/assets/js/bootstrap.bundle.min.js.map','GET',385,1,0,206600,807808,500,0,1,'172.18.0.1',NULL,'2026-07-13 11:29:30'),(3,'http://localhost:3000/api/cache/clearByType','POST',369,1,0,193880,773832,200,0,1,'172.18.0.1',NULL,'2026-07-13 11:41:52'),(4,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',169,2,0,2098408,2718032,200,0,1,'172.18.0.1',NULL,'2026-07-13 11:41:54'),(5,'http://localhost:3000/admin/member_level/edit/1','GET',415,2,0,2061312,2722552,200,0,1,'172.18.0.1',NULL,'2026-07-13 11:49:31'),(6,'http://localhost:3000/admin/member_level/index?_=1783914662547','GET',492,2,0,2058792,2644064,200,0,1,'172.18.0.1',NULL,'2026-07-13 11:51:55'),(7,'http://localhost:3000/assets/css/bootstrap.min.css.map','GET',250,1,0,247888,862072,500,0,1,'172.18.0.1',NULL,'2026-07-13 11:52:00'),(8,'http://localhost:3000/api/cache/clearByType','POST',363,1,0,194176,773056,200,0,1,'172.18.0.1',NULL,'2026-07-13 11:57:15'),(9,'http://localhost:3000/admin/member_level/index?_=1783915325104','GET',130,2,0,2079720,2659552,200,0,1,'172.18.0.1',NULL,'2026-07-13 12:02:15'),(10,'http://localhost:3000/admin/member_level/index','GET',332,1,0,2045944,2607616,200,0,1,'172.18.0.1',NULL,'2026-07-13 12:02:17'),(11,'http://localhost:3000/assets/js/bootstrap.bundle.min.js.map','GET',307,1,0,247888,862120,500,0,1,'172.18.0.1',NULL,'2026-07-13 12:05:00'),(12,'http://localhost:3000/assets/js/bootstrap.bundle.min.js.map','GET',138,1,0,247888,862120,500,0,1,'172.18.0.1',NULL,'2026-07-13 12:05:25'),(13,'http://localhost:3000/admin/member_level/edit/2?modal=1','GET',515,2,0,2051216,2695784,200,0,1,'172.18.0.1',NULL,'2026-07-13 12:05:30'),(14,'http://localhost:3000/assets/js/bootstrap.bundle.min.js.map','GET',139,1,0,247888,862120,500,0,1,'172.18.0.1',NULL,'2026-07-13 12:05:49'),(15,'http://localhost:3000/admin/dashboard/index?_=1783915696265','GET',529,1,0,2208304,2810056,200,0,1,'172.18.0.1',NULL,'2026-07-13 12:09:09'),(16,'http://localhost:3000/admin/dashboard/overview','GET',57,8,2,2023328,2748072,200,0,1,'172.18.0.1',NULL,'2026-07-13 12:09:09'),(17,'http://localhost:3000/admin/mail_log/statistics_data?type=trend','GET',40,2,0,2017952,2571480,200,0,1,'172.18.0.1',NULL,'2026-07-13 12:09:49'),(18,'http://localhost:3000/admin/mail_log/statistics_data?type=status','GET',29,2,0,2002720,2554648,200,0,1,'172.18.0.1',NULL,'2026-07-13 12:09:49'),(19,'http://localhost:3000/assets/css/bootstrap.min.css.map','GET',169,1,0,247888,862072,500,0,1,'172.18.0.1',NULL,'2026-07-13 12:28:02'),(20,'http://localhost:3000/admin/content/index?_=1783916882328','GET',599,7,2,2191160,2792976,200,0,1,'172.18.0.1',NULL,'2026-07-13 12:28:10'),(21,'http://localhost:3000/admin/content/edit/1?_=1783916882329','GET',707,12,3,2300936,3207424,200,0,1,'172.18.0.1',NULL,'2026-07-13 12:28:14'),(22,'http://localhost:3000/assets/js/bootstrap.bundle.min.js.map','GET',163,2,0,250768,807584,500,0,1,'172.18.0.1',NULL,'2026-07-13 12:36:27'),(23,'http://localhost:3000/admin?_=1783939094375','GET',516,4,1,2127944,2710024,200,0,1,'172.18.0.1',NULL,'2026-07-13 18:38:21'),(24,'http://localhost:3000/admin/banner/index?_=1783939102893','GET',503,2,0,2076160,2716632,200,0,1,'172.18.0.1',NULL,'2026-07-13 18:39:16'),(25,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',458,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 18:39:24'),(26,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',445,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 18:40:24'),(27,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',425,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 19:01:24'),(28,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',454,2,0,2040152,2635704,200,0,1,'172.18.0.1',NULL,'2026-07-13 19:04:24'),(29,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',426,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 19:20:09'),(30,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',434,3,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 19:22:08'),(31,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',447,2,0,2040152,2635704,200,0,1,'172.18.0.1',NULL,'2026-07-13 19:28:08'),(32,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',417,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 20:21:09'),(33,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',447,2,0,2040184,2635736,200,0,1,'172.18.0.1',NULL,'2026-07-13 20:41:09'),(34,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',429,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 20:52:09'),(35,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',424,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 21:33:09'),(36,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',429,2,0,2040152,2635704,200,0,1,'172.18.0.1',NULL,'2026-07-13 21:35:09'),(37,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',443,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 21:45:09'),(38,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',445,2,0,2040184,2635736,200,0,1,'172.18.0.1',NULL,'2026-07-13 21:49:09'),(39,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',437,2,0,2040152,2635704,200,0,1,'172.18.0.1',NULL,'2026-07-13 21:53:09'),(40,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',441,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 21:56:09'),(41,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',488,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 22:10:09'),(42,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',465,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 22:13:09'),(43,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',506,2,0,2040152,2635704,200,0,1,'172.18.0.1',NULL,'2026-07-13 22:24:09'),(44,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',474,2,0,2040152,2635704,200,0,1,'172.18.0.1',NULL,'2026-07-13 22:27:09'),(45,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',472,2,0,2040152,2635704,200,0,1,'172.18.0.1',NULL,'2026-07-13 22:29:09'),(46,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',483,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 22:33:09'),(47,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',468,3,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 22:57:09'),(48,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',485,2,0,2040184,2635736,200,0,1,'172.18.0.1',NULL,'2026-07-13 23:04:09'),(49,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',507,2,0,2040184,2635736,200,0,1,'172.18.0.1',NULL,'2026-07-13 23:16:09'),(50,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',442,2,0,2040184,2635736,200,0,1,'172.18.0.1',NULL,'2026-07-13 23:31:09'),(51,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',478,2,0,2040184,2635736,200,0,1,'172.18.0.1',NULL,'2026-07-13 23:55:25'),(52,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',447,1,0,2204632,2734584,500,0,1,'172.18.0.1',NULL,'2026-07-14 10:38:37'),(53,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',430,1,0,2204632,2734584,500,0,1,'172.18.0.1',NULL,'2026-07-14 10:47:44'),(54,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',426,1,0,2204632,2734584,500,0,1,'172.18.0.1',NULL,'2026-07-14 10:50:47'),(55,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',426,1,0,2204632,2734584,500,0,1,'172.18.0.1',NULL,'2026-07-14 10:54:51'),(56,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',535,1,0,2204632,2734584,500,0,1,'172.18.0.1',NULL,'2026-07-14 11:00:57'),(57,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',479,1,0,2204632,2734584,500,0,1,'172.18.0.1',NULL,'2026-07-14 11:04:02'),(58,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',469,1,0,2204632,2734584,500,0,1,'172.18.0.1',NULL,'2026-07-14 11:15:10'),(59,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',448,1,0,2204632,2734584,500,0,1,'172.18.0.1',NULL,'2026-07-14 11:22:10'),(60,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',441,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 11:38:10'),(61,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',454,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 11:41:10'),(62,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',439,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 11:42:10'),(63,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',474,2,0,2137264,2720984,200,0,1,'172.18.0.1',NULL,'2026-07-14 11:52:10'),(64,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',437,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 11:53:10'),(65,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',468,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 11:55:10'),(66,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',436,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 12:21:53'),(67,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',457,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 12:25:57'),(68,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',431,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 12:38:37'),(69,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',408,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 12:48:47'),(70,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',403,3,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 13:20:10'),(71,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',399,2,0,2137264,2720984,200,0,1,'172.18.0.1',NULL,'2026-07-14 13:21:10'),(72,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',458,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 13:49:10'),(73,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',444,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 13:53:10'),(74,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',474,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 14:06:37'),(75,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',463,2,0,2137264,2720936,200,0,1,'172.18.0.1',NULL,'2026-07-14 14:14:41'),(76,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',399,2,0,2137248,2720920,200,0,1,'172.18.0.1',NULL,'2026-07-14 14:23:49'),(77,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',408,2,0,2137264,2720936,200,0,1,'172.18.0.1',NULL,'2026-07-14 14:25:52'),(78,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',405,2,0,2137248,2720920,200,0,1,'172.18.0.1',NULL,'2026-07-14 14:30:57'),(79,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',414,3,0,2137232,2720904,200,0,1,'172.18.0.1',NULL,'2026-07-14 14:32:59'),(80,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',401,2,0,2137248,2720920,200,0,1,'172.18.0.1',NULL,'2026-07-14 14:34:00'),(81,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',414,2,0,2137264,2720936,200,0,1,'172.18.0.1',NULL,'2026-07-14 15:22:10'),(82,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',401,2,0,2137248,2720920,200,0,1,'172.18.0.1',NULL,'2026-07-14 15:35:10'),(83,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',398,2,0,2137232,2720904,200,0,1,'172.18.0.1',NULL,'2026-07-14 15:50:10'),(84,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',511,2,0,2137232,2720904,200,0,1,'172.18.0.1',NULL,'2026-07-14 16:12:47'),(85,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',415,3,0,2137264,2720936,200,0,1,'172.18.0.1',NULL,'2026-07-14 16:16:51'),(86,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',422,2,0,2137248,2720920,200,0,1,'172.18.0.1',NULL,'2026-07-14 16:18:53'),(87,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',408,2,0,2137264,2720936,200,0,1,'172.18.0.1',NULL,'2026-07-14 16:19:54'),(88,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',424,2,0,2137232,2720904,200,0,1,'172.18.0.1',NULL,'2026-07-14 16:20:55'),(89,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',411,3,0,2137248,2720920,200,0,1,'172.18.0.1',NULL,'2026-07-14 16:27:01'),(90,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',408,2,0,2137232,2720904,200,0,1,'172.18.0.1',NULL,'2026-07-14 16:33:07'),(91,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',434,2,0,2137248,2720920,200,0,1,'172.18.0.1',NULL,'2026-07-14 16:58:41'),(92,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',431,2,0,2137248,2720920,200,0,1,'172.18.0.1',NULL,'2026-07-14 17:01:44'),(93,'http://localhost:3000/admin/ad/index?_=1783996535772','GET',112,2,1,2197536,2821856,200,0,1,'172.18.0.1',NULL,'2026-07-14 17:12:03'),(94,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',429,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 17:29:50'),(95,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',458,2,0,2137264,2720984,200,0,1,'172.18.0.1',NULL,'2026-07-14 17:31:52'),(96,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',445,6,1,2163144,3173816,200,0,1,'172.18.0.1',NULL,'2026-07-14 17:40:00'),(97,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',398,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 17:55:09'),(98,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',410,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:01:10'),(99,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',413,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:11:10'),(100,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',436,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:23:40'),(101,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',444,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:27:37'),(102,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',434,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:28:38'),(103,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',484,2,0,2137264,2720984,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:30:40'),(104,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',484,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:32:42'),(105,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',483,6,1,2163160,3173800,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:40:40'),(106,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',452,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:51:43'),(107,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',499,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:58:50'),(108,'http://aicms.test/admin','GET',3676,12,4636,3275288,4133912,200,1,1,'127.0.0.1',NULL,'2026-07-22 13:58:53'),(109,'http://aicms.test/admin/member/index','GET',12,5,1001,2912480,3626800,200,0,1,'127.0.0.1',NULL,'2026-07-22 13:59:31'),(110,'http://aicms.test/admin/banner/index','GET',13,2,1001,2789800,3502928,200,0,1,'127.0.0.1',NULL,'2026-07-22 13:59:31'),(111,'http://aicms.test/admin/social_share/index','GET',16,7,1003,2832192,3540856,200,0,1,'127.0.0.1',NULL,'2026-07-22 13:59:31'),(112,'http://aicms.test/admin/ai_log/index','GET',16,4,1005,2788496,3472432,200,0,1,'127.0.0.1',NULL,'2026-07-22 13:59:31'),(113,'http://aicms.test/admin/ai_model/index','GET',17,0,0,2696064,3490208,500,0,1,'127.0.0.1',NULL,'2026-07-22 13:59:31'),(114,'http://aicms.test/admin/ai_translation/index','GET',14,5,1001,2928864,3644800,200,0,1,'127.0.0.1',NULL,'2026-07-22 13:59:31'),(115,'http://aicms.test/admin/ai_workflow/templates','GET',21,3,1003,2972088,3636128,200,0,1,'127.0.0.1',NULL,'2026-07-22 13:59:31'),(116,'http://aicms.test/admin/ai_workflow/logs','GET',15,4,1003,2841488,3510016,200,0,1,'127.0.0.1',NULL,'2026-07-22 13:59:31'),(117,'http://aicms.test/admin/plugin_store/index','GET',17,4,1002,2757720,3430224,200,0,1,'127.0.0.1',NULL,'2026-07-22 13:59:31'),(118,'http://aicms.test/admin/template_store_ops/reviewBatch','GET',10,3,1000,2786448,3501456,200,0,1,'127.0.0.1',NULL,'2026-07-22 13:59:31'),(119,'http://aicms.test/admin/quality_monitor/index','GET',13,2,1000,2760712,3585584,500,0,1,'127.0.0.1',NULL,'2026-07-22 13:59:31'),(120,'http://aicms.test/admin/storage/config','GET',9,0,0,2723976,3406400,200,0,1,'127.0.0.1',NULL,'2026-07-22 13:59:31'),(121,'http://aicms.test/admin/email_log/index','GET',13,3,1002,2769104,3449952,200,0,1,'127.0.0.1',NULL,'2026-07-22 13:59:32'),(122,'http://aicms.test/admin/mail_log/index','GET',9,0,0,2705784,3388608,200,0,1,'127.0.0.1',NULL,'2026-07-22 13:59:32'),(123,'http://aicms.test/admin/rating/index','GET',14,7,1003,2801440,3526512,200,0,1,'127.0.0.1',NULL,'2026-07-22 13:59:32'),(124,'http://aicms.test/admin/review/index','GET',14,7,1003,2835104,3565536,200,0,1,'127.0.0.1',NULL,'2026-07-22 13:59:32'),(125,'http://aicms.test/admin/paid_order/index','GET',13,4,1002,2787720,3499560,200,0,1,'127.0.0.1',NULL,'2026-07-22 13:59:32'),(126,'http://aicms.test/admin/collect_source/index','GET',13,3,1002,2779464,3453376,200,0,1,'127.0.0.1',NULL,'2026-07-22 13:59:32'),(127,'http://aicms.test/admin/plugin_market/index','GET',1764,0,0,2874544,3613992,200,0,1,'127.0.0.1',NULL,'2026-07-22 13:59:33'),(128,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',26,2,1000,2729032,3387168,200,0,1,'127.0.0.1',NULL,'2026-07-22 14:01:53'),(129,'http://aicms.test/admin','GET',26,4,1001,2852960,3518008,200,0,1,'127.0.0.1',NULL,'2026-07-22 14:13:55'),(130,'http://aicms.test/admin/media/index?_=1784700836061','GET',8,2,1000,2836800,3547920,200,0,1,'127.0.0.1',NULL,'2026-07-22 14:14:03'),(131,'http://aicms.test/admin/workflow/index?_=1784700836062','GET',7,2,1000,2759056,3490600,200,0,1,'127.0.0.1',NULL,'2026-07-22 14:14:04'),(132,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2780704,3438840,200,0,1,'127.0.0.1',NULL,'2026-07-22 14:14:53'),(133,'http://aicms.test/admin/member_level/edit/3?modal=1','GET',13,3,1000,2749976,3407840,200,0,1,'127.0.0.1',NULL,'2026-07-22 14:16:11'),(134,'http://aicms.test/api/cache/clearByType','POST',37,2,1031,376616,1007608,200,0,1,'127.0.0.1',NULL,'2026-07-22 14:16:52'),(135,'http://aicms.test/api/cache/clearByType','POST',20,2,1013,376552,1007576,200,0,1,'127.0.0.1',NULL,'2026-07-22 14:18:15'),(136,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2780744,3438976,200,0,1,'127.0.0.1',NULL,'2026-07-22 14:22:41'),(137,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',38,3,1000,2796624,3454760,200,0,1,'127.0.0.1',NULL,'2026-07-22 14:26:45'),(138,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',24,2,1000,2780760,3438896,200,0,1,'127.0.0.1',NULL,'2026-07-22 14:32:45'),(139,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',32,2,1000,2780776,3439008,200,0,1,'127.0.0.1',NULL,'2026-07-22 14:32:51'),(140,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',26,2,1000,2780744,3438880,200,0,1,'127.0.0.1',NULL,'2026-07-22 14:33:45'),(141,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',31,2,1000,2780760,3438992,200,0,1,'127.0.0.1',NULL,'2026-07-22 14:39:58'),(142,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',18,2,1000,2812952,3471184,200,0,1,'127.0.0.1',NULL,'2026-07-22 14:44:59'),(143,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',22,2,1000,2812952,3471184,200,0,1,'127.0.0.1',NULL,'2026-07-22 14:45:59'),(144,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',23,2,1000,2812968,3471200,200,0,1,'127.0.0.1',NULL,'2026-07-22 14:48:59'),(145,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',27,2,1000,2812936,3471168,200,0,1,'127.0.0.1',NULL,'2026-07-22 14:49:59'),(146,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',24,2,1000,2812968,3471104,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:01:45'),(147,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',36,2,1000,2812952,3471184,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:02:59'),(148,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',32,2,1000,2812968,3471200,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:03:59'),(149,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2812952,3471088,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:05:45'),(150,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',35,2,1000,2812952,3471088,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:13:45'),(151,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',28,2,1000,2812936,3471168,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:13:59'),(152,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',53,7,1002,2899776,4054696,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:18:45'),(153,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',25,2,1000,2812952,3471088,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:19:45'),(154,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',27,2,1000,2812968,3471200,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:19:59'),(155,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',32,2,1000,2812952,3471088,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:21:45'),(156,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',19,2,1000,2812952,3471184,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:23:59'),(157,'http://aicms.test/admin/member_level/index?_=1784705156352','GET',12,2,1000,2828552,3488584,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:25:57'),(158,'http://aicms.test/admin/member_level/index?_=1784705156356','GET',8,0,0,2750600,3410632,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:26:01'),(159,'http://aicms.test/admin/member_level/edit/1?modal=1','GET',8,2,1000,2766392,3424256,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:26:03'),(160,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2812952,3471184,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:26:15'),(161,'http://aicms.test/admin/member_benefit/index?_=1784705175578','GET',9,2,1000,2824944,3498808,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:26:17'),(162,'http://aicms.test/admin/plugin_store/index?_=1784705175582','GET',8,3,1000,2779384,3450696,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:26:21'),(163,'http://aicms.test/admin/plugin/batchIndex?_=1784705175583','GET',9,2,1000,2793216,3463696,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:26:22'),(164,'http://aicms.test/admin/content_audit_log/index','GET',27,3,1003,2907208,3572320,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:29:55'),(165,'http://aicms.test/admin/template_audit_workflow/index','GET',7,0,0,2668264,3350504,500,0,1,'127.0.0.1',NULL,'2026-07-22 15:29:55'),(166,'http://aicms.test/admin/system_health/index','GET',8,0,0,2668256,3350320,500,0,1,'127.0.0.1',NULL,'2026-07-22 15:29:55'),(167,'http://aicms.test/admin/ops_automation/create','GET',9,0,0,2738112,3436376,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:29:55'),(168,'http://aicms.test/admin/ai_agent/monitor','GET',10,1,1000,2817400,3646320,500,0,1,'127.0.0.1',NULL,'2026-07-22 15:29:55'),(169,'http://aicms.test/admin/report/index','GET',8,0,0,2769680,3439016,500,0,1,'127.0.0.1',NULL,'2026-07-22 15:29:55'),(170,'http://aicms.test/admin/ai_workflow/templates','GET',9,0,0,2842736,3509056,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:33:05'),(171,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',34,2,1000,2812968,3471104,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:36:45'),(172,'http://aicms.test/admin/login','GET',12,0,0,2601960,3322552,200,0,0,'127.0.0.1',NULL,'2026-07-22 15:38:40'),(173,'http://aicms.test/admin/template_pack/index','GET',13,4,1003,2865256,3535552,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:38:41'),(174,'http://aicms.test/admin/user_segment/index','GET',10,1,1000,2825592,3646384,500,0,1,'127.0.0.1',NULL,'2026-07-22 15:38:41'),(175,'http://aicms.test/admin/quality_monitor/index','GET',12,2,1000,2835528,3658552,500,0,1,'127.0.0.1',NULL,'2026-07-22 15:38:41'),(176,'http://aicms.test/admin/ai_workflow/logs','GET',10,3,1000,2863240,3525008,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:38:41'),(177,'http://aicms.test/admin/api_key/index','GET',7,2,1000,2810656,3463800,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:38:57'),(178,'http://aicms.test/admin/ai_agent/monitor','GET',8,1,1000,2817240,3644472,500,0,1,'127.0.0.1',NULL,'2026-07-22 15:38:57'),(179,'http://aicms.test/admin/monitor/index','GET',13,5,1004,2848520,3511120,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:38:57'),(180,'http://aicms.test/admin/notify_center/subscriptions','GET',7,0,0,2804272,3467320,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:39:09'),(181,'http://aicms.test/admin/ai_workflow/logs','GET',9,3,1000,2863256,3525024,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:39:09'),(182,'http://aicms.test/admin/ai_agent/monitor','GET',8,1,1000,2817400,3644472,500,0,1,'127.0.0.1',NULL,'2026-07-22 15:39:09'),(183,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',12,3,1000,2829616,3543608,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:40:45'),(184,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',27,2,1000,2812952,3471088,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:42:45'),(185,'http://aicms.test/admin/ai_batch/index','GET',11,0,0,2852136,3516496,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:43:33'),(186,'http://aicms.test/admin/content_audit_log/index','GET',17,2,1000,2974328,3762216,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:45:56'),(187,'http://aicms.test/admin/template_audit_workflow/index','GET',14,3,1001,2896040,3567144,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:45:57'),(188,'http://aicms.test/admin/db_rw/index','GET',16,2,1000,2902576,3565288,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:45:57'),(189,'http://aicms.test/admin/queue/index','GET',12,1,1000,2798952,3625688,500,0,1,'127.0.0.1',NULL,'2026-07-22 15:45:57'),(190,'http://aicms.test/admin/asset_optimize/index','GET',10,0,0,2851464,3514576,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:45:57'),(191,'http://aicms.test/admin/plugin_store/index','GET',9,3,1000,2852280,3507024,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:49:08'),(192,'http://aicms.test/admin/content_model/index','GET',14,1,1000,2865560,3528448,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:49:08'),(193,'http://aicms.test/admin/ops_automation/index','GET',10,3,1000,2903112,3565048,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:49:09'),(194,'http://aicms.test/admin/ai_agent/monitor','GET',15,7,1002,2932896,3595944,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:49:09'),(195,'http://aicms.test/admin/monitor/index','GET',17,5,1004,2918592,3584768,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:49:09'),(196,'http://aicms.test/admin/ops_automation/index','GET',11,3,1000,2902216,3564152,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:49:26'),(197,'http://aicms.test/admin/notify_center/subscriptions','GET',10,0,0,2833912,3497448,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:49:26'),(198,'http://aicms.test/admin/monitor/index','GET',15,5,1004,2918288,3584464,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:49:26'),(199,'http://aicms.test/admin/ai_workflow/templates','GET',12,0,0,2844952,3511272,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:49:32'),(200,'http://aicms.test/admin/ai_batch/index','GET',10,0,0,2853952,3520208,200,0,1,'127.0.0.1',NULL,'2026-07-22 15:53:15'),(201,'http://aicms.test/admin/mail_log/overview','GET',12,5,1002,2753256,3407736,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:00:12'),(202,'http://aicms.test/admin/mail_log/statistics_data?type=hourly','GET',8,2,1000,2766904,3397176,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:00:13'),(203,'http://aicms.test/admin/template_category/index','GET',22,1,1000,2883168,3686416,500,0,1,'127.0.0.1',NULL,'2026-07-22 16:00:13'),(204,'http://aicms.test/admin/ai_batch/index','GET',11,0,0,2782792,3502360,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:00:13'),(205,'http://aicms.test/admin/subscriber/analysis/trend','GET',31,62,1016,2763664,3415832,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:00:15'),(206,'http://aicms.test/admin/mail_log/statistics_data?type=status','GET',9,2,1000,2753864,3385048,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:00:17'),(207,'http://aicms.test/admin/comment_admin/index?_=1784707210822','GET',10,2,1000,2804888,3471536,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:00:51'),(208,'http://aicms.test/admin/log/index?_=1784707210828','GET',10,5,1000,3033640,3640528,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:01:01'),(209,'http://aicms.test/admin/user/index?_=1784707210834','GET',8,3,1000,2878504,3582072,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:01:06'),(210,'http://aicms.test/admin/sms/logs','GET',11,3,1000,2835120,3497040,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:02:57'),(211,'http://aicms.test/admin/content_audit_log/index','GET',14,2,1000,2917936,3596744,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:07:39'),(212,'http://aicms.test/admin/plugin/index','GET',2383,3,1000,3096560,3777384,200,1,1,'127.0.0.1',NULL,'2026-07-22 16:07:42'),(213,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',32,2,1000,2814784,3472968,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:19:22'),(214,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',10,2,1000,2814768,3472952,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:22:25'),(215,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',11,2,1000,2814784,3472968,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:28:31'),(216,'http://aicms.test/admin/sms/logs?_=1784709290697','GET',10,3,1000,2820568,3499992,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:34:53'),(217,'http://aicms.test/admin/pwa_config/index?_=1784709290723','GET',9,7,1001,2775248,3467696,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:35:09'),(218,'http://aicms.test/admin/dashboard/trend?days=7','GET',12,3,1001,2744808,3391248,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:35:15'),(219,'http://aicms.test/admin/dashboard/index','GET',7,0,0,2777648,3509032,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:35:20'),(220,'http://aicms.test/admin/dashboard/getSourceAnalysis?days=7','GET',9,2,1000,2740288,3372512,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:35:20'),(221,'http://aicms.test/admin?_=1784709331807','GET',8,4,1000,2897456,3564728,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:35:34'),(222,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',32,2,1000,2814768,3474672,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:37:32'),(223,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',31,2,1000,2814768,3474672,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:42:34'),(224,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',11,2,1000,2814784,3474688,200,0,1,'127.0.0.1',NULL,'2026-07-22 16:50:42'),(225,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',33,2,1000,2814768,3474672,200,0,1,'127.0.0.1',NULL,'2026-07-22 17:03:55'),(226,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',37,3,1000,2830600,3490504,200,0,1,'127.0.0.1',NULL,'2026-07-22 17:05:57'),(227,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',11,2,1000,2814768,3474672,200,0,1,'127.0.0.1',NULL,'2026-07-22 17:06:58'),(228,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2814784,3474688,200,0,1,'127.0.0.1',NULL,'2026-07-22 17:14:59'),(229,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2814768,3474672,200,0,1,'127.0.0.1',NULL,'2026-07-22 17:20:32'),(230,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',11,2,1000,2814768,3474720,200,0,1,'127.0.0.1',NULL,'2026-07-22 17:29:40'),(231,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',29,6,1002,2901608,4042480,200,0,1,'127.0.0.1',NULL,'2026-07-22 17:35:46'),(232,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',14,3,1000,2830600,3490552,200,0,1,'127.0.0.1',NULL,'2026-07-22 17:36:47'),(233,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',29,2,1000,2814752,3474704,200,0,1,'127.0.0.1',NULL,'2026-07-22 17:39:50'),(234,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',11,2,1000,2814768,3474720,200,0,1,'127.0.0.1',NULL,'2026-07-22 17:54:59'),(235,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2814768,3474720,200,0,1,'127.0.0.1',NULL,'2026-07-22 17:55:59'),(236,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2814752,3474704,200,0,1,'127.0.0.1',NULL,'2026-07-22 17:59:59'),(237,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2814784,3474736,200,0,1,'127.0.0.1',NULL,'2026-07-22 18:03:32'),(238,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',11,2,1000,2814752,3474704,200,0,1,'127.0.0.1',NULL,'2026-07-22 18:12:40'),(239,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',11,2,1000,2814784,3474736,200,0,1,'127.0.0.1',NULL,'2026-07-22 18:22:50'),(240,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',35,2,1000,2814768,3474720,200,0,1,'127.0.0.1',NULL,'2026-07-22 18:24:52'),(241,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',24,2,1000,2814768,3474720,200,0,1,'127.0.0.1',NULL,'2026-07-22 18:28:56'),(242,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',24,2,1000,2814784,3474736,200,0,1,'127.0.0.1',NULL,'2026-07-22 18:38:59'),(243,'http://aicms.test/admin/ai_config/index?_=1784716779548','GET',14,13,1002,2854640,3524672,200,0,1,'127.0.0.1',NULL,'2026-07-22 18:39:48'),(244,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',26,2,1000,2815584,3529792,200,0,1,'127.0.0.1',NULL,'2026-07-22 18:50:06'),(245,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',20,2,1000,2814768,3472976,200,0,1,'127.0.0.1',NULL,'2026-07-22 18:57:06'),(246,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',30,2,1000,2814752,3472960,200,0,1,'127.0.0.1',NULL,'2026-07-22 19:03:07'),(247,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',34,2,1000,2814768,3472976,200,0,1,'127.0.0.1',NULL,'2026-07-22 19:05:07'),(248,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',34,2,1000,2814768,3472976,200,0,1,'127.0.0.1',NULL,'2026-07-22 19:06:07'),(249,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',27,2,1000,2814768,3472976,200,0,1,'127.0.0.1',NULL,'2026-07-22 19:10:07'),(250,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',28,2,1000,2814784,3472992,200,0,1,'127.0.0.1',NULL,'2026-07-22 19:12:07'),(251,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',22,2,1000,2814768,3472976,200,0,1,'127.0.0.1',NULL,'2026-07-22 19:20:07'),(252,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',21,2,1000,2814768,3472976,200,0,1,'127.0.0.1',NULL,'2026-07-22 19:21:07'),(253,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',21,2,1000,2814768,3472976,200,0,1,'127.0.0.1',NULL,'2026-07-22 19:24:07'),(254,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',33,2,1000,2814768,3472976,200,0,1,'127.0.0.1',NULL,'2026-07-22 19:36:07'),(255,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',39,4,1001,2830640,3488848,200,0,1,'127.0.0.1',NULL,'2026-07-22 19:42:07'),(256,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',27,2,1000,2814768,3472976,200,0,1,'127.0.0.1',NULL,'2026-07-22 19:46:07'),(257,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',27,2,1000,2814784,3472992,200,0,1,'127.0.0.1',NULL,'2026-07-22 20:01:07'),(258,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',31,2,1000,2814752,3472960,200,0,1,'127.0.0.1',NULL,'2026-07-22 20:19:07'),(259,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',26,2,1000,2814768,3472976,200,0,1,'127.0.0.1',NULL,'2026-07-22 20:23:07'),(260,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',23,2,1000,2814784,3472992,200,0,1,'127.0.0.1',NULL,'2026-07-22 20:24:07'),(261,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',24,3,1000,2830656,3488864,200,0,1,'127.0.0.1',NULL,'2026-07-22 20:32:07'),(262,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',23,2,1000,2814752,3472960,200,0,1,'127.0.0.1',NULL,'2026-07-22 20:40:07'),(263,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',38,2,1000,2814784,3472992,200,0,1,'127.0.0.1',NULL,'2026-07-22 20:44:07'),(264,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',16,2,1000,2814768,3472976,200,0,1,'127.0.0.1',NULL,'2026-07-22 20:48:07'),(265,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',21,2,1000,2814752,3472960,200,0,1,'127.0.0.1',NULL,'2026-07-22 20:59:07'),(266,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',20,3,1000,2830640,3488848,200,0,1,'127.0.0.1',NULL,'2026-07-22 21:27:07'),(267,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',18,2,1000,2814768,3472976,200,0,1,'127.0.0.1',NULL,'2026-07-22 21:29:07'),(268,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',36,2,1000,2814768,3472976,200,0,1,'127.0.0.1',NULL,'2026-07-22 21:37:07'),(269,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',14,2,1000,2814752,3472960,200,0,1,'127.0.0.1',NULL,'2026-07-22 21:58:07'),(270,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',21,2,1000,2814752,3472960,200,0,1,'127.0.0.1',NULL,'2026-07-22 22:02:07'),(271,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',22,2,1000,2814784,3472992,200,0,1,'127.0.0.1',NULL,'2026-07-22 22:07:07'),(272,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',23,3,1000,2830640,3488848,200,0,1,'127.0.0.1',NULL,'2026-07-22 22:11:07'),(273,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',22,2,1000,2814752,3472960,200,0,1,'127.0.0.1',NULL,'2026-07-22 22:21:07'),(274,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',29,3,1000,2830640,3488848,200,0,1,'127.0.0.1',NULL,'2026-07-22 22:55:07'),(275,'http://aicms.test/admin/plugin_market/index?_=1784717346839','GET',3034,0,0,2894968,3628624,200,1,1,'127.0.0.1',NULL,'2026-07-22 22:56:17'),(276,'http://aicms.test/admin/content/add?_=1784717346851','GET',26,14,1007,3385192,4215656,200,0,1,'127.0.0.1',NULL,'2026-07-22 22:56:33'),(277,'http://aicms.test/admin/login','GET',36,11,1007,2827400,4054080,302,0,1,'127.0.0.1',NULL,'2026-07-22 23:01:05'),(278,'http://aicms.test/admin/login','GET',8,0,0,2578632,3365072,200,0,0,'127.0.0.1',NULL,'2026-07-22 23:02:06'),(279,'http://aicms.test/admin/login','GET',5,0,0,2577832,3233432,200,0,0,'127.0.0.1',NULL,'2026-07-22 23:02:07'),(280,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',16,3,1000,357624,1055920,404,0,0,'127.0.0.1',NULL,'2026-07-22 23:02:13'),(281,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',12,3,1000,357528,1056448,404,0,1,'127.0.0.1',NULL,'2026-07-22 23:02:22'),(282,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2815584,3531088,200,0,1,'127.0.0.1',NULL,'2026-07-22 23:02:22'),(283,'http://aicms.test/admin/language/index?_=1784732520991','GET',9,2,1000,2864880,3533288,200,0,1,'127.0.0.1',NULL,'2026-07-22 23:03:18'),(284,'http://aicms.test/admin/plugin_market/index?_=1784732520992','GET',1819,0,0,2894872,3628480,200,0,1,'127.0.0.1',NULL,'2026-07-22 23:03:23'),(285,'http://aicms.test/admin/plugin/index?_=1784732520993','GET',67,3,1001,2956592,3643200,200,0,1,'127.0.0.1',NULL,'2026-07-22 23:03:24'),(286,'http://aicms.test/admin/email_template/add?_=1784732520996','GET',8,0,0,2788200,3459736,200,0,1,'127.0.0.1',NULL,'2026-07-22 23:03:30'),(287,'http://aicms.test/admin/mail_log/statistics_data?type=status','GET',13,2,1000,2753704,3385120,200,0,1,'127.0.0.1',NULL,'2026-07-22 23:03:52'),(288,'http://aicms.test/admin/ai_workflow/templates?_=1784732521003','GET',14,3,1002,2892208,3572280,200,0,1,'127.0.0.1',NULL,'2026-07-22 23:03:54'),(289,'http://aicms.test/admin/menu_manager/index?_=1784732521020','GET',19,3,1001,4322136,4972432,200,0,1,'127.0.0.1',NULL,'2026-07-22 23:04:25'),(290,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2825392,3485344,200,0,1,'127.0.0.1',NULL,'2026-07-22 23:12:55'),(291,'http://aicms.test/admin/ai_model/index','GET',11,2,1000,2890640,3557592,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:03:07'),(292,'http://aicms.test/admin/ai_model/index','GET',55,13,1006,3095952,4066312,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:06:41'),(293,'http://aicms.test/admin/ai_model/index','GET',9,2,1000,2890800,3557400,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:06:44'),(294,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',14,3,1000,357512,1056392,404,0,1,'127.0.0.1',NULL,'2026-07-23 00:07:09'),(295,'http://aicms.test/admin/ai_model/index','GET',23,2,1000,2890800,3557400,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:11:47'),(296,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',34,12,1005,412008,1098584,404,0,1,'127.0.0.1',NULL,'2026-07-23 00:22:16'),(297,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2826208,3541760,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:22:16'),(298,'http://aicms.test/admin/ai_log/index?_=1784737327293','GET',10,3,1000,2822632,3553136,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:22:52'),(299,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',24,3,1000,357512,1056392,404,0,1,'127.0.0.1',NULL,'2026-07-23 00:23:15'),(300,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',12,3,1001,2886896,3530480,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:23:16'),(301,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',12,3,1000,357528,1056408,404,0,1,'127.0.0.1',NULL,'2026-07-23 00:23:19'),(302,'http://aicms.test/admin/log/index?_=1784737376926','GET',20,6,1002,3130384,3802800,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:24:33'),(303,'http://aicms.test/admin/user_segment/index?_=1784737504196','GET',15,4,1001,2831176,3510704,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:25:22'),(304,'http://aicms.test/news/1','GET',24,7,1003,589344,1600488,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:25:51'),(305,'http://aicms.test/member/captcha','GET',15,4,1001,369144,1073624,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:26:07'),(306,'http://aicms.test/member/captcha','GET',15,4,1001,369144,1073688,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:26:09'),(307,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',10,2,1000,2825392,3485392,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:26:42'),(308,'http://aicms.test/member/register','GET',21,5,1001,475064,1282992,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:47:12'),(309,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',11,3,1000,357528,1056272,404,0,1,'127.0.0.1',NULL,'2026-07-23 00:50:09'),(310,'http://aicms.test/member/register','GET',13,4,1000,458744,1281504,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:52:00'),(311,'http://aicms.test/member/register','GET',25,4,1001,391312,1243200,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:52:11'),(312,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',10,2,1000,2825376,3485376,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:52:44'),(313,'http://aicms.test/member/captcha','GET',12,4,1000,369128,1073712,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:54:01'),(314,'http://aicms.test/member/captcha','GET',15,4,1001,369144,1074176,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:57:45'),(315,'http://aicms.test/member/captcha','GET',16,4,1001,369128,1073672,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:58:00'),(316,'http://aicms.test/member/login','GET',14,4,1001,431056,1181456,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:58:20'),(317,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',13,3,1000,357528,1056272,404,0,1,'127.0.0.1',NULL,'2026-07-23 00:58:29'),(318,'http://aicms.test/member/login','GET',10,4,1000,386296,1057704,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:58:34'),(319,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',12,3,1000,357528,1056272,404,0,1,'127.0.0.1',NULL,'2026-07-23 00:58:35'),(320,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',12,3,1000,357528,1056272,404,0,1,'127.0.0.1',NULL,'2026-07-23 00:58:37'),(321,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',12,3,1000,357528,1056272,404,0,1,'127.0.0.1',NULL,'2026-07-23 00:58:37'),(322,'http://aicms.test/news','GET',12,5,1001,440976,1118528,200,0,1,'127.0.0.1',NULL,'2026-07-23 00:58:46'),(323,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',12,3,1000,357528,1056272,404,0,1,'127.0.0.1',NULL,'2026-07-23 00:58:52'),(324,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',30,2,1000,2825392,3485392,200,0,1,'127.0.0.1',NULL,'2026-07-23 01:09:00'),(325,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',32,2,1000,2825392,3485392,200,0,1,'127.0.0.1',NULL,'2026-07-23 01:15:00'),(326,'http://aicms.test/member/register','GET',38,4,1001,391312,1234968,200,0,1,'127.0.0.1',NULL,'2026-07-23 01:23:25'),(327,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',37,3,1000,357528,1056272,404,0,1,'127.0.0.1',NULL,'2026-07-23 01:23:25'),(328,'http://aicms.test/member/register','GET',12,4,1000,390496,1057944,200,0,1,'127.0.0.1',NULL,'2026-07-23 01:23:26'),(329,'http://aicms.test/member/login','GET',13,4,1000,452456,1263088,200,0,1,'127.0.0.1',NULL,'2026-07-23 01:23:34'),(330,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',22,2,1000,2825376,3485376,200,0,1,'127.0.0.1',NULL,'2026-07-23 01:27:00'),(331,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2827248,3485552,200,0,1,'127.0.0.1',NULL,'2026-07-23 01:38:44'),(332,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',12,2,1000,2827248,3485552,200,0,1,'127.0.0.1',NULL,'2026-07-23 01:44:44'),(333,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',23,2,1000,2827264,3487264,200,0,1,'127.0.0.1',NULL,'2026-07-23 01:46:00'),(334,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',38,3,1000,2843120,3503120,200,0,1,'127.0.0.1',NULL,'2026-07-23 01:53:00'),(335,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',26,3,1000,2843104,3501408,200,0,1,'127.0.0.1',NULL,'2026-07-23 02:03:44'),(336,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',21,2,1000,2827248,3487248,200,0,1,'127.0.0.1',NULL,'2026-07-23 02:07:00'),(337,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',31,2,1000,2827248,3487248,200,0,1,'127.0.0.1',NULL,'2026-07-23 02:12:00'),(338,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',30,2,1000,2827248,3485552,200,0,1,'127.0.0.1',NULL,'2026-07-23 02:13:44'),(339,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',28,2,1000,2827232,3487232,200,0,1,'127.0.0.1',NULL,'2026-07-23 02:15:00'),(340,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',28,2,1000,2827248,3485552,200,0,1,'127.0.0.1',NULL,'2026-07-23 02:15:44'),(341,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',23,2,1000,2827264,3487264,200,0,1,'127.0.0.1',NULL,'2026-07-23 02:16:00'),(342,'http://aicms.test/member/register','GET',15,4,1001,390536,1058008,200,0,1,'127.0.0.1',NULL,'2026-07-23 02:26:31'),(343,'http://aicms.test/member/captcha','GET',16,4,1001,369168,1097240,200,0,1,'127.0.0.1',NULL,'2026-07-23 02:26:31'),(344,'http://aicms.test/admin/system/allTemplates','GET',8,0,0,2705584,3341080,200,0,1,'127.0.0.1',NULL,'2026-07-23 02:28:34'),(345,'http://aicms.test//admin/system/config?tab=system','GET',21,13,1002,2963752,4445592,200,0,1,'127.0.0.1',NULL,'2026-07-23 02:29:50'),(346,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2827248,3487248,200,0,1,'127.0.0.1',NULL,'2026-07-23 02:30:00'),(347,'http://aicms.test/admin/login','GET',11,2,1000,2669336,3340960,200,0,0,'127.0.0.1',NULL,'2026-07-23 02:30:30'),(348,'http://aicms.test/download','GET',17,6,1002,449312,1121016,200,0,0,'127.0.0.1',NULL,'2026-07-24 09:38:59'),(349,'http://aicms.test/','GET',20,6,1002,426512,1084016,200,0,0,'127.0.0.1',NULL,'2026-07-24 10:08:58'),(350,'http://aicms.test/admin','GET',4465,12,5441,3043016,3836152,200,1,1,'127.0.0.1',NULL,'2026-07-24 10:28:59'),(351,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',13,2,1002,2828632,3556352,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:28:59'),(352,'http://aicms.test/admin/content/add','GET',23,13,1004,3084584,4182480,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:29:17'),(353,'http://aicms.test/admin/publish_platform/index?_=1784860157472','GET',15,4,1003,2881488,3550800,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:29:56'),(354,'http://aicms.test/admin/system/config','GET',15,11,1002,3059512,4425936,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:30:29'),(355,'http://aicms.test/admin/system/customVar?_=1784860308756','GET',6,2,1000,2824752,3496984,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:32:23'),(356,'http://aicms.test/admin/backup/index?_=1784860308763','GET',13,0,0,2869680,3569856,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:33:37'),(357,'http://aicms.test/admin/ai_agent/monitor?_=1784860308770','GET',19,9,1006,2860912,3541336,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:33:54'),(358,'http://aicms.test/admin/review/index?_=1784860308781','GET',8,6,1001,2906040,3606192,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:34:09'),(359,'http://aicms.test/admin/workflow/records?_=1784860308784','GET',7,3,1000,2817464,3486992,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:34:15'),(360,'http://aicms.test/admin/sms/logs','GET',7,3,1000,2796400,3453776,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:34:34'),(361,'http://aicms.test/admin/ad/index?_=1784860474445','GET',14,3,1004,2875224,3563040,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:34:45'),(362,'http://aicms.test/admin/template_settlement_admin/index?_=1784860474455','GET',17,6,1005,2914520,3592608,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:34:59'),(363,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2827248,3487248,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:35:34'),(364,'http://aicms.test/admin/system/allTemplates','GET',7,0,0,2705584,3342728,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:35:59'),(365,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',21,7,1002,395232,1098752,404,0,1,'127.0.0.1',NULL,'2026-07-24 10:36:17'),(366,'http://aicms.test/admin/login','GET',9,2,1000,2669400,3342032,200,0,0,'127.0.0.1',NULL,'2026-07-24 10:36:20'),(367,'http://aicms.test/admin/system/allTemplates','GET',8,0,0,2705584,3342776,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:38:06'),(368,'http://aicms.test/admin/system/allTemplates','GET',9,0,0,2705584,3342776,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:38:07'),(369,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',12,3,1000,2843136,3503136,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:39:17'),(370,'http://aicms.test/admin/login','GET',8,2,1000,2656384,3310624,200,0,0,'127.0.0.1',NULL,'2026-07-24 10:40:32'),(371,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2828064,3543664,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:42:17'),(372,'http://aicms.test/admin/login','GET',6,2,1000,2656416,3358248,200,0,0,'127.0.0.1',NULL,'2026-07-24 10:42:37'),(373,'http://aicms.test/admin/system/allTemplates','GET',8,0,0,2705584,3342776,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:42:42'),(374,'http://aicms.test/admin/system/moduleControl?_=1784860962430','GET',10,3,1000,3265872,3930824,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:43:19'),(375,'http://aicms.test/admin/sms/logs?_=1784860962435','GET',10,4,1001,2833000,3512808,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:43:25'),(376,'http://aicms.test/admin/user/add?_=1784860962449','GET',7,0,0,2808024,3489344,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:43:39'),(377,'http://aicms.test/admin/member_benefit/index?_=1784860962455','GET',8,2,1000,2840232,3553120,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:43:53'),(378,'http://aicms.test/admin/system/allTemplates','GET',9,0,0,2705584,3342776,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:44:12'),(379,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2827248,3487248,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:44:42'),(380,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2827264,3485568,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:46:40'),(381,'http://aicms.test/api/cache/clearByType','POST',10,2,1004,376584,1008568,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:46:40'),(382,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',12,3,1000,357568,1056848,404,0,1,'127.0.0.1',NULL,'2026-07-24 10:47:10'),(383,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2827264,3485400,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:47:41'),(384,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2827248,3485552,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:48:40'),(385,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2827248,3487248,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:50:10'),(386,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2827232,3485536,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:54:43'),(387,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2827248,3485384,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:55:44'),(388,'http://aicms.test/admin/system/allTemplates','GET',8,0,0,2705584,3342776,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:56:19'),(389,'http://aicms.test/member/captcha','GET',12,4,1000,369184,1096712,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:58:33'),(390,'http://aicms.test/member/captcha','GET',41,14,1009,406880,1133800,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:58:40'),(391,'http://aicms.test/member/captcha','GET',13,4,1000,369128,1096688,200,0,1,'127.0.0.1',NULL,'2026-07-24 10:58:45'),(392,'http://aicms.test/admin/mail_log/overview','GET',12,5,1001,2764968,3421208,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:00:40'),(393,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2827264,3487264,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:01:19'),(394,'http://aicms.test/admin/content/index?_=1784862088017','GET',17,9,1004,3230488,3945872,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:01:45'),(395,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',21,2,1000,2827264,3485400,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:05:54'),(396,'http://aicms.test/admin/tag/index','GET',13,6,1001,2893848,3569776,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:07:24'),(397,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2827248,3487200,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:10:25'),(398,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',32,2,1000,2827248,3487200,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:14:27'),(399,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',22,2,1000,2827232,3487184,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:23:34'),(400,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',58,13,1006,2913864,4070560,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:25:36'),(401,'http://aicms.test/member/register','GET',32,14,1006,438656,1269256,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:27:28'),(402,'http://aicms.test/admin/tag/index','GET',13,6,1002,2860272,3517512,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:27:35'),(403,'http://aicms.test/admin/tag/index','GET',16,6,1001,2893032,3550272,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:27:39'),(404,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2827248,3487128,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:27:39'),(405,'http://aicms.test/admin?_=1784863659397','GET',34,15,1007,2969600,3732872,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:28:53'),(406,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',16,3,1000,357568,1056736,404,0,1,'127.0.0.1',NULL,'2026-07-24 11:28:57'),(407,'http://aicms.test/admin/media/index?_=1784863737835','GET',9,3,1001,2977216,3677232,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:29:13'),(408,'http://aicms.test/admin/plugin_market/index?_=1784863737857','GET',994,0,0,2826728,3503240,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:29:29'),(409,'http://aicms.test/admin/data_dashboard/index?_=1784863737874','GET',11,5,1001,2788384,3485056,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:29:42'),(410,'http://aicms.test/admin/traffic/getDeviceStats?days=7','GET',9,2,1000,2752704,3384272,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:29:44'),(411,'http://aicms.test/admin/monitor/index?_=1784863784410','GET',16,5,1007,2842128,3522536,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:29:44'),(412,'http://aicms.test/admin/ops_automation/index?_=1784863784423','GET',13,4,1003,2834072,3513816,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:29:56'),(413,'http://aicms.test/admin/payment/index?_=1784863784429','GET',13,2,1004,2874352,3548456,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:29:59'),(414,'http://aicms.test/admin/member/index?_=1784863784440','GET',10,5,1000,3017544,3722056,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:30:10'),(415,'http://aicms.test/admin/user_segment/index?_=1784863784451','GET',8,3,1000,2782680,3455944,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:30:17'),(416,'http://aicms.test/admin/subscriber/analysis/trend','GET',50,62,1038,2775360,3429232,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:30:23'),(417,'http://aicms.test/admin/translate/languages?_=1784863784467','GET',9,4,1000,2979848,3642744,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:30:54'),(418,'http://aicms.test/admin/ai_theme/index?_=1784863784469','GET',8,3,1000,2855352,3597952,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:30:55'),(419,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2827232,3487112,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:31:44'),(420,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',10,2,1000,2827232,3487112,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:39:49'),(421,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',10,2,1000,2827232,3487112,200,0,1,'127.0.0.1',NULL,'2026-07-24 11:40:50'),(422,'http://aicms.test/admin/login','POST',224,15,1016,2718280,4066048,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:00:57'),(423,'http://aicms.test/admin/login','POST',242,4,1048,2637728,3326888,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:01:51'),(424,'http://aicms.test/admin/login','POST',219,4,1013,2637712,3326864,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:01:56'),(425,'http://aicms.test/admin/login','POST',219,4,1029,2642520,3331680,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:04:49'),(426,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',32,12,1005,412032,1098600,404,0,1,'127.0.0.1',NULL,'2026-07-24 12:07:34'),(427,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2832872,3548352,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:07:34'),(428,'http://aicms.test/api/cache/clearByType','POST',22,3,1016,376576,1047872,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:07:37'),(429,'http://aicms.test/admin/mail_log/overview','GET',11,4,1000,2769824,3425984,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:07:42'),(430,'http://aicms.test/admin/user_segment/index?_=1784866058610','GET',10,4,1001,2837824,3517696,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:07:51'),(431,'http://aicms.test/admin/rating/index?_=1784866058623','GET',12,7,1002,2911072,3625496,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:08:01'),(432,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2832024,3491952,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:09:39'),(433,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2894376,3569984,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:11:05'),(434,'http://aicms.test/admin/ai_workflow/templates','GET',8,0,0,2798072,3496152,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:11:32'),(435,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',26,2,1000,2832040,3490272,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:18:48'),(436,'http://aicms.test/','GET',40,11,1004,449840,1106112,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:24:05'),(437,'http://aicms.test/admin/system/config?_=1784867070318','GET',19,12,1002,3141232,4432808,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:24:55'),(438,'http://aicms.test/admin/notification/index?_=1784867070323','GET',6,2,1000,2821624,3491008,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:24:59'),(439,'http://aicms.test/admin/mail_log/statistics_data?type=status','GET',9,2,1000,2771136,3402320,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:25:01'),(440,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2832040,3490272,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:27:30'),(441,'http://aicms.test/admin/stats/index','GET',6,0,0,2815320,3464736,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:31:05'),(442,'http://aicms.test/admin/ai_workflow/logs?_=1784867070331','GET',9,3,1000,2870176,3574952,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:38:45'),(443,'http://aicms.test/admin/ai_workflow/templates.html?_=1784867070334','GET',7,0,0,2790480,3499432,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:38:47'),(444,'http://aicms.test/admin/ai_agent/monitor?_=1784867070336','GET',10,7,1001,2820952,3547376,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:38:48'),(445,'http://aicms.test/admin/system/config?_=1784867070337','GET',19,11,1002,3141216,4432808,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:38:49'),(446,'http://aicms.test/admin/user_segment/create?_=1784867938933','GET',8,0,0,2803664,3470440,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:39:02'),(447,'http://aicms.test/admin/comment/index?_=1784867938936','GET',8,2,1000,2895296,3612136,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:39:08'),(448,'http://aicms.test/admin/payment/revenue?_=1784867938953','GET',9,7,1001,2808160,3501568,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:39:22'),(449,'http://aicms.test/admin/asset_optimize/index?_=1784867938957','GET',7,0,0,2743912,3470520,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:39:26'),(450,'http://aicms.test/admin/aiStat/getProviderStats','GET',11,3,1001,2757816,3406312,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:40:34'),(451,'http://aicms.test/admin/monitor/index?_=1784868050812','GET',13,5,1003,2786872,3548416,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:40:52'),(452,'http://aicms.test/admin/monitor/index','GET',13,5,1003,2867600,3535056,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:40:52'),(453,'http://aicms.test/admin/export/index?_=1784868057726','GET',8,0,0,2796688,3468096,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:41:03'),(454,'http://aicms.test/admin/dashboard/getDeadLinkStats','GET',10,2,1000,2781080,3418272,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:41:06'),(455,'http://aicms.test/admin/dashboard/overview','GET',11,8,1002,2773104,3422360,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:41:09'),(456,'http://aicms.test/admin/dashboard/topContent','GET',12,0,0,2687368,3371496,500,0,1,'127.0.0.1',NULL,'2026-07-24 12:41:14'),(457,'http://aicms.test/admin/dashboard/getSourceAnalysis?days=7','GET',14,0,0,2686896,3373704,500,0,1,'127.0.0.1',NULL,'2026-07-24 12:41:14'),(458,'http://aicms.test/admin/dashboard/overview','GET',18,8,1002,2773088,3422352,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:41:14'),(459,'http://aicms.test/admin/dashboard/categoryStats','GET',8,0,0,2687368,3371544,500,0,1,'127.0.0.1',NULL,'2026-07-24 12:41:16'),(460,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2832056,3491936,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:45:16'),(461,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2832040,3491920,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:47:17'),(462,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',24,2,1000,2832040,3491920,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:50:20'),(463,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',28,2,1000,2832040,3491920,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:52:22'),(464,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',28,2,1000,2832040,3490224,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:53:27'),(465,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',20,2,1000,2832040,3490152,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:53:38'),(466,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2832040,3490224,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:55:27'),(467,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',20,2,1000,2832024,3490136,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:55:40'),(468,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',25,2,1000,2832040,3490224,200,0,1,'127.0.0.1',NULL,'2026-07-24 12:57:27'),(469,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2832024,3490208,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:01:27'),(470,'http://aicms.test/admin/monitor/index','GET',20,5,1005,2928392,3593832,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:03:49'),(471,'http://aicms.test/admin/ops_automation/index','GET',11,4,1001,2859608,3549088,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:06:20'),(472,'http://aicms.test/admin/db_rw/index','GET',17,2,1000,2867216,3526784,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:06:20'),(473,'http://aicms.test/admin/queue/failed','GET',8,1,1000,2824720,3488888,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:06:20'),(474,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',34,2,1000,2833880,3491992,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:11:56'),(475,'http://aicms.test/admin/seo/index?_=1784869985711','GET',6,0,0,2750960,3474608,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:13:24'),(476,'http://aicms.test/admin/dashboard/categoryStats','GET',8,0,0,2801608,3483792,500,0,1,'127.0.0.1',NULL,'2026-07-24 13:13:37'),(477,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',16,3,1000,357552,1056272,404,0,1,'127.0.0.1',NULL,'2026-07-24 13:13:47'),(478,'http://aicms.test/admin/dashboard/getContentRank?limit=20','GET',8,2,1000,2765688,3397480,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:13:47'),(479,'http://aicms.test/admin/dashboard/index?_=1784870027863','GET',9,2,1000,2898328,3568440,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:13:52'),(480,'http://aicms.test/admin/dashboard/getSourceAnalysis?days=7','GET',11,2,1000,2759360,3391512,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:13:52'),(481,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',12,3,1000,357552,1056272,404,0,1,'127.0.0.1',NULL,'2026-07-24 13:13:58'),(482,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2833864,3493744,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:13:58'),(483,'http://aicms.test/admin/dashboard/getRevenueStats?days=7','GET',11,0,0,2801352,3488088,500,0,1,'127.0.0.1',NULL,'2026-07-24 13:14:00'),(484,'http://aicms.test/admin/traffic/getHourlyStats?days=7','GET',10,2,1000,2759560,3391136,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:14:10'),(485,'http://aicms.test/admin/ai_theme/index?_=1784870111514','GET',12,4,1001,2938336,3633824,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:15:20'),(486,'http://aicms.test/admin/ai_template/index?_=1784870111523','GET',10,4,1002,2923200,3615640,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:15:33'),(487,'http://aicms.test/admin/ai_batch/index?_=1784870111524','GET',8,2,1000,2824744,3548200,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:15:34'),(488,'http://aicms.test/admin/plugin_market/index?_=1784870111527','GET',4233,0,0,2913968,3647968,200,1,1,'127.0.0.1',NULL,'2026-07-24 13:15:42'),(489,'http://aicms.test/admin/ai_model/index','GET',12,2,1000,2864128,3529400,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:16:04'),(490,'http://aicms.test/admin/dashboard/getRevenueStats?days=7','GET',10,2,1000,2775152,3462680,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:16:15'),(491,'http://aicms.test/admin/dashboard/getRevenueStats?days=7&_t=999','GET',7,2,1000,2781528,3443376,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:17:22'),(492,'http://aicms.test/admin/login','GET',9,2,1000,2677648,3409864,200,0,0,'127.0.0.1',NULL,'2026-07-24 13:18:17'),(493,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',35,12,1006,412032,1098088,404,0,0,'127.0.0.1',NULL,'2026-07-24 13:18:17'),(494,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',216,4,1000,357664,1055760,404,0,0,'127.0.0.1',NULL,'2026-07-24 13:18:17'),(495,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',13,3,1000,357680,1055776,404,0,0,'127.0.0.1',NULL,'2026-07-24 13:18:18'),(496,'http://aicms.test/admin','GET',9,4,1001,2924968,3586056,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:18:31'),(497,'http://aicms.test/admin/comment/index?_=1784870311485','GET',11,3,1001,2911776,3628616,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:18:41'),(498,'http://aicms.test/admin/dashboard/topContent','GET',7,0,0,2703600,3387552,500,0,1,'127.0.0.1',NULL,'2026-07-24 13:18:43'),(499,'http://aicms.test/admin/dashboard/index?_=1784870311488','GET',8,0,0,2849904,3519936,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:18:44'),(500,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',11,3,1000,357552,1056272,404,0,1,'127.0.0.1',NULL,'2026-07-24 13:18:47'),(501,'http://aicms.test/admin/dashboard/getSourceAnalysis?days=7','GET',8,2,1000,2775512,3407664,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:18:51'),(502,'http://aicms.test/admin/dashboard/getDeadLinkStats','GET',7,0,0,2703752,3387656,500,0,1,'127.0.0.1',NULL,'2026-07-24 13:18:51'),(503,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2848544,3506680,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:18:57'),(504,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2848528,3508408,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:19:57'),(505,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2848528,3508408,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:20:05'),(506,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2848528,3508408,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:20:05'),(507,'http://aicms.test/api/cache/clearByType','POST',26,2,1020,376616,1008464,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:20:12'),(508,'http://aicms.test/api/cache/clearByType','POST',10,2,1004,376600,1008504,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:20:27'),(509,'http://aicms.test/admin/dashboard/overview','GET',12,8,1002,2789480,3439160,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:21:58'),(510,'http://aicms.test/admin/dashboard/getMemberGrowth?days=7','GET',8,3,1000,2780576,3430224,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:22:01'),(511,'http://aicms.test/admin/dashboard/topContent','GET',8,2,1000,2778904,3407912,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:22:02'),(512,'http://aicms.test/admin/dashboard/getSourceAnalysis?days=7','GET',9,0,0,2703400,3390208,500,0,1,'127.0.0.1',NULL,'2026-07-24 13:22:04'),(513,'http://aicms.test/admin/dashboard/topContent','GET',11,2,1000,2778920,3407928,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:22:04'),(514,'http://aicms.test/admin/dashboard/categoryStats','GET',8,0,0,2816288,3498472,500,0,1,'127.0.0.1',NULL,'2026-07-24 13:22:04'),(515,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2848560,3506744,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:22:06'),(516,'http://aicms.test/admin/dashboard/overview','GET',10,8,1002,2789480,3437464,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:22:54'),(517,'http://aicms.test/admin/dashboard/getMemberGrowth','GET',6,3,1000,2780928,3424064,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:23:07'),(518,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2848544,3508424,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:25:05'),(519,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',10,2,1000,2848544,3508424,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:26:29'),(520,'http://aicms.test/admin/dashboard/index','GET',7,0,0,2810800,3480488,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:31:00'),(521,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',11,3,1000,357552,1056272,404,0,1,'127.0.0.1',NULL,'2026-07-24 13:31:00'),(522,'http://aicms.test/admin/dashboard/overview','GET',12,8,1002,2789496,3439168,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:31:00'),(523,'http://aicms.test/admin/dashboard/index','GET',7,0,0,2810800,3480488,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:31:08'),(524,'http://aicms.test/admin/dashboard/trend?days=7','GET',6,0,0,2721992,3365448,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:31:08'),(525,'http://aicms.test/admin/dashboard/categoryStats','GET',6,0,0,2719504,3363504,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:31:08'),(526,'http://aicms.test/admin/dashboard/getSourceAnalysis?days=7','GET',9,0,0,2703400,3390208,500,0,1,'127.0.0.1',NULL,'2026-07-24 13:31:11'),(527,'http://aicms.test/admin/dashboard/getRevenueStats?days=7','GET',8,2,1000,2780744,3412800,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:31:17'),(528,'http://aicms.test/admin/dashboard/index','GET',8,0,0,2810800,3480488,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:31:47'),(529,'http://aicms.test/admin/dashboard/getContentRank?limit=20','GET',7,2,1000,2780240,3412448,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:31:47'),(530,'http://aicms.test/admin/dashboard/categoryStats','GET',6,0,0,2719504,3362168,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:32:08'),(531,'http://aicms.test/admin/login','GET',6,2,1000,2677616,3334656,200,0,0,'127.0.0.1',NULL,'2026-07-24 13:32:26'),(532,'http://aicms.test/admin/system/config?_=1784871139719','GET',18,11,1002,3028016,4449680,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:32:34'),(533,'http://aicms.test/admin/system/config?tab=system','GET',18,11,1002,3054208,4449904,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:32:51'),(534,'http://aicms.test/admin/login','POST',226,4,1036,2658944,3353664,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:33:14'),(535,'http://aicms.test/admin/ai_agent/monitor?_=1784871198935','GET',10,7,1001,2837472,3560792,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:33:25'),(536,'http://aicms.test/admin/dashboard/trend?days=7','GET',7,0,0,2816024,3499056,500,0,1,'127.0.0.1',NULL,'2026-07-24 13:33:28'),(537,'http://aicms.test/admin/dashboard/categoryStats','GET',9,2,1000,2780648,3418016,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:33:28'),(538,'http://aicms.test/admin/dashboard/getRevenueStats?days=7','GET',10,0,0,2815920,3501040,500,0,1,'127.0.0.1',NULL,'2026-07-24 13:36:12'),(539,'http://aicms.test/admin/dashboard/topContent','GET',8,2,1000,2778920,3407928,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:36:13'),(540,'http://aicms.test/admin/dashboard/getContentRank?limit=20','GET',8,2,1000,2780256,3412464,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:36:14'),(541,'http://aicms.test/admin/dashboard/getDeadLinkStats','GET',7,0,0,2816280,3498528,500,0,1,'127.0.0.1',NULL,'2026-07-24 13:36:14'),(542,'http://aicms.test/admin/seo_diagnose/index?_=1784871374867','GET',44,7,1002,2910776,3592904,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:36:18'),(543,'http://aicms.test/admin/seo_keyword/index','GET',8,4,1000,2854624,3512000,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:36:21'),(544,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',12,3,1000,357568,1056288,404,0,1,'127.0.0.1',NULL,'2026-07-24 13:36:52'),(545,'http://aicms.test/admin/dashboard/categoryStats','GET',7,0,0,2719504,3363504,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:36:52'),(546,'http://aicms.test/admin/dashboard/index','GET',6,0,0,2810800,3480440,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:36:56'),(547,'http://aicms.test/admin/dashboard/trend?days=7','GET',7,0,0,2721992,3365448,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:36:56'),(548,'http://aicms.test/admin/dashboard/getContentRank?limit=20','GET',6,2,1000,2780240,3412448,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:36:56'),(549,'http://aicms.test/admin/dashboard/getSourceAnalysis?days=7','GET',7,0,0,2703400,3390208,500,0,1,'127.0.0.1',NULL,'2026-07-24 13:36:59'),(550,'http://aicms.test/admin/dashboard/categoryStats','GET',8,0,0,2816288,3498472,500,0,1,'127.0.0.1',NULL,'2026-07-24 13:36:59'),(551,'http://aicms.test/admin/dashboard/getRevenueStats?days=7','GET',7,2,1000,2780760,3411304,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:37:06'),(552,'http://aicms.test/','GET',12,5,1001,410160,1084032,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:37:09'),(553,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2848560,3508440,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:37:59'),(554,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2848560,3508440,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:38:59'),(555,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2848560,3508440,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:39:42'),(556,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2848528,3506712,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:39:54'),(557,'http://aicms.test/admin/login','GET',6,2,1000,2681712,3334000,200,0,0,'127.0.0.1',NULL,'2026-07-24 13:40:27'),(558,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',14,3,1000,357552,1056272,404,0,1,'127.0.0.1',NULL,'2026-07-24 13:41:39'),(559,'http://aicms.test/admin/dashboard/topContent','GET',9,2,1000,2778904,3407912,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:41:40'),(560,'http://aicms.test/admin/dashboard/getDeadLinkStats','GET',7,0,0,2719160,3363568,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:41:47'),(561,'http://aicms.test/admin/dashboard/index','GET',8,0,0,2810800,3480488,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:41:47'),(562,'http://aicms.test/admin/dashboard/getRevenueStats?days=7','GET',8,2,1000,2780760,3412640,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:41:51'),(563,'http://aicms.test/admin/dashboard/categoryStats','GET',8,0,0,2719504,3363504,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:41:51'),(564,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2848528,3506712,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:41:54'),(565,'http://aicms.test/admin/dashboard/getMemberGrowth?days=7','GET',8,3,1000,2780576,3430208,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:41:55'),(566,'http://aicms.test/admin/dashboard/index?_=1784871707736','GET',8,0,0,2849280,3519392,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:41:56'),(567,'http://aicms.test/admin/dashboard/trend?days=7','GET',8,0,0,2721992,3365448,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:41:56'),(568,'http://aicms.test/admin/dashboard/getSourceAnalysis?days=7','GET',9,2,1000,2774056,3406208,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:41:56'),(569,'http://aicms.test/admin/dashboard/trend?days=7','GET',8,0,0,2721992,3365448,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:42:05'),(570,'http://aicms.test/admin/dashboard/getMemberGrowth?days=7','GET',10,3,1000,2780576,3430224,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:42:05'),(571,'http://aicms.test/admin/dashboard/overview','GET',9,8,1001,2789496,3439168,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:43:00'),(572,'http://aicms.test/admin/dashboard/categoryStats','GET',7,0,0,2719504,3363504,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:43:00'),(573,'http://aicms.test/admin/dashboard/getSourceAnalysis?days=7','GET',8,2,1000,2774040,3406192,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:43:00'),(574,'http://aicms.test/admin/dashboard/getRevenueStats?days=7','GET',9,2,1000,2780744,3412624,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:43:00'),(575,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',14,3,1000,357552,1056272,404,0,1,'127.0.0.1',NULL,'2026-07-24 13:43:05'),(576,'http://aicms.test/admin/dashboard/getMemberGrowth?days=7','GET',8,3,1000,2782016,3431672,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:43:06'),(577,'http://aicms.test/admin/dashboard/trend?days=7','GET',7,0,0,2816024,3500824,500,0,1,'127.0.0.1',NULL,'2026-07-24 13:43:07'),(578,'http://aicms.test/admin/dashboard/getContentRank?limit=20','GET',8,2,1000,2780256,3412464,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:43:07'),(579,'http://aicms.test/admin/dashboard/getMemberGrowth?days=7','GET',8,3,1000,2780576,3430224,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:43:07'),(580,'http://aicms.test/admin/dashboard/topContent','GET',10,2,1000,2778920,3407928,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:43:09'),(581,'http://aicms.test/admin/dashboard/overview','GET',11,8,1002,2789496,3439168,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:43:10'),(582,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2848544,3506728,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:49:54'),(583,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',10,2,1000,2848544,3508424,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:53:56'),(584,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2848528,3508408,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:57:07'),(585,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2848528,3508456,200,0,1,'127.0.0.1',NULL,'2026-07-24 13:58:08'),(586,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,3,1000,2864400,3522584,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:00:54'),(587,'http://aicms.test/admin/dashboard/getSourceAnalysis?days=7','GET',9,2,1000,2774056,3406208,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:02:04'),(588,'http://aicms.test/admin/dashboard/index','GET',7,0,0,2818992,3484584,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:02:08'),(589,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',13,3,1000,357568,1056288,404,0,1,'127.0.0.1',NULL,'2026-07-24 14:02:08'),(590,'http://aicms.test/admin/dashboard/categoryStats','GET',7,0,0,2719504,3363504,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:02:08'),(591,'http://aicms.test/admin/dashboard/trend?days=7','GET',8,0,0,2721992,3365448,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:02:12'),(592,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2848560,3508488,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:02:16'),(593,'http://aicms.test/admin/seo_diagnose/index?_=1784872936559','GET',37,7,1002,2910760,3574440,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:02:18'),(594,'http://aicms.test/admin/dashboard/index','GET',9,0,0,2818992,3484584,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:02:37'),(595,'http://aicms.test/admin/dashboard/categoryStats','GET',10,0,0,2719504,3363504,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:02:44'),(596,'http://aicms.test/admin/dashboard/getSourceAnalysis?days=7','GET',13,2,1000,2774056,3406208,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:02:44'),(597,'http://aicms.test/admin/dashboard/getDeadLinkStats','GET',12,0,0,2719160,3363568,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:02:44'),(598,'http://aicms.test/admin/dashboard/categoryStats','GET',9,0,0,2720800,3385856,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:02:45'),(599,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2848560,3506744,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:07:54'),(600,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2848544,3508472,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:08:08'),(601,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2848544,3508424,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:08:37'),(602,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2848544,3508424,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:09:37'),(603,'http://aicms.test/admin/dashboard/getContentRank?limit=20','GET',7,0,0,2815920,3501088,500,0,1,'127.0.0.1',NULL,'2026-07-24 14:09:50'),(604,'http://aicms.test/admin/captcha/config?_=1784873463657','GET',10,8,1001,2834000,3525176,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:11:31'),(605,'http://aicms.test/admin/visit_archive/index?_=1784873463658','GET',14,3,1003,2874520,3560224,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:11:31'),(606,'http://aicms.test/admin/log/index','GET',11,5,1001,2997208,3683432,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:11:37'),(607,'http://aicms.test/admin/system/allTemplates','GET',8,0,0,2726880,3362184,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:11:38'),(608,'http://aicms.test/admin/system/allTemplates','GET',12,0,0,2726880,3363952,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:11:42'),(609,'http://aicms.test/admin/ai_agent/monitor?_=1784873463668','GET',14,9,1003,2897328,3576216,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:11:43'),(610,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2848544,3508424,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:11:50'),(611,'http://aicms.test/admin/mail_log/overview','GET',10,0,0,2720728,3363344,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:11:52'),(612,'http://aicms.test/admin/dashboard/getRevenueStats?days=7','GET',9,3,1001,2780760,3426688,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:14:07'),(613,'http://aicms.test/admin/dashboard/categoryStats','GET',10,0,0,2816288,3498472,500,0,1,'127.0.0.1',NULL,'2026-07-24 14:14:10'),(614,'http://aicms.test/admin/dashboard/getRevenueStats?days=7','GET',10,3,1001,2780840,3426760,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:14:12'),(615,'http://aicms.test/admin/seo/index?_=1784873652565','GET',6,0,0,2765368,3434760,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:14:15'),(616,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',13,3,1000,357568,1056200,404,0,1,'127.0.0.1',NULL,'2026-07-24 14:14:16'),(617,'http://aicms.test/admin/dashboard/trend?days=7','GET',6,0,0,2721992,3365448,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:14:16'),(618,'http://aicms.test/admin/dashboard/getDeadLinkStats','GET',8,0,0,2719160,3363568,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:14:16'),(619,'http://aicms.test/admin/dashboard/topContent','GET',12,3,1001,2778824,3426704,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:14:20'),(620,'http://aicms.test/admin/seo_keyword/index?_=1784873656796','GET',12,6,1002,2926776,3638056,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:14:27'),(621,'http://aicms.test/admin/dashboard/index?_=1784873656799','GET',8,0,0,2853376,3527632,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:14:30'),(622,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2848544,3508424,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:14:31'),(623,'http://aicms.test/admin/dashboard/getContentRank?limit=20','GET',8,3,1001,2780192,3431232,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:14:31'),(624,'http://aicms.test/admin/dashboard/categoryStats','GET',10,0,0,2719504,3363504,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:16:38'),(625,'http://aicms.test/admin/dashboard/categoryStats','GET',7,0,0,2719504,3363504,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:16:41'),(626,'http://aicms.test/api/cache/clearByType','POST',13,2,1007,376584,1008448,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:16:46'),(627,'http://aicms.test/admin/dashboard/index?_=1784873807781','GET',7,0,0,2853376,3527584,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:16:51'),(628,'http://aicms.test/admin/seo/index?_=1784873807782','GET',6,0,0,2765640,3476864,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:16:52'),(629,'http://aicms.test/admin/dashboard/getSourceAnalysis?days=7','GET',9,0,0,2815928,3501080,500,0,1,'127.0.0.1',NULL,'2026-07-24 14:16:54'),(630,'http://aicms.test/admin/dashboard/trend?days=7','GET',9,0,0,2721992,3365448,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:16:55'),(631,'http://aicms.test/admin/dashboard/getRevenueStats?days=7','GET',8,0,0,2703392,3390168,500,0,1,'127.0.0.1',NULL,'2026-07-24 14:16:55'),(632,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',12,3,1000,357568,1056200,404,0,1,'127.0.0.1',NULL,'2026-07-24 14:16:59'),(633,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2848544,3508424,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:17:04'),(634,'http://aicms.test/admin/dashboard/getMemberGrowth?days=7','GET',10,5,1002,2780560,3430264,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:17:04'),(635,'http://aicms.test/admin/dashboard/trend?days=7','GET',6,0,0,2721992,3365448,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:17:12'),(636,'http://aicms.test/admin/dashboard/getRevenueStats?days=7','GET',9,3,1001,2780760,3426688,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:17:17'),(637,'http://aicms.test/admin/dashboard/getContentRank?limit=20','GET',9,3,1001,2780192,3431232,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:17:17'),(638,'http://aicms.test/admin/dashboard/getMemberGrowth?days=7','GET',9,5,1002,2780560,3430264,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:17:31'),(639,'http://aicms.test/admin/dashboard/overview','GET',18,15,1009,2789480,3440264,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:17:40'),(640,'http://aicms.test/admin/dashboard/getContentRank?limit=20','GET',9,3,1001,2780160,3431216,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:17:40'),(641,'http://aicms.test/admin/dashboard/trend?days=7','GET',10,0,0,2721992,3363752,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:19:46'),(642,'http://aicms.test/admin/dashboard/getMemberGrowth?days=7','GET',9,0,0,2815920,3499344,500,0,1,'127.0.0.1',NULL,'2026-07-24 14:19:46'),(643,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2848544,3508424,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:22:40'),(644,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2848544,3508424,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:23:40'),(645,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',26,2,1000,2848560,3506744,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:25:54'),(646,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',11,2,1000,2848544,3508472,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:25:58'),(647,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2848544,3508424,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:26:33'),(648,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2848544,3508424,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:27:40'),(649,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2847872,3507800,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:29:57'),(650,'http://aicms.test/admin/dashboard/getMemberGrowth?days=7','GET',10,5,1003,2779904,3429600,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:31:23'),(651,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2847872,3507752,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:31:38'),(652,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2847872,3507752,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:34:41'),(653,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2847856,3506040,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:38:54'),(654,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2847872,3507800,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:39:06'),(655,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',11,3,1000,2863760,3523640,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:40:47'),(656,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2847872,3507752,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:41:18'),(657,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2847872,3507752,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:42:49'),(658,'http://aicms.test/admin/dashboard/getContentRank?limit=20','GET',9,3,1001,2779504,3430552,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:43:13'),(659,'http://aicms.test/admin/dashboard/getRevenueStats?days=7','GET',9,3,1001,2780168,3426088,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:43:16'),(660,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',13,3,1000,357552,1056184,404,0,1,'127.0.0.1',NULL,'2026-07-24 14:43:24'),(661,'http://aicms.test/admin/seo/index?_=1784875393764','GET',7,0,0,2764856,3434088,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:43:39'),(662,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',17,3,1001,357552,1056184,404,0,1,'127.0.0.1',NULL,'2026-07-24 14:48:08'),(663,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',22,3,1000,357552,1056184,404,0,1,'127.0.0.1',NULL,'2026-07-24 14:48:08'),(664,'http://aicms.test/admin/mail_log/statistics_data?type=status','GET',10,3,1001,2786920,3436032,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:48:08'),(665,'http://aicms.test/admin/mail_log/overview','GET',12,5,1002,2787048,3449264,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:48:08'),(666,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2847888,3507816,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:48:09'),(667,'http://aicms.test/admin/mail_log/statistics_data?type=hourly','GET',8,2,1000,2800008,3431976,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:48:11'),(668,'http://aicms.test/admin/mail_log/overview','GET',7,0,0,2720056,3363320,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:48:18'),(669,'http://aicms.test/admin/mail_log/statistics_data?type=hourly','GET',8,2,1000,2800008,3431976,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:48:21'),(670,'http://aicms.test/admin/system/config?_=1784875698449','GET',24,11,1004,3157048,4449056,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:48:27'),(671,'http://aicms.test/admin/mail_log/overview','GET',6,0,0,2720056,3363320,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:48:29'),(672,'http://aicms.test/admin/seo/index?_=1784870574885','GET',8,0,0,2823856,3659944,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:50:05'),(673,'http://aicms.test/admin/dashboard/getMemberGrowth?days=7','GET',13,5,1002,2779888,3429592,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:50:13'),(674,'http://aicms.test/admin/dashboard/getSourceAnalysis?days=7','GET',9,2,1000,2773368,3405520,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:50:15'),(675,'http://aicms.test/admin/dashboard/getContentRank?limit=20','GET',8,3,1001,2779488,3430544,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:50:15'),(676,'http://aicms.test/admin/dashboard/overview','GET',28,15,1010,2788808,3439592,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:50:17'),(677,'http://aicms.test/admin/dashboard/getMemberGrowth?days=7','GET',10,5,1002,2779904,3429600,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:50:17'),(678,'http://aicms.test/admin/dashboard/getDeadLinkStats','GET',6,0,0,2718488,3363544,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:50:17'),(679,'http://aicms.test/admin/seo_keyword/index?_=1784875815483','GET',12,6,1002,2926104,3637384,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:50:20'),(680,'http://aicms.test/admin/queue/index?_=1784875815485','GET',12,3,1001,2881376,3584272,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:50:21'),(681,'http://aicms.test/admin/db_rw/index?_=1784875824874','GET',14,0,0,2760432,3483192,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:50:26'),(682,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2847888,3506072,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:50:54'),(683,'http://aicms.test/admin/ai_theme/index?_=1784875824883','GET',10,4,1001,2952344,3647832,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:51:17'),(684,'http://aicms.test/admin/ai_translation/index?_=1784875824886','GET',14,6,1002,3059064,3757248,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:51:19'),(685,'http://aicms.test/admin/ai_model/index?_=1784875824889','GET',11,3,1001,2958408,3627512,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:51:20'),(686,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2847872,3507752,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:58:24'),(687,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',12,3,1000,356552,1055184,404,0,1,'127.0.0.1',NULL,'2026-07-24 14:59:00'),(688,'http://aicms.test/admin/ai_batch/index?_=1784876347947','GET',9,2,1000,2838752,3562208,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:59:19'),(689,'http://aicms.test/admin/subscriber/analysis?_=1784876347948','GET',6,0,0,2761248,3465720,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:59:26'),(690,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2847872,3507800,200,0,1,'127.0.0.1',NULL,'2026-07-24 14:59:27'),(691,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2847888,3507816,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:01:36'),(692,'http://aicms.test/admin/login','GET',37,12,1005,2775152,4084144,200,0,0,'127.0.0.1',NULL,'2026-07-24 15:05:59'),(693,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',18,3,1000,356552,1055184,404,0,1,'127.0.0.1',NULL,'2026-07-24 15:22:19'),(694,'http://aicms.test/admin/template_order_admin/index?_=1784877744339','GET',22,8,1006,2886688,3569056,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:22:37'),(695,'http://aicms.test/admin/template_settlement_admin/index?_=1784877744345','GET',10,5,1001,2873064,3599488,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:22:40'),(696,'http://aicms.test/admin/plugin_market/index?_=1784877744353','GET',4192,0,0,2926704,3660656,200,1,1,'127.0.0.1',NULL,'2026-07-24 15:22:53'),(697,'http://aicms.test/admin/ai_theme/index?_=1784877744363','GET',14,4,1001,2951072,3646560,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:23:10'),(698,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',11,3,1000,356536,1055168,404,0,1,'127.0.0.1',NULL,'2026-07-24 15:23:10'),(699,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',19,3,1001,356552,1055184,404,0,1,'127.0.0.1',NULL,'2026-07-24 15:23:30'),(700,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',18,3,1001,356552,1055184,404,0,1,'127.0.0.1',NULL,'2026-07-24 15:23:43'),(701,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2846000,3505928,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:35:27'),(702,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',14,3,1000,356552,1055184,404,0,1,'127.0.0.1',NULL,'2026-07-24 15:36:43'),(703,'http://aicms.test/admin/translate/languages?_=1784878603158','GET',11,4,1000,2998424,3661272,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:36:49'),(704,'http://aicms.test/admin/ai_theme/index?_=1784878603162','GET',7,3,1000,2873288,3566488,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:39:43'),(705,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2845984,3505912,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:40:32'),(706,'http://aicms.test/admin/system/config?tab=features','GET',27,11,1003,3461856,4447840,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:44:34'),(707,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2846000,3504136,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:46:02'),(708,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',31,12,1006,411032,1097568,404,0,1,'127.0.0.1',NULL,'2026-07-24 15:51:59'),(709,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',222,4,1000,371568,1070152,404,0,1,'127.0.0.1',NULL,'2026-07-24 15:51:59'),(710,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',11,3,1000,356552,1055136,404,0,1,'127.0.0.1',NULL,'2026-07-24 15:52:01'),(711,'http://aicms.test/admin/ai_batch/index?_=1784879521352','GET',10,3,1001,2898872,3583624,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:52:07'),(712,'http://aicms.test/admin/template_store_stats/index?_=1784879521362','GET',18,18,1005,2994680,3695856,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:52:29'),(713,'http://aicms.test/admin/ad/index?_=1784879521367','GET',10,3,1002,2893800,3581496,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:52:33'),(714,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2845984,3505864,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:53:01'),(715,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2846000,3504136,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:54:02'),(716,'http://aicms.test/admin/template_audit_workflow/index?_=1784879521400','GET',7,3,1000,2865544,3527696,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:54:22'),(717,'http://aicms.test/admin/template_recommend_position/index?_=1784879521401','GET',7,3,1000,2827392,3550400,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:54:23'),(718,'http://aicms.test/admin/plugin_store/index?_=1784879521421','GET',7,3,1000,2816288,3488016,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:57:38'),(719,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,3,1001,2845936,3505864,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:58:01'),(720,'http://aicms.test/admin/ai_theme/index?_=1784879074861','GET',9,4,1001,2950472,3645960,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:59:42'),(721,'http://aicms.test/admin/publish_platform/add','GET',8,0,0,2838080,3504304,200,0,1,'127.0.0.1',NULL,'2026-07-24 15:59:54'),(722,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',17,3,1000,356552,1055136,404,0,1,'127.0.0.1',NULL,'2026-07-24 15:59:57'),(723,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',10,3,1000,356552,1055136,404,0,1,'127.0.0.1',NULL,'2026-07-24 16:01:42'),(724,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2845984,3505912,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:01:49'),(725,'http://aicms.test/admin/email_template/add?_=1784880084780','GET',7,0,0,2759048,3480200,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:01:58'),(726,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2846000,3505928,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:02:25'),(727,'http://aicms.test/admin/ops_automation/create?_=1784880183287','GET',6,0,0,2775368,3460488,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:03:50'),(728,'http://aicms.test/admin/dashboard/getRevenueStats?days=7','GET',11,4,1003,2776648,3423800,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:04:15'),(729,'http://aicms.test/admin/dashboard/getMemberGrowth?days=7','GET',10,5,1002,2776504,3426200,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:04:15'),(730,'http://aicms.test/admin/email_log/index?_=1784880183300','GET',8,3,1001,2825032,3551136,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:04:26'),(731,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2844472,3502608,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:05:02'),(732,'http://aicms.test/admin/content_model/index','GET',10,3,1001,2874264,3546104,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:06:56'),(733,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',32,12,1005,411016,1097584,404,0,1,'127.0.0.1',NULL,'2026-07-24 16:06:56'),(734,'http://aicms.test/api/cache/clearByType','POST',37,3,1015,367848,1039200,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:07:01'),(735,'http://aicms.test/admin/plugin_market/index','GET',7482,0,0,2931152,3591000,200,1,1,'127.0.0.1',NULL,'2026-07-24 16:07:16'),(736,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',13,3,1001,356536,1055120,404,0,1,'127.0.0.1',NULL,'2026-07-24 16:07:29'),(737,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',13,3,1000,356648,1054608,404,0,0,'127.0.0.1',NULL,'2026-07-24 16:08:55'),(738,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',13,3,1000,356664,1054624,404,0,0,'127.0.0.1',NULL,'2026-07-24 16:08:55'),(739,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',9,3,1000,356648,1054608,404,0,0,'127.0.0.1',NULL,'2026-07-24 16:08:55'),(740,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',9,3,1000,356648,1054608,404,0,0,'127.0.0.1',NULL,'2026-07-24 16:08:55'),(741,'http://aicms.test/admin/login/captcha','GET',14,2,1000,2687352,3412904,200,0,0,'127.0.0.1',NULL,'2026-07-24 16:08:55'),(742,'http://aicms.test/admin/publish_platform/add','GET',7,0,0,2836552,3502424,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:09:07'),(743,'http://aicms.test/admin/content_model/index','GET',18,3,1001,2874984,3544776,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:10:28'),(744,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2844472,3502704,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:12:28'),(745,'http://aicms.test/admin/publish_platform/add','GET',66,11,1005,3442360,5027760,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:13:18'),(746,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2845304,3559136,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:13:21'),(747,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2844488,3504416,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:13:39'),(748,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2844456,3504384,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:14:21'),(749,'http://aicms.test/admin/content_model/index','GET',8,3,1000,2860256,3530016,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:16:48'),(750,'http://aicms.test/admin/content_model/index','GET',8,3,1000,2989208,3630088,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:19:42'),(751,'http://aicms.test/admin/content_model/index?_=1784881302256','GET',7,3,1000,2929256,3594296,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:21:48'),(752,'http://aicms.test/admin/content_model/index','GET',7,3,1000,2898176,3567936,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:22:07'),(753,'http://aicms.test/admin/content_model/create.html?_=1784881327327','GET',10,2,1000,2826984,3500872,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:22:47'),(754,'http://aicms.test/admin/content_model_migration/index?_=1784881327336','GET',15,4,1004,2999624,3665232,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:23:44'),(755,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2844472,3502704,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:26:16'),(756,'http://aicms.test/admin/member/edit?id=1&_=1784881327359','GET',15,6,1005,3014464,3707136,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:29:33'),(757,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2844488,3504416,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:29:37'),(758,'http://aicms.test/admin/rating/index?_=1784881327367','GET',8,6,1000,2844184,3505016,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:29:48'),(759,'http://aicms.test/admin/quality_monitor/index?_=1784881327388','GET',13,5,1002,2864328,3552296,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:30:24'),(760,'http://aicms.test/admin/invite/detail/1?_=1784881327399','GET',8,4,1001,2869376,3601616,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:30:47'),(761,'http://aicms.test/admin/ab_test/create?_=1784881327408','GET',7,0,0,2819640,3484616,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:31:02'),(762,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,3,1001,2844408,3504288,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:31:48'),(763,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',219,4,1001,362920,1061504,404,0,1,'127.0.0.1',NULL,'2026-07-24 16:31:48'),(764,'http://aicms.test/admin/ab_test/results?_=1784881910536','GET',8,3,1001,2832352,3562856,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:31:55'),(765,'http://aicms.test/admin/ops_automation/index?_=1784881910553','GET',9,3,1000,2800192,3473504,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:32:40'),(766,'http://aicms.test/admin/comment/index?_=1784882055095','GET',7,3,1001,2834880,3501936,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:34:19'),(767,'http://aicms.test/admin/redis/index','GET',29,11,1006,2899160,4043912,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:34:49'),(768,'http://aicms.test/admin/user_segment/create?_=1784882091082','GET',7,0,0,2765928,3428104,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:35:01'),(769,'http://aicms.test/admin/pwa_config/index?_=1784882091092','GET',9,7,1001,2799392,3470832,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:35:28'),(770,'http://aicms.test/admin/message/sendSystem?_=1784882091097','GET',7,0,0,2755336,3423832,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:35:34'),(771,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2843576,3503504,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:35:43'),(772,'http://aicms.test/admin/comment/index?_=1784882243403','GET',6,2,1000,2833112,3500168,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:37:30'),(773,'http://aicms.test/admin/rating/index?_=1784882243404','GET',9,7,1001,2842720,3503552,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:37:33'),(774,'http://aicms.test/admin/collect_log/index?_=1784882316867','GET',11,3,1001,2878440,3560816,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:41:14'),(775,'http://aicms.test/admin/email_log/index?_=1784882316869','GET',12,3,1001,2877672,3559912,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:41:16'),(776,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',12,3,1000,356552,1055136,404,0,1,'127.0.0.1',NULL,'2026-07-24 16:41:23'),(777,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',37,12,1005,411824,1243832,404,0,1,'127.0.0.1',NULL,'2026-07-24 16:41:29'),(778,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',36,12,1005,479800,1241696,404,0,1,'127.0.0.1',NULL,'2026-07-24 16:41:29'),(779,'http://aicms.test/admin/report/index','GET',8,2,1000,2860128,3515408,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:42:53'),(780,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',15,3,1000,356536,1055120,404,0,1,'127.0.0.1',NULL,'2026-07-24 16:45:01'),(781,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',12,3,1000,356536,1055120,404,0,1,'127.0.0.1',NULL,'2026-07-24 16:45:10'),(782,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2843016,3501200,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:45:53'),(783,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2842984,3501264,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:52:35'),(784,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',32,2,1000,2843000,3502880,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:57:17'),(785,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2842984,3501264,200,0,1,'127.0.0.1',NULL,'2026-07-24 16:59:35'),(786,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',20,2,1000,2843000,3502880,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:00:20'),(787,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',12,3,1000,356552,1055136,404,0,1,'127.0.0.1',NULL,'2026-07-24 17:01:04'),(788,'http://aicms.test/admin/report/index','GET',7,2,1000,2860128,3517480,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:01:39'),(789,'http://aicms.test/admin/report/index','GET',8,2,1000,2860128,3517480,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:01:39'),(790,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2842984,3502864,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:01:39'),(791,'http://aicms.test/admin/content_model/designer.html?id=10','GET',20,6,1004,2847976,3574584,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:01:55'),(792,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2843000,3501208,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:02:49'),(793,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2843000,3502976,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:02:56'),(794,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',12,3,1000,356536,1055120,404,0,1,'127.0.0.1',NULL,'2026-07-24 17:03:06'),(795,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2843000,3502976,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:04:06'),(796,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2843000,3502880,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:04:40'),(797,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2842824,3501192,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:06:04'),(798,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2843000,3502976,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:06:06'),(799,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2843016,3502896,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:07:39'),(800,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2842984,3501192,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:09:05'),(801,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2843000,3502976,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:11:08'),(802,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2842984,3502912,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:11:49'),(803,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',10,2,1000,2842984,3501264,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:15:15'),(804,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2843000,3502928,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:16:50'),(805,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2842984,3501192,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:18:08'),(806,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2843000,3502880,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:21:39'),(807,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2843016,3502944,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:22:56'),(808,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2842984,3501264,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:24:15'),(809,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',5,2,1000,2843000,3502880,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:24:39'),(810,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2843016,3502896,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:27:39'),(811,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2849264,3539512,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:30:40'),(812,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',56,2,1000,3275416,4678872,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:32:08'),(813,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2848448,3506728,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:33:03'),(814,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2848480,3506688,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:33:08'),(815,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2848464,3508392,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:37:58'),(816,'http://aicms.test/admin/content_model/designer/10?preview=1','GET',10,4,1000,2912104,3641608,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:41:58'),(817,'http://aicms.test/admin/plugin_market/index?_=1784886188380','GET',1435,0,0,2928664,3662616,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:43:17'),(818,'http://aicms.test/admin/theme_market/index?_=1784886188384','GET',17,3,1003,3166056,3923200,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:43:22'),(819,'http://aicms.test/admin/subscriber/index?_=1784886188391','GET',8,0,0,2846040,3515104,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:44:23'),(820,'http://aicms.test/admin/template_store_ops/statsDashboard?_=1784886188397','GET',13,14,1004,2950456,3660280,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:44:30'),(821,'http://aicms.test/admin/api_key/index?_=1784886188398','GET',9,3,1001,2907464,3614200,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:44:30'),(822,'http://aicms.test/admin/api_key/add','GET',6,0,0,2724248,3381632,404,0,1,'127.0.0.1',NULL,'2026-07-24 17:44:32'),(823,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',17,3,1001,356536,1055120,404,0,1,'127.0.0.1',NULL,'2026-07-24 17:44:34'),(824,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2848480,3508360,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:44:34'),(825,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2848464,3508344,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:44:52'),(826,'http://aicms.test/admin/subscriber/analysis/overview','GET',10,7,1001,2786392,3439968,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:44:58'),(827,'http://aicms.test/admin/subscriber/analysis/trend','GET',24,61,1010,2796616,3450496,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:44:58'),(828,'http://aicms.test/admin/push/log?_=1784886292849','GET',5,0,0,2768440,3479144,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:45:05'),(829,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2848464,3508392,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:45:08'),(830,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',14,3,1000,356552,1055136,404,0,1,'127.0.0.1',NULL,'2026-07-24 17:50:12'),(831,'http://aicms.test/admin/workflow/records?_=1784886612147','GET',11,4,1001,2914976,3638536,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:50:16'),(832,'http://aicms.test/admin/content_model/index?_=1784886612148','GET',7,3,1000,2936392,3597000,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:50:17'),(833,'http://aicms.test/admin/sms/logs?_=1784886612154','GET',13,4,1001,2861032,3548696,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:50:23'),(834,'http://aicms.test/admin/content_model/designer.html?id=10&_=1784886612167','GET',16,5,1002,3041984,3814496,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:50:47'),(835,'http://aicms.test/admin/template_store/index?_=1784886612177','GET',12,4,1001,3121696,3809744,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:51:03'),(836,'http://aicms.test/admin/template_store/add?_=1784886612178','GET',7,0,0,2882624,3588600,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:51:04'),(837,'http://aicms.test/admin/plugin_market/index?_=1784886612180','GET',2848,0,0,3065120,3799120,200,1,1,'127.0.0.1',NULL,'2026-07-24 17:51:10'),(838,'http://aicms.test/admin/email_log/index?_=1784886612186','GET',13,3,1001,2885128,3567320,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:51:15'),(839,'http://aicms.test/admin/publish_platform/add?_=1784886612191','GET',7,0,0,2840504,3512064,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:51:21'),(840,'http://aicms.test/admin/collect_source/index?_=1784886682560','GET',10,3,1001,2888576,3570784,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:51:28'),(841,'http://aicms.test/admin/ai_batch/index?_=1784886682575','GET',11,3,1002,2895136,3588080,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:51:50'),(842,'http://aicms.test/admin/ai_batch/create.html?_=1784886682576','GET',12,3,1000,2971312,3645016,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:51:51'),(843,'http://aicms.test/admin/ai_log/index?_=1784886682577','GET',12,4,1001,2910976,3589848,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:51:52'),(844,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',13,3,1000,356552,1055136,404,0,1,'127.0.0.1',NULL,'2026-07-24 17:52:36'),(845,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',12,3,1000,356552,1055136,404,0,1,'127.0.0.1',NULL,'2026-07-24 17:52:37'),(846,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',11,3,1000,356536,1055120,404,0,1,'127.0.0.1',NULL,'2026-07-24 17:52:39'),(847,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',9,3,1000,356536,1055120,404,0,1,'127.0.0.1',NULL,'2026-07-24 17:52:39'),(848,'http://aicms.test/admin/api_key/add?_=1784886561296','GET',6,0,0,2768344,3429752,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:53:00'),(849,'http://aicms.test/admin/push/channel?_=1784886802762','GET',8,0,0,2919896,3593576,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:53:27'),(850,'http://aicms.test/admin/workflow/index?_=1784886682587','GET',6,2,1000,2824536,3548032,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:53:39'),(851,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',25,3,1000,356536,1055120,404,0,1,'127.0.0.1',NULL,'2026-07-24 17:54:26'),(852,'http://aicms.test/admin/message/system?_=1784886682616','GET',11,3,1002,2888392,3570288,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:55:09'),(853,'http://aicms.test/admin/payment/index?_=1784886682621','GET',7,2,1000,2835832,3534416,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:55:13'),(854,'http://aicms.test/admin/publish_platform/index?_=1784886913629','GET',10,4,1001,2906040,3575352,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:55:58'),(855,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851800,3510032,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:56:13'),(856,'http://aicms.test/admin/publish_log/index?_=1784886977259','GET',14,10,1002,2940192,3655824,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:58:09'),(857,'http://aicms.test/admin/system/config?_=1784887121073','GET',20,11,1002,3152880,4453688,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:59:12'),(858,'http://aicms.test/admin/system/allTemplates','GET',7,0,0,2730136,3367376,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:59:18'),(859,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851800,3511680,200,0,1,'127.0.0.1',NULL,'2026-07-24 17:59:32'),(860,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851800,3509984,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:02:17'),(861,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',10,2,1000,2851800,3511728,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:05:27'),(862,'http://aicms.test/admin/form/add?_=1784887344298','GET',7,0,0,2844344,3514368,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:05:46'),(863,'http://aicms.test/admin/form/index?_=1784887344307','GET',6,2,1000,2833936,3502720,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:06:00'),(864,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1001,2851800,3511680,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:07:02'),(865,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851800,3511680,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:13:04'),(866,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851800,3511712,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:16:07'),(867,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851800,3511760,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:16:35'),(868,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851816,3511776,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:18:37'),(869,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851816,3511776,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:20:39'),(870,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851816,3510032,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:21:17'),(871,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851784,3510000,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:24:17'),(872,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851816,3511728,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:31:03'),(873,'http://aicms.test/admin/log/index?_=1784887166056','GET',11,5,1001,3157008,3825704,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:31:46'),(874,'http://aicms.test/admin/log/index?_=1784887166058','GET',10,5,1001,3071264,3685888,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:31:47'),(875,'http://aicms.test/admin/log/index','GET',9,5,1000,2999000,3687328,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:31:56'),(876,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851800,3510016,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:32:17'),(877,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851800,3510016,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:33:17'),(878,'http://aicms.test/admin/points_product/index?_=1784889310338','GET',10,3,1002,2889264,3571352,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:35:16'),(879,'http://aicms.test/admin/traffic/getPageRank?days=7','GET',7,2,1000,2776888,3406760,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:35:55'),(880,'http://aicms.test/admin/monitor/index?_=1784886977278','GET',12,5,1004,2825640,3492256,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:35:57'),(881,'http://aicms.test/admin/export/dialog?_=1784886977280','GET',7,0,0,2764944,3469288,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:35:59'),(882,'http://aicms.test/admin/traffic/getSourceStats?days=7','GET',7,2,1000,2779024,3408776,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:36:01'),(883,'http://aicms.test/admin/traffic/getPageRank?days=7','GET',10,2,1000,2776888,3406760,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:36:01'),(884,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1001,2851800,3511712,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:36:26'),(885,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851800,3510016,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:40:17'),(886,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851784,3511696,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:40:20'),(887,'http://aicms.test/admin','GET',9,4,1001,2920056,3585320,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:41:24'),(888,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851784,3511648,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:41:28'),(889,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851784,3510000,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:42:17'),(890,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851800,3511664,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:45:28'),(891,'http://aicms.test/','HEAD',13,5,1001,409632,1077784,200,0,0,'127.0.0.1',NULL,'2026-07-24 18:45:56'),(892,'http://aicms.test/admin/banner/index','GET',7,2,1000,2848336,3507080,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:45:59'),(893,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851800,3511712,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:45:59'),(894,'http://aicms.test/admin/export/index?_=1784889959621','GET',5,0,0,2756152,3480384,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:46:06'),(895,'http://aicms.test/admin/traffic/getDeviceStats?days=7','GET',7,2,1000,2777240,3408840,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:46:11'),(896,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',13,4,1001,373480,1055168,404,0,1,'127.0.0.1',NULL,'2026-07-24 18:46:18'),(897,'http://aicms.test/admin/redis/index?_=1784889959636','GET',8,0,0,2817424,3506592,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:46:57'),(898,'http://aicms.test/admin/redis/index?_=1784889959638','GET',7,0,0,2760784,3490896,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:46:59'),(899,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851800,3511712,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:46:59'),(900,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',15,3,1000,356536,1055152,404,0,1,'127.0.0.1',NULL,'2026-07-24 18:47:02'),(901,'http://aicms.test/admin/redis/index?_=1784889959644','GET',6,0,0,2760160,3434112,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:47:05'),(902,'http://aicms.test/admin/report/index?_=1784889959646','GET',8,2,1000,2841704,3583696,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:47:22'),(903,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',16,3,1000,356552,1055168,404,0,1,'127.0.0.1',NULL,'2026-07-24 18:47:25'),(904,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851784,3511744,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:47:59'),(905,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851816,3510080,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:48:17'),(906,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851816,3510080,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:52:17'),(907,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851784,3511648,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:53:35'),(908,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',28,2,1000,2851816,3510080,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:54:17'),(909,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2851800,3511760,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:54:41'),(910,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',17,3,1001,356536,1055152,404,0,1,'127.0.0.1',NULL,'2026-07-24 18:54:43'),(911,'http://aicms.test/api/cache/clearByType','POST',125,3,1111,367832,1039128,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:57:35'),(912,'http://aicms.test/admin','GET',50,26,1014,3216704,4047384,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:57:36'),(913,'http://aicms.test/admin/report/index','GET',8,2,1000,2923056,3589976,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:57:40'),(914,'http://aicms.test/admin/report/index','GET',7,2,1000,2923216,3590136,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:58:10'),(915,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',36,3,1007,290336,1063624,404,0,1,'127.0.0.1',NULL,'2026-07-24 18:58:19'),(916,'http://aicms.test/','GET',43,10,1014,573032,1628400,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:58:26'),(917,'http://aicms.test/admin','GET',16,4,1002,2920552,3583744,200,0,1,'127.0.0.1',NULL,'2026-07-24 18:58:29'),(918,'http://aicms.test/admin/report/index','GET',8,2,1000,2966784,3621856,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:03:56'),(919,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',9,3,1000,356552,1059824,404,0,1,'127.0.0.1',NULL,'2026-07-24 19:03:56'),(920,'http://aicms.test/admin/report/index','GET',9,2,1000,2966784,3620160,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:04:48'),(921,'http://aicms.test/admin/report/index','GET',7,2,1000,2966784,3621856,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:06:00'),(922,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',11,3,1000,356552,1059824,404,0,1,'127.0.0.1',NULL,'2026-07-24 19:06:00'),(923,'http://aicms.test/admin/report/index','GET',9,2,1000,2966784,3621856,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:09:06'),(924,'http://aicms.test/admin','GET',28,12,1013,3141768,3773584,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:09:08'),(925,'http://aicms.test/admin/login','GET',6,2,1000,2786880,3418600,302,0,1,'127.0.0.1',NULL,'2026-07-24 19:13:11'),(926,'http://aicms.test/admin/dashboard/trend?days=7','GET',7,0,0,2725248,3367688,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:14:03'),(927,'http://aicms.test/admin/dashboard/getSourceAnalysis?days=7','GET',6,2,1000,2777336,3407824,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:14:04'),(928,'http://aicms.test/admin','GET',18,12,1004,3075848,3771248,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:14:55'),(929,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2852720,3566536,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:15:59'),(930,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:17:09'),(931,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851936,3511800,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:21:09'),(932,'http://aicms.test/assets/css/bootstrap.min.css.map','GET',12,3,1000,356536,1059760,404,0,1,'127.0.0.1',NULL,'2026-07-24 19:21:33'),(933,'http://aicms.test/admin/report/detail/9?_=1784892092954','GET',9,2,1000,2849992,3591088,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:21:39'),(934,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851936,3511800,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:22:09'),(935,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',11,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:27:09'),(936,'http://aicms.test/admin/report/detail/7?_=1784892505482','GET',9,2,1000,2913776,3618136,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:29:49'),(937,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851904,3511816,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:29:56'),(938,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851904,3510120,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:32:25'),(939,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851920,3510136,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:34:25'),(940,'http://aicms.test/admin/aiStat/index?_=1784892998357','GET',9,0,0,2837464,3511112,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:36:45'),(941,'http://aicms.test/admin/report/detail/8?_=1784892998359','GET',7,2,1000,2843248,3571192,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:36:49'),(942,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851904,3511816,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:38:38'),(943,'http://aicms.test/assets/js/bootstrap.bundle.min.js.map','GET',22,7,1002,396928,1082616,404,0,1,'127.0.0.1',NULL,'2026-07-24 19:40:55'),(944,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851904,3510120,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:41:06'),(945,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:49:46'),(946,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-24 19:50:09'),(947,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',29,2,1000,2851904,3511864,200,0,1,'127.0.0.1',NULL,'2026-07-24 20:00:57'),(948,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',31,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-24 20:08:04'),(949,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-24 20:11:09'),(950,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',24,2,1000,2851936,3511800,200,0,1,'127.0.0.1',NULL,'2026-07-24 20:17:09'),(951,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-24 20:19:09'),(952,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-24 20:20:09'),(953,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',226,3,1000,2867792,3527656,200,0,1,'127.0.0.1',NULL,'2026-07-24 20:22:09'),(954,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-24 20:23:09'),(955,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-24 20:23:09'),(956,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-24 20:25:09'),(957,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-24 20:26:09'),(958,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-24 20:34:09'),(959,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-24 20:35:09'),(960,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-24 20:36:09'),(961,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-24 20:42:09'),(962,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851904,3511864,200,0,1,'127.0.0.1',NULL,'2026-07-24 20:47:09'),(963,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2851904,3511768,200,0,1,'127.0.0.1',NULL,'2026-07-24 21:04:09'),(964,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-24 21:06:09'),(965,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851904,3511768,200,0,1,'127.0.0.1',NULL,'2026-07-24 21:14:09'),(966,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-24 21:16:09'),(967,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-24 21:23:09'),(968,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851936,3511800,200,0,1,'127.0.0.1',NULL,'2026-07-24 21:27:09'),(969,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2851904,3511864,200,0,1,'127.0.0.1',NULL,'2026-07-24 21:38:09'),(970,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-24 21:43:09'),(971,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',10,2,1000,2851936,3511800,200,0,1,'127.0.0.1',NULL,'2026-07-24 21:47:09'),(972,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851904,3511864,200,0,1,'127.0.0.1',NULL,'2026-07-24 21:53:09'),(973,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851936,3511800,200,0,1,'127.0.0.1',NULL,'2026-07-24 21:58:09'),(974,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851936,3511800,200,0,1,'127.0.0.1',NULL,'2026-07-24 21:59:09'),(975,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-24 22:06:09'),(976,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-24 22:08:09'),(977,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-24 22:08:09'),(978,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851936,3511800,200,0,1,'127.0.0.1',NULL,'2026-07-24 22:14:09'),(979,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-24 22:19:09'),(980,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-24 22:21:09'),(981,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-24 22:24:09'),(982,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-24 22:35:09'),(983,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-24 22:39:09'),(984,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851904,3511768,200,0,1,'127.0.0.1',NULL,'2026-07-24 22:42:09'),(985,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-24 22:50:09'),(986,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-24 22:54:09'),(987,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851936,3511800,200,0,1,'127.0.0.1',NULL,'2026-07-24 22:57:09'),(988,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',11,2,1000,2851936,3511800,200,0,1,'127.0.0.1',NULL,'2026-07-24 22:59:09'),(989,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851904,3511768,200,0,1,'127.0.0.1',NULL,'2026-07-24 23:05:09'),(990,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851904,3511768,200,0,1,'127.0.0.1',NULL,'2026-07-24 23:06:09'),(991,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-24 23:11:09'),(992,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-24 23:12:09'),(993,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851936,3511800,200,0,1,'127.0.0.1',NULL,'2026-07-24 23:13:09'),(994,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-24 23:14:09'),(995,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-24 23:20:09'),(996,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',10,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-24 23:21:09'),(997,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-24 23:31:09'),(998,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2851904,3511864,200,0,1,'127.0.0.1',NULL,'2026-07-24 23:36:09'),(999,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851904,3511864,200,0,1,'127.0.0.1',NULL,'2026-07-24 23:38:09'),(1000,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851936,3511800,200,0,1,'127.0.0.1',NULL,'2026-07-24 23:43:09'),(1001,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',23,7,1002,2930960,4050008,200,0,1,'127.0.0.1',NULL,'2026-07-24 23:44:09'),(1002,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',12,3,1000,2869248,3529208,200,0,1,'127.0.0.1',NULL,'2026-07-24 23:51:09'),(1003,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-24 23:52:09'),(1004,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-24 23:54:09'),(1005,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2851904,3511768,200,0,1,'127.0.0.1',NULL,'2026-07-25 00:00:09'),(1006,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-25 00:09:09'),(1007,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2851904,3511768,200,0,1,'127.0.0.1',NULL,'2026-07-25 00:12:09'),(1008,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-25 00:18:09'),(1009,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-25 00:19:09'),(1010,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-25 00:32:09'),(1011,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',11,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-25 00:43:09'),(1012,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',10,2,1000,2851936,3511800,200,0,1,'127.0.0.1',NULL,'2026-07-25 00:48:09'),(1013,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-25 00:51:09'),(1014,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-25 00:52:09'),(1015,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',13,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-25 00:54:09'),(1016,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',11,3,1000,2867808,3527768,200,0,1,'127.0.0.1',NULL,'2026-07-25 00:57:09'),(1017,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-25 00:58:09'),(1018,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,3,1000,2867808,3527768,200,0,1,'127.0.0.1',NULL,'2026-07-25 01:08:09'),(1019,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',10,2,1000,2851904,3511864,200,0,1,'127.0.0.1',NULL,'2026-07-25 01:13:09'),(1020,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',10,2,1000,2851904,3511864,200,0,1,'127.0.0.1',NULL,'2026-07-25 01:17:09'),(1021,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-25 01:18:09'),(1022,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-25 01:20:09'),(1023,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-25 01:21:09'),(1024,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-25 01:22:09'),(1025,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851936,3511800,200,0,1,'127.0.0.1',NULL,'2026-07-25 01:22:09'),(1026,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-25 01:33:09'),(1027,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-25 01:35:09'),(1028,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',22,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-25 01:38:09'),(1029,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',10,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-25 01:44:09'),(1030,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-25 01:45:09'),(1031,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',15,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-25 01:51:09'),(1032,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-25 01:56:09'),(1033,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',16,2,1000,2851904,3511864,200,0,1,'127.0.0.1',NULL,'2026-07-25 02:00:09'),(1034,'http://aicsm333.test/case','GET',29,6,1002,448800,1417632,200,0,0,'127.0.0.1',NULL,'2026-07-25 02:07:31'),(1035,'http://aicsm333.test/member/register','GET',25,4,1001,523488,1281432,200,0,0,'127.0.0.1',NULL,'2026-07-25 02:07:33'),(1036,'http://aicsm333.test/member/captcha','GET',24,4,1001,369128,1164608,200,0,0,'127.0.0.1',NULL,'2026-07-25 02:07:33'),(1037,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',58,2,1000,3182616,4666104,200,0,1,'127.0.0.1',NULL,'2026-07-25 02:08:09'),(1038,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',14,2,1000,2851904,3511768,200,0,1,'127.0.0.1',NULL,'2026-07-25 02:09:09'),(1039,'http://aicsm333.test/member/login','GET',89,7,1003,806480,3056936,200,0,0,'127.0.0.1',NULL,'2026-07-25 02:13:19'),(1040,'http://aicsm333.test/admin/login','GET',60,9,1007,2851296,4046240,200,0,0,'127.0.0.1',NULL,'2026-07-25 02:14:14'),(1041,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',13,2,1000,2851904,3511768,200,0,1,'127.0.0.1',NULL,'2026-07-25 02:16:09'),(1042,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',14,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-25 02:16:47'),(1043,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',14,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-25 02:17:09'),(1044,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',13,2,1000,2851904,3511864,200,0,1,'127.0.0.1',NULL,'2026-07-25 02:18:44'),(1045,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',11,3,1000,2868576,3528536,200,0,1,'127.0.0.1',NULL,'2026-07-25 02:23:49'),(1046,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',6,2,1000,2851904,3511768,200,0,1,'127.0.0.1',NULL,'2026-07-25 02:24:09'),(1047,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',14,2,1000,2851936,3511800,200,0,1,'127.0.0.1',NULL,'2026-07-25 02:33:09'),(1048,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',13,3,1000,2867792,3527752,200,0,1,'127.0.0.1',NULL,'2026-07-25 02:44:09'),(1049,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-25 02:46:09'),(1050,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-25 02:50:09'),(1051,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',23,2,1000,2851936,3511824,200,0,1,'127.0.0.1',NULL,'2026-07-25 02:52:09'),(1052,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',14,2,1000,2851936,3511800,200,0,1,'127.0.0.1',NULL,'2026-07-25 02:53:09'),(1053,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',7,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-25 02:54:09'),(1054,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',9,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-25 02:59:09'),(1055,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',8,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-25 03:01:09'),(1056,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',12,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-25 03:02:09'),(1057,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',10,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-25 03:03:09'),(1058,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',20,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-25 03:16:09'),(1059,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',15,2,1000,2851904,3511864,200,0,1,'127.0.0.1',NULL,'2026-07-25 03:19:09'),(1060,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',16,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-25 03:21:09'),(1061,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',16,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-25 03:22:09'),(1062,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',29,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-25 03:25:09'),(1063,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',24,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-25 03:26:09'),(1064,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',20,2,1000,2851904,3511864,200,0,1,'127.0.0.1',NULL,'2026-07-25 03:27:02'),(1065,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',14,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-25 03:37:09'),(1066,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',19,2,1000,2851904,3511768,200,0,1,'127.0.0.1',NULL,'2026-07-25 03:44:09'),(1067,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',17,2,1000,2851936,3511896,200,0,1,'127.0.0.1',NULL,'2026-07-25 03:46:02'),(1068,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',13,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-25 03:46:09'),(1069,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',19,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-25 03:49:05'),(1070,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',16,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-25 03:50:06'),(1071,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',15,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-25 04:06:09'),(1072,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',17,2,1000,2851936,3511800,200,0,1,'127.0.0.1',NULL,'2026-07-25 04:10:09'),(1073,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',15,2,1000,2851920,3511880,200,0,1,'127.0.0.1',NULL,'2026-07-25 04:11:09'),(1074,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',18,2,1000,2851936,3511800,200,0,1,'127.0.0.1',NULL,'2026-07-25 04:13:09'),(1075,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',18,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-25 04:16:09'),(1076,'http://aicms.test/admin/notification/index?is_read=0&ajax=1','GET',17,2,1000,2851920,3511784,200,0,1,'127.0.0.1',NULL,'2026-07-25 04:21:09');
DROP TABLE IF EXISTS `{prefix}platform_app`;
CREATE TABLE `{prefix}platform_app` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `app_name` varchar(100) NOT NULL DEFAULT '' COMMENT '应用名称',
  `app_identifier` varchar(100) NOT NULL COMMENT '应用唯一标识(UNIQUE)',
  `app_type` varchar(30) NOT NULL DEFAULT 'web' COMMENT '类型: web/mobile/plugin/integration/other',
  `developer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开发者ID',
  `description` text COMMENT '应用描述',
  `app_config` json DEFAULT NULL COMMENT '应用配置(回调URL/权限等)',
  `required_permissions` json DEFAULT NULL COMMENT '所需权限列表',
  `api_key` varchar(128) NOT NULL DEFAULT '' COMMENT 'API Key',
  `api_secret` varchar(128) NOT NULL DEFAULT '' COMMENT 'API Secret',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT '状态: pending/approved/rejected/offline/published',
  `version` varchar(20) NOT NULL DEFAULT '1.0.0' COMMENT '当前版本',
  `install_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '安装次数',
  `avg_rating` decimal(2,1) NOT NULL DEFAULT '0.0' COMMENT '平均评分',
  `screenshots` json DEFAULT NULL COMMENT '截图URL列表',
  `download_url` varchar(500) NOT NULL DEFAULT '' COMMENT '下载地址',
  `category` varchar(50) NOT NULL DEFAULT '' COMMENT '分类',
  `tags` varchar(255) NOT NULL DEFAULT '' COMMENT '标签',
  `sort_order` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `audit_remark` text COMMENT '审核备注',
  `audited_at` datetime DEFAULT NULL COMMENT '审核时间',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_identifier` (`app_identifier`),
  KEY `idx_developer_id` (`developer_id`),
  KEY `idx_app_type` (`app_type`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`),
  KEY `idx_install_count` (`install_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='开放平台应用表';

DROP TABLE IF EXISTS `{prefix}plugin`;
CREATE TABLE `{prefix}plugin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '插件名称',
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '插件唯一标识',
  `store_id` int(11) DEFAULT '0' COMMENT '商店插件ID',
  `developer_id` int(11) DEFAULT '0' COMMENT '开发者ID',
  `developer_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '开发者名称',
  `audit_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'draft' COMMENT '审核状态(draft/pending/passed/rejected/online/offline)',
  `audit_comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '审核意见',
  `audit_time` datetime DEFAULT NULL COMMENT '审核时间',
  `audit_admin_id` int(11) DEFAULT '0' COMMENT '审核管理员ID',
  `auto_audit_score` decimal(5,2) DEFAULT '0.00' COMMENT '自动审核评分(0-100)',
  `category_id` int(11) DEFAULT '0' COMMENT '分类ID',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '价格',
  `rating` decimal(2,1) DEFAULT '0.0' COMMENT '评分',
  `download_count` int(11) DEFAULT '0' COMMENT '下载次数',
  `rating_count` int(11) DEFAULT '0' COMMENT '评分人数',
  `install_count` int(11) DEFAULT '0' COMMENT '安装次数',
  `screenshots` json DEFAULT NULL COMMENT '插件截图',
  `tags` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '插件标签',
  `plugin_docs` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '插件文档(Markdown)',
  `is_featured` tinyint(4) DEFAULT '0' COMMENT '是否精选',
  `is_recommended` tinyint(4) DEFAULT '0' COMMENT '是否推荐',
  `version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '1.0.0' COMMENT '版本号',
  `author` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '作者',
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '描述',
  `hooks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '注册的Hook列表(JSON)',
  `config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '插件配置(JSON)',
  `config_schema` json DEFAULT NULL COMMENT '插件配置Schema(JSON)',
  `is_enabled` tinyint(4) DEFAULT '0' COMMENT '启用状态: 0禁用 1启用',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  `store_config` json DEFAULT NULL COMMENT 'V2.9.35 PLUG-3 商店配置(商店ID/价格/评分/下载URL/更新检测时间)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件注册表';

INSERT INTO `{prefix}plugin` VALUES (1,'HelloWorld 示例插件','helloworld',0,0,'','draft',NULL,NULL,0,0.00,0,0.00,0.0,0,0,0,NULL,'',NULL,0,0,'1.0.0','AI-CMS','V2.5插件系统示例插件，演示Hook注册和事件触发','{\"content_after_detail\":\"onContentAfterDetail\",\"dashboard_widget\":\"onDashboardWidget\"}',NULL,NULL,0,1777775583,1777776420,NULL);
DROP TABLE IF EXISTS `{prefix}plugin_category`;
CREATE TABLE `{prefix}plugin_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分类名称',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '分类描述',
  `icon` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '图标类名',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0=禁用 1=启用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sort` (`sort`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件分类-V2.9.25';

INSERT INTO `{prefix}plugin_category` VALUES (1,'功能增强','扩展系统核心功能的插件','bi bi-plug',10,1,'2026-06-21 00:46:36','2026-06-21 00:46:36'),(2,'SEO优化','搜索引擎优化相关插件','bi bi-search',20,1,'2026-06-21 00:46:36','2026-06-21 00:46:36'),(3,'社交分享','社交平台和分享功能插件','bi bi-share',30,1,'2026-06-21 00:46:36','2026-06-21 00:46:36'),(4,'数据统计','数据分析和统计报表插件','bi bi-bar-chart',40,1,'2026-06-21 00:46:36','2026-06-21 00:46:36'),(5,'内容管理','内容编辑和排版增强插件','bi bi-file-text',50,1,'2026-06-21 00:46:36','2026-06-21 00:46:36'),(6,'安全防护','安全加固和防护插件','bi bi-shield-check',60,1,'2026-06-21 00:46:36','2026-06-21 00:46:36'),(7,'界面美化','主题和界面美化插件','bi bi-palette',70,1,'2026-06-21 00:46:36','2026-06-21 00:46:36'),(8,'第三方集成','第三方服务和API集成','bi bi-cloud',80,1,'2026-06-21 00:46:36','2026-06-21 00:46:36');
DROP TABLE IF EXISTS `{prefix}plugin_complaint`;
CREATE TABLE `{prefix}plugin_complaint` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}plugin_dependency`;
CREATE TABLE `{prefix}plugin_dependency` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `plugin_id` int(10) unsigned NOT NULL COMMENT '主插件ID',
  `depends_on_plugin_id` int(10) unsigned NOT NULL COMMENT '依赖插件ID',
  `min_version` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '1.0.0' COMMENT '最低版本要求',
  `max_version` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '*' COMMENT '最高版本要求（*=无限制）',
  `is_required` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0=可选 1=必须',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plugin_dep` (`plugin_id`,`depends_on_plugin_id`),
  KEY `idx_plugin_id` (`plugin_id`),
  KEY `idx_depends_on` (`depends_on_plugin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件依赖关系-V2.9.25';

DROP TABLE IF EXISTS `{prefix}plugin_download_log`;
CREATE TABLE `{prefix}plugin_download_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `plugin_id` int(10) unsigned NOT NULL COMMENT '插件包ID',
  `version` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '下载版本',
  `user_id` int(10) unsigned DEFAULT '0' COMMENT '用户ID（0=匿名）',
  `ip` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'UA',
  `source` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'web' COMMENT '来源：web/admin/api',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0=失败 1=成功',
  `error_msg` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '失败原因',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_plugin_id` (`plugin_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件下载日志-V2.9.25';

DROP TABLE IF EXISTS `{prefix}plugin_hook`;
CREATE TABLE `{prefix}plugin_hook` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plugin_id` bigint(20) unsigned NOT NULL COMMENT '插件ID',
  `hook_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '钩子名称',
  `hook_type` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'action' COMMENT '钩子类型: action/filter',
  `callback` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '回调(类名@方法名)',
  `priority` int(11) NOT NULL DEFAULT '100' COMMENT '优先级(越小越先执行)',
  `enabled` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否启用',
  `exec_count` bigint(20) NOT NULL DEFAULT '0' COMMENT '执行次数',
  `exec_time` bigint(20) NOT NULL DEFAULT '0' COMMENT '累计执行耗时(微秒)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_plugin_id` (`plugin_id`),
  KEY `idx_hook_name` (`hook_name`),
  KEY `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='V2.9.35 插件钩子绑定';

DROP TABLE IF EXISTS `{prefix}plugin_install_log`;
CREATE TABLE `{prefix}plugin_install_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `plugin_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '插件标识',
  `action` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作类型(install/update/rollback)',
  `version_from` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '旧版本',
  `version_to` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '新版本',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态:0进行中1成功2失败',
  `log` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '详细日志',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作人',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_plugin` (`plugin_name`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件安装日志表(V2.9.28 P-1)';

DROP TABLE IF EXISTS `{prefix}plugin_order`;
CREATE TABLE `{prefix}plugin_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL,
  `plugin_id` int(11) NOT NULL,
  `plugin_name` varchar(100) NOT NULL,
  `plugin_version` varchar(20) NOT NULL,
  `price` decimal(10,2) DEFAULT '0.00',
  `member_id` int(11) NOT NULL,
  `member_name` varchar(100) NOT NULL,
  `pay_type` varchar(20) DEFAULT '',
  `pay_status` varchar(20) DEFAULT 'pending',
  `pay_time` datetime DEFAULT NULL,
  `license_key` varchar(64) DEFAULT '',
  `license_domain` varchar(200) DEFAULT '',
  `license_expire` datetime DEFAULT NULL,
  `order_data` json DEFAULT NULL,
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_plugin` (`plugin_id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_pay_status` (`pay_status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='插件订单表';

DROP TABLE IF EXISTS `{prefix}plugin_package`;
CREATE TABLE `{prefix}plugin_package` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '插件标识（目录名）',
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '插件名称',
  `version` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1.0.0' COMMENT '当前版本',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '插件描述',
  `author` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '作者',
  `author_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '作者链接',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '图标URL',
  `screenshots` json DEFAULT NULL COMMENT '截图URL数组',
  `tags` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '标签，逗号分隔',
  `category_id` int(10) unsigned DEFAULT '0' COMMENT '分类ID',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '价格（0=免费）',
  `is_free` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否免费',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0=下架 1=上架 2=审核中',
  `download_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '下载次数',
  `install_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '安装次数',
  `rating_avg` decimal(2,1) NOT NULL DEFAULT '5.0' COMMENT '平均评分',
  `rating_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评分人数',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `is_recommended` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否推荐',
  `is_hot` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否热门',
  `signature` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'HMAC-SHA256 签名',
  `signature_method` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'HMAC-SHA256' COMMENT '签名算法',
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '包文件路径',
  `file_size` int(10) unsigned DEFAULT '0' COMMENT '包文件大小（字节）',
  `file_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '包文件 SHA256 哈希',
  `requirements` json DEFAULT NULL COMMENT '依赖要求（PHP版本、扩展等）',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `delete_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_category` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_price` (`price`),
  KEY `idx_sort` (`sort`),
  KEY `idx_recommended` (`is_recommended`),
  KEY `idx_hot` (`is_hot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件包主表-V2.9.25';

DROP TABLE IF EXISTS `{prefix}plugin_payout`;
CREATE TABLE `{prefix}plugin_payout` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `developer_id` int(11) NOT NULL,
  `developer_name` varchar(100) NOT NULL,
  `order_id` int(11) NOT NULL,
  `plugin_id` int(11) NOT NULL,
  `order_amount` decimal(10,2) NOT NULL,
  `platform_ratio` decimal(5,2) DEFAULT '30.00',
  `developer_ratio` decimal(5,2) DEFAULT '70.00',
  `platform_amount` decimal(10,2) NOT NULL,
  `developer_amount` decimal(10,2) NOT NULL,
  `payout_status` varchar(20) DEFAULT 'pending',
  `payout_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_developer` (`developer_id`),
  KEY `idx_plugin` (`plugin_id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_status` (`payout_status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='插件分成结算表';

DROP TABLE IF EXISTS `{prefix}plugin_rating`;
CREATE TABLE `{prefix}plugin_rating` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `plugin_code` varchar(100) NOT NULL DEFAULT '' COMMENT '插件标识',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `rating` tinyint(3) unsigned NOT NULL DEFAULT '5' COMMENT '评分1-5',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '评价内容',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plugin_user` (`plugin_code`,`user_id`),
  KEY `plugin_code` (`plugin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='插件评分评价';

DROP TABLE IF EXISTS `{prefix}plugin_update_check`;
CREATE TABLE `{prefix}plugin_update_check` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `plugin_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '插件标识',
  `current_version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '当前版本',
  `latest_version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '最新版本',
  `has_update` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否有更新',
  `check_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '检查时间',
  `changelog` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '更新日志',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plugin` (`plugin_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件更新检查记录表(V2.9.28 P-6)';

DROP TABLE IF EXISTS `{prefix}plugin_version`;
CREATE TABLE `{prefix}plugin_version` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `plugin_id` int(10) unsigned NOT NULL COMMENT '插件包ID',
  `version` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '版本号',
  `changelog` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '更新日志',
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '版本包路径',
  `file_size` int(10) unsigned DEFAULT '0' COMMENT '包大小',
  `file_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'SHA256 哈希',
  `signature` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '签名',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0=废弃 1=可用',
  `is_current` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否当前版本',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plugin_version` (`plugin_id`,`version`),
  KEY `idx_plugin_id` (`plugin_id`),
  KEY `idx_is_current` (`is_current`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件版本历史-V2.9.25';

DROP TABLE IF EXISTS `{prefix}points_exchange`;
CREATE TABLE `{prefix}points_exchange` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `product_id` int(10) unsigned NOT NULL COMMENT '商品ID',
  `points` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '消耗积分',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态:0待处理 1已发放 2已拒绝',
  `delivery_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '发货信息JSON',
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`,`create_time`),
  KEY `idx_status` (`status`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分兑换记录表';

DROP TABLE IF EXISTS `{prefix}points_log`;
CREATE TABLE `{prefix}points_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `points` int(11) NOT NULL COMMENT '变动积分（正增负减）',
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '类型: signin/comment/like/favorite/purchase/register/admin_adjust',
  `source_id` int(11) DEFAULT '0' COMMENT '来源ID',
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_type_time` (`type`,`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分变动记录表';

INSERT INTO `{prefix}points_log` VALUES (1,1,5,'signin',0,'签到第1天',1778340304),(2,1,5,'signin',0,'签到第1天',1778435313),(3,1,5,'signin',0,'签到第2天',1778548411),(4,1,5,'signin',0,'签到第1天',1779362537);
DROP TABLE IF EXISTS `{prefix}points_product`;
CREATE TABLE `{prefix}points_product` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商品名称',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '商品描述',
  `image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '商品图片',
  `points` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所需积分',
  `stock` int(11) NOT NULL DEFAULT '0' COMMENT '库存(-1表示无限)',
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'virtual' COMMENT '类型:virtual/physical/coupon',
  `config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '类型配置JSON',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `is_enabled` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否上架',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_enabled_sort` (`is_enabled`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分商品表';

DROP TABLE IF EXISTS `{prefix}privacy_consent`;
CREATE TABLE `{prefix}privacy_consent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL COMMENT '用户ID',
  `consent_type` varchar(30) NOT NULL COMMENT '同意类型(cookie/privacy_policy/terms/data_processing/marketing)',
  `consent_version` varchar(20) NOT NULL COMMENT '同意版本',
  `consent_status` varchar(20) NOT NULL COMMENT '同意状态(granted/revoked/expired)',
  `cookie_preferences` json DEFAULT NULL COMMENT 'Cookie偏好(JSON: 分类/开关)',
  `ip_address` varchar(45) DEFAULT '' COMMENT '同意时的IP地址',
  `user_agent` text COMMENT '同意时的User-Agent',
  `consent_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '同意时间',
  `revoke_time` datetime DEFAULT NULL COMMENT '撤销时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_type` (`consent_type`),
  KEY `idx_status` (`consent_status`),
  KEY `idx_consent_time` (`consent_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='隐私同意记录表';

DROP TABLE IF EXISTS `{prefix}privacy_policy`;
CREATE TABLE `{prefix}privacy_policy` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}privacy_request`;
CREATE TABLE `{prefix}privacy_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL COMMENT '用户ID',
  `request_type` varchar(30) NOT NULL COMMENT '请求类型(access/rectify/erase/restrict/portability/object)',
  `request_data` json DEFAULT NULL COMMENT '请求数据(JSON: 请求内容/范围)',
  `request_status` varchar(20) DEFAULT 'pending' COMMENT '状态(pending/verifying/processing/completed/rejected)',
  `verification_method` varchar(30) DEFAULT 'email' COMMENT '验证方式(email/sms/other)',
  `verification_data` text COMMENT '验证数据',
  `process_result` json DEFAULT NULL COMMENT '处理结果(JSON)',
  `completed_time` datetime DEFAULT NULL COMMENT '完成时间',
  `processor_id` int(11) DEFAULT '0' COMMENT '处理人ID(0=自动处理)',
  `reject_reason` text COMMENT '拒绝原因',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_type` (`request_type`),
  KEY `idx_status` (`request_status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='隐私请求记录表';

DROP TABLE IF EXISTS `{prefix}private_message`;
CREATE TABLE `{prefix}private_message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}publish_log`;
CREATE TABLE `{prefix}publish_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(11) NOT NULL COMMENT '内容ID',
  `platform_id` int(11) NOT NULL COMMENT '平台ID',
  `platform` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '发布平台: weixin/toutiao/zhihu',
  `action` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'publish' COMMENT '操作: publish/update/delete/retry',
  `platform_content_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '外部平台内容ID',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态: 0待发布 1已发布 2失败',
  `error_msg` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '错误信息',
  `retry_count` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '重试次数',
  `publish_time` int(10) unsigned DEFAULT '0' COMMENT '发布时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_platform` (`platform_id`),
  KEY `platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='发布记录表';

DROP TABLE IF EXISTS `{prefix}publish_platform`;
CREATE TABLE `{prefix}publish_platform` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '平台标识: wechat_mp/toutiao',
  `display_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '显示名称',
  `config_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '平台配置(JSON: appid/secret/token等)',
  `is_enabled` tinyint(4) DEFAULT '0' COMMENT '启用状态',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='发布平台配置表';

INSERT INTO `{prefix}publish_platform` VALUES (1,'wechat_mp','微信公众号','{\"appid\":\"\",\"secret\":\"\"}',0,1777774068,1777774068),(2,'toutiao','头条号','{\"client_key\":\"\",\"client_secret\":\"\"}',0,1777774069,1777774069);
DROP TABLE IF EXISTS `{prefix}push_channel`;
CREATE TABLE `{prefix}push_channel` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '通道名称',
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'webhook' COMMENT '通道类型: webhook|wechat_push|broadcast',
  `config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '配置信息JSON: {url, headers, method, format, token}',
  `trigger_mode` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '触发方式: 0=手动, 1=自动(发布时触发)',
  `push_scope` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '推送范围: 空=全部, 分类ID逗号分隔',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态: 0=禁用, 1=启用',
  `last_push_at` datetime DEFAULT NULL COMMENT '最后推送时间',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `platform_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '平台类型(wechat/toutiao/zhihu/weibo)',
  `platform_account_id` int(11) DEFAULT '0' COMMENT '平台账号ID',
  `third_party_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '第三方平台文章URL',
  `reads` int(11) DEFAULT '0' COMMENT '阅读量',
  `likes` int(11) DEFAULT '0' COMMENT '点赞数',
  `comments` int(11) DEFAULT '0' COMMENT '评论数',
  `shares` int(11) DEFAULT '0' COMMENT '转发数',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推送通道配置';

DROP TABLE IF EXISTS `{prefix}push_log`;
CREATE TABLE `{prefix}push_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `channel_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联推送通道ID',
  `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联内容ID',
  `request_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '请求URL',
  `request_body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '请求体JSON',
  `response_code` int(11) NOT NULL DEFAULT '0' COMMENT '响应状态码',
  `response_body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '响应内容摘要',
  `duration_ms` int(11) NOT NULL DEFAULT '0' COMMENT '请求耗时(毫秒)',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态: 0=待发送, 1=成功, 2=失败',
  `error_msg` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '失败原因',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `retried_at` datetime DEFAULT NULL COMMENT '重试时间',
  PRIMARY KEY (`id`),
  KEY `idx_channel_id` (`channel_id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推送日志';

DROP TABLE IF EXISTS `{prefix}push_retry`;
CREATE TABLE `{prefix}push_retry` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `push_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '推送内容ID',
  `channel` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '通道标识',
  `reason` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '入队原因',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0=待重试 1=成功 -1=失败',
  `retry_count` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '已重试次数',
  `error_msg` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '错误信息',
  `next_retry_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '下次重试时间戳',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_status_next` (`status`,`next_retry_at`),
  KEY `idx_push_id` (`push_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推送重试队列';

DROP TABLE IF EXISTS `{prefix}qa_log`;
CREATE TABLE `{prefix}qa_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) NOT NULL COMMENT '对话会话ID',
  `member_id` int(11) DEFAULT '0' COMMENT '提问用户ID(0为游客)',
  `question` text NOT NULL COMMENT '用户问题',
  `answer` text COMMENT 'AI回答',
  `answer_source` json DEFAULT NULL COMMENT '回答来源(JSON: 引用的内容ID列表)',
  `confidence` decimal(3,2) DEFAULT '0.00' COMMENT '回答置信度(0-1)',
  `is_helpful` tinyint(4) DEFAULT NULL COMMENT '是否有用(Null未反馈/1有用/0无用)',
  `is_sensitive` tinyint(4) DEFAULT '0' COMMENT '是否敏感问题:1是0否',
  `is_answered` tinyint(4) DEFAULT '1' COMMENT '是否已回答:1是0否',
  `response_time` int(11) DEFAULT '0' COMMENT '响应时间(毫秒)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_answered` (`is_answered`),
  KEY `idx_helpful` (`is_helpful`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='智能问答日志表';

DROP TABLE IF EXISTS `{prefix}quality_monitor_config`;
CREATE TABLE `{prefix}quality_monitor_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `threshold` decimal(10,2) DEFAULT NULL,
  `enabled` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `{prefix}queue_failed_jobs`;
CREATE TABLE `{prefix}queue_failed_jobs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}queue_job`;
CREATE TABLE `{prefix}queue_job` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}queue_jobs`;
CREATE TABLE `{prefix}queue_jobs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}queue_worker`;
CREATE TABLE `{prefix}queue_worker` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}rating_reply`;
CREATE TABLE `{prefix}rating_reply` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rating_id` int(10) unsigned NOT NULL COMMENT '关联评价ID',
  `user_id` int(10) unsigned DEFAULT '0' COMMENT '回复用户(管理员ID)',
  `member_id` int(10) unsigned DEFAULT '0' COMMENT '回复会员ID',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '回复内容',
  `create_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_rating` (`rating_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='评价回复记录';

DROP TABLE IF EXISTS `{prefix}recommend_log`;
CREATE TABLE `{prefix}recommend_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_id` int(11) NOT NULL COMMENT '内容ID',
  `member_id` int(11) DEFAULT '0' COMMENT '用户ID(0为游客)',
  `session_id` varchar(64) DEFAULT '' COMMENT '会话ID(游客用)',
  `event_type` varchar(20) NOT NULL COMMENT '事件类型(view/favorite/like/comment/share/search/click/duration)',
  `event_data` json DEFAULT NULL COMMENT '事件附加数据(JSON)',
  `event_time` datetime NOT NULL COMMENT '事件时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_event_time` (`event_time`),
  KEY `idx_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='推荐行为日志表';

DROP TABLE IF EXISTS `{prefix}report_definition`;
CREATE TABLE `{prefix}report_definition` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_name` varchar(100) NOT NULL COMMENT '报表名称',
  `report_type` varchar(30) NOT NULL COMMENT '报表类型(content/user/template/distribute/pay)',
  `data_source` varchar(30) NOT NULL COMMENT '数据源(content/member/template_store/push_channel/order)',
  `metrics` json NOT NULL COMMENT '核心指标(JSON数组)',
  `dimensions` json NOT NULL COMMENT '维度(JSON数组)',
  `filters` json DEFAULT NULL COMMENT '筛选条件(JSON)',
  `group_by` varchar(50) DEFAULT '' COMMENT '分组方式',
  `chart_type` varchar(20) DEFAULT 'bar' COMMENT '图表类型(bar/line/pie/radar/heatmap)',
  `date_range` varchar(20) DEFAULT 'last_30_days' COMMENT '默认时间范围',
  `is_system` tinyint(4) DEFAULT '0' COMMENT '是否系统预置',
  `creator_id` int(11) DEFAULT '0' COMMENT '创建人',
  `create_time` int(10) unsigned DEFAULT '0',
  `update_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`report_type`),
  KEY `idx_creator` (`creator_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='报表定义表';

INSERT INTO `{prefix}report_definition` VALUES (1,'内容发布日报','content','content','[\"publish_count\", \"views\", \"interactions\"]','[\"date\"]',NULL,'','bar','last_7_days',1,0,1783830747,1783830747),(2,'用户增长周报','user','member','[\"new_users\", \"active_users\", \"retention_rate\"]','[\"date\"]',NULL,'','line','last_30_days',1,0,1783830770,1783830770),(3,'内容质量月报','content','content','[\"avg_quality_score\", \"repair_count\", \"tag_accuracy\"]','[\"date\"]',NULL,'','line','last_30_days',1,0,1783830770,1783830770),(4,'模板安装排行','template','template_store','[\"install_count\", \"avg_rating\"]','[\"template\"]',NULL,'','bar','last_30_days',1,0,1783830770,1783830770),(5,'付费收入统计','pay','order','[\"revenue\", \"paid_users\", \"arpu\"]','[\"date\"]',NULL,'','line','last_30_days',1,0,1783830770,1783830770),(6,'分发效果报表','distribute','push_channel','[\"distribute_count\", \"reads\", \"interactions\"]','[\"platform\"]',NULL,'','bar','last_30_days',1,0,1783830770,1783830770),(7,'会员等级分布','user','member','[\"level_count\", \"level_ratio\"]','[\"level\"]',NULL,'','pie','all_time',1,0,1783830770,1783830770),(8,'内容模型使用率','content','content','[\"model_count\", \"model_views\"]','[\"content_model\"]',NULL,'','pie','last_30_days',1,0,1783830770,1783830770),(9,'多语言翻译覆盖率','content','content','[\"translated_count\", \"coverage_rate\"]','[\"lang\"]',NULL,'','bar','all_time',1,0,1783830770,1783830770),(10,'系统健康周报','content','content','[\"cpu_avg\", \"memory_avg\", \"error_rate\"]','[\"date\"]',NULL,'','line','last_7_days',1,0,1783830770,1783830770);
DROP TABLE IF EXISTS `{prefix}review`;
CREATE TABLE `{prefix}review` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `content_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '审核人ID',
  `action` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作:approve通过/reject驳回',
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '审核意见',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审核记录表';

DROP TABLE IF EXISTS `{prefix}review_log`;
CREATE TABLE `{prefix}review_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int(10) unsigned NOT NULL COMMENT '审核记录ID',
  `step` tinyint(4) NOT NULL DEFAULT '1' COMMENT '步骤序号',
  `reviewer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核人ID',
  `action` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '动作:pass/reject/withdraw/transfer',
  `comment` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '审核意见',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_record` (`record_id`,`step`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审核日志表';

DROP TABLE IF EXISTS `{prefix}review_record`;
CREATE TABLE `{prefix}review_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_id` int(10) unsigned NOT NULL COMMENT '流程ID',
  `target_id` int(10) unsigned NOT NULL COMMENT '目标对象ID(如内容ID)',
  `target_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'content' COMMENT '目标类型',
  `current_step` tinyint(4) NOT NULL DEFAULT '1' COMMENT '当前步骤序号',
  `total_steps` tinyint(4) NOT NULL DEFAULT '1' COMMENT '总步骤数',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态:0待审核 1审核中 2已通过 3已拒绝 4已撤回',
  `submitter_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '提交者ID',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_target` (`target_id`,`target_type`,`workflow_id`),
  KEY `idx_status` (`status`,`current_step`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审核记录表';

DROP TABLE IF EXISTS `{prefix}review_workflow`;
CREATE TABLE `{prefix}review_workflow` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '流程名称',
  `module` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'content' COMMENT '适用模块:content/member/comment',
  `steps` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '流程步骤JSON [{step:1,role_id:0,name:"一审"},...]',
  `is_default` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否默认流程:0否1是',
  `is_enabled` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否启用',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_module` (`module`,`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审核工作流定义表';

DROP TABLE IF EXISTS `{prefix}search_history`;
CREATE TABLE `{prefix}search_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}search_keyword`;
CREATE TABLE `{prefix}search_keyword` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `keyword` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '关键词',
  `count` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '搜索次数',
  `last_search_time` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_keyword` (`keyword`),
  KEY `idx_count` (`count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='搜索关键词统计';

DROP TABLE IF EXISTS `{prefix}security_alert`;
CREATE TABLE `{prefix}security_alert` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}security_audit`;
CREATE TABLE `{prefix}security_audit` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `theme_slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板标识',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件路径',
  `issue_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '问题类型',
  `severity` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '严重程度:critical/high/medium/low',
  `message` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '问题描述',
  `line` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '行号',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态:0=未处理,1=已修复,2=已忽略',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_theme` (`theme_slug`),
  KEY `idx_severity` (`severity`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='安全审计记录表 - V2.9.31';

DROP TABLE IF EXISTS `{prefix}security_log`;
CREATE TABLE `{prefix}security_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '事件类型: xss/csrf/sqli/file_upload/auth_deny/login_fail/login_success/permission_denied/sensitive_access',
  `severity` tinyint(4) NOT NULL DEFAULT '1' COMMENT '严重级别: 1低 2中 3高 4严重',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联用户ID(0=未登录)',
  `username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP地址(IPv4/IPv6)',
  `user_agent` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'User-Agent',
  `url` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '请求URL',
  `method` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GET' COMMENT '请求方法',
  `payload` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '攻击载荷/请求数据摘要',
  `description` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '事件描述',
  `extra` json DEFAULT NULL COMMENT '扩展数据(前后数据diff/规则命中详情等)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at_date` date GENERATED ALWAYS AS (cast(`created_at` as date)) STORED,
  PRIMARY KEY (`id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_ip` (`ip`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_created_at_date` (`created_at_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='V2.9.35 安全事件日志';

DROP TABLE IF EXISTS `{prefix}seo_deadlinks`;
CREATE TABLE `{prefix}seo_deadlinks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '死链URL',
  `status_code` int(11) NOT NULL DEFAULT '0' COMMENT 'HTTP状态码',
  `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '来源页面',
  `check_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '检测时间',
  `is_fixed` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否已修复:0否/1是',
  PRIMARY KEY (`id`),
  KEY `idx_is_fixed` (`is_fixed`),
  KEY `idx_check_time` (`check_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEO死链检测表';

DROP TABLE IF EXISTS `{prefix}seo_diagnosis`;
CREATE TABLE `{prefix}seo_diagnosis` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `score` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'SEO评分(0-100)',
  `issues` json DEFAULT NULL COMMENT '问题列表JSON',
  `stats` json DEFAULT NULL COMMENT '统计信息JSON',
  `suggestions` json DEFAULT NULL COMMENT '修复建议JSON',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_score` (`score`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEO诊断记录表 - V2.9.31';

DROP TABLE IF EXISTS `{prefix}seo_keyword`;
CREATE TABLE `{prefix}seo_keyword` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `keyword` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '关键词',
  `group_id` int(11) DEFAULT '0' COMMENT '分组ID',
  `search_volume` int(11) DEFAULT '0' COMMENT '搜索量',
  `difficulty` tinyint(4) DEFAULT '50' COMMENT '难度指数（0-100）',
  `is_sensitive` tinyint(4) DEFAULT '0' COMMENT '是否敏感词',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态: 1启用 0禁用',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_keyword` (`keyword`),
  KEY `idx_group` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEO关键词表';

DROP TABLE IF EXISTS `{prefix}seo_keyword_group`;
CREATE TABLE `{prefix}seo_keyword_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分组名称',
  `sort` int(11) DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEO关键词分组表';

DROP TABLE IF EXISTS `{prefix}share_click`;
CREATE TABLE `{prefix}share_click` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联内容ID',
  `source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分享来源: wechat|weibo|qq|twitter|copy',
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '访客IP',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '点击时间',
  PRIMARY KEY (`id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_source` (`source`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分享点击日志';

DROP TABLE IF EXISTS `{prefix}share_log`;
CREATE TABLE `{prefix}share_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `channel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other' COMMENT '分享渠道: wechat/weibo/qq/copy/other',
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID(0=游客)',
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户IP',
  `referer` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '来源页',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分享时间',
  PRIMARY KEY (`id`),
  KEY `idx_content_channel` (`content_id`,`channel`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_channel` (`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分享日志表-V2.9.9';

DROP TABLE IF EXISTS `{prefix}signin_log`;
CREATE TABLE `{prefix}signin_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `signin_date` date NOT NULL,
  `points` int(11) DEFAULT '0' COMMENT '签到获得积分',
  `consecutive_days` int(11) DEFAULT '1' COMMENT '连续签到天数',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_date` (`member_id`,`signin_date`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='签到记录表';

INSERT INTO `{prefix}signin_log` VALUES (19,1,'2026-05-09',5,1,1778340304),(20,1,'2026-05-11',5,1,1778435313),(21,1,'2026-05-12',5,2,1778548411),(22,1,'2026-05-21',5,1,1779362537);
DROP TABLE IF EXISTS `{prefix}sms_log`;
CREATE TABLE `{prefix}sms_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}sms_template`;
CREATE TABLE `{prefix}sms_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}sse_client`;
CREATE TABLE `{prefix}sse_client` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `client_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户端唯一标识(UUID)',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID(0=游客)',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户端IP',
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'User-Agent',
  `channels` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system' COMMENT '订阅通道(逗号分隔)',
  `last_event_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '最后接收的消息ID',
  `last_active` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后活跃时间',
  `connect_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '连接建立时间',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态(1在线/0离线)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_client_id` (`client_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_status` (`status`),
  KEY `idx_last_active` (`last_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SSE客户端连接';

DROP TABLE IF EXISTS `{prefix}sse_message_queue`;
CREATE TABLE `{prefix}sse_message_queue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID(自增,用作Last-Event-Id)',
  `channel` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system' COMMENT '通道(audit/comment/system/notification)',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '目标用户ID(0=广播)',
  `event_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'message' COMMENT '事件类型',
  `payload` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '消息内容(JSON)',
  `is_delivered` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否已投递(1是/0否)',
  `delivered_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '投递时间',
  `expires_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '过期时间(0=不过期)',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_channel_user` (`channel`,`user_id`),
  KEY `idx_is_delivered` (`is_delivered`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SSE消息队列(DB持久化)';

DROP TABLE IF EXISTS `{prefix}sse_notification`;
CREATE TABLE `{prefix}sse_notification` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}subscriber`;
CREATE TABLE `{prefix}subscriber` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱地址',
  `nickname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '昵称(可选)',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态: 0=待确认, 1=已确认, 2=已退订',
  `confirm_token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '确认token(唯一)',
  `source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '订阅来源: detail_page|footer|admin_add|register',
  `tag` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分组标签',
  `subscribed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '订阅时间',
  `confirmed_at` datetime DEFAULT NULL COMMENT '确认时间',
  `unsubscribed_at` datetime DEFAULT NULL COMMENT '退订时间',
  `invalid_at` datetime DEFAULT NULL COMMENT '标记为无效的时间',
  `fail_count` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '连续发送失败次数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  UNIQUE KEY `uk_confirm_token` (`confirm_token`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件订阅者';

DROP TABLE IF EXISTS `{prefix}subscription`;
CREATE TABLE `{prefix}subscription` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}system_config`;
CREATE TABLE `{prefix}system_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `config_value` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}tag`;
CREATE TABLE `{prefix}tag` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标签名称',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='标签表';

INSERT INTO `{prefix}tag` VALUES (1,'123',1,1776952387);
DROP TABLE IF EXISTS `{prefix}task`;
CREATE TABLE `{prefix}task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '任务标题',
  `description` text COMMENT '任务描述',
  `type` varchar(50) DEFAULT 'general' COMMENT '任务类型',
  `priority` tinyint(4) DEFAULT '2' COMMENT '优先级:1低/2中/3高/4紧急',
  `status` varchar(20) DEFAULT 'pending' COMMENT '状态:pending/in_progress/pending_review/completed/cancelled/overdue',
  `assignee_id` int(11) DEFAULT '0' COMMENT '主负责人ID',
  `collaborators` json DEFAULT NULL COMMENT '协作者ID列表(JSON数组)',
  `reviewer_id` int(11) DEFAULT '0' COMMENT '审核人ID',
  `notifiers` json DEFAULT NULL COMMENT '通知人ID列表(JSON数组)',
  `progress` int(11) DEFAULT '0' COMMENT '进度(0-100)',
  `milestones` json DEFAULT NULL COMMENT '里程碑列表(JSON数组)',
  `progress_note` varchar(500) DEFAULT '' COMMENT '最近进度备注',
  `deadline` datetime DEFAULT NULL COMMENT '截止时间',
  `start_time` datetime DEFAULT NULL COMMENT '开始时间',
  `complete_time` datetime DEFAULT NULL COMMENT '完成时间',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_assignee` (`assignee_id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_priority` (`priority`),
  KEY `idx_deadline` (`deadline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='任务表';

DROP TABLE IF EXISTS `{prefix}task_assign_log`;
CREATE TABLE `{prefix}task_assign_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL DEFAULT '0' COMMENT '任务ID',
  `from_user_id` int(11) DEFAULT '0' COMMENT '原负责人ID',
  `to_user_id` int(11) DEFAULT '0' COMMENT '新负责人ID',
  `action` varchar(20) DEFAULT 'assign' COMMENT '动作:assign/reassign/batch_assign/auto_assign',
  `reason` varchar(500) DEFAULT '' COMMENT '原因',
  `operator_id` int(11) DEFAULT '0' COMMENT '操作人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_task` (`task_id`),
  KEY `idx_to_user` (`to_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='任务分配历史表';

DROP TABLE IF EXISTS `{prefix}task_notify_template`;
CREATE TABLE `{prefix}task_notify_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '模板名称',
  `content` text COMMENT '模板内容(支持{task_title},{deadline},{assignee}等变量)',
  `type` varchar(30) DEFAULT 'reminder' COMMENT '类型:reminder/overdue/stalled/custom',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态:1启用0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='任务通知模板表';

DROP TABLE IF EXISTS `{prefix}task_progress_log`;
CREATE TABLE `{prefix}task_progress_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL DEFAULT '0' COMMENT '任务ID',
  `progress` int(11) DEFAULT '0' COMMENT '进度值',
  `note` varchar(500) DEFAULT '' COMMENT '备注',
  `operator_id` int(11) DEFAULT '0' COMMENT '操作人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_task` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='任务进度历史表';

DROP TABLE IF EXISTS `{prefix}task_template`;
CREATE TABLE `{prefix}task_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '模板名称',
  `category` varchar(50) DEFAULT '' COMMENT '模板分类',
  `description` varchar(500) DEFAULT '' COMMENT '模板描述',
  `task_data` json DEFAULT NULL COMMENT '任务数据(JSON)',
  `subtasks` json DEFAULT NULL COMMENT '子任务列表(JSON数组)',
  `milestones` json DEFAULT NULL COMMENT '里程碑列表(JSON数组)',
  `assign_rules` json DEFAULT NULL COMMENT '分配规则(JSON)',
  `audit_flow` json DEFAULT NULL COMMENT '审核流程(JSON)',
  `variables` json DEFAULT NULL COMMENT '模板变量定义(JSON)',
  `attachments` json DEFAULT NULL COMMENT '附件(JSON数组)',
  `usage_count` int(11) DEFAULT '0' COMMENT '使用次数',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态:1启用0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort_order`),
  KEY `idx_usage` (`usage_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='任务模板表';

DROP TABLE IF EXISTS `{prefix}template_audit_config`;
CREATE TABLE `{prefix}template_audit_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '模板ID(0=全局默认)',
  `audit_level` tinyint(4) NOT NULL DEFAULT '2' COMMENT '审核层级:1单级2两级3三级',
  `first_reviewer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '初审人ID',
  `final_reviewer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '终审人ID',
  `need_file_diff` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否需要版本对比',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审核配置表(V2.9.28 M-5)';

DROP TABLE IF EXISTS `{prefix}template_audit_log`;
CREATE TABLE `{prefix}template_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL DEFAULT '0',
  `auditor_id` int(11) NOT NULL DEFAULT '0',
  `auditor_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `action` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reason_id` int(11) NOT NULL DEFAULT '0',
  `prev_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `new_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_auditor` (`auditor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板审核日志';

DROP TABLE IF EXISTS `{prefix}template_audit_report`;
CREATE TABLE `{prefix}template_audit_report` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(10) unsigned NOT NULL DEFAULT '0',
  `code_quality_score` decimal(3,1) NOT NULL DEFAULT '0.0',
  `compatibility_score` decimal(3,1) NOT NULL DEFAULT '0.0',
  `responsive_score` decimal(3,1) NOT NULL DEFAULT '0.0',
  `security_score` decimal(3,1) NOT NULL DEFAULT '0.0',
  `total_score` decimal(3,1) NOT NULL DEFAULT '0.0',
  `issues` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '问题详情(JSON)',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0待审1通过2驳回',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板自动审核报告';

DROP TABLE IF EXISTS `{prefix}template_backup`;
CREATE TABLE `{prefix}template_backup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '网站主用户ID',
  `slug` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板标识',
  `backup_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备份名称',
  `backup_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备份文件路径',
  `config_snapshot` json DEFAULT NULL COMMENT '配置快照JSON',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual' COMMENT '备份类型:manual手动/auto自动',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_member_slug` (`member_id`,`slug`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板备份记录表(V2.9.12)';

DROP TABLE IF EXISTS `{prefix}template_banner`;
CREATE TABLE `{prefix}template_banner` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Banner标题',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Banner图片URL',
  `target_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '跳转类型:1外部URL/2模板详情/3分类页面',
  `target_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '跳转目标ID',
  `target_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '外部跳转URL',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序号',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始展示时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结束展示时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`,`sort`),
  KEY `idx_time` (`start_time`,`end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板商店Banner表(V2.9.24)';

DROP TABLE IF EXISTS `{prefix}template_batch_log`;
CREATE TABLE `{prefix}template_batch_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operator_id` int(11) NOT NULL COMMENT '操作人ID',
  `operator_name` varchar(100) NOT NULL DEFAULT '' COMMENT '操作人名称',
  `action` varchar(50) NOT NULL COMMENT '操作类型',
  `target_ids` text COMMENT '目标模板ID列表(JSON)',
  `params` text COMMENT '操作参数(JSON)',
  `result` text COMMENT '操作结果(JSON)',
  `preview` text COMMENT '操作前预览数据(JSON)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_operator_id` (`operator_id`),
  KEY `idx_action` (`action`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='模板批量操作日志表';

DROP TABLE IF EXISTS `{prefix}template_cache_log`;
CREATE TABLE `{prefix}template_cache_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `template_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板路径',
  `template_md5` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'MD5校验值',
  `action` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'refresh' COMMENT '操作类型: refresh/clear/rebuild',
  `file_size` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小(字节)',
  `operator` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system' COMMENT '操作者',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作时间',
  PRIMARY KEY (`id`),
  KEY `idx_template_path` (`template_path`(191)),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板缓存变更日志表(V2.9.23 A-4)';

DROP TABLE IF EXISTS `{prefix}template_cart`;
CREATE TABLE `{prefix}template_cart` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL DEFAULT '0',
  `template_id` int(10) unsigned NOT NULL DEFAULT '0',
  `quantity` int(10) unsigned NOT NULL DEFAULT '1',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_template` (`member_id`,`template_id`),
  KEY `idx_member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板购物车';

DROP TABLE IF EXISTS `{prefix}template_category`;
CREATE TABLE `{prefix}template_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父级ID(0=顶级)',
  `level` tinyint(4) DEFAULT '1' COMMENT '层级:1一级2二级',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类维度(content_model/industry/style)',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类标识(unique)',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '描述',
  `icon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图标',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态(1启用/0禁用)',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  `dimension` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'industry',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status_sort` (`status`,`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板分类';

INSERT INTO `{prefix}template_category` VALUES (1,0,1,0,'content_model','通用型','cat_model_general','适用于多种内容类型的通用模板','bi bi-grid',10,1,1780903524,1780903524,'industry'),(2,0,1,0,'content_model','文章型','cat_model_article','专注于文章、博客类内容展示','bi bi-file-text',20,1,1780903524,1780903524,'industry'),(3,0,1,0,'content_model','产品型','cat_model_product','适用于产品展示、电商类站点','bi bi-box',30,1,1780903524,1780903524,'industry'),(4,0,1,0,'content_model','图片型','cat_model_gallery','专注于图片画廊、作品集展示','bi bi-images',40,1,1780903524,1780903524,'industry'),(5,0,1,0,'content_model','下载型','cat_model_download','适用于软件下载、资源分享类站点','bi bi-cloud-download',50,1,1780903524,1780903524,'industry'),(6,0,1,0,'content_model','视频型','cat_model_video','专注于视频内容展示与播放','bi bi-play-btn',60,1,1780903524,1780903524,'industry'),(7,0,1,0,'industry','企业官网','cat_ind_enterprise','适用于企业官方网站','bi bi-building',10,1,1780903524,1780903524,'industry'),(8,0,1,0,'industry','电商','cat_ind_ecommerce','适用于在线商城、电商平台','bi bi-cart',20,1,1780903524,1780903524,'industry'),(9,0,1,0,'industry','科技','cat_ind_tech','适用于科技公司、IT服务类站点','bi bi-cpu',30,1,1780903524,1780903524,'industry'),(10,0,1,0,'industry','教育','cat_ind_edu','适用于培训机构、学校、在线课程','bi bi-mortarboard',40,1,1780903524,1780903524,'industry'),(11,0,1,0,'industry','餐饮','cat_ind_catering','适用于餐厅、酒店、美食类站点','bi bi-cup-hot',50,1,1780903524,1780903524,'industry'),(12,0,1,0,'industry','医疗','cat_ind_medical','适用于医院、诊所、健康类站点','bi bi-heart-pulse',60,1,1780903524,1780903524,'industry'),(13,0,1,0,'industry','金融','cat_ind_finance','适用于银行、保险、投资类站点','bi bi-bank',70,1,1780903524,1780903524,'industry'),(14,0,1,0,'industry','个人博客','cat_ind_blog','适用于个人博客、自媒体站点','bi bi-person',80,1,1780903524,1780903524,'industry'),(15,0,1,0,'style','简约现代','cat_style_minimal','简洁大气的现代设计风格','bi bi-layout-text-window',10,1,1780903524,1780903524,'industry'),(16,0,1,0,'style','科技时尚','cat_style_tech','充满科技感的时尚设计风格','bi bi-rocket',20,1,1780903524,1780903524,'industry'),(17,0,1,0,'style','自然温暖','cat_style_nature','自然温馨、亲和力强设计风格','bi bi-tree',30,1,1780903524,1780903524,'industry'),(18,0,1,0,'style','活泼创意','cat_style_creative','色彩丰富、富有创意的设计风格','bi bi-palette',40,1,1780903524,1780903524,'industry');
DROP TABLE IF EXISTS `{prefix}template_category_map`;
CREATE TABLE `{prefix}template_category_map` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `template_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '模板ID',
  `category_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否主分类（1=主分类，0=次分类）',
  `confidence` tinyint(3) unsigned NOT NULL DEFAULT '100' COMMENT '匹配置信度（0-100）',
  `created_by` tinyint(1) NOT NULL DEFAULT '1' COMMENT '创建来源（1=人工，2=AI自动）',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tmpl_cat` (`template_id`,`category_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_template_primary` (`template_id`,`is_primary`),
  KEY `idx_category_confidence` (`category_id`,`confidence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板-分类映射';

DROP TABLE IF EXISTS `{prefix}template_category_v2`;
CREATE TABLE `{prefix}template_category_v2` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dimension` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'industry/style/function',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `sort` int(10) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `template_count` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_dimension` (`dimension`),
  KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板分类v2(三维分类)';

DROP TABLE IF EXISTS `{prefix}template_color_variant`;
CREATE TABLE `{prefix}template_color_variant` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '商店模板ID',
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配色方案名称',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '描述',
  `colors` json DEFAULT NULL COMMENT '色值JSON对象',
  `css_variables` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'CSS变量文本',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否默认:0否/1是',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序号',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_store_sort` (`store_id`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板配色变体表(V2.9.12)';

DROP TABLE IF EXISTS `{prefix}template_component`;
CREATE TABLE `{prefix}template_component` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'navbar/footer/carousel/card/button/form/list/icon/divider/heading',
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `preview_image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `component_data` json NOT NULL COMMENT 'HTML/CSS/JS配置',
  `config_schema` json DEFAULT NULL COMMENT '可配置参数定义',
  `version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'v1.0.0',
  `author_id` int(11) DEFAULT '0',
  `status` tinyint(4) DEFAULT '1',
  `is_system` tinyint(4) DEFAULT '0',
  `create_time` int(10) unsigned DEFAULT '0',
  `update_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_author` (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板组件库表';

DROP TABLE IF EXISTS `{prefix}template_coupon`;
CREATE TABLE `{prefix}template_coupon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `discount_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `min_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `max_uses` int(11) NOT NULL DEFAULT '0',
  `used_count` int(11) NOT NULL DEFAULT '0',
  `template_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板优惠码';

DROP TABLE IF EXISTS `{prefix}template_custom_config`;
CREATE TABLE `{prefix}template_custom_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '网站主用户ID',
  `slug` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板标识',
  `config_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置键',
  `config_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '配置值',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  `whitelist_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT '白名单状态',
  `whitelist_audit_time` int(10) unsigned DEFAULT '0' COMMENT '白名单审批时间',
  `whitelist_auditor` int(11) DEFAULT '0' COMMENT '审批人',
  `components` json DEFAULT NULL COMMENT '使用的组件列表',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_slug_key` (`member_id`,`slug`,`config_key`),
  KEY `idx_member_slug` (`member_id`,`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板自定义配置表(V2.9.12)';

DROP TABLE IF EXISTS `{prefix}template_daily_stats`;
CREATE TABLE `{prefix}template_daily_stats` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '模板ID（0=全站汇总）',
  `stats_date` date NOT NULL COMMENT '统计日期',
  `view_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '浏览次数',
  `unique_visitors` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '独立访客数',
  `install_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '安装次数',
  `uninstall_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '卸载次数',
  `activate_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '激活次数',
  `dau` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'DAU',
  `mau` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'MAU（月活，仅每月1日计算）',
  `revenue` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '当日收入',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_date` (`template_id`,`stats_date`),
  KEY `idx_date` (`stats_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板日统计汇总表(V2.9.25 N-2)';

DROP TABLE IF EXISTS `{prefix}template_dev_upload`;
CREATE TABLE `{prefix}template_dev_upload` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开发者用户ID',
  `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联商店模板ID(审核通过后)',
  `slug` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板标识',
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板名称',
  `version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1.0.0' COMMENT '版本号',
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '上传文件路径',
  `screenshots` json DEFAULT NULL COMMENT '预览截图JSON数组',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '模板描述',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0待审核/1通过/2拒绝/3需修改',
  `audit_comment` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '审核意见',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_status` (`status`,`create_time`),
  KEY `idx_store` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='开发者模板上传审核表(V2.9.12)';

DROP TABLE IF EXISTS `{prefix}template_developer_app`;
CREATE TABLE `{prefix}template_developer_app` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `app_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `app_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `app_secret` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(4) DEFAULT '1' COMMENT '1启用0禁用',
  `last_used_time` int(10) unsigned DEFAULT '0',
  `create_time` int(10) unsigned DEFAULT '0',
  `update_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_key` (`app_key`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板开发者应用表';

DROP TABLE IF EXISTS `{prefix}template_install`;
CREATE TABLE `{prefix}template_install` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '商店模板ID',
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '网站主用户ID',
  `slug` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板标识',
  `theme_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板名称',
  `is_active` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否当前激活:0否/1是',
  `install_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '安装路径',
  `quality_on_install` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'install quality score',
  `config` json DEFAULT NULL COMMENT '配置数据JSON',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_slug` (`member_id`,`slug`),
  KEY `idx_member_active` (`member_id`,`is_active`),
  KEY `idx_store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板安装记录表(V2.9.12)';

DROP TABLE IF EXISTS `{prefix}template_install_log`;
CREATE TABLE `{prefix}template_install_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '模板ID',
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `action` tinyint(1) NOT NULL DEFAULT '1' COMMENT '动作:1安装/2卸载/3切换/4基线迁移',
  `source` tinyint(1) NOT NULL DEFAULT '1' COMMENT '来源:1商店/2上传/3恢复',
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作IP',
  `extra` json DEFAULT NULL COMMENT '额外信息JSON',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_template_action` (`template_id`,`action`),
  KEY `idx_member` (`member_id`),
  KEY `idx_action_time` (`action`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板安装日志表(V2.9.24)';

DROP TABLE IF EXISTS `{prefix}template_invoice`;
CREATE TABLE `{prefix}template_invoice` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联订单ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '申请用户ID',
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '发票抬头',
  `tax_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '税号',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '开票金额',
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '接收邮箱',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态:0待开1已开2拒绝',
  `invoice_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '发票号码',
  `invoice_file` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '发票文件路径',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板发票申请表(V2.9.28 M-1)';

DROP TABLE IF EXISTS `{prefix}template_layout`;
CREATE TABLE `{prefix}template_layout` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `theme_slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板标识',
  `preset_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '应用的预设方案key',
  `sections` json DEFAULT NULL COMMENT '布局区块配置JSON',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_theme` (`member_id`,`theme_slug`),
  KEY `idx_preset_key` (`preset_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板布局自定义表 - V2.9.31';

DROP TABLE IF EXISTS `{prefix}template_license`;
CREATE TABLE `{prefix}template_license` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `license_code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `template_id` int(10) unsigned NOT NULL DEFAULT '0',
  `order_id` int(10) unsigned NOT NULL DEFAULT '0',
  `member_id` int(10) unsigned NOT NULL DEFAULT '0',
  `license_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'permanent' COMMENT 'permanent/yearly/lifetime',
  `domains` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `expires_at` int(10) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_license_code` (`license_code`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板授权';

DROP TABLE IF EXISTS `{prefix}template_order`;
CREATE TABLE `{prefix}template_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '订单编号',
  `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '商店模板ID',
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '购买者用户ID',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `original_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '原始金额',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '优惠金额',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实付金额',
  `coupon_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '优惠码',
  `promotion_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '促销ID',
  `license_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '授权ID',
  `pay_method` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '支付方式',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0待支付/1已支付/2已退款/3已关闭',
  `pay_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '支付方式',
  `pay_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付时间',
  `refund_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '退款时间',
  `refund_reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '退款原因',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_member_status` (`member_id`,`status`),
  KEY `idx_store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板订单表(V2.9.12)';

DROP TABLE IF EXISTS `{prefix}template_pack`;
CREATE TABLE `{prefix}template_pack` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '包名称',
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '包描述',
  `cover` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '封面图',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '打包价格',
  `original_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '原价合计',
  `sort` int(10) unsigned NOT NULL DEFAULT '99' COMMENT '排序',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板包组合表(V2.9.28 M-4)';

DROP TABLE IF EXISTS `{prefix}template_pack_item`;
CREATE TABLE `{prefix}template_pack_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pack_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '包ID',
  `template_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '模板ID',
  `sort` int(10) unsigned NOT NULL DEFAULT '99' COMMENT '排序',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pack_template` (`pack_id`,`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板包关联表(V2.9.28 M-4)';

DROP TABLE IF EXISTS `{prefix}template_preset_color`;
CREATE TABLE `{prefix}template_preset_color` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配色名称',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配色描述',
  `colors` json NOT NULL COMMENT '配色JSON {primary, secondary, bg, text, heading, link, accent}',
  `industry_tags` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '行业标签(逗号分隔)',
  `is_system` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否系统预设(1=系统/0=自定义)',
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID，0表示系统预设(V2.9.24)',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_industry` (`industry_tags`),
  KEY `idx_sort` (`sort`),
  KEY `idx_member` (`member_id`,`is_system`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='预设配色方案表(V2.9.23 C-4)';

INSERT INTO `{prefix}template_preset_color` VALUES (1,'商务蓝','专业稳重的商务蓝色调','{\"bg\": \"#ffffff\", \"link\": \"#2563eb\", \"text\": \"#1f2937\", \"accent\": \"#f59e0b\", \"heading\": \"#0f172a\", \"primary\": \"#1e40af\", \"secondary\": \"#3b82f6\"}','科技,企业,金融',1,0,100,1781672031),(2,'温暖橙','充满活力的暖色系','{\"bg\": \"#fffbeb\", \"link\": \"#ea580c\", \"text\": \"#1c1917\", \"accent\": \"#dc2626\", \"heading\": \"#7c2d12\", \"primary\": \"#ea580c\", \"secondary\": \"#fb923c\"}','电商,餐饮,教育',1,0,90,1781672031),(3,'自然绿','清新自然的绿色系','{\"bg\": \"#f0fdf4\", \"link\": \"#16a34a\", \"text\": \"#14532d\", \"accent\": \"#84cc16\", \"heading\": \"#052e16\", \"primary\": \"#15803d\", \"secondary\": \"#22c55e\"}','农业,环保,健康',1,0,85,1781672031),(4,'典雅紫','神秘高贵的紫色调','{\"bg\": \"#faf5ff\", \"link\": \"#9333ea\", \"text\": \"#1e1b4b\", \"accent\": \"#ec4899\", \"heading\": \"#2e1065\", \"primary\": \"#7e22ce\", \"secondary\": \"#a855f7\"}','美妆,时尚,设计',1,0,80,1781672031),(5,'简约灰','极简现代的灰白调','{\"bg\": \"#ffffff\", \"link\": \"#4b5563\", \"text\": \"#111827\", \"accent\": \"#0ea5e9\", \"heading\": \"#030712\", \"primary\": \"#374151\", \"secondary\": \"#6b7280\"}','设计,艺术,建筑',1,0,75,1781672031),(6,'热情红','激情澎湃的红色调','{\"bg\": \"#fef2f2\", \"link\": \"#dc2626\", \"text\": \"#1f2937\", \"accent\": \"#f59e0b\", \"heading\": \"#7f1d1d\", \"primary\": \"#dc2626\", \"secondary\": \"#ef4444\"}','餐饮,娱乐,体育',1,0,70,1781672031),(7,'医疗青','专业可信的医疗色调','{\"bg\": \"#ecfeff\", \"link\": \"#0891b2\", \"text\": \"#164e63\", \"accent\": \"#14b8a6\", \"heading\": \"#083344\", \"primary\": \"#0e7490\", \"secondary\": \"#06b6d4\"}','医疗,健康,生物',1,0,65,1781672031),(8,'奢华金','尊贵典雅的奢华金调','{\"bg\": \"#fffbeb\", \"link\": \"#b45309\", \"text\": \"#1c1917\", \"accent\": \"#a16207\", \"heading\": \"#451a03\", \"primary\": \"#92400e\", \"secondary\": \"#d97706\"}','珠宝,奢侈品,金融',1,0,60,1781672031),(9,'商务蓝','专业稳重的商务蓝色调','{\"bg\": \"#ffffff\", \"link\": \"#2563eb\", \"text\": \"#1f2937\", \"accent\": \"#f59e0b\", \"heading\": \"#0f172a\", \"primary\": \"#1e40af\", \"secondary\": \"#3b82f6\"}','科技,企业,金融',1,0,100,1781672037),(10,'温暖橙','充满活力的暖色系','{\"bg\": \"#fffbeb\", \"link\": \"#ea580c\", \"text\": \"#1c1917\", \"accent\": \"#dc2626\", \"heading\": \"#7c2d12\", \"primary\": \"#ea580c\", \"secondary\": \"#fb923c\"}','电商,餐饮,教育',1,0,90,1781672037),(11,'自然绿','清新自然的绿色系','{\"bg\": \"#f0fdf4\", \"link\": \"#16a34a\", \"text\": \"#14532d\", \"accent\": \"#84cc16\", \"heading\": \"#052e16\", \"primary\": \"#15803d\", \"secondary\": \"#22c55e\"}','农业,环保,健康',1,0,85,1781672037),(12,'典雅紫','神秘高贵的紫色调','{\"bg\": \"#faf5ff\", \"link\": \"#9333ea\", \"text\": \"#1e1b4b\", \"accent\": \"#ec4899\", \"heading\": \"#2e1065\", \"primary\": \"#7e22ce\", \"secondary\": \"#a855f7\"}','美妆,时尚,设计',1,0,80,1781672037),(13,'简约灰','极简现代的灰白调','{\"bg\": \"#ffffff\", \"link\": \"#4b5563\", \"text\": \"#111827\", \"accent\": \"#0ea5e9\", \"heading\": \"#030712\", \"primary\": \"#374151\", \"secondary\": \"#6b7280\"}','设计,艺术,建筑',1,0,75,1781672037),(14,'热情红','激情澎湃的红色调','{\"bg\": \"#fef2f2\", \"link\": \"#dc2626\", \"text\": \"#1f2937\", \"accent\": \"#f59e0b\", \"heading\": \"#7f1d1d\", \"primary\": \"#dc2626\", \"secondary\": \"#ef4444\"}','餐饮,娱乐,体育',1,0,70,1781672037),(15,'医疗青','专业可信的医疗色调','{\"bg\": \"#ecfeff\", \"link\": \"#0891b2\", \"text\": \"#164e63\", \"accent\": \"#14b8a6\", \"heading\": \"#083344\", \"primary\": \"#0e7490\", \"secondary\": \"#06b6d4\"}','医疗,健康,生物',1,0,65,1781672037),(16,'奢华金','尊贵典雅的奢华金调','{\"bg\": \"#fffbeb\", \"link\": \"#b45309\", \"text\": \"#1c1917\", \"accent\": \"#a16207\", \"heading\": \"#451a03\", \"primary\": \"#92400e\", \"secondary\": \"#d97706\"}','珠宝,奢侈品,金融',1,0,60,1781672037),(17,'商务蓝','专业稳重的商务蓝色调','{\"bg\": \"#ffffff\", \"link\": \"#2563eb\", \"text\": \"#1f2937\", \"accent\": \"#f59e0b\", \"heading\": \"#0f172a\", \"primary\": \"#1e40af\", \"secondary\": \"#3b82f6\"}','科技,企业,金融',1,0,100,1781672072),(18,'温暖橙','充满活力的暖色系','{\"bg\": \"#fffbeb\", \"link\": \"#ea580c\", \"text\": \"#1c1917\", \"accent\": \"#dc2626\", \"heading\": \"#7c2d12\", \"primary\": \"#ea580c\", \"secondary\": \"#fb923c\"}','电商,餐饮,教育',1,0,90,1781672072),(19,'自然绿','清新自然的绿色系','{\"bg\": \"#f0fdf4\", \"link\": \"#16a34a\", \"text\": \"#14532d\", \"accent\": \"#84cc16\", \"heading\": \"#052e16\", \"primary\": \"#15803d\", \"secondary\": \"#22c55e\"}','农业,环保,健康',1,0,85,1781672072),(20,'典雅紫','神秘高贵的紫色调','{\"bg\": \"#faf5ff\", \"link\": \"#9333ea\", \"text\": \"#1e1b4b\", \"accent\": \"#ec4899\", \"heading\": \"#2e1065\", \"primary\": \"#7e22ce\", \"secondary\": \"#a855f7\"}','美妆,时尚,设计',1,0,80,1781672072),(21,'简约灰','极简现代的灰白调','{\"bg\": \"#ffffff\", \"link\": \"#4b5563\", \"text\": \"#111827\", \"accent\": \"#0ea5e9\", \"heading\": \"#030712\", \"primary\": \"#374151\", \"secondary\": \"#6b7280\"}','设计,艺术,建筑',1,0,75,1781672072),(22,'热情红','激情澎湃的红色调','{\"bg\": \"#fef2f2\", \"link\": \"#dc2626\", \"text\": \"#1f2937\", \"accent\": \"#f59e0b\", \"heading\": \"#7f1d1d\", \"primary\": \"#dc2626\", \"secondary\": \"#ef4444\"}','餐饮,娱乐,体育',1,0,70,1781672072),(23,'医疗青','专业可信的医疗色调','{\"bg\": \"#ecfeff\", \"link\": \"#0891b2\", \"text\": \"#164e63\", \"accent\": \"#14b8a6\", \"heading\": \"#083344\", \"primary\": \"#0e7490\", \"secondary\": \"#06b6d4\"}','医疗,健康,生物',1,0,65,1781672072),(24,'奢华金','尊贵典雅的奢华金调','{\"bg\": \"#fffbeb\", \"link\": \"#b45309\", \"text\": \"#1c1917\", \"accent\": \"#a16207\", \"heading\": \"#451a03\", \"primary\": \"#92400e\", \"secondary\": \"#d97706\"}','珠宝,奢侈品,金融',1,0,60,1781672072);
DROP TABLE IF EXISTS `{prefix}template_price_log`;
CREATE TABLE `{prefix}template_price_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL DEFAULT '0',
  `operator_id` int(11) NOT NULL DEFAULT '0',
  `operator_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `action` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `old_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `new_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `reason` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板价格变更日志';

DROP TABLE IF EXISTS `{prefix}template_pricing`;
CREATE TABLE `{prefix}template_pricing` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(10) unsigned NOT NULL DEFAULT '0',
  `billing_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'one_time' COMMENT 'one_time/recurring/free/trial',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `original_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `recurring_period` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'monthly/yearly',
  `trial_days` int(10) unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `sort` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_billing` (`template_id`,`billing_type`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板定价';

DROP TABLE IF EXISTS `{prefix}template_promotion`;
CREATE TABLE `{prefix}template_promotion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'discount',
  `discount_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `template_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `category_id` int(11) NOT NULL DEFAULT '0',
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `sort` int(11) NOT NULL DEFAULT '100',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板促销活动';

DROP TABLE IF EXISTS `{prefix}template_promotion_activity`;
CREATE TABLE `{prefix}template_promotion_activity` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `activity_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activity_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '类型(discount/full_reduction/coupon/bundle/new_user)',
  `discount_rate` decimal(3,2) DEFAULT '1.00',
  `condition_value` decimal(10,2) DEFAULT '0.00',
  `start_time` int(10) unsigned NOT NULL,
  `end_time` int(10) unsigned NOT NULL,
  `target_user_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'all',
  `template_ids` json DEFAULT NULL,
  `status` tinyint(4) DEFAULT '0' COMMENT '0未开始1进行中2已结束3已终止',
  `create_time` int(10) unsigned DEFAULT '0',
  `update_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`activity_type`),
  KEY `idx_status` (`status`),
  KEY `idx_time` (`start_time`,`end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板促销活动表';

DROP TABLE IF EXISTS `{prefix}template_quality_tag`;
CREATE TABLE `{prefix}template_quality_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL DEFAULT '0',
  `tag_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tag_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'auto',
  `score` decimal(3,1) NOT NULL DEFAULT '0.0',
  `weight` int(11) NOT NULL DEFAULT '100',
  `auditor_id` int(11) NOT NULL DEFAULT '0',
  `auditor_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_tag_type` (`tag_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板质量标签';

DROP TABLE IF EXISTS `{prefix}template_recommend`;
CREATE TABLE `{prefix}template_recommend` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `position` tinyint(1) NOT NULL DEFAULT '1' COMMENT '推荐位置:1首页顶部/2热门/3新品/4精选',
  `recommend_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '推荐类型:1手动指定/2自动热门/3自动最新',
  `template_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联模板ID(手动指定时)',
  `title` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '推荐位标题',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '推荐位描述',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序号',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始展示时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结束展示时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_position_status` (`position`,`status`,`sort`),
  KEY `idx_template` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板推荐位表(V2.9.24)';

DROP TABLE IF EXISTS `{prefix}template_recommend_item`;
CREATE TABLE `{prefix}template_recommend_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `position_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '推荐位ID',
  `template_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '模板ID',
  `sort` int(10) unsigned NOT NULL DEFAULT '99' COMMENT '排序',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '生效时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '失效时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_position_template` (`position_id`,`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推荐位模板关联表(V2.9.28 M-6)';

DROP TABLE IF EXISTS `{prefix}template_recommend_log`;
CREATE TABLE `{prefix}template_recommend_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `template_id` int(11) NOT NULL COMMENT '推荐模板ID',
  `recommend_strategy` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '推荐策略',
  `recommend_scene` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '推荐场景',
  `impression` tinyint(4) DEFAULT '0',
  `click` tinyint(4) DEFAULT '0',
  `install` tinyint(4) DEFAULT '0',
  `click_time` int(10) unsigned DEFAULT '0',
  `install_time` int(10) unsigned DEFAULT '0',
  `create_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_strategy` (`recommend_strategy`),
  KEY `idx_scene` (`recommend_scene`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板推荐日志表';

DROP TABLE IF EXISTS `{prefix}template_recommend_position`;
CREATE TABLE `{prefix}template_recommend_position` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '推荐位名称',
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标识(home_banner/home_featured/home_hot/guess_like)',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '类型:1人工2规则3AI',
  `max_count` int(10) unsigned NOT NULL DEFAULT '10' COMMENT '最大展示数',
  `config` json DEFAULT NULL COMMENT '规则配置(JSON)',
  `sort` int(10) unsigned NOT NULL DEFAULT '99' COMMENT '排序',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推荐位定义表(V2.9.28 M-6)';

INSERT INTO `{prefix}template_recommend_position` VALUES (1,'首页轮播','home_banner',1,5,'{\"desc\": \"首页顶部轮播展示\"}',1,1,1782110071,1782110071),(2,'精品推荐','home_featured',1,10,'{\"desc\": \"首页精品推荐区域\"}',2,1,1782110071,1782110071),(3,'热门排行','home_hot',2,10,'{\"desc\": true, \"rule\": \"order_by\", \"field\": \"install_count\"}',3,1,1782110071,1782110071),(4,'猜你喜欢','guess_like',3,10,'{\"desc\": \"基于用户行为的AI推荐(预留)\"}',4,1,1782110071,1782110071);
DROP TABLE IF EXISTS `{prefix}template_recommend_queue`;
CREATE TABLE `{prefix}template_recommend_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `template_id` int(10) unsigned NOT NULL DEFAULT '0',
  `score` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `reason` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'hot/collaborative/category',
  `expire_time` int(10) unsigned DEFAULT NULL,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_template` (`user_id`,`template_id`),
  KEY `idx_user_score` (`user_id`,`score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板推荐队列表';

DROP TABLE IF EXISTS `{prefix}template_recommend_rule`;
CREATE TABLE `{prefix}template_recommend_rule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '规则名称',
  `rule_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual' COMMENT '规则类型: manual=手动置顶, ai=AI推荐, category=分类热门, festival=节日特推, new_release=新品首发',
  `template_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '手动指定的模板ID列表(JSON数组)',
  `category_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联分类ID(分类热门时有效)',
  `priority` int(11) NOT NULL DEFAULT '10' COMMENT '优先级(数字越大越靠前)',
  `ab_group` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A' COMMENT 'A/B测试分组: A/B/ALL',
  `conditions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '触发条件(JSON: 用户标签/时间段/设备等)',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态: 0=禁用 1=启用',
  `start_time` datetime DEFAULT NULL COMMENT '生效开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '生效结束时间',
  `sort` int(11) NOT NULL DEFAULT '100' COMMENT '排序',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rule_type` (`rule_type`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category_id`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板推荐规则表';

DROP TABLE IF EXISTS `{prefix}template_recommend_stats`;
CREATE TABLE `{prefix}template_recommend_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL DEFAULT '0' COMMENT '模板ID',
  `rule_id` int(11) NOT NULL DEFAULT '0' COMMENT '触发的规则ID',
  `position` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '推荐位: home/sidebar/detail/search',
  `ab_group` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A' COMMENT 'A/B测试分组',
  `impression_count` int(11) NOT NULL DEFAULT '0' COMMENT '曝光次数',
  `click_count` int(11) NOT NULL DEFAULT '0' COMMENT '点击次数',
  `install_count` int(11) NOT NULL DEFAULT '0' COMMENT '安装次数',
  `stat_date` date NOT NULL COMMENT '统计日期',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_rule_pos_date` (`template_id`,`rule_id`,`position`,`stat_date`),
  KEY `idx_template` (`template_id`),
  KEY `idx_stat_date` (`stat_date`),
  KEY `idx_position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板推荐效果统计表';

DROP TABLE IF EXISTS `{prefix}template_refund`;
CREATE TABLE `{prefix}template_refund` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联订单ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '申请用户ID',
  `reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '退款原因',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '退款金额',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态:0待审1通过2拒绝',
  `admin_remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '管理员备注',
  `process_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '处理时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板退款记录表(V2.9.28 M-1)';

DROP TABLE IF EXISTS `{prefix}template_reject_reason`;
CREATE TABLE `{prefix}template_reject_reason` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `sort` int(11) NOT NULL DEFAULT '100',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板驳回理由模板';

INSERT INTO `{prefix}template_reject_reason` VALUES (1,'模板设计不完整，缺少必要的页面文件','quality',10,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(2,'模板存在明显的兼容性问题','quality',20,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(3,'模板代码质量不达标，存在安全风险','quality',30,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(4,'模板截图与实际效果不符','quality',40,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(5,'模板涉及版权问题，请提供授权证明','copyright',50,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(6,'模板描述信息不完整','general',60,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(7,'模板分类选择不正确','general',70,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(8,'模板定价不合理','general',80,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(9,'模板存在重复内容','quality',90,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(10,'其他原因（请在审核意见中说明）','other',100,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(11,'代码存在语法错误，无法通过编译','code',1,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(12,'代码不符合PHP编码规范(PSR-12)','code',2,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(13,'存在硬编码的数据库连接信息','code',3,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(14,'页面布局在移动端显示异常','design',1,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(15,'颜色对比度不符合无障碍标准','design',2,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(16,'页面加载速度过慢(>3秒)','design',3,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(17,'存在SQL注入风险','safety',1,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(18,'存在XSS跨站脚本风险','safety',2,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(19,'文件上传缺少安全校验','safety',3,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(20,'模板描述与实际功能不符','other',1,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(21,'缺少使用文档或README','other',2,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(22,'与其他已上架模板重复度过高','other',3,1,'2026-06-22 14:34:31','2026-06-22 14:34:31');
DROP TABLE IF EXISTS `{prefix}template_review`;
CREATE TABLE `{prefix}template_review` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '商店模板ID',
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论者用户ID',
  `rating` tinyint(1) NOT NULL DEFAULT '5' COMMENT '评分1-5',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '评论内容',
  `images` json DEFAULT NULL COMMENT '评论图片URL数组',
  `reply` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '管理员回复(V2.9.24)',
  `reply_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '回复时间(V2.9.24)',
  `is_audited` tinyint(1) NOT NULL DEFAULT '0' COMMENT '审核状态:0待审核/1通过/2拒绝',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '审核状态:0待审核/1通过/2拒绝(兼容字段)',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_store_member` (`store_id`,`member_id`),
  KEY `idx_store_audit` (`store_id`,`is_audited`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板评分评论表(V2.9.12)';

DROP TABLE IF EXISTS `{prefix}template_review_report`;
CREATE TABLE `{prefix}template_review_report` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `review_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '被举报评价ID',
  `reporter_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '举报人ID',
  `reason` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '举报原因',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态:0待处理1已通过(隐藏)2已驳回',
  `admin_remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '处理备注',
  `process_time` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_review` (`review_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板评价举报表(V2.9.28 M-2)';

DROP TABLE IF EXISTS `{prefix}template_review_v2`;
CREATE TABLE `{prefix}template_review_v2` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `template_id` int(10) unsigned NOT NULL DEFAULT '0',
  `rating_overall` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rating_ease` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rating_design` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rating_feature` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `rating_performance` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `content` text COLLATE utf8mb4_unicode_ci,
  `images` json DEFAULT NULL,
  `likes` int(10) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `audit_time` int(10) unsigned DEFAULT NULL,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_template` (`user_id`,`template_id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_rating` (`rating_overall`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板评分评论表V2 - V2.9.32';

DROP TABLE IF EXISTS `{prefix}template_section_config`;
CREATE TABLE `{prefix}template_section_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `theme_slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板标识',
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `page_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'index' COMMENT '页面类型: index/detail/list',
  `sections` json NOT NULL COMMENT '区块配置JSON[{id,name,visible,sort}]',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_theme_page` (`theme_slug`,`member_id`,`page_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='前台区块配置表(V2.9.23 C-2)';

DROP TABLE IF EXISTS `{prefix}template_settlement`;
CREATE TABLE `{prefix}template_settlement` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `batch_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '结算批次号',
  `period_start` date NOT NULL COMMENT '结算周期开始',
  `period_end` date NOT NULL COMMENT '结算周期结束',
  `total_orders` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单总数',
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '订单总金额',
  `commission_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '平台佣金',
  `settlement_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '应结金额',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态: 0待审核/1已审核/2已打款/3已关闭',
  `auditor` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '审核人',
  `audit_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核时间',
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_batch_no` (`batch_no`),
  KEY `idx_period` (`period_start`,`period_end`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='结算报表表(V2.9.25 N-3)';

DROP TABLE IF EXISTS `{prefix}template_settlement_rule`;
CREATE TABLE `{prefix}template_settlement_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `developer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开发者用户ID',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT '30.00' COMMENT '平台抽成比例(%)',
  `min_withdraw` decimal(10,2) NOT NULL DEFAULT '100.00' COMMENT '最低提现金额',
  `settle_cycle` tinyint(4) NOT NULL DEFAULT '1' COMMENT '结算周期:1月结2季结3年结',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_developer` (`developer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='结算规则表(V2.9.28 M-7)';

DROP TABLE IF EXISTS `{prefix}template_store`;
CREATE TABLE `{prefix}template_store` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板唯一标识',
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板名称',
  `category_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '模板描述',
  `seo_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `seo_keywords` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `screenshots` json DEFAULT NULL COMMENT '预览截图JSON数组',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '售价(0表示免费)',
  `billing_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'free' COMMENT '计费类型: free/one_time/subscription',
  `price_original` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '原价',
  `price_sale` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '促销价',
  `author_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '作者名称',
  `author_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开发者用户ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0待审核/1上架/2下架/3拒绝',
  `review_status` tinyint(4) DEFAULT '0' COMMENT '审核状态:0草稿1待初审2待终审3通过4驳回',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否推荐:0否/1是',
  `is_recommended` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否推荐(0否/1是)',
  `is_published` tinyint(4) DEFAULT '0' COMMENT '是否已发布',
  `banner_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商店首页轮播Banner图',
  `quality_score` int(11) NOT NULL DEFAULT '0' COMMENT 'AI质量评分(0-100)',
  `last_quality_check` int(10) unsigned DEFAULT '0' COMMENT '最近质量检查时间',
  `recommend_weight` int(11) DEFAULT '0' COMMENT '推荐权重(0-100)',
  `developer_id` int(11) DEFAULT '0' COMMENT '开发者用户ID',
  `upload_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'draft' COMMENT '上传状态(draft/pending_audit/approved/rejected)',
  `reject_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '驳回原因',
  `pack_validation_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT '打包校验状态',
  `validation_report` json DEFAULT NULL COMMENT '校验报告',
  `install_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '安装次数',
  `install_count_7d` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '近7天安装数(B-5排行用)',
  `view_count` int(10) unsigned DEFAULT '0' COMMENT '浏览次数',
  `rating_avg` decimal(2,1) NOT NULL DEFAULT '5.0' COMMENT '平均评分(1-5)',
  `rating_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评分人数',
  `version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1.0.0' COMMENT '版本号',
  `requirements` json DEFAULT NULL COMMENT '环境要求JSON',
  `file_size` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小(字节)',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  `support_models` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '[]' COMMENT '支持的模型类型(JSON数组)',
  `quality_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT '质量状态(pending/passed/failed/repairing)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_category_status` (`category_id`,`status`),
  KEY `idx_featured` (`is_featured`,`status`),
  KEY `idx_author` (`author_id`),
  KEY `idx_rating` (`rating_avg`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板商店表(V2.9.12)';

INSERT INTO `{prefix}template_store` VALUES (1,'default-official','官方默认模板',1,'八界AI-CMS官方默认模板，简洁大方，适用于各类企业官网。响应式设计，支持PC和移动端。','','','','[]',0.00,'free',0.00,0.00,'八界AI官方',0,1,0,1,0,0,'',92,0,0,0,'draft',NULL,'pending',NULL,128,0,0,4.8,56,'2.9.12','{\"cms\": \">=2.9.0\", \"php\": \">=8.0\"}',2048000,1780201507,1780855474,'[\"article\",\"image\",\"download\",\"product\",\"video\"]','pending'),(2,'corporate-pro','企业商务Pro',1,'专为企业打造的商务风格模板，蓝色主色调，专业可信。包含首页、关于、服务、案例、联系等页面。','','','','[]',99.00,'free',0.00,0.00,'八界AI官方',0,1,0,1,0,0,'',95,0,0,0,'draft',NULL,'pending',NULL,86,0,0,4.9,42,'2.9.12','{\"cms\": \">=2.9.0\", \"php\": \">=8.0\"}',3584000,1780201507,1780855469,'[\"article\",\"image\",\"download\",\"product\",\"video\"]','pending'),(3,'blog-minimal','极简博客',3,'文艺清新的博客模板，注重阅读体验，排版优雅。适合个人博客、技术博客、知识分享类网站。','','','','[]',0.00,'free',0.00,0.00,'八界AI官方',0,1,0,0,0,0,'',88,0,0,0,'draft',NULL,'pending',NULL,215,0,0,4.6,103,'2.9.12','{\"cms\": \">=2.9.0\", \"php\": \">=8.0\"}',1536000,1780201507,1780855434,'[\"article\",\"image\",\"download\",\"product\",\"video\"]','pending');
DROP TABLE IF EXISTS `{prefix}template_store_category`;
CREATE TABLE `{prefix}template_store_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '父分类ID(0=顶级)',
  `level` tinyint(4) NOT NULL DEFAULT '1' COMMENT '分类层级(1=顶级)',
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `slug` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类标识',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '分类描述',
  `meta_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `meta_description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `meta_keywords` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `icon` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '图标类名',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序号',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用:0否/1是',
  `is_visible` tinyint(1) NOT NULL DEFAULT '1' COMMENT '前台是否可见:0隐藏/1显示(V2.9.24)',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_enabled_sort` (`is_enabled`,`sort`),
  KEY `idx_visible_sort` (`is_enabled`,`is_visible`,`sort`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_level` (`level`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板商店分类表(V2.9.12)';

INSERT INTO `{prefix}template_store_category` VALUES (1,0,1,'企业商务','corporate','企业官网、商务展示类模板','','','','bi bi-briefcase',1,1,1,0,0),(2,0,1,'电商促销','ecommerce','在线商城、促销活动类模板','','','','bi bi-cart',2,1,1,0,0),(3,0,1,'博客文艺','blog','个人博客、文学创作类模板','','','','bi bi-journal-text',3,1,1,0,0),(4,0,1,'门户资讯','portal','新闻门户、资讯聚合类模板','','','','bi bi-newspaper',4,1,1,0,0),(5,0,1,'医疗健康','medical','医院诊所、健康管理类模板','','','','bi bi-heart-pulse',5,1,1,0,0),(6,0,1,'教育培训','education','学校机构、在线教育类模板','','','','bi bi-mortarboard',6,1,1,0,0),(7,0,1,'餐饮美食','catering','餐厅酒店、美食推荐类模板','','','','bi bi-cup-hot',7,1,1,0,0),(8,0,1,'金融理财','finance','银行保险、投资理财类模板','','','','bi bi-bank',8,1,1,0,0),(9,0,1,'科技互联网','technology','科技公司、SaaS产品类模板','','','','bi bi-cpu',9,1,1,0,0),(10,0,1,'房产家居','realestate','房产中介、家居装修类模板','','','','bi bi-house-door',10,1,1,0,0);
DROP TABLE IF EXISTS `{prefix}template_style_version`;
CREATE TABLE `{prefix}template_style_version` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL DEFAULT '0',
  `template_id` int(10) unsigned NOT NULL DEFAULT '0',
  `version` int(10) unsigned NOT NULL DEFAULT '1',
  `change_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `change_summary` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `config_snapshot` json NOT NULL,
  `diff` json DEFAULT NULL,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_member_template` (`member_id`,`template_id`),
  KEY `idx_template_version` (`template_id`,`version`),
  KEY `idx_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板样式版本历史表 - V2.9.32';

DROP TABLE IF EXISTS `{prefix}template_tag`;
CREATE TABLE `{prefix}template_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '标签名称',
  `type` varchar(50) NOT NULL COMMENT '标签类型(industry/style/function/custom)',
  `color` varchar(20) DEFAULT '#1890ff' COMMENT '标签颜色',
  `sort` int(11) DEFAULT '99' COMMENT '排序',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态:1启用0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='模板标签表';

DROP TABLE IF EXISTS `{prefix}template_tag_relation`;
CREATE TABLE `{prefix}template_tag_relation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL COMMENT '模板ID',
  `tag_id` int(11) NOT NULL COMMENT '标签ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_tag` (`template_id`,`tag_id`),
  KEY `idx_tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='模板标签关系表';

DROP TABLE IF EXISTS `{prefix}template_usage_log`;
CREATE TABLE `{prefix}template_usage_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '模板ID',
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `event_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '事件类型: view/preview/install/activate/custom',
  `device` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pc' COMMENT '设备: pc/mobile/tablet',
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'UA',
  `referer` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '来源页',
  `extra` json DEFAULT NULL COMMENT '额外信息JSON',
  `create_date` date NOT NULL COMMENT '日期（用于汇总）',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_template_date` (`template_id`,`create_date`),
  KEY `idx_event_date` (`event_type`,`create_date`),
  KEY `idx_member` (`member_id`),
  KEY `idx_date` (`create_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板使用日志表(V2.9.25 N-2)';

DROP TABLE IF EXISTS `{prefix}template_user_action`;
CREATE TABLE `{prefix}template_user_action` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `template_id` int(10) unsigned NOT NULL DEFAULT '0',
  `action` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'view/download/buy/favorite',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_action` (`action`,`create_time`),
  KEY `idx_user_action` (`user_id`,`action`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板用户行为表';

DROP TABLE IF EXISTS `{prefix}template_user_profile`;
CREATE TABLE `{prefix}template_user_profile` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `stat_date` date NOT NULL COMMENT '统计日期',
  `dimension` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '维度(region/hobby/hour)',
  `dimension_value` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '维度值',
  `user_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户数',
  `download_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '下载数',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_date_dimension` (`stat_date`,`dimension`,`dimension_value`),
  KEY `idx_date` (`stat_date`),
  KEY `idx_dimension` (`dimension`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板用户画像聚合表';

DROP TABLE IF EXISTS `{prefix}template_version_record`;
CREATE TABLE `{prefix}template_version_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL DEFAULT '0',
  `version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `changelog` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `file_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `file_diff` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `grayscale_percent` tinyint(4) NOT NULL DEFAULT '100',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `operator_id` int(11) NOT NULL DEFAULT '0',
  `operator_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_version` (`version`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板版本记录';

DROP TABLE IF EXISTS `{prefix}template_withdraw`;
CREATE TABLE `{prefix}template_withdraw` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `developer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开发者ID',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '提现金额',
  `fee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '手续费',
  `actual_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际到账金额',
  `account_info` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '收款账户信息(JSON)',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态:0待审1打款中2已完成3已驳回',
  `admin_remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '管理员备注',
  `process_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '处理时间',
  `confirm_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '到账确认时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_developer` (`developer_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='提现申请表(V2.9.28 M-7)';

DROP TABLE IF EXISTS `{prefix}terminology`;
CREATE TABLE `{prefix}terminology` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}theme_analytics`;
CREATE TABLE `{prefix}theme_analytics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `theme_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `event_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `event_data` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_theme_event` (`theme_id`,`event_type`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}theme_config`;
CREATE TABLE `{prefix}theme_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `theme` varchar(50) NOT NULL DEFAULT 'default' COMMENT '主题标识',
  `scope` enum('global','page','component') NOT NULL DEFAULT 'global' COMMENT '配置范围',
  `scope_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '范围ID',
  `config_key` varchar(100) NOT NULL COMMENT '配置键名',
  `config_value` text COMMENT '配置值',
  `config_type` enum('color','text','number','image','select','boolean','json') NOT NULL DEFAULT 'text' COMMENT '值类型',
  `label` varchar(100) DEFAULT '' COMMENT '显示标签',
  `description` varchar(255) DEFAULT '' COMMENT '配置说明',
  `sort` int(11) NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned DEFAULT '0',
  `update_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_theme_scope_key` (`theme`,`scope`,`scope_id`,`config_key`),
  KEY `idx_theme_scope` (`theme`,`scope`,`scope_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='前台主题配置表';

DROP TABLE IF EXISTS `{prefix}theme_customization`;
CREATE TABLE `{prefix}theme_customization` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `theme_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `variant_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `custom_data` json NOT NULL,
  `is_active` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_theme_variant` (`theme_id`,`variant_name`),
  KEY `idx_theme_active` (`theme_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}theme_info`;
CREATE TABLE `{prefix}theme_info` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '主题标识',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'frontend' COMMENT '类型: frontend/admin',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '主题名称',
  `version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '1.0.0' COMMENT '版本号',
  `author` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '作者',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '描述',
  `thumbnail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '缩略图',
  `is_installed` tinyint(4) DEFAULT '1' COMMENT '是否已安装',
  `installed_version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '1.0.0' COMMENT '已安装版本',
  `update_available` tinyint(4) DEFAULT '0' COMMENT '有可用更新',
  `industry` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '行业类型(S15)',
  `style_tag` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '风格标签(S15)',
  `is_market` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否上架市场(0=否,1=是,S15)',
  `market_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '市场远程URL(S15)',
  `avg_rating` decimal(2,1) unsigned NOT NULL DEFAULT '0.0' COMMENT '平均评分(1-5星,S15)',
  `install_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '安装次数(S15)',
  `screenshots` json DEFAULT NULL COMMENT '截图URL数组(S15)',
  `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '来源商店模板ID，0表示非商店模板(V2.9.12)',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code_type` (`code`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='主题信息表';

DROP TABLE IF EXISTS `{prefix}theme_log`;
CREATE TABLE `{prefix}theme_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `theme_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '主题ID',
  `action` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作类型(install/rollback/update/rate)',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作用户ID',
  `detail` json DEFAULT NULL COMMENT '操作详情',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_theme_id` (`theme_id`),
  KEY `idx_action` (`action`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='主题操作日志表(S14+S16)';

DROP TABLE IF EXISTS `{prefix}theme_pending`;
CREATE TABLE `{prefix}theme_pending` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '主题标识',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '主题名称',
  `version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '1.0.0' COMMENT '版本号',
  `developer_id` int(11) DEFAULT '0' COMMENT '开发者用户ID',
  `developer_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '开发者名称',
  `developer_email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '开发者邮箱',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '描述',
  `industry` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '行业',
  `tags` json DEFAULT NULL COMMENT '标签',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT '状态: pending/approved/rejected',
  `schema_score` int(11) DEFAULT '0' COMMENT 'Schema规范评分',
  `quality_score` decimal(4,1) DEFAULT '0.0' COMMENT 'CSS质量评分',
  `xss_high` tinyint(4) DEFAULT '0' COMMENT '是否有高危XSS',
  `file_size` int(11) DEFAULT '0' COMMENT 'ZIP文件大小(字节)',
  `package_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '市场包路径',
  `is_auto_passed` tinyint(4) DEFAULT '0' COMMENT '是否自动通过',
  `audit_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '审核理由',
  `admin_id` int(11) DEFAULT '0' COMMENT '审核管理员ID',
  `audit_time` int(10) unsigned DEFAULT '0' COMMENT '审核时间',
  `theme_json` json DEFAULT NULL COMMENT 'theme.json完整内容',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_developer` (`developer_id`),
  KEY `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='第三方主题待审核表';

DROP TABLE IF EXISTS `{prefix}theme_rate`;
CREATE TABLE `{prefix}theme_rate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `theme_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '主题ID',
  `rating` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '评分(1-5星)',
  `is_favorite` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否收藏(0=否,1=是)',
  `comment` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '评价内容',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_theme` (`user_id`,`theme_id`),
  KEY `idx_theme_id` (`theme_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='主题评分收藏表(S16)';

DROP TABLE IF EXISTS `{prefix}translation`;
CREATE TABLE `{prefix}translation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lang_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '语言代码',
  `group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'common' COMMENT '分组: common/admin/frontend',
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '原文/翻译键',
  `translation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '译文',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lang_key` (`lang_code`,`group`,`key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='翻译表';

DROP TABLE IF EXISTS `{prefix}translation_memory`;
CREATE TABLE `{prefix}translation_memory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_text` text NOT NULL COMMENT '源文本',
  `target_text` text NOT NULL COMMENT '目标文本',
  `source_lang` varchar(10) NOT NULL COMMENT '源语言',
  `target_lang` varchar(10) NOT NULL COMMENT '目标语言',
  `context_type` varchar(50) DEFAULT '' COMMENT '上下文类型(content/field/lang_pack/template)',
  `context_id` int(11) DEFAULT '0' COMMENT '上下文ID',
  `quality_score` decimal(3,1) DEFAULT '0.0' COMMENT '翻译质量评分(0-5)',
  `use_count` int(11) DEFAULT '1' COMMENT '使用次数',
  `is_confirmed` tinyint(4) DEFAULT '0' COMMENT '是否人工确认:1是0否',
  `hash_value` varchar(64) NOT NULL COMMENT '源文本哈希(用于快速匹配)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_hash` (`hash_value`,`source_lang`,`target_lang`),
  KEY `idx_source_lang` (`source_lang`),
  KEY `idx_target_lang` (`target_lang`),
  KEY `idx_quality` (`quality_score`),
  KEY `idx_use_count` (`use_count`),
  KEY `idx_confirmed` (`is_confirmed`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='翻译记忆表';

DROP TABLE IF EXISTS `{prefix}translation_project`;
CREATE TABLE `{prefix}translation_project` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}translation_project_item`;
CREATE TABLE `{prefix}translation_project_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}translation_task`;
CREATE TABLE `{prefix}translation_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_content_id` int(11) NOT NULL COMMENT '源内容ID',
  `source_lang` varchar(10) NOT NULL COMMENT '源语言',
  `target_lang` varchar(10) NOT NULL COMMENT '目标语言',
  `task_type` varchar(20) DEFAULT 'content' COMMENT '任务类型(content/template/plugin/system)',
  `status` varchar(20) DEFAULT 'pending' COMMENT '状态(pending/translating/reviewing/completed/rejected)',
  `translator_id` int(11) DEFAULT '0' COMMENT '翻译人员ID(0=AI翻译)',
  `reviewer_id` int(11) DEFAULT '0' COMMENT '审核人员ID(0=不需要审核)',
  `priority` varchar(10) DEFAULT 'normal' COMMENT '优先级(high/normal/low)',
  `deadline` datetime DEFAULT NULL COMMENT '截止时间',
  `ai_translation` text COMMENT 'AI翻译结果',
  `human_translation` text COMMENT '人工翻译结果',
  `translation_quality` decimal(3,2) DEFAULT '0.00' COMMENT '翻译质量评分(0-1)',
  `review_comment` text COMMENT '审核意见',
  `quality_auto_score` decimal(3,2) DEFAULT '0.00' COMMENT 'AI自动质量评分',
  `quality_human_score` decimal(3,2) DEFAULT '0.00' COMMENT '人工质量评分',
  `translation_memory_id` bigint(20) DEFAULT '0' COMMENT '翻译记忆库ID',
  `terminology_issues` text COMMENT '术语问题(JSON)',
  `completed_time` datetime DEFAULT NULL COMMENT '完成时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_content` (`source_content_id`,`source_lang`,`target_lang`),
  KEY `idx_status` (`status`),
  KEY `idx_translator` (`translator_id`),
  KEY `idx_reviewer` (`reviewer_id`),
  KEY `idx_priority` (`priority`),
  KEY `idx_deadline` (`deadline`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='翻译任务表';

DROP TABLE IF EXISTS `{prefix}user`;
CREATE TABLE `{prefix}user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `email_verified` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '邮箱是否已验证',
  `register_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '注册IP',
  `register_source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '注册来源: username|email',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '头像',
  `bio` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '个人简介',
  `notify_settings` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '通知偏好设置 JSON',
  `lang_pref` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '偏好语言代码',
  `role_id` tinyint(4) NOT NULL DEFAULT '3' COMMENT '角色:1超管/2管理员/3编辑',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `last_login_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `last_login_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '最后登录IP',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

INSERT INTO `{prefix}user` VALUES (1,'admin','admin@aicms.com',0,'','','$2y$12$BoP4lCWrvqlrujRh.WL6mucqyNiXNy777ksfxV6MOCC6sHxenOGZW','超级管理员','','',NULL,'',1,1,1784880542,'127.0.0.1',1776933035,1784880542);
DROP TABLE IF EXISTS `{prefix}user_chapter`;
CREATE TABLE `{prefix}user_chapter` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL,
  `content_id` int(10) unsigned NOT NULL COMMENT '章节content_id',
  `parent_id` int(10) unsigned DEFAULT '0' COMMENT '父内容id',
  `order_sn` varchar(50) DEFAULT '' COMMENT '订单号',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '购买价格',
  `create_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_chapter` (`member_id`,`content_id`),
  KEY `idx_content` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户已购章节';

DROP TABLE IF EXISTS `{prefix}user_coupon`;
CREATE TABLE `{prefix}user_coupon` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL,
  `template_id` int(10) unsigned NOT NULL,
  `code` varchar(32) NOT NULL COMMENT '优惠券码',
  `coupon_type` enum('reduce','discount','free_shipping') NOT NULL COMMENT '冗余:券类型',
  `condition_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '冗余:门槛金额',
  `reduce_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '冗余:减免金额/折扣率',
  `status` tinyint(4) DEFAULT '0' COMMENT '0未使用/1已使用/2已过期/3已作废/4已退还',
  `used_at` int(10) unsigned DEFAULT '0' COMMENT '使用时间',
  `used_order_id` int(10) unsigned DEFAULT '0' COMMENT '使用的订单ID',
  `expire_at` int(10) unsigned DEFAULT '0' COMMENT '过期时间',
  `create_time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_member_status` (`member_id`,`status`),
  KEY `idx_expire` (`expire_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户优惠券表';

DROP TABLE IF EXISTS `{prefix}user_segment`;
CREATE TABLE `{prefix}user_segment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}visit_log`;
CREATE TABLE `{prefix}visit_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID(0为首页/列表)',
  `visitor_id` int(10) unsigned DEFAULT '0' COMMENT '会员id,0=游客',
  `session_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'IP地址',
  `ua` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'User-Agent',
  `visit_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '访问时间',
  `page_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '访问页面',
  `source_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'direct' COMMENT '来源类型: direct/search/social/referral/other',
  `referrer` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '来源URL',
  `event_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'visit' COMMENT '事件类型: visit/share/click',
  `share_channel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '分享渠道: wechat/weibo/qq/copy',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_visit_time` (`visit_time`),
  KEY `idx_visitor_time` (`visitor_id`,`visit_time`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_session_id` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1020 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='访问日志表';

INSERT INTO `{prefix}visit_log` VALUES (1,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778167013,'http://localhost:3000/member/login','direct','','visit',''),(2,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778167110,'http://localhost:3000/member/profile','direct','','visit',''),(3,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778167113,'http://localhost:3000/points','direct','','visit',''),(4,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778167131,'http://localhost:3000/member/profile','direct','','visit',''),(5,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778167135,'http://localhost:3000/member/points','direct','','visit',''),(6,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778168769,'http://localhost:3000/member/login','direct','','visit',''),(7,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778168785,'http://localhost:3000/member/profile','direct','','visit',''),(8,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778168789,'http://localhost:3000/points','direct','','visit',''),(9,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778168979,'http://localhost:3000/signin','direct','','visit',''),(10,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169043,'http://localhost:3000/signin','direct','','visit',''),(11,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169047,'http://localhost:3000/points','direct','','visit',''),(12,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169049,'http://localhost:3000/signin','direct','','visit',''),(13,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169053,'http://localhost:3000/member/points','direct','','visit',''),(14,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169055,'http://localhost:3000/signin','direct','','visit',''),(15,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169111,'http://localhost:3000/member/login','direct','','visit',''),(16,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169112,'http://localhost:3000/member/login','direct','','visit',''),(17,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169121,'http://localhost:3000/member/profile','direct','','visit',''),(18,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169125,'http://localhost:3000/points','direct','','visit',''),(19,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169128,'http://localhost:3000/signin','direct','','visit',''),(20,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169234,'http://localhost:3000/member/login','direct','','visit',''),(21,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169236,'http://localhost:3000/member/login','direct','','visit',''),(22,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169244,'http://localhost:3000/member/profile','direct','','visit',''),(23,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169247,'http://localhost:3000/points','direct','','visit',''),(24,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169250,'http://localhost:3000/signin','direct','','visit',''),(25,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169258,'http://localhost:3000/signin','direct','','visit',''),(26,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169354,'http://localhost:3000/member/login','direct','','visit',''),(27,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169372,'http://localhost:3000/member/profile','direct','','visit',''),(28,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169375,'http://localhost:3000/points','direct','','visit',''),(29,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169377,'http://localhost:3000/signin','direct','','visit',''),(30,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169522,'http://localhost:3000/signin','direct','','visit',''),(31,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169562,'http://localhost:3000/member/login','direct','','visit',''),(32,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169569,'http://localhost:3000/member/profile','direct','','visit',''),(33,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169573,'http://localhost:3000/points','direct','','visit',''),(34,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169575,'http://localhost:3000/signin','direct','','visit',''),(35,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169580,'http://localhost:3000/signin','direct','','visit',''),(36,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169581,'http://localhost:3000/signin','direct','','visit',''),(37,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169601,'http://localhost:3000/case','direct','','visit',''),(38,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169609,'http://localhost:3000/news','direct','','visit',''),(39,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169620,'http://localhost:3000/news?cate_id=3','direct','','visit',''),(40,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169777,'http://localhost:3000/points','direct','','visit',''),(41,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169781,'http://localhost:3000/signin','direct','','visit',''),(42,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778170485,'http://localhost:3000/signin','direct','','visit',''),(43,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778170493,'http://localhost:3000/points','direct','','visit',''),(44,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778171457,'http://localhost:3000/member/login','direct','','visit',''),(45,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778202945,'http://localhost:3000/member/login','direct','','visit',''),(46,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778208413,'http://localhost:3000/member/login','direct','','visit',''),(47,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778211648,'http://localhost:3000/member/login','direct','','visit',''),(48,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778212716,'http://localhost:3000/member/login','direct','','visit',''),(49,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778212718,'http://localhost:3000/member/login','direct','','visit',''),(50,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778218340,'http://localhost:3000/member/login','direct','','visit',''),(51,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778218341,'http://localhost:3000/product','direct','','visit',''),(52,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778218343,'http://localhost:3000/','direct','','visit',''),(53,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778218930,'http://localhost:3000/','direct','','visit',''),(54,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778228771,'http://localhost:3000/','direct','','visit',''),(55,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778235427,'http://localhost:3000/','direct','','visit',''),(56,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778235427,'http://localhost:3000/','direct','','visit',''),(57,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778237981,'http://localhost:3000/','direct','','visit',''),(58,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778244628,'http://localhost:3000/','direct','','visit',''),(59,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778244816,'http://localhost:3000/','direct','','visit',''),(60,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778244849,'http://localhost:3000/case','direct','','visit',''),(61,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778277418,'http://localhost:3000/case','direct','','visit',''),(62,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279178,'http://localhost:3000/case','direct','','visit',''),(63,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279184,'http://localhost:3000/points','direct','','visit',''),(64,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279187,'http://localhost:3000/member/login','direct','','visit',''),(65,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279197,'http://localhost:3000/member/profile','direct','','visit',''),(66,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279200,'http://localhost:3000/points','direct','','visit',''),(67,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279204,'http://localhost:3000/signin','direct','','visit',''),(68,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279217,'http://localhost:3000/member/points','direct','','visit',''),(69,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279219,'http://localhost:3000/signin','direct','','visit',''),(70,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279229,'http://localhost:3000/download','direct','','visit',''),(71,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279240,'http://localhost:3000/job','direct','','visit',''),(72,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778280779,'http://localhost:3000/case','direct','','visit',''),(73,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778280780,'http://localhost:3000/news','direct','','visit',''),(74,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778280782,'http://localhost:3000/','direct','','visit',''),(75,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778293952,'http://localhost:3000/','direct','','visit',''),(76,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778328585,'http://localhost:3000/','direct','','visit',''),(77,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778332579,'http://localhost:3000/','direct','','visit',''),(78,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778332582,'http://localhost:3000/case','direct','','visit',''),(79,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778332586,'http://localhost:3000/case','direct','','visit',''),(80,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778332587,'http://localhost:3000/download','direct','','visit',''),(81,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778332590,'http://localhost:3000/job','direct','','visit',''),(82,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778332592,'http://localhost:3000/member/login','direct','','visit',''),(83,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778332593,'http://localhost:3000/member/register','direct','','visit',''),(84,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778335388,'http://localhost:3000/member/register','direct','','visit',''),(85,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778335389,'http://localhost:3000/points','direct','','visit',''),(86,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778335391,'http://localhost:3000/member/login','direct','','visit',''),(87,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778335402,'http://localhost:3000/member/profile','direct','','visit',''),(88,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778335406,'http://localhost:3000/points','direct','','visit',''),(89,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778335408,'http://localhost:3000/signin','direct','','visit',''),(90,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778335583,'http://localhost:3000/member/points','direct','','visit',''),(91,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778335587,'http://localhost:3000/signin','direct','','visit',''),(92,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778335594,'http://localhost:3000/signin','direct','','visit',''),(93,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778338642,'http://localhost:3000/','direct','','visit',''),(94,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778338645,'http://localhost:3000/','direct','','visit',''),(95,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778339468,'http://localhost:3000/','direct','','visit',''),(96,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778339487,'http://localhost:3000/news','direct','','visit',''),(97,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778339492,'http://localhost:3000/points','direct','','visit',''),(98,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778339495,'http://localhost:3000/member/login','direct','','visit',''),(99,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778339503,'http://localhost:3000/member/profile','direct','','visit',''),(100,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778339507,'http://localhost:3000/points','direct','','visit',''),(101,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778339508,'http://localhost:3000/signin','direct','','visit',''),(102,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778339517,'http://localhost:3000/signin','direct','','visit',''),(103,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340047,'http://localhost:3000/member/login','direct','','visit',''),(104,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340056,'http://localhost:3000/member/profile','direct','','visit',''),(105,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340058,'http://localhost:3000/points','direct','','visit',''),(106,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340060,'http://localhost:3000/signin','direct','','visit',''),(107,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340067,'http://localhost:3000/signin','direct','','visit',''),(108,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340157,'http://localhost:3000/news','direct','','visit',''),(109,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340302,'http://localhost:3000/signin','direct','','visit',''),(110,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340308,'http://localhost:3000/signin','direct','','visit',''),(111,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340312,'http://localhost:3000/member/points','direct','','visit',''),(112,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340317,'http://localhost:3000/signin','direct','','visit',''),(113,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340322,'http://localhost:3000/points','direct','','visit',''),(114,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778348300,'http://localhost:3000/points','direct','','visit',''),(115,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778378968,'http://localhost:3000/product/1','direct','','visit',''),(116,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778379109,'http://localhost:3000/product/1','direct','','visit',''),(117,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778379247,'http://localhost:3000/product/1','direct','','visit',''),(118,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778379359,'http://localhost:3000/product/1','direct','','visit',''),(119,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778379376,'http://localhost:3000/product/1','direct','','visit',''),(120,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778379405,'http://localhost:3000/product/1','direct','','visit',''),(121,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778380301,'http://localhost:3000/points','direct','','visit',''),(122,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778380302,'http://localhost:3000/member/login','direct','','visit',''),(123,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778380339,'http://localhost:3000/','direct','','visit',''),(124,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778380482,'http://localhost:3000/','direct','','visit',''),(125,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778385624,'http://localhost:3000/','direct','','visit',''),(126,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778385631,'http://localhost:3000/','direct','','visit',''),(127,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778386175,'http://localhost:3000/product/1','direct','','visit',''),(128,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778387307,'http://localhost:3000/','direct','','visit',''),(129,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778387309,'http://localhost:3000/','direct','','visit',''),(130,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389278,'http://localhost:3000/product/1','direct','','visit',''),(131,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389394,'http://localhost:3000/','direct','','visit',''),(132,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389397,'http://localhost:3000/','direct','','visit',''),(133,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389398,'http://localhost:3000/','direct','','visit',''),(134,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389403,'http://localhost:3000/','direct','','visit',''),(135,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389420,'http://localhost:3000/product/1','direct','','visit',''),(136,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389424,'http://localhost:3000/','direct','','visit',''),(137,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389669,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(138,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389675,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(139,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389677,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(140,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389678,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(141,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389680,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(142,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389681,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(143,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389682,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(144,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389683,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(145,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389685,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(146,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389691,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news','visit',''),(147,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389712,'http://localhost:3000/product','referral','http://localhost:3000/news?cate_id=3','visit',''),(148,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390028,'http://localhost:3000/product','referral','http://localhost:3000/news?cate_id=3','visit',''),(149,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390035,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(150,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390040,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(151,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390042,'http://localhost:3000/case','referral','http://localhost:3000/product?cate_id=1','visit',''),(152,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390043,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(153,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390044,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(154,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390045,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(155,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390046,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(156,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390048,'http://localhost:3000/case?cate_id=2','referral','http://localhost:3000/case','visit',''),(157,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390051,'http://localhost:3000/case?cate_id=2','referral','http://localhost:3000/case','visit',''),(158,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390052,'http://localhost:3000/case?cate_id=2','referral','http://localhost:3000/case','visit',''),(159,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390053,'http://localhost:3000/product','referral','http://localhost:3000/case?cate_id=2','visit',''),(160,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390055,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(161,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390059,'http://localhost:3000/product','referral','http://localhost:3000/product?cate_id=1','visit',''),(162,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390060,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(163,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390347,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(164,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390351,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product?cate_id=1','visit',''),(165,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390354,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product?cate_id=1','visit',''),(166,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390357,'http://localhost:3000/case','referral','http://localhost:3000/product?cate_id=1','visit',''),(167,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390357,'http://localhost:3000/case?cate_id=2','referral','http://localhost:3000/case','visit',''),(168,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390360,'http://localhost:3000/case?cate_id=2','referral','http://localhost:3000/case','visit',''),(169,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390363,'http://localhost:3000/job','referral','http://localhost:3000/case?cate_id=2','visit',''),(170,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390367,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(171,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390374,'http://localhost:3000/points','referral','http://localhost:3000/job','visit',''),(172,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390375,'http://localhost:3000/points','referral','http://localhost:3000/points','visit',''),(173,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778391096,'http://localhost:3000/points','referral','http://localhost:3000/points','visit',''),(174,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778391097,'http://localhost:3000/','referral','http://localhost:3000/points','visit',''),(175,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778428868,'http://localhost:3000/','referral','http://localhost:3000/member/login','visit',''),(176,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778428870,'http://localhost:3000/','referral','http://localhost:3000/member/login','visit',''),(177,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778428887,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(178,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778428888,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(179,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778428909,'http://localhost:3000/','referral','http://localhost:3000/points','visit',''),(180,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778428910,'http://localhost:3000/','referral','http://localhost:3000/points','visit',''),(181,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778428910,'http://localhost:3000/','referral','http://localhost:3000/points','visit',''),(182,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778428910,'http://localhost:3000/','referral','http://localhost:3000/points','visit',''),(183,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778432810,'http://localhost:3000/member/login','direct','','visit',''),(184,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778432819,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(185,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778432822,'http://localhost:3000/points','referral','http://localhost:3000/member/profile','visit',''),(186,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778435282,'http://localhost:3000/member/level','direct','','visit',''),(187,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778435294,'http://localhost:3000/member/level','direct','','visit',''),(188,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778435309,'http://localhost:3000/points','referral','http://localhost:3000/member/level','visit',''),(189,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778435310,'http://localhost:3000/signin','referral','http://localhost:3000/points','visit',''),(190,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778435317,'http://localhost:3000/signin','referral','http://localhost:3000/signin','visit',''),(191,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778435321,'http://localhost:3000/','referral','http://localhost:3000/signin','visit',''),(192,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778435322,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(193,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436595,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product/1','visit',''),(194,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436599,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product/1','visit',''),(195,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436658,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(196,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436659,'http://localhost:3000/points','referral','http://localhost:3000/case','visit',''),(197,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436661,'http://localhost:3000/signin','referral','http://localhost:3000/points','visit',''),(198,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436668,'http://localhost:3000/member/profile','referral','http://localhost:3000/signin','visit',''),(199,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436670,'http://localhost:3000/member/login','referral','http://localhost:3000/member/profile','visit',''),(200,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436673,'http://localhost:3000/member/login','referral','http://localhost:3000/member/login','visit',''),(201,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436674,'http://localhost:3000/job','referral','http://localhost:3000/member/login','visit',''),(202,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436675,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(203,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436676,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(204,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436676,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(205,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436678,'http://localhost:3000/download','referral','http://localhost:3000/','visit',''),(206,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436678,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(207,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436681,'http://localhost:3000/product','referral','http://localhost:3000/product?cate_id=1','visit',''),(208,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436682,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(209,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436682,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(210,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436683,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(211,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436684,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(212,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436685,'http://localhost:3000/news','referral','http://localhost:3000/','visit',''),(213,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436685,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(214,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436688,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(215,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436689,'http://localhost:3000/','referral','http://localhost:3000/case','visit',''),(216,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437048,'http://localhost:3000/product','referral','http://localhost:3000/download','visit',''),(217,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437048,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(218,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437049,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(219,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437049,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(220,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437050,'http://localhost:3000/case','referral','http://localhost:3000/download','visit',''),(221,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437050,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(222,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437051,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(223,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437051,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(224,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437052,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(225,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437052,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(226,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437053,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(227,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437056,'http://localhost:3000/points','referral','http://localhost:3000/download','visit',''),(228,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437057,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(229,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437057,'http://localhost:3000/member/register','referral','http://localhost:3000/member/login','visit',''),(230,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437059,'http://localhost:3000/','referral','http://localhost:3000/member/register','visit',''),(231,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437060,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(232,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437205,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(233,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437205,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(234,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437266,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news','visit',''),(235,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778465650,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(236,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778465651,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(237,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778465652,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(238,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778465658,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(239,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778467022,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(240,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778467839,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(241,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471746,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(242,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471792,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(243,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471792,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(244,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471793,'http://localhost:3000/member/login','referral','http://localhost:3000/product','visit',''),(245,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471799,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(246,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471805,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/profile','visit',''),(247,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471810,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/profile','visit',''),(248,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471967,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/profile','visit',''),(249,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471981,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/profile','visit',''),(250,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471982,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/profile','visit',''),(251,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778473420,'http://localhost:3000/','referral','http://localhost:3000/admin/member_benefit/members','visit',''),(252,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778473422,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(253,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778473430,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(254,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778473434,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(255,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778475943,'http://localhost:3000/member/login','referral','http://localhost:3000/member/profile','visit',''),(256,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778475963,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(257,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778475979,'http://localhost:3000/member/exchange','direct','','visit',''),(258,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778475982,'http://localhost:3000/points','referral','http://localhost:3000/member/exchange','visit',''),(259,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778475983,'http://localhost:3000/signin','referral','http://localhost:3000/points','visit',''),(260,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778476438,'http://localhost:3000/','referral','http://localhost:3000/admin/member_benefit/members','visit',''),(261,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778476440,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(262,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477478,'http://localhost:3000/','referral','http://localhost:3000/admin','visit',''),(263,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477482,'http://localhost:3000/','referral','http://localhost:3000/admin','visit',''),(264,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477603,'http://localhost:3000/','referral','http://localhost:3000/admin','visit',''),(265,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477610,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(266,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477617,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(267,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477625,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(268,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477636,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(269,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477637,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(270,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477639,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(271,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477642,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(272,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477642,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(273,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477664,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(274,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477665,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(275,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477666,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(276,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477674,'http://localhost:3000/member/login','direct','','visit',''),(277,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477710,'http://localhost:3000/','referral','http://localhost:3000/admin','visit',''),(278,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778479144,'http://localhost:3000/member/login','direct','','visit',''),(279,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778479153,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(280,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778479157,'http://localhost:3000/member/level','direct','','visit',''),(281,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778479163,'http://localhost:3000/member/level','direct','','visit',''),(282,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480324,'http://localhost:3000/member/level','direct','','visit',''),(283,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480327,'http://localhost:3000/member/level','direct','','visit',''),(284,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480329,'http://localhost:3000/member/level','direct','','visit',''),(285,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480337,'http://localhost:3000/member/level','direct','','visit',''),(286,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480403,'http://localhost:3000/product','referral','http://localhost:3000/member/level','visit',''),(287,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480405,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(288,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480406,'http://localhost:3000/','referral','http://localhost:3000/case','visit',''),(289,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480409,'http://localhost:3000/news','referral','http://localhost:3000/','visit',''),(290,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480413,'http://localhost:3000/job','referral','http://localhost:3000/news','visit',''),(291,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480414,'http://localhost:3000/points','referral','http://localhost:3000/job','visit',''),(292,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480416,'http://localhost:3000/signin','referral','http://localhost:3000/points','visit',''),(293,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480421,'http://localhost:3000/member/points','referral','http://localhost:3000/signin','visit',''),(294,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480487,'http://localhost:3000/member/level','direct','','visit',''),(295,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480702,'http://localhost:3000/member/level','direct','','visit',''),(296,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480709,'http://localhost:3000/member/level','direct','','visit',''),(297,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480764,'http://localhost:3000/member/level','direct','','visit',''),(298,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480766,'http://localhost:3000/product','referral','http://localhost:3000/member/level','visit',''),(299,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480767,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(300,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480769,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(301,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480772,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(302,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480773,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(303,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480773,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(304,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480773,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(305,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480774,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(306,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480774,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(307,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480776,'http://localhost:3000/points','referral','http://localhost:3000/job','visit',''),(308,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480777,'http://localhost:3000/','referral','http://localhost:3000/points','visit',''),(309,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480777,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(310,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480791,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(311,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480791,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(312,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480792,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(313,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480792,'http://localhost:3000/product','referral','http://localhost:3000/download','visit',''),(314,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480793,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(315,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480795,'http://localhost:3000/news','referral','http://localhost:3000/','visit',''),(316,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480797,'http://localhost:3000/job','referral','http://localhost:3000/news','visit',''),(317,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480797,'http://localhost:3000/','referral','http://localhost:3000/job','visit',''),(318,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480797,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(319,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480798,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(320,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480799,'http://localhost:3000/points','referral','http://localhost:3000/','visit',''),(321,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480800,'http://localhost:3000/member/profile','referral','http://localhost:3000/points','visit',''),(322,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480802,'http://localhost:3000/member/points','referral','http://localhost:3000/member/profile','visit',''),(323,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480804,'http://localhost:3000/member/exchange','referral','http://localhost:3000/member/points','visit',''),(324,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480806,'http://localhost:3000/points','referral','http://localhost:3000/member/exchange','visit',''),(325,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778520173,'http://localhost:3000/','referral','http://localhost:3000/admin/rating/index','visit',''),(326,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778520174,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(327,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778520175,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(328,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778520175,'http://localhost:3000/news','referral','http://localhost:3000/product','visit',''),(329,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778520176,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(330,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778520176,'http://localhost:3000/','referral','http://localhost:3000/download','visit',''),(331,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778520177,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(332,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778520179,'http://localhost:3000/points','referral','http://localhost:3000/','visit',''),(333,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521029,'http://localhost:3000/product','referral','http://localhost:3000/points','visit',''),(334,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521029,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(335,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521031,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(336,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521037,'http://localhost:3000/','referral','http://localhost:3000/news','visit',''),(337,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521037,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(338,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521038,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(339,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521038,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(340,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521039,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(341,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521040,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(342,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521040,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(343,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521043,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(344,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521042,'http://localhost:3000/','referral','http://localhost:3000/job','visit',''),(345,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521043,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(346,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521046,'http://localhost:3000/member/register','referral','http://localhost:3000/','visit',''),(347,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521047,'http://localhost:3000/member/register','referral','http://localhost:3000/member/register','visit',''),(348,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521048,'http://localhost:3000/member/login','referral','http://localhost:3000/member/register','visit',''),(349,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521049,'http://localhost:3000/download','referral','http://localhost:3000/member/login','visit',''),(350,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521049,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(351,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521050,'http://localhost:3000/product','referral','http://localhost:3000/job','visit',''),(352,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521050,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(353,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526909,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(354,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526909,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(355,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526909,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(356,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526910,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(357,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526910,'http://localhost:3000/download','referral','http://localhost:3000/job','visit',''),(358,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526911,'http://localhost:3000/news','referral','http://localhost:3000/download','visit',''),(359,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526911,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(360,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526912,'http://localhost:3000/case','referral','http://localhost:3000/download','visit',''),(361,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526912,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(362,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526913,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(363,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526914,'http://localhost:3000/job','referral','http://localhost:3000/','visit',''),(364,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526916,'http://localhost:3000/news','referral','http://localhost:3000/download','visit',''),(365,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526916,'http://localhost:3000/download','referral','http://localhost:3000/job','visit',''),(366,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526916,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(367,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526917,'http://localhost:3000/points','referral','http://localhost:3000/download','visit',''),(368,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526917,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(369,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526918,'http://localhost:3000/member/register','referral','http://localhost:3000/member/login','visit',''),(370,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526919,'http://localhost:3000/case','referral','http://localhost:3000/member/register','visit',''),(371,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526919,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(372,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526920,'http://localhost:3000/product','direct','','visit',''),(373,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526920,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(374,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527266,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(375,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527266,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(376,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527267,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(377,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527267,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(378,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527268,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(379,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527269,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(380,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527270,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(381,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527270,'http://localhost:3000/member/register','referral','http://localhost:3000/member/login','visit',''),(382,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527271,'http://localhost:3000/points','referral','http://localhost:3000/member/register','visit',''),(383,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527272,'http://localhost:3000/signin','referral','http://localhost:3000/points','visit',''),(384,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527282,'http://localhost:3000/member/points','referral','http://localhost:3000/signin','visit',''),(385,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527283,'http://localhost:3000/points','referral','http://localhost:3000/member/points','visit',''),(386,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527284,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(387,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548128,'http://localhost:3000/','direct','','visit',''),(388,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548130,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(389,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548145,'http://localhost:3000/points','referral','http://localhost:3000/member/points','visit',''),(390,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548145,'http://localhost:3000/member/points','referral','http://localhost:3000/signin','visit',''),(391,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548209,'http://localhost:3000/','direct','','visit',''),(392,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548211,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(393,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548212,'http://localhost:3000/job','referral','http://localhost:3000/case','visit',''),(394,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548213,'http://localhost:3000/download','referral','http://localhost:3000/job','visit',''),(395,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548213,'http://localhost:3000/news','referral','http://localhost:3000/download','visit',''),(396,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548214,'http://localhost:3000/job','referral','http://localhost:3000/news','visit',''),(397,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548216,'http://localhost:3000/points','referral','http://localhost:3000/job','visit',''),(398,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548217,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(399,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548217,'http://localhost:3000/member/register','referral','http://localhost:3000/member/login','visit',''),(400,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548219,'http://localhost:3000/','referral','http://localhost:3000/member/register','visit',''),(401,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548220,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(402,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548338,'http://localhost:3000/','direct','','visit',''),(403,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548340,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(404,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548340,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(405,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548341,'http://localhost:3000/','referral','http://localhost:3000/case','visit',''),(406,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548341,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(407,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548341,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(408,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548342,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(409,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548342,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(410,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548342,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(411,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548396,'http://localhost:3000/case','referral','http://localhost:3000/member/register','visit',''),(412,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548396,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(413,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548397,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(414,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548398,'http://localhost:3000/member/login','referral','http://localhost:3000/product?cate_id=1','visit',''),(415,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548405,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(416,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548409,'http://localhost:3000/points','referral','http://localhost:3000/member/profile','visit',''),(417,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548410,'http://localhost:3000/signin','referral','http://localhost:3000/points','visit',''),(418,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548414,'http://localhost:3000/signin','referral','http://localhost:3000/signin','visit',''),(419,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548418,'http://localhost:3000/member/points','referral','http://localhost:3000/signin','visit',''),(420,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548421,'http://localhost:3000/member/points','referral','http://localhost:3000/signin','visit',''),(421,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548421,'http://localhost:3000/member/points','referral','http://localhost:3000/signin','visit',''),(422,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548425,'http://localhost:3000/member/exchange','referral','http://localhost:3000/member/points','visit',''),(423,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548427,'http://localhost:3000/member/points','referral','http://localhost:3000/member/exchange','visit',''),(424,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548428,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/points','visit',''),(425,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548429,'http://localhost:3000/news','referral','http://localhost:3000/member/profile','visit',''),(426,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548432,'http://localhost:3000/news','referral','http://localhost:3000/member/profile','visit',''),(427,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548433,'http://localhost:3000/news','referral','http://localhost:3000/member/profile','visit',''),(428,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548433,'http://localhost:3000/news','referral','http://localhost:3000/member/profile','visit',''),(429,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548434,'http://localhost:3000/news','referral','http://localhost:3000/member/profile','visit',''),(430,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548434,'http://localhost:3000/news','referral','http://localhost:3000/member/profile','visit',''),(431,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548434,'http://localhost:3000/news','referral','http://localhost:3000/member/profile','visit',''),(432,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548436,'http://localhost:3000/news','referral','http://localhost:3000/member/profile','visit',''),(433,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548436,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(434,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548437,'http://localhost:3000/download','referral','http://localhost:3000/case','visit',''),(435,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548437,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(436,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548438,'http://localhost:3000/product','referral','http://localhost:3000/job','visit',''),(437,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548438,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(438,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548439,'http://localhost:3000/job','referral','http://localhost:3000/product','visit',''),(439,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548440,'http://localhost:3000/download','referral','http://localhost:3000/job','visit',''),(440,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548441,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(441,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548442,'http://localhost:3000/news','referral','http://localhost:3000/job','visit',''),(442,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548442,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(443,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548443,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(444,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548444,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(445,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548448,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(446,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548449,'http://localhost:3000/member/register','referral','http://localhost:3000/member/login','visit',''),(447,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548449,'http://localhost:3000/member/login','referral','http://localhost:3000/member/register','visit',''),(448,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548450,'http://localhost:3000/','referral','http://localhost:3000/member/login','visit',''),(449,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548451,'http://localhost:3000/','referral','http://localhost:3000/member/login','visit',''),(450,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548452,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(451,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548453,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(452,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548453,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(453,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548454,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(454,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548455,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(455,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548455,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(456,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548456,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(457,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548459,'http://localhost:3000/download','referral','http://localhost:3000/','visit',''),(458,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548459,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(459,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548461,'http://localhost:3000/job?cate_id=5','referral','http://localhost:3000/job','visit',''),(460,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548461,'http://localhost:3000/job?cate_id=5','referral','http://localhost:3000/job?cate_id=5','visit',''),(461,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548466,'http://localhost:3000/member/exchange','referral','http://localhost:3000/job?cate_id=5','visit',''),(462,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548467,'http://localhost:3000/member/points','referral','http://localhost:3000/member/exchange','visit',''),(463,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548468,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/points','visit',''),(464,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548602,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/points','visit',''),(465,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548602,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/points','visit',''),(466,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548602,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/points','visit',''),(467,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548602,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/points','visit',''),(468,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548603,'http://localhost:3000/news','referral','http://localhost:3000/member/profile','visit',''),(469,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548603,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(470,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548604,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(471,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548604,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(472,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548606,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(473,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548749,'http://localhost:3000/member/points','referral','http://localhost:3000/signin','visit',''),(474,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548752,'http://localhost:3000/signin','referral','http://localhost:3000/member/points','visit',''),(475,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548898,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(476,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548898,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(477,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548898,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(478,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548898,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(479,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548899,'http://localhost:3000/download','referral','http://localhost:3000/','visit',''),(480,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548900,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(481,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548901,'http://localhost:3000/','referral','http://localhost:3000/job','visit',''),(482,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548907,'http://localhost:3000/signin','referral','http://localhost:3000/member/points','visit',''),(483,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548908,'http://localhost:3000/points','referral','http://localhost:3000/signin','visit',''),(484,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548909,'http://localhost:3000/','referral','http://localhost:3000/points','visit',''),(485,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548909,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(486,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778549372,'http://localhost:3000/','referral','http://localhost:3000/job','visit',''),(487,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778549373,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(488,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778549373,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(489,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778549373,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(490,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778549374,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(491,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778549374,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(492,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778549375,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(493,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778549375,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(494,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778549375,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(495,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778551014,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(496,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778551051,'http://localhost:3000/','direct','','visit',''),(497,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553056,'http://localhost:3000/','referral','http://localhost:3000/admin/system/config?tab=basic','visit',''),(498,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553058,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(499,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553059,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(500,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553061,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product?cate_id=1','visit',''),(501,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553062,'http://localhost:3000/download','referral','http://localhost:3000/product?cate_id=1','visit',''),(502,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553062,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(503,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553063,'http://localhost:3000/job?cate_id=5','referral','http://localhost:3000/job','visit',''),(504,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553502,'http://localhost:3000/case','referral','http://localhost:3000/job?cate_id=5','visit',''),(505,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553502,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(506,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553502,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(507,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553502,'http://localhost:3000/','referral','http://localhost:3000/news','visit',''),(508,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553503,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(509,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553509,'http://localhost:3000/','direct','','visit',''),(510,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553513,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(511,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553513,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(512,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553517,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(513,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553520,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(514,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553521,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(515,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553526,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(516,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553527,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(517,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553529,'http://localhost:3000/download','referral','http://localhost:3000/','visit',''),(518,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553529,'http://localhost:3000/news','referral','http://localhost:3000/download','visit',''),(519,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553530,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(520,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553530,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(521,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553530,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(522,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553531,'http://localhost:3000/download','referral','http://localhost:3000/','visit',''),(523,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553531,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(524,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553532,'http://localhost:3000/news','referral','http://localhost:3000/job','visit',''),(525,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553532,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(526,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553533,'http://localhost:3000/download','referral','http://localhost:3000/case','visit',''),(527,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553533,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(528,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553534,'http://localhost:3000/points','referral','http://localhost:3000/download','visit',''),(529,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553537,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(530,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553539,'http://localhost:3000/member/register','referral','http://localhost:3000/member/login','visit',''),(531,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553540,'http://localhost:3000/member/login','referral','http://localhost:3000/member/register','visit',''),(532,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553557,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(533,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553560,'http://localhost:3000/job','referral','http://localhost:3000/member/profile','visit',''),(534,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553566,'http://localhost:3000/member/login','referral','http://localhost:3000/job','visit',''),(535,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553567,'http://localhost:3000/job','referral','http://localhost:3000/member/login','visit',''),(536,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554164,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(537,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554165,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(538,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554165,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(539,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554166,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(540,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554166,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(541,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554167,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(542,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554167,'http://localhost:3000/','referral','http://localhost:3000/case','visit',''),(543,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554168,'http://localhost:3000/news','referral','http://localhost:3000/','visit',''),(544,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554169,'http://localhost:3000/','referral','http://localhost:3000/news','visit',''),(545,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554228,'http://localhost:3000/job','referral','http://localhost:3000/member/login','visit',''),(546,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554229,'http://localhost:3000/job','referral','http://localhost:3000/member/login','visit',''),(547,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554229,'http://localhost:3000/job','referral','http://localhost:3000/member/login','visit',''),(548,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554230,'http://localhost:3000/job','referral','http://localhost:3000/member/login','visit',''),(549,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554230,'http://localhost:3000/job','referral','http://localhost:3000/member/login','visit',''),(550,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554230,'http://localhost:3000/job','referral','http://localhost:3000/member/login','visit',''),(551,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554230,'http://localhost:3000/job','referral','http://localhost:3000/member/login','visit',''),(552,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554232,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(553,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554243,'http://localhost:3000/','referral','http://localhost:3000/news','visit',''),(554,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554244,'http://localhost:3000/download','referral','http://localhost:3000/','visit',''),(555,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554245,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(556,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554246,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(557,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554247,'http://localhost:3000/download','referral','http://localhost:3000/job','visit',''),(558,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554248,'http://localhost:3000/points','referral','http://localhost:3000/download','visit',''),(559,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554249,'http://localhost:3000/news','referral','http://localhost:3000/points','visit',''),(560,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554249,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(561,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554250,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(562,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554250,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(563,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554251,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(564,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554251,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(565,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554252,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(566,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554252,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(567,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554587,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(568,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554587,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news','visit',''),(569,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554736,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(570,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554737,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(571,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554737,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(572,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554737,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(573,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554876,'http://localhost:3000/','referral','http://localhost:3000/admin','visit',''),(574,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554877,'http://localhost:3000/','referral','http://localhost:3000/admin','visit',''),(575,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554879,'http://localhost:3000/','referral','http://localhost:3000/admin','visit',''),(576,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554880,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(577,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554880,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(578,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554881,'http://localhost:3000/news','referral','http://localhost:3000/product','visit',''),(579,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554881,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(580,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554882,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(581,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554883,'http://localhost:3000/member/login','referral','http://localhost:3000/job','visit',''),(582,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554896,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(583,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554898,'http://localhost:3000/job','referral','http://localhost:3000/member/profile','visit',''),(584,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554899,'http://localhost:3000/download','referral','http://localhost:3000/job','visit',''),(585,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554900,'http://localhost:3000/news','referral','http://localhost:3000/download','visit',''),(586,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554900,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(587,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554901,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(588,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554901,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(589,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554903,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(590,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554904,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(591,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554904,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(592,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554905,'http://localhost:3000/download','referral','http://localhost:3000/product','visit',''),(593,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554905,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(594,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554906,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(595,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554906,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(596,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554907,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(597,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554907,'http://localhost:3000/product','referral','http://localhost:3000/job','visit',''),(598,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554908,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(599,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554909,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(600,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554909,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(601,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554909,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(602,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554910,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(603,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554910,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(604,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554910,'http://localhost:3000/news','referral','http://localhost:3000/job','visit',''),(605,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554911,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(606,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554912,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(607,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554912,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(608,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554916,'http://localhost:3000/','direct','','visit',''),(609,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554920,'http://localhost:3000/news','referral','http://localhost:3000/','visit',''),(610,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554921,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(611,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554922,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(612,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554923,'http://localhost:3000/job','referral','http://localhost:3000/product','visit',''),(613,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554924,'http://localhost:3000/download','referral','http://localhost:3000/job','visit',''),(614,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554925,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(615,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554925,'http://localhost:3000/news','referral','http://localhost:3000/download','visit',''),(616,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554925,'http://localhost:3000/','referral','http://localhost:3000/case','visit',''),(617,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554929,'http://localhost:3000/points','referral','http://localhost:3000/','visit',''),(618,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554929,'http://localhost:3000/','referral','http://localhost:3000/points','visit',''),(619,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554932,'http://localhost:3000/','referral','http://localhost:3000/points','visit',''),(620,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554932,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(621,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554939,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(622,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554941,'http://localhost:3000/job','referral','http://localhost:3000/member/profile','visit',''),(623,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554955,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(624,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554955,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(625,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554956,'http://localhost:3000/job','referral','http://localhost:3000/case','visit',''),(626,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554957,'http://localhost:3000/download','referral','http://localhost:3000/job','visit',''),(627,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554958,'http://localhost:3000/news','referral','http://localhost:3000/download','visit',''),(628,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554959,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(629,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554959,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(630,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554959,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(631,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554960,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(632,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554960,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(633,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554961,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(634,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556394,'http://localhost:3000/','direct','','visit',''),(635,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556397,'http://localhost:3000/','direct','','visit',''),(636,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556399,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(637,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556400,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(638,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556401,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(639,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556404,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(640,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556407,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(641,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556408,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(642,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556410,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(643,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556410,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(644,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556410,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(645,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556413,'http://localhost:3000/points','referral','http://localhost:3000/','visit',''),(646,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556414,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(647,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556421,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(648,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556421,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/profile','visit',''),(649,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556421,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/profile','visit',''),(650,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556422,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/profile','visit',''),(651,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556422,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/profile','visit',''),(652,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556423,'http://localhost:3000/job','referral','http://localhost:3000/member/profile','visit',''),(653,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556423,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(654,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556423,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(655,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556423,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(656,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556424,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(657,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556424,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(658,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556424,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(659,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556424,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(660,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556424,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(661,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556425,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(662,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556425,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(663,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556426,'http://localhost:3000/news','referral','http://localhost:3000/job','visit',''),(664,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556426,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(665,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556426,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(666,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556426,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(667,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556426,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(668,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556427,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(669,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556427,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(670,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556427,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(671,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556427,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(672,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556427,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(673,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556428,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(674,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556428,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(675,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556429,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(676,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556429,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(677,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556429,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(678,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556429,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(679,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556429,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(680,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556430,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(681,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556430,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(682,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556430,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(683,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556430,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(684,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556430,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(685,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556431,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(686,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556431,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(687,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556432,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(688,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556432,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(689,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556432,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(690,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556432,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(691,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556432,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(692,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556433,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(693,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556433,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(694,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556433,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(695,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556433,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(696,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556433,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(697,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556434,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(698,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556434,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(699,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556434,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(700,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556435,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(701,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556435,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(702,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556435,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(703,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556435,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(704,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556436,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(705,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556436,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(706,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556436,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(707,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556436,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(708,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556436,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(709,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556436,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(710,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556437,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(711,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556437,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(712,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556438,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(713,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556438,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(714,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556438,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(715,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556438,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(716,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556438,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(717,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556439,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(718,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556439,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(719,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556439,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(720,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556439,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(721,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556440,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(722,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556440,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(723,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556441,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(724,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556441,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(725,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556441,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(726,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556441,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(727,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556441,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(728,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556442,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(729,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556442,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(730,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556442,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(731,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556442,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(732,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556442,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(733,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556443,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(734,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556444,'http://localhost:3000/','referral','http://localhost:3000/download','visit',''),(735,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556444,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(736,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556444,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(737,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556445,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(738,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556445,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(739,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556445,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(740,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556445,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(741,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556445,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(742,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556446,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(743,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556446,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(744,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556447,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(745,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556447,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(746,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556447,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(747,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556447,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(748,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556447,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(749,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556448,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(750,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556448,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(751,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556448,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(752,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556448,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(753,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556449,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(754,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556449,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(755,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556450,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(756,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556450,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(757,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556450,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(758,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556451,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(759,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556451,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(760,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556451,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(761,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556452,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(762,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556452,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(763,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556453,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(764,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556453,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(765,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556453,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(766,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556453,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(767,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556454,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(768,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556454,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(769,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556454,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(770,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556455,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(771,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556455,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(772,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556456,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(773,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556456,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(774,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556456,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(775,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556457,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(776,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556457,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(777,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556457,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(778,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556457,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(779,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556458,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(780,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556459,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(781,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556459,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(782,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556459,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(783,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556459,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(784,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556460,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(785,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556460,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(786,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556461,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(787,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556461,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(788,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556462,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(789,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556462,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(790,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556462,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(791,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556463,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(792,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556463,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(793,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556463,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(794,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556464,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(795,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556464,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(796,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556465,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(797,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556465,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(798,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556465,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(799,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556465,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(800,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556466,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(801,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556466,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(802,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556466,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(803,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556466,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(804,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556466,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(805,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556467,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(806,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556467,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(807,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556468,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(808,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556676,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news','visit',''),(809,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556676,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(810,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556676,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(811,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556677,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(812,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556677,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(813,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556678,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(814,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556678,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(815,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556678,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(816,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556678,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(817,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556679,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(818,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556679,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(819,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556679,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(820,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556679,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(821,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556680,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(822,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556680,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(823,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556681,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(824,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556681,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(825,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556681,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(826,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556682,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(827,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556682,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(828,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556682,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(829,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556682,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(830,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556683,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(831,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556892,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(832,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556895,'http://localhost:3000/case','referral','http://localhost:3000/news?cate_id=3','visit',''),(833,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556896,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(834,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556899,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(835,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556900,'http://localhost:3000/product','referral','http://localhost:3000/download','visit',''),(836,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556902,'http://localhost:3000/download','referral','http://localhost:3000/product','visit',''),(837,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556904,'http://localhost:3000/news','referral','http://localhost:3000/download','visit',''),(838,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556904,'http://localhost:3000/job','referral','http://localhost:3000/news','visit',''),(839,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556907,'http://localhost:3000/','referral','http://localhost:3000/job','visit',''),(840,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556909,'http://localhost:3000/news','referral','http://localhost:3000/','visit',''),(841,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556910,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(842,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556911,'http://localhost:3000/case','referral','http://localhost:3000/download','visit',''),(843,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556911,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(844,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556913,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(845,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556917,'http://localhost:3000/points','referral','http://localhost:3000/product?cate_id=1','visit',''),(846,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556920,'http://localhost:3000/signin','referral','http://localhost:3000/points','visit',''),(847,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556924,'http://localhost:3000/news','referral','http://localhost:3000/signin','visit',''),(848,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556925,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(849,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556926,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(850,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556928,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(851,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556928,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(852,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556932,'http://localhost:3000/member/points','referral','http://localhost:3000/','visit',''),(853,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556935,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/points','visit',''),(854,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556938,'http://localhost:3000/member/exchange','referral','http://localhost:3000/member/profile','visit',''),(855,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556939,'http://localhost:3000/job','referral','http://localhost:3000/member/exchange','visit',''),(856,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556943,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(857,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778565942,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(858,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778565945,'http://localhost:3000/job?cate_id=5','referral','http://localhost:3000/job','visit',''),(859,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778565948,'http://localhost:3000/case','referral','http://localhost:3000/job?cate_id=5','visit',''),(860,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778565948,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(861,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778565949,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(862,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778566098,'http://localhost:3000/download','referral','http://localhost:3000/','visit',''),(863,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778566099,'http://localhost:3000/news','referral','http://localhost:3000/download','visit',''),(864,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778566101,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(865,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778566104,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(866,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778566123,'http://localhost:3000/download','referral','http://localhost:3000/','visit',''),(867,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778566125,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(868,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778566904,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(869,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778569897,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(870,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778574443,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(871,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778574444,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(872,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778576724,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(873,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778579123,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(874,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778579488,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(875,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778579492,'http://localhost:3000/news','referral','http://localhost:3000/','visit',''),(876,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778579492,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(877,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778579494,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(878,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778579497,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(879,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778580992,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(880,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778583377,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(881,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778583426,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(882,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778583461,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(883,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778583684,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(884,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778648899,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(885,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) CodeBuddyCN/1.100.0 Chrome/132.0.6834.210 Electron/34.5.1 Safari/537.36',1778650121,'http://localhost:3000/','direct','','visit',''),(886,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) CodeBuddyCN/1.100.0 Chrome/132.0.6834.210 Electron/34.5.1 Safari/537.36',1778651269,'http://localhost:3000/','direct','','visit',''),(887,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651277,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(888,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651281,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(889,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651284,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(890,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651286,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(891,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651287,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(892,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651289,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(893,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651290,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(894,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651293,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(895,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651294,'http://localhost:3000/download','referral','http://localhost:3000/job','visit',''),(896,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651296,'http://localhost:3000/points','referral','http://localhost:3000/download','visit',''),(897,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651297,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(898,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651298,'http://localhost:3000/member/register','referral','http://localhost:3000/member/login','visit',''),(899,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651299,'http://localhost:3000/news','referral','http://localhost:3000/member/register','visit',''),(900,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651300,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(901,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) CodeBuddyCN/1.100.0 Chrome/132.0.6834.210 Electron/34.5.1 Safari/537.36',1778651310,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(902,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) CodeBuddyCN/1.100.0 Chrome/132.0.6834.210 Electron/34.5.1 Safari/537.36',1778651501,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(903,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) CodeBuddyCN/1.100.0 Chrome/132.0.6834.210 Electron/34.5.1 Safari/537.36',1778651850,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(904,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651857,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(905,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651860,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(906,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651861,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(907,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652024,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(908,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) CodeBuddyCN/1.100.0 Chrome/132.0.6834.210 Electron/34.5.1 Safari/537.36',1778652080,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(909,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) CodeBuddyCN/1.100.0 Chrome/132.0.6834.210 Electron/34.5.1 Safari/537.36',1778652568,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(910,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) CodeBuddyCN/1.100.0 Chrome/132.0.6834.210 Electron/34.5.1 Safari/537.36',1778652576,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(911,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) CodeBuddyCN/1.100.0 Chrome/132.0.6834.210 Electron/34.5.1 Safari/537.36',1778652579,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(912,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) CodeBuddyCN/1.100.0 Chrome/132.0.6834.210 Electron/34.5.1 Safari/537.36',1778652719,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(913,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) CodeBuddyCN/1.100.0 Chrome/132.0.6834.210 Electron/34.5.1 Safari/537.36',1778652720,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(914,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) CodeBuddyCN/1.100.0 Chrome/132.0.6834.210 Electron/34.5.1 Safari/537.36',1778652721,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(915,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) CodeBuddyCN/1.100.0 Chrome/132.0.6834.210 Electron/34.5.1 Safari/537.36',1778652723,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(916,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652749,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(917,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652750,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(918,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652756,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(919,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652757,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(920,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652759,'http://localhost:3000/case','referral','http://localhost:3000/download','visit',''),(921,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652759,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(922,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652762,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(923,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652764,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(924,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652766,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(925,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652769,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(926,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652770,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(927,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652772,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(928,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) CodeBuddyCN/1.100.0 Chrome/132.0.6834.210 Electron/34.5.1 Safari/537.36',1778654048,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(929,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) CodeBuddyCN/1.100.0 Chrome/132.0.6834.210 Electron/34.5.1 Safari/537.36',1778656388,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(930,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) CodeBuddyCN/1.100.0 Chrome/132.0.6834.210 Electron/34.5.1 Safari/537.36',1778656396,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(931,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778656401,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(932,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778656402,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(933,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778656404,'http://localhost:3000/product','referral','http://localhost:3000/download','visit',''),(934,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778656406,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(935,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778656407,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(936,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778656409,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(937,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659257,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(938,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659262,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(939,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659333,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(940,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659337,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(941,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659687,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(942,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659687,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(943,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659689,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(944,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659922,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(945,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659925,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(946,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659926,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(947,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659928,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(948,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659947,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(949,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660370,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(950,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660501,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(951,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660506,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(952,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660905,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(953,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660907,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(954,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660909,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(955,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660913,'http://localhost:3000/case','referral','http://localhost:3000/product?cate_id=1','visit',''),(956,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660914,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(957,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660917,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(958,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660923,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(959,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660927,'http://localhost:3000/points','referral','http://localhost:3000/job','visit',''),(960,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660930,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(961,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660934,'http://localhost:3000/case','referral','http://localhost:3000/member/login','visit',''),(962,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660935,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(963,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660937,'http://localhost:3000/news','referral','http://localhost:3000/product','visit',''),(964,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660938,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(965,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660940,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(966,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660941,'http://localhost:3000/news','referral','http://localhost:3000/product','visit',''),(967,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660942,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(968,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660943,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(969,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660947,'http://localhost:3000/','referral','http://localhost:3000/job','visit',''),(970,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660950,'http://localhost:3000/','referral','http://localhost:3000/job','visit',''),(971,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660952,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(972,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660953,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(973,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660956,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(974,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660957,'http://localhost:3000/product','referral','http://localhost:3000/download','visit',''),(975,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660958,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(976,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660959,'http://localhost:3000/download','referral','http://localhost:3000/product','visit',''),(977,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660961,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(978,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660962,'http://localhost:3000/points','referral','http://localhost:3000/job','visit',''),(979,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660963,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(980,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660964,'http://localhost:3000/member/register','referral','http://localhost:3000/member/login','visit',''),(981,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660965,'http://localhost:3000/','referral','http://localhost:3000/member/register','visit',''),(982,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660969,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(983,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664077,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(984,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664078,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(985,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664081,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(986,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664083,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(987,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664086,'http://localhost:3000/','referral','http://localhost:3000/download','visit',''),(988,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664089,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(989,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664091,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(990,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664093,'http://localhost:3000/case','referral','http://localhost:3000/product?cate_id=1','visit',''),(991,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664095,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(992,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664097,'http://localhost:3000/news','referral','http://localhost:3000/product','visit',''),(993,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664098,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(994,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664103,'http://localhost:3000/','referral','http://localhost:3000/case','visit',''),(995,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664106,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(996,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664552,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(997,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778677125,'http://localhost:3000/case','direct','','visit',''),(998,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778677126,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(999,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778677130,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(1000,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778677131,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(1001,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778687343,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(1002,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778687345,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(1003,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778687346,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(1004,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778687349,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(1005,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778693741,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(1006,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778693742,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(1007,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778693744,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(1008,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778700747,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(1009,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778700751,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(1010,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778724543,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(1011,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778736794,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(1012,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778736797,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(1013,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778736798,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(1014,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778736800,'http://localhost:3000/','referral','http://localhost:3000/case','visit',''),(1015,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778736800,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(1016,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778758362,'http://localhost:3000/','referral','http://localhost:3000/admin/system/config?tab=system','visit',''),(1017,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778758439,'http://localhost:3000/','referral','http://localhost:3000/admin/system/config?tab=system','visit',''),(1018,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) CodeBuddyCN/1.100.0 Chrome/132.0.6834.210 Electron/34.5.1 Safari/537.36',1778765400,'http://localhost:3000/','direct','','visit',''),(1019,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) CodeBuddyCN/1.100.0 Chrome/132.0.6834.210 Electron/34.5.1 Safari/537.36',1778765405,'http://localhost:3000/','direct','','visit','');
DROP TABLE IF EXISTS `{prefix}visit_log_archive`;
CREATE TABLE `{prefix}visit_log_archive` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `period` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '归档周期 如:2026-04',
  `period_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'month' COMMENT '周期类型',
  `pv` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '月PV',
  `uv` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '月UV',
  `content_stats` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '内容访问排行(JSON)',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_period` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='访问日志归档表';

DROP TABLE IF EXISTS `{prefix}visitor_log`;
CREATE TABLE `{prefix}visitor_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `{prefix}webhook_endpoint`;
CREATE TABLE `{prefix}webhook_endpoint` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '端点名称',
  `url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '推送URL',
  `secret` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '签名密钥',
  `events` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '监听事件列表(JSON数组)',
  `is_active` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否激活',
  `retry_count` tinyint(3) unsigned NOT NULL DEFAULT '3' COMMENT '最大重试次数',
  `timeout_seconds` int(10) unsigned NOT NULL DEFAULT '10' COMMENT '超时时间(秒)',
  `fail_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '连续失败次数',
  `last_sent_at` int(10) unsigned DEFAULT NULL COMMENT '最后推送时间',
  `last_status` tinyint(4) DEFAULT NULL COMMENT '最后状态:1成功0失败',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Webhook端点表';

DROP TABLE IF EXISTS `{prefix}webhook_log`;
CREATE TABLE `{prefix}webhook_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `endpoint_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '端点ID',
  `event_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '事件名称',
  `payload` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '推送数据(JSON)',
  `response_code` int(11) NOT NULL DEFAULT '0' COMMENT '响应状态码',
  `response_body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '响应内容',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态:0待推送1推送中2成功3失败',
  `attempt` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '第几次重试',
  `duration_ms` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '耗时(毫秒)',
  `error_message` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '错误消息',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_endpoint` (`endpoint_id`),
  KEY `idx_status` (`status`),
  KEY `idx_event` (`event_name`),
  KEY `idx_create` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Webhook推送日志表';

DROP TABLE IF EXISTS `{prefix}cate`;
CREATE TABLE `{prefix}cate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '分类类型:1产品/2案例/3新闻/4下载/5招聘/6单页',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `seo_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `seo_description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分类表';

INSERT INTO `{prefix}cate` VALUES (1,'产品中心',1,0,1,1,'','','',1777005355,1777005355),(2,'成功案例',2,0,2,1,'','','',1777005355,1777005355),(3,'新闻动态',3,0,3,1,'','','',1777005355,1777005355),(4,'资料下载',4,0,4,1,'','','',1777005355,1777005355),(5,'人才招聘',5,0,5,1,'','','',1777005355,1777005355);
DROP TABLE IF EXISTS `{prefix}config`;
CREATE TABLE `{prefix}config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `group` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分组',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置名',
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '配置值',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text' COMMENT '类型:text/textarea/number/switch/select',
  `options` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '选项(JSON,select/switch用)',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '说明',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_group` (`group`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

INSERT INTO `{prefix}config` VALUES (1,'basic','site_name','AI-CMS','text','',1,'网站名称'),(2,'basic','site_keywords','AI,CMS,内容管理','text','',2,'网站关键词'),(3,'basic','site_description','AI驱动的企业信息管理系统','textarea','',3,'网站描述'),(4,'basic','site_logo','','text','',4,'网站Logo'),(5,'basic','site_icp','','text','',5,'ICP备案号'),(6,'upload','upload_max_size','10','number','',1,'上传大小限制(MB)'),(7,'upload','upload_image_ext','jpg,jpeg,png,gif,webp,svg','text','',2,'允许的图片格式'),(8,'ai','ai_enabled','1','switch','',1,'启用AI功能'),(9,'ai','ai_default_model','deepseek-chat','text','',2,'默认AI模型');
DROP TABLE IF EXISTS `{prefix}content`;
CREATE TABLE `{prefix}content` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标题',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '内容',
  `excerpt` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '摘要',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '类型:1产品/2案例/3新闻/4下载/5招聘/6单页',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态:0草稿/1待审/2已发布/-1已删除',
  `cate_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '作者ID',
  `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '封面图',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `is_top` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否置顶:0否/1是',
  `views` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '浏览量',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_type_status` (`type`,`status`),
  KEY `idx_cate` (`cate_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容表';

DROP TABLE IF EXISTS `{prefix}content_ext`;
CREATE TABLE `{prefix}content_ext` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `content_id` bigint(20) unsigned NOT NULL COMMENT '内容ID',
  `type` tinyint(4) NOT NULL COMMENT '内容类型',
  `data` json DEFAULT NULL COMMENT '扩展数据(JSON)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_content_type` (`content_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容扩展表';

DROP TABLE IF EXISTS `{prefix}content_tag`;
CREATE TABLE `{prefix}content_tag` (
  `content_id` bigint(20) unsigned NOT NULL COMMENT '内容ID',
  `tag_id` int(10) unsigned NOT NULL COMMENT '标签ID',
  PRIMARY KEY (`content_id`,`tag_id`),
  KEY `idx_tag` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容标签关联表';

DROP TABLE IF EXISTS `{prefix}content_version`;
CREATE TABLE `{prefix}content_version` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标题',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '正文内容',
  `excerpt` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '摘要',
  `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '封面图',
  `cate_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态',
  `ext_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '扩展字段数据(JSON)',
  `tag_ids` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '标签ID集合',
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '操作人ID',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '版本创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容版本历史表';

DROP TABLE IF EXISTS `{prefix}log`;
CREATE TABLE `{prefix}log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `module` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模块',
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作',
  `target` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '操作对象',
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'IP地址',
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '操作数据',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表';

DROP TABLE IF EXISTS `{prefix}tag`;
CREATE TABLE `{prefix}tag` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标签名称',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='标签表';

DROP TABLE IF EXISTS `{prefix}user`;
CREATE TABLE `{prefix}user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '头像',
  `role_id` tinyint(4) NOT NULL DEFAULT '3' COMMENT '角色:1超管/2管理员/3编辑',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `last_login_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `last_login_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '最后登录IP',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

INSERT INTO `{prefix}user` VALUES (1,'admin','admin@aicms.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','超级管理员','',1,1,0,'',1777005355,1777005355);

SET FOREIGN_KEY_CHECKS = 1;
SET UNIQUE_CHECKS = 1;
COMMIT;