-- ============================================
-- 八界AI-CMS 安装SQL
-- 版本: V2.9.40
-- 表前缀: {prefix}
-- 生成方式: Docker MySQL 完整导出（含表结构+种子数据，纯 CREATE TABLE，无 ALTER）
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `{prefix}ab_test`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ab_test` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `test_name` varchar(100) NOT NULL DEFAULT '' COMMENT '测试名称',
  `test_type` varchar(30) NOT NULL DEFAULT 'content' COMMENT '类型: content/template/feature/price',
  `description` text COMMENT '测试描述',
  `version_a_config` json DEFAULT NULL COMMENT '版本A配置',
  `version_b_config` json DEFAULT NULL COMMENT '版本B配置',
  `traffic_ratio` int NOT NULL DEFAULT '50' COMMENT '版本B流量占比(%)',
  `primary_metric` varchar(50) NOT NULL DEFAULT 'click_rate' COMMENT '主要指标: click_rate/conversion_rate/bounce_rate/avg_duration/revenue',
  `target_audience` json DEFAULT NULL COMMENT '目标受众条件',
  `status` varchar(20) NOT NULL DEFAULT 'draft' COMMENT '状态: draft/running/paused/completed/archived',
  `winner` varchar(5) NOT NULL DEFAULT '' COMMENT '获胜版本: A/B/none',
  `confidence` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '置信度(%)',
  `start_time` datetime DEFAULT NULL COMMENT '开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '结束时间',
  `version_a_visitors` int unsigned NOT NULL DEFAULT '0' COMMENT '版本A访客数',
  `version_a_conversions` int unsigned NOT NULL DEFAULT '0' COMMENT '版本A转化数',
  `version_b_visitors` int unsigned NOT NULL DEFAULT '0' COMMENT '版本B访客数',
  `version_b_conversions` int unsigned NOT NULL DEFAULT '0' COMMENT '版本B转化数',
  `created_by` int unsigned NOT NULL DEFAULT '0' COMMENT '创建者ID',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_test_type` (`test_type`),
  KEY `idx_status` (`status`),
  KEY `idx_start_time` (`start_time`),
  KEY `idx_end_time` (`end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='A/B测试表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ab_test` WRITE;
/*!40000 ALTER TABLE `{prefix}ab_test` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}ab_test` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ad` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `position_id` int unsigned NOT NULL DEFAULT '0' COMMENT '广告位ID',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '广告标题',
  `image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '图片地址',
  `link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '链接地址',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '代码/富文本内容',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `start_time` int unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_position_status` (`position_id`,`status`),
  KEY `idx_status_sort` (`status`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='广告表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ad` WRITE;
/*!40000 ALTER TABLE `{prefix}ad` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}ad` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ad_position`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ad_position` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '广告位名称',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '广告位标识(唯一)',
  `width` int NOT NULL DEFAULT '0' COMMENT '宽度(px)',
  `height` int NOT NULL DEFAULT '0' COMMENT '高度(px)',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '描述',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='广告位表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ad_position` WRITE;
/*!40000 ALTER TABLE `{prefix}ad_position` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}ad_position` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ad_stat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ad_stat` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ad_id` int unsigned NOT NULL DEFAULT '0' COMMENT '广告ID',
  `stat_date` date NOT NULL COMMENT '统计日期',
  `views` int unsigned NOT NULL DEFAULT '0' COMMENT '展示次数',
  `clicks` int unsigned NOT NULL DEFAULT '0' COMMENT '点击次数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ad_date` (`ad_id`,`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='广告统计表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ad_stat` WRITE;
/*!40000 ALTER TABLE `{prefix}ad_stat` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}ad_stat` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_batch_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_batch_task` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int unsigned DEFAULT '0' COMMENT 'å…³è”AIæ¨¡æ¿ID',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '任务名称',
  `keywords` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '关键词列表（换行分隔）',
  `style` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'default' COMMENT '写作风格: default/formal/casual/marketing/technical',
  `cate_id` int DEFAULT '0' COMMENT '目标分类',
  `model_id` int DEFAULT '0' COMMENT '使用的AI模型ID',
  `total` int DEFAULT '0' COMMENT '总数量',
  `completed` int DEFAULT '0' COMMENT '已完成数量',
  `status` tinyint DEFAULT '0' COMMENT '状态: 0排队 1进行中 2完成 3失败',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_template` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI批量生成任务表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_batch_task` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_batch_task` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}ai_batch_task` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_content_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_content_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0',
  `content_id` int NOT NULL DEFAULT '0',
  `mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `style` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `input_text` text COLLATE utf8mb4_unicode_ci,
  `output_text` text COLLATE utf8mb4_unicode_ci,
  `provider` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tokens_used` int NOT NULL DEFAULT '0',
  `elapsed_ms` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_mode` (`mode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI content log';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_content_log` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_content_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}ai_content_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_editor_conversation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_editor_conversation` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '会话标识',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `content_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联内容ID(0=未关联内容，仅允许临时对话，不支持跨content对话)',
  `role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '角色(user/assistant)',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '对话内容',
  `token_count` int unsigned NOT NULL DEFAULT '0' COMMENT '本轮Token数量',
  `session_token_total` int unsigned NOT NULL DEFAULT '0' COMMENT '会话累计Token总数',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI编辑器对话记录表(V2.9.28 A-2)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_editor_conversation` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_editor_conversation` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}ai_editor_conversation` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_editor_snapshot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_editor_snapshot` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `version` int unsigned NOT NULL DEFAULT '0' COMMENT '版本号(自增)',
  `content` longtext COLLATE utf8mb4_unicode_ci COMMENT '内容快照',
  `content_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '内容哈希(sha256)',
  `operation_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作类型(continue/rewrite/expand/translate/optimize)',
  `operation_desc` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作描述',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_content_version` (`content_id`,`version`),
  KEY `idx_content` (`content_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI编辑器版本快照表(V2.9.28 A-7)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_editor_snapshot` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_editor_snapshot` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}ai_editor_snapshot` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_editor_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_editor_template` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板名称',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板描述',
  `prompt` text COLLATE utf8mb4_unicode_ci COMMENT 'Prompt模板(含变量占位符)',
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类',
  `industry` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '行业标签',
  `tags` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标签(逗号分隔)',
  `example_output` text COLLATE utf8mb4_unicode_ci COMMENT '示例输出',
  `sort` int unsigned NOT NULL DEFAULT '99' COMMENT '排序',
  `is_system` tinyint NOT NULL DEFAULT '0' COMMENT '是否系统预制',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '创建用户(0=系统)',
  `use_count` int unsigned NOT NULL DEFAULT '0' COMMENT '使用次数',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI编辑器模板库(V2.9.28 A-5)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_editor_template` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_editor_template` DISABLE KEYS */;
INSERT INTO `{prefix}ai_editor_template` VALUES (1,'营销文案生成','生成吸引人的营销文案','请根据以下信息生成一段营销文案：\n产品名称：{product_name}\n目标受众：{target_audience}\n核心卖点：{selling_points}\n文案风格：吸引眼球、简洁有力','marketing','ecommerce','营销,文案,广告','【限时特惠】{product_name}，专为{target_audience}打造！{selling_points}，让每一次选择都物超所值。',1,1,0,0,1,1782111123,1782111123),(2,'产品描述优化','优化产品描述使其更具吸引力','请优化以下产品描述，使其更专业、更有吸引力：\n{content}\n要求：突出产品优势、使用场景化描述','marketing','ecommerce','产品,描述,优化','这款产品采用XX工艺，精选优质材料...',2,1,0,0,1,1782111123,1782111123),(3,'新闻稿撰写','撰写标准格式的新闻稿','请撰写一篇新闻稿：\n标题：{title}\n事件：{event}\n时间：{date}\n地点：{location}\n要求：客观、正式、信息完整','news','enterprise','新闻,稿件,公关','{date}，{location}讯——{title}。据悉，{event}...',3,1,0,0,1,1782111123,1782111123),(4,'博客文章生成','生成博客文章框架','请围绕以下主题撰写一篇博客文章：\n主题：{topic}\n字数：{word_count}\n风格：{style}\n要求：开头吸引人、内容有价值、结尾有总结','blog','blog','博客,文章,内容','在这个信息爆炸的时代，{topic}成为了热门话题...',4,1,0,0,1,1782111123,1782111123),(5,'邮件营销模板','生成邮件营销内容','请撰写一封营销邮件：\n收件人：{recipient}\n产品：{product}\n目的：{purpose}\n要求：标题吸引人、正文简洁、CTA明确','email','enterprise','邮件,营销,EDM','亲爱的{recipient}，\n\n我们很高兴向您介绍{product}...',5,1,0,0,1,1782111123,1782111123),(6,'SEO摘要生成','生成SEO友好的内容摘要','请为以下内容生成SEO友好的摘要(150字以内)：\n{content}\n要求：包含核心关键词、吸引点击、适合搜索引擎','seo','enterprise','SEO,摘要,优化','本文深入探讨{topic}，为您揭示...',6,1,0,0,1,1782111123,1782111123),(7,'社交媒体文案','生成社交媒体发布文案','请为以下内容生成社交媒体文案：\n平台：{platform}\n内容：{content}\n要求：符合平台调性、带话题标签、互动性强','social','ecommerce','社交媒体,文案,互动','刚刚了解到{content}，太赞了！#话题标签',7,1,0,0,1,1782111123,1782111123),(8,'产品说明书','撰写产品使用说明书','请撰写产品使用说明书：\n产品：{product}\n功能：{features}\n要求：步骤清晰、语言简洁、安全提示','manual','enterprise','产品,说明书,文档','一、产品概述\n{product}是一款{features}的产品...',8,1,0,0,1,1782111123,1782111123),(9,'教育课程大纲','生成教育培训课程大纲','请生成课程大纲：\n课程名称：{course_name}\n目标学员：{target}\n课时数：{hours}\n要求：循序渐进、知识点清晰','education','education','教育,课程,培训','第一讲：{course_name}基础\n第二讲：进阶知识...',9,1,0,0,1,1782111123,1782111123),(10,'医疗科普文章','撰写通俗易懂的医疗科普','请撰写医疗科普文章：\n主题：{topic}\n读者：普通大众\n要求：科学准确、通俗易懂、有实用建议','article','medical','医疗,科普,健康','关于{topic}，很多人都有疑问...',10,1,0,0,1,1782111123,1782111123),(11,'金融分析报告','生成金融数据分析报告','请撰写金融分析报告：\n分析对象：{target}\n数据：{data}\n要求：客观分析、数据支撑、有结论建议','report','finance','金融,分析,报告','一、市场概况\n根据数据，{target}近期表现...',11,1,0,0,1,1782111123,1782111123),(12,'旅游攻略生成','生成旅游目的地攻略','请生成旅游攻略：\n目的地：{destination}\n天数：{days}\n要求：行程合理、必去景点、美食推荐','guide','tourism','旅游,攻略,出行','第一天：抵达{destination}，建议游览...',12,1,0,0,1,1782111123,1782111123),(13,'电商详情页文案','生成电商产品详情页文案','请为电商产品生成详情页文案：\n产品：{product}\n卖点：{features}\n要求：分模块展示、图文并茂、转化率高','ecommerce','ecommerce','电商,详情页,转化','【产品亮点】\n{features}\n【使用场景】\n...',13,1,0,0,1,1782111123,1782111123),(14,'技术文档撰写','撰写技术API文档','请撰写技术文档：\n功能：{feature}\n接口：{api}\n要求：参数说明、示例代码、注意事项','tech','enterprise','技术,文档,API','## 接口说明\n{api}\n\n## 请求参数\n...',14,1,0,0,1,1782111123,1782111123),(15,'品牌故事撰写','撰写品牌故事','请撰写品牌故事：\n品牌：{brand}\n历史：{history}\n价值观：{values}\n要求：感人、真实、有记忆点','brand','enterprise','品牌,故事,营销','{brand}的故事始于{history}...',15,1,0,0,1,1782111123,1782111123),(16,'FAQ常见问题','生成FAQ问答','请生成FAQ：\n主题：{topic}\n常见问题数：{count}\n要求：问题典型、答案简洁','faq','enterprise','FAQ,问答,帮助','Q1: {topic}是什么？\nA: ...',16,1,0,0,1,1782111123,1782111123),(17,'视频脚本撰写','生成短视频脚本','请撰写短视频脚本：\n主题：{topic}\n时长：{duration}秒\n平台：{platform}\n要求：开头3秒抓眼球、节奏紧凑','video','ecommerce','视频,脚本,短视频','【0-3秒】开场白\n【3-15秒】核心内容...',17,1,0,0,1,1782111123,1782111123),(18,'活动策划方案','生成活动策划方案','请生成活动策划方案：\n活动类型：{type}\n预算：{budget}\n人数：{participants}\n要求：创意、可执行、有ROI分析','event','enterprise','活动,策划,方案','一、活动概述\n{type}活动，预计{participants}人参加...',18,1,0,0,1,1782111123,1782111123),(19,'用户评测文案','生成用户评测文案','请撰写产品评测文案：\n产品：{product}\n使用体验：{experience}\n要求：真实客观、优缺点对比','review','ecommerce','评测,产品,体验','用了{product}一周后，我的真实感受...',19,1,0,0,1,1782111123,1782111123),(20,'招聘JD撰写','生成招聘职位描述','请撰写招聘JD：\n职位：{position}\n要求：{requirements}\n公司：{company}\n要求：吸引人、职责清晰、要求合理','hr','enterprise','招聘,JD,HR','我们正在寻找{position}！\n在{company}，你将...',20,1,0,0,1,1782111123,1782111123);
/*!40000 ALTER TABLE `{prefix}ai_editor_template` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_image_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_image_task` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联内容ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0排队中/1生成中/2完成/3失败',
  `prompt` text COLLATE utf8mb4_unicode_ci COMMENT '生成提示词',
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '生成图片URL',
  `provider` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '图片提供商',
  `error_msg` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '错误信息',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_status` (`status`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI配图任务表(V2.9.12)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_image_task` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_image_task` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}ai_image_task` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `model_id` int NOT NULL COMMENT '使用的模型ID',
  `task_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '任务类型: write/seo/translate/summarize',
  `prompt_length` int DEFAULT '0' COMMENT '输入长度',
  `response_length` int DEFAULT '0' COMMENT '输出长度',
  `tokens_used` int DEFAULT '0' COMMENT '消耗token数',
  `duration_ms` int DEFAULT '0' COMMENT '耗时（毫秒）',
  `status` tinyint DEFAULT '1' COMMENT '状态: 1成功 2失败 3降级',
  `error_msg` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '错误信息',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_model_time` (`model_id`,`create_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI调用日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_log` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_log` DISABLE KEYS */;
INSERT INTO `{prefix}ai_log` VALUES (1,0,'seoOptimize',41,3547,0,5387,1,'',1779246923),(2,0,'seoOptimize',41,4811,0,8057,1,'',1779248613);
/*!40000 ALTER TABLE `{prefix}ai_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_model`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_model` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模型名称',
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '供应商: deepseek/qwen/ernie/glm/openai',
  `model_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模型ID（API调用用）',
  `api_base` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'API Base URL',
  `api_key` text COLLATE utf8mb4_unicode_ci COMMENT 'API密钥（加密存储）',
  `capabilities` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'write,seo,translate' COMMENT '能力标签，逗号分隔',
  `is_default` tinyint DEFAULT '0' COMMENT '是否默认模型',
  `is_enabled` tinyint DEFAULT '1' COMMENT '启用状态',
  `max_tokens` int DEFAULT '2000' COMMENT '最大输出token数',
  `temperature` float DEFAULT '0.7' COMMENT '温度参数',
  `sort` int DEFAULT '0' COMMENT '排序（故障降级顺序）',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  `rate_limit_rpm` int DEFAULT '60' COMMENT '每分钟最大请求数',
  `rate_limit_rph` int DEFAULT '1000' COMMENT '每小时最大请求数',
  `api_key_encrypted` tinyint DEFAULT '0' COMMENT 'API密钥是否已加密',
  `status` tinyint DEFAULT '1' COMMENT '状态: 1启用 0禁用',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provider_model` (`provider`,`model_id`),
  KEY `idx_enabled_default` (`is_enabled`,`is_default`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI模型配置表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_model` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_model` DISABLE KEYS */;
INSERT INTO `{prefix}ai_model` VALUES (1,'DeepSeek V4-Flash','deepseek','deepseek-chat','https://api.deepseek.com/v1','','write,seo,translate,summarize',1,1,2000,0.7,1,1777457479,1778141229,60,1000,0,1),(4,'GLM-4-Flash','glm','glm-4-flash','https://open.bigmodel.cn/api/paas/v4','','write,seo,translate',0,1,2000,0.7,3,1777774128,1777774128,60,1000,0,1),(5,'ERNIE-Speed','ernie','ernie-speed-128k','https://qianfan.baidubce.com/v2','','write,seo',0,0,2000,0.7,4,1777774128,1779255885,60,1000,0,1),(6,'OpenAI兼容','openai','gpt-3.5-turbo','https://api.openai.com/v1','','write,seo,translate,summarize',0,0,2000,0.7,5,1777774128,1777774128,60,1000,0,1);
/*!40000 ALTER TABLE `{prefix}ai_model` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_quality_check`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_quality_check` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int NOT NULL COMMENT '内容ID',
  `content_type` varchar(20) DEFAULT 'article' COMMENT '内容类型',
  `ai_generated` tinyint DEFAULT '1' COMMENT '是否AI生成',
  `quality_score` decimal(4,2) DEFAULT '0.00' COMMENT '总质量评分(0-100)',
  `dimension_scores` json DEFAULT NULL COMMENT '各维度评分(JSON)',
  `check_rules` json DEFAULT NULL COMMENT '检查规则结果(JSON)',
  `issues` json DEFAULT NULL COMMENT '发现的问题(JSON)',
  `suggestions` text COMMENT '改进建议',
  `auto_optimized` tinyint DEFAULT '0' COMMENT '是否已自动优化',
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
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_quality_check` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_quality_check` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}ai_quality_check` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_report` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL COMMENT 'daily/weekly/monthly/manual',
  `title` varchar(200) NOT NULL COMMENT 'æŠ¥å‘Šæ ‡é¢˜',
  `period_start` int unsigned NOT NULL COMMENT 'ç»Ÿè®¡å¼€å§‹æ—¶é—´æˆ³',
  `period_end` int unsigned NOT NULL COMMENT 'ç»Ÿè®¡ç»“æŸæ—¶é—´æˆ³',
  `raw_data` json DEFAULT NULL COMMENT 'åŽŸå§‹æ•°æ®å¿«ç…§',
  `summary` text COMMENT 'ä¸€å¥è¯æ€»ç»“',
  `findings` json DEFAULT NULL COMMENT 'å…³é”®å‘çŽ°åˆ—è¡¨',
  `anomalies` json DEFAULT NULL COMMENT 'å¼‚å¸¸æ£€æµ‹åˆ—è¡¨',
  `recommendations` json DEFAULT NULL COMMENT 'å»ºè®®åˆ—è¡¨',
  `sections` json DEFAULT NULL COMMENT 'è¯¦ç»†ç« èŠ‚',
  `status` tinyint DEFAULT '0' COMMENT '0ç”Ÿæˆä¸­/1å·²å®Œæˆ/2å‘å¸ƒ/3å¤±è´¥',
  `create_time` int unsigned DEFAULT '0',
  `update_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_period` (`period_start`,`period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='AIåˆ†æžæŠ¥å‘Š';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_report` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}ai_report` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_rewrite_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_rewrite_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT '操作人',
  `content_id` int NOT NULL COMMENT '内容ID',
  `rewrite_type` varchar(50) NOT NULL COMMENT '改写类型(title/summary/body/style)',
  `style` varchar(50) DEFAULT '' COMMENT '改写风格',
  `original_content` text COMMENT '原始内容',
  `rewritten_content` text COMMENT '改写后内容',
  `status` tinyint DEFAULT '1' COMMENT '状态:1已生成2已确认3已放弃',
  `token_used` int DEFAULT '0' COMMENT '消耗token数',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='AI改写日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_rewrite_log` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_rewrite_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}ai_rewrite_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_task_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_task_queue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '任务ID',
  `task_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '任务类型：ai_image_generate / batch_seo_optimize / single_seo_optimize',
  `agent_id` int unsigned NOT NULL DEFAULT '0' COMMENT '智能体ID',
  `agent_session_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '智能体会话ID',
  `agent_plan` json DEFAULT NULL COMMENT '智能体执行计划',
  `agent_memory` json DEFAULT NULL COMMENT '智能体记忆上下文',
  `biz_id` int unsigned NOT NULL DEFAULT '0' COMMENT '业务ID：content_id / batch_id 等',
  `biz_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '业务标识：用于分组，如batch_seo:20260531',
  `payload` json DEFAULT NULL COMMENT '任务参数（JSON），含prompt/options/extra等',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态：0=pending, 1=running, 2=completed, 3=failed, 4=paused, 5=cancelled',
  `progress` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '进度百分比 0-100',
  `result` json DEFAULT NULL COMMENT '执行结果（JSON），成功时包含urls/task_ids等',
  `error_msg` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '失败原因',
  `retry_count` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '重试次数',
  `max_retries` tinyint unsigned NOT NULL DEFAULT '3' COMMENT '最大重试次数',
  `priority` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '优先级：0=普通, 1=高',
  `scheduled_at` int unsigned NOT NULL DEFAULT '0' COMMENT '计划执行时间（时间戳，0=立即）',
  `started_at` int unsigned NOT NULL DEFAULT '0' COMMENT '开始执行时间',
  `completed_at` int unsigned NOT NULL DEFAULT '0' COMMENT '完成时间',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_biz` (`biz_id`,`task_type`),
  KEY `idx_biz_key` (`biz_key`),
  KEY `idx_status` (`status`,`priority`,`scheduled_at`),
  KEY `idx_type_status` (`task_type`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI任务队列表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_task_queue` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_task_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}ai_task_queue` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_template` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'æ¨¡æ¿åç§°',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'æ¨¡æ¿æè¿°',
  `nl_description` text COLLATE utf8mb4_unicode_ci COMMENT 'è‡ªç„¶è¯­è¨€æè¿°(V2.9.9ï¼Œä¾›AIç†è§£æ¨¡æ¿æ„å›¾)',
  `generate_mode` enum('nlp','example') COLLATE utf8mb4_unicode_ci DEFAULT 'nlp' COMMENT 'ç”Ÿæˆæ¨¡å¼: nlpè‡ªç„¶è¯­è¨€/exampleå‚è€ƒç¤ºä¾‹',
  `cate_id` int unsigned DEFAULT '0' COMMENT 'é»˜è®¤å†…å®¹åˆ†ç±»ID',
  `model_id` int unsigned DEFAULT '0' COMMENT 'é»˜è®¤AIæ¨¡åž‹ID',
  `style` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'default' COMMENT 'å†™ä½œé£Žæ ¼: default/formal/casual/marketing/technical',
  `title_rule` text COLLATE utf8mb4_unicode_ci COMMENT 'æ ‡é¢˜ç”Ÿæˆè§„åˆ™(NLæè¿°)',
  `content_rule` text COLLATE utf8mb4_unicode_ci COMMENT 'å†…å®¹ç”Ÿæˆè§„åˆ™(NLæè¿°)',
  `keyword_hint` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'é»˜è®¤å…³é”®è¯æç¤º',
  `fields_config` text COLLATE utf8mb4_unicode_ci COMMENT 'è‡ªå®šä¹‰å­—æ®µé…ç½®JSON',
  `image_config` text COLLATE utf8mb4_unicode_ci COMMENT 'é…å›¾é…ç½®JSON',
  `field_mapping` text COLLATE utf8mb4_unicode_ci COMMENT '字段映射规则JSON(含mappings/variables/image_config_override)',
  `quality_config` text COLLATE utf8mb4_unicode_ci COMMENT '质量检测配置JSON(min_score/max_retry/action_on_low_quality/check_items)',
  `publisher` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'é»˜è®¤ä½œè€…',
  `contact` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'é»˜è®¤è”ç³»æ–¹å¼',
  `example_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'ç¤ºä¾‹æ ‡é¢˜',
  `example_content` longtext COLLATE utf8mb4_unicode_ci COMMENT 'ç¤ºä¾‹æ­£æ–‡å†…å®¹ï¼ˆç”¨äºŽé£Žæ ¼å­¦ä¹ ï¼‰',
  `default_batch` smallint unsigned DEFAULT '10' COMMENT 'é»˜è®¤æ‰¹é‡æ•°é‡(1-100)',
  `status` tinyint DEFAULT '1' COMMENT 'çŠ¶æ€: 0ç¦ç”¨ 1å¯ç”¨',
  `source` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'custom' COMMENT 'æ¨¡æ¿æ¥æº:systemå®˜æ–¹/customè‡ªå»º/importedå¯¼å…¥(V2.9.9)',
  `sort` int DEFAULT '0' COMMENT 'æŽ’åºæƒé‡',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_cate` (`cate_id`),
  KEY `idx_mode` (`generate_mode`),
  KEY `idx_source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AIå†…å®¹ç”Ÿæˆæ¨¡æ¿è¡¨(V2.6)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_template` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_template` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}ai_template` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_theme_chat_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_theme_chat_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联ai_theme_record.id',
  `version` int unsigned NOT NULL DEFAULT '0' COMMENT '修改时的版本号',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '操作者用户ID（审计用）',
  `role` enum('user','ai','system') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user' COMMENT '消息角色',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '消息内容',
  `changed_files` json DEFAULT NULL COMMENT '本次修改变更的文件列表',
  `prompt_tokens` int unsigned NOT NULL DEFAULT '0' COMMENT '输入Token数',
  `completion_tokens` int unsigned NOT NULL DEFAULT '0' COMMENT '输出Token数',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_record_version` (`record_id`,`version`),
  KEY `idx_record_role` (`record_id`,`role`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI主题多轮对话记录';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_theme_chat_log` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_theme_chat_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}ai_theme_chat_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_theme_palette`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_theme_palette` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `industry_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '行业标识',
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '调色板名称',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '描述',
  `colors` json NOT NULL COMMENT '色板JSON: {primary,primaryLight,primaryDark,secondary,accent,bg,bgSecondary,bgSection,text,textSecondary,border}',
  `is_system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否系统内置:1是/0否',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_industry_system` (`industry_type`,`is_system`),
  KEY `idx_industry` (`industry_type`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI主题行业调色板(V2.9.11)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_theme_palette` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_theme_palette` DISABLE KEYS */;
INSERT INTO `{prefix}ai_theme_palette` VALUES (1,'corporate','企业商务','专业、可信、现代简约','{\"bg\": \"#FFFFFF\", \"text\": \"#1E293B\", \"accent\": \"#F59E0B\", \"border\": \"#E2E8F0\", \"primary\": \"#2563EB\", \"bgSection\": \"#F1F5F9\", \"secondary\": \"#64748B\", \"bgSecondary\": \"#F8FAFC\", \"primaryDark\": \"#1E40AF\", \"primaryLight\": \"#DBEAFE\", \"textSecondary\": \"#64748B\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34'),(2,'ecommerce','电商促销','热闹、促销、信任感','{\"bg\": \"#FFFFFF\", \"text\": \"#1F2937\", \"accent\": \"#EF4444\", \"border\": \"#E5E7EB\", \"primary\": \"#F97316\", \"bgSection\": \"#FFFBEB\", \"secondary\": \"#6B7280\", \"bgSecondary\": \"#FFF7ED\", \"primaryDark\": \"#EA580C\", \"primaryLight\": \"#FFEDD5\", \"textSecondary\": \"#6B7280\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34'),(3,'blog','博客文艺','舒适阅读、极简、知识分享','{\"bg\": \"#FFFFFF\", \"text\": \"#111827\", \"accent\": \"#8B5CF6\", \"border\": \"#E5E7EB\", \"primary\": \"#059669\", \"bgSection\": \"#F3F4F6\", \"secondary\": \"#6B7280\", \"bgSecondary\": \"#F9FAFB\", \"primaryDark\": \"#047857\", \"primaryLight\": \"#D1FAE5\", \"textSecondary\": \"#6B7280\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34'),(4,'portal','门户资讯','信息密集、权威、时效','{\"bg\": \"#FFFFFF\", \"text\": \"#0F172A\", \"accent\": \"#0EA5E9\", \"border\": \"#CBD5E1\", \"primary\": \"#1D4ED8\", \"bgSection\": \"#E2E8F0\", \"secondary\": \"#475569\", \"bgSecondary\": \"#F1F5F9\", \"primaryDark\": \"#1E3A8A\", \"primaryLight\": \"#DBEAFE\", \"textSecondary\": \"#475569\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34'),(5,'medical','医疗健康','清洁、专业、信任、安心','{\"bg\": \"#FFFFFF\", \"text\": \"#1E293B\", \"accent\": \"#14B8A6\", \"border\": \"#E2E8F0\", \"primary\": \"#0EA5E9\", \"bgSection\": \"#F0F9FF\", \"secondary\": \"#64748B\", \"bgSecondary\": \"#F8FAFC\", \"primaryDark\": \"#0284C7\", \"primaryLight\": \"#E0F2FE\", \"textSecondary\": \"#64748B\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34'),(6,'education','教育培训','活力、知识、信任、成长','{\"bg\": \"#FFFFFF\", \"text\": \"#1E293B\", \"accent\": \"#F59E0B\", \"border\": \"#E2E8F0\", \"primary\": \"#3B82F6\", \"bgSection\": \"#FFFBEB\", \"secondary\": \"#64748B\", \"bgSecondary\": \"#FEF3C7\", \"primaryDark\": \"#1D4ED8\", \"primaryLight\": \"#DBEAFE\", \"textSecondary\": \"#64748B\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34'),(7,'catering','餐饮美食','食欲、温暖、热闹、品质','{\"bg\": \"#FFFFFF\", \"text\": \"#1F2937\", \"accent\": \"#EF4444\", \"border\": \"#E5E7EB\", \"primary\": \"#F97316\", \"bgSection\": \"#FEF2F2\", \"secondary\": \"#6B7280\", \"bgSecondary\": \"#FFF7ED\", \"primaryDark\": \"#EA580C\", \"primaryLight\": \"#FFEDD5\", \"textSecondary\": \"#6B7280\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34'),(8,'finance','金融理财','稳重、专业、信任、安全','{\"bg\": \"#FFFFFF\", \"text\": \"#0F172A\", \"accent\": \"#D97706\", \"border\": \"#CBD5E1\", \"primary\": \"#1E3A8A\", \"bgSection\": \"#E2E8F0\", \"secondary\": \"#475569\", \"bgSecondary\": \"#F1F5F9\", \"primaryDark\": \"#0F172A\", \"primaryLight\": \"#DBEAFE\", \"textSecondary\": \"#475569\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34'),(9,'technology','科技互联网','创新、前沿、简洁、高效','{\"bg\": \"#FFFFFF\", \"text\": \"#1E293B\", \"accent\": \"#06B6D4\", \"border\": \"#E2E8F0\", \"primary\": \"#6366F1\", \"bgSection\": \"#F1F5F9\", \"secondary\": \"#6B7280\", \"bgSecondary\": \"#F8FAFC\", \"primaryDark\": \"#4338CA\", \"primaryLight\": \"#E0E7FF\", \"textSecondary\": \"#6B7280\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34'),(10,'realestate','房产家居','品质、温馨、稳重、信赖','{\"bg\": \"#FFFFFF\", \"text\": \"#1E293B\", \"accent\": \"#F59E0B\", \"border\": \"#E2E8F0\", \"primary\": \"#0D9488\", \"bgSection\": \"#F8FAFC\", \"secondary\": \"#64748B\", \"bgSecondary\": \"#F0FDFA\", \"primaryDark\": \"#0F766E\", \"primaryLight\": \"#CCFBF1\", \"textSecondary\": \"#64748B\"}',1,'2026-05-23 19:23:34','2026-05-23 19:23:34');
/*!40000 ALTER TABLE `{prefix}ai_theme_palette` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_theme_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_theme_record` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `theme_name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '生成的主题名',
  `source_theme_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '源骨架主题ID，骨架模式时记录复制来源(V2.9.11)',
  `generate_mode` enum('full','skeleton') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'full' COMMENT '生成模式:full从零生成/skeleton基于骨架(V2.9.11)',
  `layout_type` enum('showcase','content') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '布局类型:showcase展示型/content内容型(V2.9.11)',
  `industry_type` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '行业类型:corporate/ecommerce/blog/portal/medical/education/catering/finance(V2.9.11)',
  `batch_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'æ‰¹é‡ç”Ÿæˆæ‰¹æ¬¡ID(S14)',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '用户输入描述',
  `options` json DEFAULT NULL COMMENT '生成选项（风格/色系等）',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态: 0生成中 1待审核 2校验通过 3已发布 4已拒绝 -1生成失败 -2校验失败',
  `prompt_log` text COLLATE utf8mb4_unicode_ci COMMENT '使用的完整Prompt（审计用）',
  `validate_result` json DEFAULT NULL COMMENT '校验结果JSON',
  `quality_score` int unsigned NOT NULL DEFAULT '0' COMMENT 'è´¨é‡è¯„åˆ†(0-100,S14)',
  `quality_detail` json DEFAULT NULL COMMENT 'è´¨é‡è¯„åˆ†æ˜Žç»†(S14)',
  `files_tree` json DEFAULT NULL COMMENT '生成的文件树结构',
  `version` int unsigned NOT NULL DEFAULT '1' COMMENT '版本号（Phase 3预埋）',
  `token_cost` int unsigned NOT NULL DEFAULT '0' COMMENT 'Token消耗',
  `cost` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '成本估算',
  `retry_count` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '已重试次数',
  `error_msg` text COLLATE utf8mb4_unicode_ci COMMENT '错误信息（失败时记录）',
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
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_theme_record` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_theme_record` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}ai_theme_record` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_translation_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_translation_cache` (
  `id` int NOT NULL AUTO_INCREMENT,
  `source_text_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `source_text` text COLLATE utf8mb4_unicode_ci,
  `source_lang` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'zh-CN',
  `target_lang` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `translated_text` text COLLATE utf8mb4_unicode_ci,
  `provider` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `quality_score` decimal(3,1) NOT NULL DEFAULT '0.0',
  `hit_count` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_hash_lang` (`source_text_hash`,`source_lang`,`target_lang`),
  KEY `idx_langs` (`source_lang`,`target_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI translation memory';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_translation_cache` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_translation_cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}ai_translation_cache` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_translation_glossary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_translation_glossary` (
  `id` int NOT NULL AUTO_INCREMENT,
  `source_term` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `target_term` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `source_lang` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'zh-CN',
  `target_lang` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_langs` (`source_lang`,`target_lang`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI translation glossary';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_translation_glossary` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_translation_glossary` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}ai_translation_glossary` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_workflow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_workflow` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
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
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '排序',
  `exec_count` int unsigned NOT NULL DEFAULT '0' COMMENT '执行次数',
  `success_count` int unsigned NOT NULL DEFAULT '0' COMMENT '成功次数',
  `fail_count` int unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
  `avg_duration` int unsigned NOT NULL DEFAULT '0' COMMENT '平均耗时(毫秒)',
  `cost_budget` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '成本预算(元)',
  `total_cost` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '累计成本(元)',
  `creator_id` int unsigned NOT NULL DEFAULT '0' COMMENT '创建者ID',
  `install_count` int unsigned NOT NULL DEFAULT '0' COMMENT '市场安装次数',
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
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_workflow` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_workflow` DISABLE KEYS */;
INSERT INTO `{prefix}ai_workflow` VALUES (1,'内容生成工作流','标题生成→正文写作→AI配图→多语言翻译→质量检测→SEO优化→自动发布','content_gen','{\"edges\": [{\"to\": \"content\", \"from\": \"title\"}, {\"to\": \"image\", \"from\": \"content\"}, {\"to\": \"translate\", \"from\": \"image\"}, {\"to\": \"quality\", \"from\": \"translate\"}, {\"to\": \"seo\", \"from\": \"quality\"}, {\"to\": \"publish\", \"from\": \"seo\"}], \"nodes\": [{\"id\": \"title\", \"type\": \"ai_write\", \"label\": \"生成标题\", \"config\": {\"prompt\": \"根据关键词生成吸引人的标题\"}}, {\"id\": \"content\", \"type\": \"ai_write\", \"label\": \"正文写作\", \"config\": {\"prompt\": \"根据标题生成高质量正文\"}}, {\"id\": \"image\", \"type\": \"ai_image\", \"label\": \"AI配图\", \"config\": {\"style\": \"auto\"}}, {\"id\": \"translate\", \"type\": \"ai_translate\", \"label\": \"多语言翻译\", \"config\": {\"target_langs\": [\"en\", \"ja\"]}}, {\"id\": \"quality\", \"type\": \"ai_qa\", \"label\": \"质量检测\", \"config\": {\"threshold\": 70}}, {\"id\": \"seo\", \"type\": \"ai_seo\", \"label\": \"SEO优化\", \"config\": {\"auto_fix\": true}}, {\"id\": \"publish\", \"type\": \"publish\", \"label\": \"自动发布\", \"config\": {\"channel\": \"default\"}}]}','manual',NULL,1,1,'内容生产','','',1,0,0,0,0,0.00,0.00,0,0,0.0,'active','2026-07-15 13:32:26','2026-07-15 13:32:26'),(2,'翻译工作流','内容提取→多语言翻译→术语统一→格式校验→发布','translation','{\"edges\": [{\"to\": \"translate\", \"from\": \"extract\"}, {\"to\": \"glossary\", \"from\": \"translate\"}, {\"to\": \"format\", \"from\": \"glossary\"}, {\"to\": \"publish\", \"from\": \"format\"}], \"nodes\": [{\"id\": \"extract\", \"type\": \"ai_write\", \"label\": \"内容提取\", \"config\": {}}, {\"id\": \"translate\", \"type\": \"ai_translate\", \"label\": \"多语言翻译\", \"config\": {\"target_langs\": [\"en\", \"ja\", \"ko\"]}}, {\"id\": \"glossary\", \"type\": \"ai_qa\", \"label\": \"术语统一\", \"config\": {}}, {\"id\": \"format\", \"type\": \"ai_qa\", \"label\": \"格式校验\", \"config\": {}}, {\"id\": \"publish\", \"type\": \"publish\", \"label\": \"发布\", \"config\": {}}]}','manual',NULL,1,1,'翻译','','',2,0,0,0,0,0.00,0.00,0,0,0.0,'active','2026-07-15 13:32:26','2026-07-15 13:32:26'),(3,'质量检测工作流','内容评分→问题识别→AI修复→复检→报告生成','quality','{\"edges\": [{\"to\": \"identify\", \"from\": \"score\"}, {\"to\": \"fix\", \"from\": \"identify\"}, {\"to\": \"recheck\", \"from\": \"fix\"}, {\"to\": \"report\", \"from\": \"recheck\"}], \"nodes\": [{\"id\": \"score\", \"type\": \"ai_qa\", \"label\": \"内容评分\", \"config\": {\"dimensions\": [\"completeness\", \"readability\", \"seo\", \"image_match\", \"tag_accuracy\"]}}, {\"id\": \"identify\", \"type\": \"ai_qa\", \"label\": \"问题识别\", \"config\": {}}, {\"id\": \"fix\", \"type\": \"ai_write\", \"label\": \"AI修复\", \"config\": {\"max_retries\": 3}}, {\"id\": \"recheck\", \"type\": \"ai_qa\", \"label\": \"复检\", \"config\": {\"threshold\": 80}}, {\"id\": \"report\", \"type\": \"ai_write\", \"label\": \"报告生成\", \"config\": {\"format\": \"markdown\"}}]}','manual',NULL,1,1,'质量','','',3,0,0,0,0,0.00,0.00,0,0,0.0,'active','2026-07-15 13:32:26','2026-07-15 13:32:26'),(4,'推荐优化工作流','行为分析→标签匹配→内容推荐→效果统计','recommend','{\"edges\": [{\"to\": \"match\", \"from\": \"analyze\"}, {\"to\": \"recommend\", \"from\": \"match\"}, {\"to\": \"stats\", \"from\": \"recommend\"}], \"nodes\": [{\"id\": \"analyze\", \"type\": \"ai_recommend\", \"label\": \"行为分析\", \"config\": {}}, {\"id\": \"match\", \"type\": \"ai_recommend\", \"label\": \"标签匹配\", \"config\": {\"strategy\": \"tfidf\"}}, {\"id\": \"recommend\", \"type\": \"ai_recommend\", \"label\": \"内容推荐\", \"config\": {\"count\": 10}}, {\"id\": \"stats\", \"type\": \"ai_write\", \"label\": \"效果统计\", \"config\": {}}]}','manual',NULL,1,1,'推荐','','',4,0,0,0,0,0.00,0.00,0,0,0.0,'active','2026-07-15 13:32:26','2026-07-15 13:32:26');
/*!40000 ALTER TABLE `{prefix}ai_workflow` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}ai_workflow_exec`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}ai_workflow_exec` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `workflow_id` int unsigned NOT NULL DEFAULT '0' COMMENT '工作流ID',
  `exec_status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT '状态: pending/running/success/failed/cancelled/paused',
  `trigger_type` varchar(20) NOT NULL DEFAULT 'manual' COMMENT '触发类型',
  `trigger_by` int unsigned NOT NULL DEFAULT '0' COMMENT '触发者ID',
  `target_ids` json DEFAULT NULL COMMENT '目标内容ID列表',
  `target_count` int unsigned NOT NULL DEFAULT '0' COMMENT '目标数量',
  `current_node` varchar(50) NOT NULL DEFAULT '' COMMENT '当前节点ID',
  `node_results` json DEFAULT NULL COMMENT '各节点执行结果',
  `total_duration` int unsigned NOT NULL DEFAULT '0' COMMENT '总耗时(毫秒)',
  `ai_call_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'AI调用次数',
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
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}ai_workflow_exec` WRITE;
/*!40000 ALTER TABLE `{prefix}ai_workflow_exec` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}ai_workflow_exec` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}api_key`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}api_key` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密钥名称',
  `api_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'API密钥',
  `api_secret` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'API密钥密钥',
  `scopes` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '权限范围(JSON数组)',
  `ip_whitelist` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP白名单(逗号分隔)',
  `rate_limit` int unsigned NOT NULL DEFAULT '100' COMMENT '每分钟限制次数',
  `is_active` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '是否启用',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_api_key` (`api_key`),
  KEY `idx_status` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='API密钥表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}api_key` WRITE;
/*!40000 ALTER TABLE `{prefix}api_key` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}api_key` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}api_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}api_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `api_key_id` int unsigned DEFAULT NULL COMMENT '密钥ID',
  `endpoint` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '接口路径',
  `method` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GET' COMMENT '请求方法',
  `ip_address` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '请求IP',
  `status_code` int NOT NULL DEFAULT '0' COMMENT '状态码',
  `duration_ms` int unsigned NOT NULL DEFAULT '0' COMMENT '耗时(毫秒)',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'UA标识',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_key` (`api_key_id`),
  KEY `idx_endpoint` (`endpoint`),
  KEY `idx_status` (`status_code`),
  KEY `idx_create` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='API调用日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}api_log` WRITE;
/*!40000 ALTER TABLE `{prefix}api_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}api_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}api_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}api_token` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '令牌名称',
  `auth_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bearer' COMMENT '认证类型:bearer/hmac',
  `token_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '令牌哈希(sha256)',
  `secret_key` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'HMAC密钥(仅auth_type=hmac时有效)',
  `scopes` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '*' COMMENT '权限范围(*/content.read/content.write等)',
  `rate_limit` int NOT NULL DEFAULT '60' COMMENT '速率限制(次/小时)',
  `last_used_time` int unsigned NOT NULL DEFAULT '0',
  `expire_time` int unsigned NOT NULL DEFAULT '0' COMMENT '过期时间(0永不过期)',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token_hash` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='API令牌表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}api_token` WRITE;
/*!40000 ALTER TABLE `{prefix}api_token` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}api_token` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}backup_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}backup_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `backup_type` varchar(20) NOT NULL DEFAULT '' COMMENT '类型: database/files',
  `file_name` varchar(255) NOT NULL DEFAULT '' COMMENT '备份文件名',
  `file_size` bigint unsigned NOT NULL DEFAULT '0' COMMENT '文件大小(bytes)',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态: 0=进行中 1=成功 2=失败',
  `error_msg` varchar(500) NOT NULL DEFAULT '' COMMENT '错误信息',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `backup_type` (`backup_type`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='备份操作日志';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}backup_log` WRITE;
/*!40000 ALTER TABLE `{prefix}backup_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}backup_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}banner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}banner` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标题',
  `image` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图片地址',
  `link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '链接地址',
  `target` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '_self' COMMENT '打开方式:_self/_blank',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `start_time` int unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='轮播图表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}banner` WRITE;
/*!40000 ALTER TABLE `{prefix}banner` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}banner` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}cache_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}cache_stats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stat_date` date NOT NULL COMMENT '统计日期',
  `cache_key` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '缓存键名/分组',
  `hit_count` bigint NOT NULL DEFAULT '0' COMMENT '命中次数',
  `miss_count` bigint NOT NULL DEFAULT '0' COMMENT '未命中次数',
  `hit_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '命中率(%)',
  `size_bytes` bigint NOT NULL DEFAULT '0' COMMENT '缓存大小(字节)',
  `level` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '缓存级别: L1/L2/L3',
  `prewarm_status` tinyint NOT NULL DEFAULT '0' COMMENT '预热状态: 0=未预热 1=已预热',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_date_key` (`stat_date`,`cache_key`),
  KEY `idx_stat_date` (`stat_date`),
  KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='V2.9.35 缓存统计';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}cache_stats` WRITE;
/*!40000 ALTER TABLE `{prefix}cache_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}cache_stats` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}cate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}cate` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `type` tinyint NOT NULL DEFAULT '1' COMMENT '分类类型:1产品/2案例/3新闻/4下载/5招聘/6单页',
  `model_id` int unsigned NOT NULL DEFAULT '0' COMMENT '内容模型ID(0=通用分类)',
  `content_model_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '内容模型code(留空=通用/article)',
  `list_template` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '自定义列表模板(留空=使用模型默认)',
  `detail_template` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '自定义详情模板(留空=使用模型默认)',
  `parent_id` int unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `default_style` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'formal' COMMENT '默认写作风格: formal/relaxed/professional/warm',
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_type` (`type`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_content_model_code` (`content_model_code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分类表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}cate` WRITE;
/*!40000 ALTER TABLE `{prefix}cate` DISABLE KEYS */;
INSERT INTO `{prefix}cate` VALUES (1,'产品中心',1,0,'','','',0,1,1,1776933035,1776933035,'formal'),(2,'成功案例',2,0,'','','',0,2,1,1776933035,1776933035,'formal'),(3,'新闻动态',3,0,'','','',0,3,1,1776933035,1776933035,'formal'),(4,'资料下载',4,0,'','','',0,4,1,1776933035,1776933035,'formal'),(5,'人才招聘',5,0,'','','',0,5,1,1776933035,1776933035,'formal');
/*!40000 ALTER TABLE `{prefix}cate` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}channel_platform`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}channel_platform` (
  `id` int NOT NULL AUTO_INCREMENT,
  `platform_type` varchar(20) NOT NULL COMMENT '平台类型(toutiao/zhihu/weibo)',
  `platform_name` varchar(100) NOT NULL COMMENT '平台账号名称',
  `platform_uid` varchar(100) DEFAULT '' COMMENT '平台用户ID',
  `access_token` text COMMENT '访问Token',
  `refresh_token` text COMMENT '刷新Token',
  `token_expire_time` int unsigned DEFAULT '0' COMMENT '过期时间戳',
  `config` json DEFAULT NULL COMMENT '平台配置(JSON)',
  `is_default` tinyint DEFAULT '0' COMMENT '是否默认账号',
  `status` tinyint DEFAULT '1' COMMENT '状态:1启用0禁用',
  `create_time` int unsigned DEFAULT '0',
  `update_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_platform` (`platform_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='第三方平台账号配置表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}channel_platform` WRITE;
/*!40000 ALTER TABLE `{prefix}channel_platform` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}channel_platform` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}channel_wechat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}channel_wechat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `app_id` varchar(50) NOT NULL COMMENT '微信公众号AppID',
  `app_secret` varchar(100) NOT NULL COMMENT 'AppSecret',
  `account_name` varchar(100) NOT NULL COMMENT '公众号名称',
  `account_type` varchar(20) DEFAULT 'subscription' COMMENT '类型(subscription/service)',
  `access_token` text COMMENT '当前access_token',
  `token_expire_time` int unsigned DEFAULT '0' COMMENT '过期时间戳',
  `is_default` tinyint DEFAULT '0' COMMENT '是否默认公众号',
  `status` tinyint DEFAULT '1' COMMENT '状态:1启用0禁用',
  `create_time` int unsigned DEFAULT '0',
  `update_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_appid` (`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='微信公众号配置表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}channel_wechat` WRITE;
/*!40000 ALTER TABLE `{prefix}channel_wechat` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}channel_wechat` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}collect_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}collect_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `source_id` int NOT NULL COMMENT '采集源ID',
  `title` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '采集标题',
  `url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '采集URL',
  `url_hash` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'URL MD5去重',
  `status` tinyint DEFAULT '0' COMMENT '状态: 0新采集 1已导入 2跳过(重复) 3失败',
  `content_id` int DEFAULT '0' COMMENT '导入后的内容ID',
  `error_msg` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '错误信息',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_url_hash` (`url_hash`),
  KEY `idx_source` (`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='采集日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}collect_log` WRITE;
/*!40000 ALTER TABLE `{prefix}collect_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}collect_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}collect_source`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}collect_source` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '来源名称',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rss' COMMENT '类型: rss/webpage',
  `url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '源URL',
  `rules` text COLLATE utf8mb4_unicode_ci COMMENT '采集规则(JSON: title_selector/content_selector等)',
  `cate_id` int DEFAULT '0' COMMENT '默认分类ID',
  `interval_minutes` int DEFAULT '60' COMMENT '采集间隔(分钟)',
  `is_enabled` tinyint DEFAULT '0' COMMENT '启用状态',
  `last_collect_time` int unsigned DEFAULT '0' COMMENT '最后采集时间',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='采集源表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}collect_source` WRITE;
/*!40000 ALTER TABLE `{prefix}collect_source` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}collect_source` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}comment` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `content_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `member_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '会员ID(0为游客)',
  `nickname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '邮箱',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '评论内容',
  `parent_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '父评论ID(0为顶级)',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态:0待审/1已通过/-1已拒绝',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'IP地址',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_content_status` (`content_id`,`status`),
  KEY `idx_member` (`member_id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_parent_status` (`parent_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='评论表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}comment` WRITE;
/*!40000 ALTER TABLE `{prefix}comment` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}comment` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `group` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分组',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置名',
  `value` text COLLATE utf8mb4_unicode_ci COMMENT '配置值',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text' COMMENT '类型:text/textarea/number/switch/select',
  `options` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '选项(JSON,select/switch用)',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '说明',
  `mini_config` json DEFAULT NULL COMMENT '小程序配置',
  `app_market_config` json DEFAULT NULL COMMENT '应用市场配置',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_group` (`group`)
) ENGINE=InnoDB AUTO_INCREMENT=358 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}config` WRITE;
/*!40000 ALTER TABLE `{prefix}config` DISABLE KEYS */;
INSERT INTO `{prefix}config` VALUES (1,'basic','site_name','AI-CMS v2.9.11','text','',1,'网站名称',NULL,NULL),(2,'basic','site_keywords','AI,CMS,内容管理','text','',2,'网站关键词',NULL,NULL),(3,'basic','site_description','AI驱动的企业信息管理系统','textarea','',3,'网站描述',NULL,NULL),(4,'basic','site_logo','/assets/images/logo_ico.png','text','',4,'网站Logo',NULL,NULL),(5,'basic','site_icp','','text','',5,'ICP备案号',NULL,NULL),(6,'upload','upload_max_size','10','number','',1,'上传大小限制(MB)',NULL,NULL),(7,'upload','upload_image_ext','jpg,jpeg,png,gif,webp,svg','text','',2,'允许的图片格式',NULL,NULL),(8,'ai','ai_enabled','1','switch','',1,'启用AI功能',NULL,NULL),(9,'ai','ai_default_model','deepseek-chat','text','',2,'默认AI模型',NULL,NULL),(10,'upload','upload_video_ext','mp4,webm,ogg','text','',3,'允许的视频格式',NULL,NULL),(11,'upload','upload_file_ext','pdf,doc,docx,xls,xlsx,zip,rar','text','',4,'允许的文件格式',NULL,NULL),(12,'basic','site_copyright','','text','',6,'版权信息',NULL,NULL),(13,'basic','site_stat_code','','textarea','',7,'统计代码',NULL,NULL),(14,'seo','seo_sitemap_enabled','1','switch','',1,'启用Sitemap自动生成',NULL,NULL),(15,'seo','seo_sitemap_frequency','daily','select','',2,'Sitemap更新频率',NULL,NULL),(16,'seo','seo_robots_txt','User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /member/\nDisallow: /api/\n\nSitemap: /sitemap.xml.html\n\n# AI-CMS v2.9.2','textarea','',3,'robots.txt内容',NULL,NULL),(17,'comment','comment_enabled','1','switch','',1,'启用评论功能',NULL,NULL),(18,'comment','comment_auto_approve','0','switch','',2,'评论自动审核通过',NULL,NULL),(19,'comment','comment_captcha','1','switch','',3,'评论验证码',NULL,NULL),(20,'notification','notification_enabled','1','switch','',1,'启用消息通知',NULL,NULL),(21,'member','member_register_enabled','1','switch','',1,'启用前台注册',NULL,NULL),(22,'member','member_oauth_gitee_enabled','1','switch','',2,'启用Gitee登录',NULL,NULL),(23,'ad','ad_enabled','1','switch','',1,'启用广告系统',NULL,NULL),(27,'points','points_comment','2','text','',0,'发表评论积分',NULL,NULL),(28,'points','points_comment_liked','1','text','',0,'评论被点赞积分',NULL,NULL),(29,'points','points_content_liked','3','text','',0,'内容被点赞积分',NULL,NULL),(30,'points','points_content_favorited','5','text','',0,'内容被收藏积分',NULL,NULL),(31,'points','points_daily_login','1','text','',0,'每日首次登录积分',NULL,NULL),(32,'points','points_register','50','text','',0,'注册奖励积分',NULL,NULL),(33,'points','points_comment_liked_daily_limit','10','text','',0,'评论被点赞每日上限',NULL,NULL),(34,'points','points_content_liked_daily_limit','20','text','',0,'内容被点赞每日上限',NULL,NULL),(35,'points','points_content_favorited_daily_limit','10','text','',0,'内容被收藏每日上限',NULL,NULL),(36,'oauth','wechat_open_appid','','text','',0,'微信开放平台AppID（扫码登录）',NULL,NULL),(37,'oauth','wechat_open_secret','','text','',0,'微信开放平台AppSecret',NULL,NULL),(38,'oauth','qq_appid','','text','',0,'QQ互联AppID',NULL,NULL),(39,'oauth','qq_appkey','','text','',0,'QQ互联AppKey',NULL,NULL),(40,'site','home_template','default','text','',0,'前台模板选择',NULL,NULL),(41,'site','frontend_theme','default','string','',50,'前台主题',NULL,NULL),(42,'site','admin_theme','default','string','',51,'后台主题',NULL,NULL),(43,'security','encrypt_cipher','AES-256-CBC','text','',0,'加密算法',NULL,NULL),(44,'security','captcha_type','math','text','',0,'验证码类型: math算术/turnstile腾讯验证码',NULL,NULL),(45,'security','captcha_enabled_forms','','text','',0,'需要验证码的表单code(逗号分隔)',NULL,NULL),(46,'payment','wechat_pay_appid','','text','',0,'微信支付AppID',NULL,NULL),(47,'payment','wechat_pay_mchid','','text','',0,'微信支付商户号',NULL,NULL),(48,'payment','wechat_pay_v3_key','','text','',0,'APIv3密钥',NULL,NULL),(49,'payment','wechat_pay_serial_no','','text','',0,'证书序列号',NULL,NULL),(50,'payment','wechat_pay_notify_url','/api/payment/wechat/notify','text','',0,'回调地址',NULL,NULL),(51,'payment','wechat_pay_enabled','0','switch','',0,'微信支付是否启用',NULL,NULL),(52,'oauth','qq_redirect','/oauth/qq/callback','text','',0,'QQ回调地址',NULL,NULL),(53,'oauth','oauth_wechat_enabled','0','switch','',0,'微信登录启用',NULL,NULL),(54,'oauth','oauth_qq_enabled','0','switch','',0,'QQ登录启用',NULL,NULL),(55,'email','smtp_host','','text','',0,'SMTP服务器',NULL,NULL),(56,'email','smtp_port','465','text','',0,'SMTP端口',NULL,NULL),(57,'email','smtp_username','','text','',0,'SMTP账号',NULL,NULL),(58,'email','smtp_password','','text','',0,'SMTP密码',NULL,NULL),(59,'email','smtp_from_email','','text','',0,'发件人邮箱',NULL,NULL),(60,'email','smtp_from_name','','text','',0,'发件人名称',NULL,NULL),(61,'email','smtp_ssl','1','switch','',0,'是否SSL',NULL,NULL),(62,'ai','ai_batch_max_count','10','text','',0,'批量生成最大篇数',NULL,NULL),(63,'ai','ai_batch_default_model','0','text','',0,'批量生成默认模型(0=系统默认)',NULL,NULL),(64,'ai','ai_long_info_threshold','2000','text','',0,'长文阈值(字数)',NULL,NULL),(65,'member','member_register_audit','0','switch','',1,'会员注册需管理员审核',NULL,NULL),(69,'system','search_engine','mysql','select','',10,'搜索引擎',NULL,NULL),(130,'member','vip_free_read_mode','0','select','',30,'VIP免费阅读范围: 0=不免费 1=全部免费',NULL,NULL),(131,'points','points_invite_register','50','number','',5,'邀请注册奖励积分',NULL,NULL),(132,'points','points_invite_signin','20','number','',6,'被邀请人首次签到奖励邀请人积分',NULL,NULL),(133,'points','points_invite_pay','100','number','',7,'被邀请人首次付费奖励邀请人积分',NULL,NULL),(135,'social','wechat_share_appid','','text','',1,'微信JS-SDK AppID',NULL,NULL),(136,'social','wechat_share_secret','','password','',2,'微信JS-SDK Secret',NULL,NULL),(137,'social','social_share_enabled','1','switch','',3,'是否启用社交分享',NULL,NULL),(138,'ai','image_provider','tongyi_wanxiang','select','',10,'AI配图Provider',NULL,NULL),(139,'ai','image_api_key','','password','',11,'AI配图API Key',NULL,NULL),(140,'ai','image_default_count','1','number','',12,'默认生成配图数(1-5)',NULL,NULL),(141,'ai','image_default_style','realistic','select','',13,'默认配图风格',NULL,NULL),(142,'ai','image_timeout','15','number','',14,'配图API超时(秒)',NULL,NULL),(143,'ai','ai_stat_enabled','1','switch','',15,'是否启用AI生成统计',NULL,NULL),(144,'ai','ai_stat_retention_days','30','number','',16,'AI统计保留天数',NULL,NULL),(145,'security','captcha_driver','local','select','',10,'验证码驱动(local=本地GD/tencent=腾讯验证码)',NULL,NULL),(146,'security','captcha_tencent_appid','','text','',11,'腾讯验证码AppID',NULL,NULL),(147,'security','captcha_tencent_secret','','password','',12,'腾讯验证码Secret',NULL,NULL),(148,'system','cdn_enabled','0','switch','',20,'是否启用CDN',NULL,NULL),(149,'system','cdn_domain','','text','',21,'CDN域名(如 https://cdn.example.com)',NULL,NULL),(150,'points','points_signin','5','number','',1,'每日签到基础积分',NULL,NULL),(151,'points','points_signin_3days','10','number','',2,'连续签到3天额外奖励',NULL,NULL),(152,'points','points_signin_7days','30','number','',3,'连续签到7天额外奖励',NULL,NULL),(153,'points','points_consume_ratio','0','number','',4,'消费返积分比例(0=不返, 0.1=返10%)',NULL,NULL),(154,'coupon','coupon_enabled','1','switch','',1,'是否启用优惠券系统',NULL,NULL),(155,'coupon','coupon_newbie_enabled','1','switch','',2,'是否启用新人券',NULL,NULL),(156,'coupon','coupon_newbie_days','7','number','',3,'注册后多少天内可领新人券',NULL,NULL),(157,'coupon','coupon_newbie_template_id','0','number','',4,'新人券模板ID',NULL,NULL),(158,'coupon','coupon_refund_return','1','switch','',5,'全额退款时是否退还优惠券',NULL,NULL),(159,'rating','rating_enabled','1','switch','',1,'是否启用评价评分系统',NULL,NULL),(160,'rating','rating_require_purchase','1','switch','',2,'是否要求购买后才能评价',NULL,NULL),(161,'rating','rating_anonymous_allowed','1','switch','',3,'是否允许匿名评价',NULL,NULL),(162,'rating','rating_auto_approve','0','switch','',4,'是否自动审核通过评价',NULL,NULL),(163,'rating','rating_media_max','5','number','',5,'评价最多上传图片数',NULL,NULL),(171,'ai','ai_image_default_provider','tongyi_wanxiang','select','',21,'默认AI配图Provider(tongyi_wanxiang/flux/dalle)',NULL,NULL),(172,'ai','ai_image_fallback_provider','flux','select','',22,'备用AI配图Provider',NULL,NULL),(173,'ai','ai_image_flux_enabled','0','switch','',23,'是否启用FLUX配图',NULL,NULL),(174,'ai','ai_image_flux_api_key','','text','',24,'FLUX API Key',NULL,NULL),(175,'ai','ai_image_flux_model','flux-pro','text','',25,'FLUX模型名称',NULL,NULL),(176,'ai','ai_image_dalle_enabled','0','switch','',26,'是否启用DALL-E配图',NULL,NULL),(177,'ai','ai_image_dalle_api_key','','text','',27,'DALL-E API Key',NULL,NULL),(178,'ai','ai_image_dalle_model','dall-e-3','text','',28,'DALL-E模型名称',NULL,NULL),(215,'invite','invite_reward_register','10','number','',1,'邀请注册奖励积分',NULL,NULL),(216,'invite','invite_reward_signin','20','number','',2,'邀请签到奖励积分',NULL,NULL),(217,'invite','invite_reward_pay','50','number','',3,'邀请付费奖励积分',NULL,NULL),(218,'invite','invite_enabled','1','switch','',4,'是否启用邀请奖励系统',NULL,NULL),(219,'coupon','shipping_coupon_type','free_shipping','text','用于CouponTemplate识别免邮券',50,'免邮券类型标识',NULL,NULL),(220,'shipping','shipping_free_threshold','0','number','订单金额超过此值免邮，0表示全部免邮',10,'免邮阈值(元)',NULL,NULL),(221,'shipping','shipping_default_fee','10','number','未触发免邮时的默认运费',20,'默认运费(元)',NULL,NULL),(222,'basic','language_switcher_enabled','0','switch','关闭后前台顶部不显示语言切换下拉菜单',95,'前台显示语言切换器',NULL,NULL),(223,'basic','language_sitewide','0','switch','开启后所有内容按语言隔离，仅显示当前语言的内容',96,'多语言全站生效',NULL,NULL),(224,'basic','logo_icon_only','1','switch','',0,'仅使用Logo图标(勾选:仅替换图标保留文字/不勾选:完整替换)',NULL,NULL),(225,'basic','logo_name','','text','',0,'后台品牌名称(留空则使用默认名称)',NULL,NULL),(226,'publish','publish_auto_sync_enabled','0','switch','',1,'内容发布后自动同步到已启用平台',NULL,NULL),(227,'member','member_auto_downgrade_grace_days','7','number','',5,'自动降级缓冲期天数(0=直接降级)',NULL,NULL),(228,'system','backup_keep_count','10','number','',30,'自动备份保留最近N个',NULL,NULL),(229,'system','app_version','2.9.23','text','',0,'当前系统版本号',NULL,NULL),(230,'content','content_quality_check_enabled','1','switch','',18,'启用AI内容质量检测(可读性/SEO/敏感词)',NULL,NULL),(231,'content','sensitive_words_check_enabled','1','switch','',19,'启用敏感词过滤检测',NULL,NULL),(232,'pay','pay_enabled','0','switch','',1,'启用支付功能(需先配置支付参数)',NULL,NULL),(233,'pay','pay_wechat_enabled','0','switch','',2,'启用微信支付',NULL,NULL),(234,'pay','pay_alipay_enabled','0','switch','',3,'启用支付宝支付',NULL,NULL),(235,'plugin','license_verify_enabled','0','switch','',15,'启用许可证远程验证(插件商店)',NULL,NULL),(236,'content','paid_content_enabled','0','switch','',21,'启用付费阅读功能',NULL,NULL),(238,'security','csp_mode','report_only','select','report_only=仅报告,enforce=强制拦截',1,'CSP策略模式',NULL,NULL),(239,'security','csrf_front_enabled','1','switch','',2,'启用后前台写操作需携带Token',NULL,NULL),(240,'performance','cache_warm_enabled','1','switch','',3,'内容变更后自动清除相关缓存',NULL,NULL),(241,'security','xss_log_enabled','1','switch','',4,'响应中包含潜在XSS特征时记录日志',NULL,NULL),(242,'system','version','V2.9.23','text','',0,'AI-CMS版本号',NULL,NULL),(273,'ai','image_daily_limit','50','number','',80,'AI配图每日限额',NULL,NULL),(274,'ai','image_max_batch','5','number','',81,'AI批量配图最大数量',NULL,NULL),(276,'ai','writing_styles','{\"formal\":{\"name\":\"正式风格\",\"system_prompt\":\"你是一位专业的内容编辑。请使用正式、严谨、权威的语言风格撰写内容。\"},\"casual\":{\"name\":\"轻松风格\",\"system_prompt\":\"你是一位亲切的内容创作者。请使用轻松、自然、口语化的语言风格撰写内容。\"},\"professional\":{\"name\":\"专业风格\",\"system_prompt\":\"你是一位行业专家。请使用专业、深度、有洞察力的语言风格撰写内容。\"},\"humorous\":{\"name\":\"幽默风格\",\"system_prompt\":\"你是一位幽默风趣的作家。请使用幽默、有趣、富有创意的语言风格撰写内容。\"},\"concise\":{\"name\":\"简洁风格\",\"system_prompt\":\"你是一位高效的内容编辑。请使用简洁、精炼、直切要点的语言风格撰写内容。\"}}','json','',83,'AI写作风格配置',NULL,NULL),(277,'ai','ai_theme_generate_daily_limit','50','number','',30,'每日AI主题生成上限次数',NULL,NULL),(278,'ai','ai_theme_generate_timeout','300','number','',31,'AI主题生成单次超时时间（秒）',NULL,NULL),(279,'ai','ai_theme_generate_max_tokens','8192','number','',32,'AI主题生成LLM最大Token数',NULL,NULL),(280,'ai','ai_theme_generate_temperature','0.5','number','',33,'AI主题生成LLM温度参数(0-1)',NULL,NULL),(282,'ai','ai_theme_chat_max_rounds','10','number','',40,'AI主题对话最大轮数',NULL,NULL),(283,'ai','ai_theme_chat_timeout','60','number','',41,'AI主题对话同步调用超时（秒）',NULL,NULL),(284,'ai','ai_theme_chat_context_budget','15000','number','',42,'AI主题对话上下文Token预算',NULL,NULL),(286,'ai','ai_image_default_size','1024x1024','select','<option value=\"1024x1024\">1:1 正方形 (1024x1024)</option><option value=\"1024x576\">16:9 宽屏 (1024x576)</option><option value=\"1024x768\">4:3 标准 (1024x768)</option><option value=\"768x1024\">3:4 竖屏 (768x1024)</option>',0,'AI配图默认尺寸',NULL,NULL),(287,'ai','ai_image_default_style','realistic','select','<option value=\"realistic\">写实</option><option value=\"illustration\">插画</option><option value=\"watercolor\">水彩</option><option value=\"3d_render\">3D</option><option value=\"pixel_art\">像素</option>',0,'AI配图默认风格',NULL,NULL),(288,'ai','ai_image_candidate_count','4','select','<option value=\"1\">1张</option><option value=\"2\">2张</option><option value=\"4\">4张</option>',0,'AI配图候选图数量',NULL,NULL),(289,'ai','ai_image_auto_on_publish','0','switch','',0,'发布时自动AI配图(开启后发布内容时自动为无封面文章生成封面图)',NULL,NULL),(291,'security','captcha_enabled','1','text','',0,'是否启用验证码',NULL,NULL),(292,'security','captcha_login','0','text','',0,'登录时需要验证码',NULL,NULL),(293,'security','captcha_register','1','text','',0,'注册时需要验证码',NULL,NULL),(294,'security','captcha_comment','0','text','',0,'评论时需要验证码',NULL,NULL),(295,'security','captcha_form','0','text','',0,'表单提交时需要验证码',NULL,NULL),(296,'security','captcha_fail_limit','3','text','',0,'验证码失败重试次数限制',NULL,NULL),(297,'points','points_shop_enabled','1','switch','',10,'积分商城开关',NULL,NULL),(300,'push','push_global_timeout','60','text','',0,'æŽ¨é€å…¨å±€è¶…æ—¶(ç§’)',NULL,NULL),(301,'notification','notify_default_settings','{\"system\":1,\"review\":1,\"publish\":1,\"comment_reply\":1,\"content_approve\":1,\"content_reject\":1,\"reward_receive\":1,\"level_upgrade\":1,\"level_downgrade\":1,\"level_grace_warning\":1}','text','',0,'通知默认设置(JSON)',NULL,NULL),(306,'content','content_model_enabled','1','text','',0,'启用内容模型差异化',NULL,NULL),(307,'template','template_store_category_enabled','1','text','',0,'æ¨¡æ¿å•†åº—å¯ç”¨åˆ†ç±»',NULL,NULL),(308,'content','content_model_seo_enabled','1','text','',0,'å†…å®¹æ¨¡åž‹SEOä¼˜åŒ–',NULL,NULL),(309,'content','content_model_relation_enabled','1','text','',0,'å†…å®¹æ¨¡åž‹å…³ç³»å›¾è°±',NULL,NULL),(310,'content','content_model_template_map_enabled','1','text','',0,'å†…å®¹æ¨¡åž‹æ¨¡æ¿æ˜ å°„',NULL,NULL),(311,'system','sse_max_connections_per_ip','5','text','',0,'SSE每IP最大连接数',NULL,NULL),(312,'system','sse_max_connections_per_user','3','text','',0,'SSE每用户最大连接数',NULL,NULL),(313,'system','sse_connection_timeout','1800','text','',0,'SSE连接超时时间(秒)',NULL,NULL),(314,'system','sse_heartbeat_interval','30','text','',0,'SSE心跳间隔(秒)',NULL,NULL),(315,'system','sse_message_ttl','3600','text','',0,'SSE消息存活时间(秒)',NULL,NULL),(316,'system','sse_offline_message_limit','100','text','',0,'SSE离线消息保留条数',NULL,NULL),(317,'template','template_store_payment_enabled','1','text','',0,'æ¨¡æ¿å•†åº—å¯ç”¨æ”¯ä»˜',NULL,NULL),(318,'template','template_store_alipay_enabled','0','text','',0,'æ¨¡æ¿å•†åº—å¯ç”¨æ”¯ä»˜å®',NULL,NULL),(319,'template','template_store_license_enabled','1','text','',0,'æ¨¡æ¿å•†åº—å¯ç”¨æŽˆæƒ',NULL,NULL),(320,'system','rss_enabled','1','text','',0,'启用RSS订阅',NULL,NULL),(321,'system','rss_cache_ttl','600','text','',0,'RSS缓存时间(秒)',NULL,NULL),(322,'oauth','oauth_github_enabled','0','text','',0,'启用GitHub登录',NULL,NULL),(323,'email','email_service_unified','1','text','',0,'启用统一邮件服务',NULL,NULL),(324,'template','template_store_refund_enabled','1','text','',0,'æ¨¡æ¿å•†åº—å¯ç”¨é€€æ¬¾',NULL,NULL),(325,'template','template_store_refund_days','7','text','',0,'æ¨¡æ¿å•†åº—é€€æ¬¾å¤©æ•°',NULL,NULL),(326,'template','template_store_invoice_enabled','1','text','',0,'æ¨¡æ¿å•†åº—å¯ç”¨å‘ç¥¨',NULL,NULL),(327,'template','template_store_commission_rate','30','text','',0,'æ¨¡æ¿å•†åº—ä½£é‡‘æ¯”ä¾‹(%)',NULL,NULL),(328,'template','template_store_min_withdraw','100','text','',0,'æ¨¡æ¿å•†åº—æœ€ä½ŽæçŽ°é‡‘é¢',NULL,NULL),(329,'template','template_store_settle_cycle','1','text','',0,'æ¨¡æ¿å•†åº—ç»“ç®—å‘¨æœŸ(å¤©)',NULL,NULL),(330,'template','template_store_seo_title','模板商店 - 八界AI-CMS','text','',0,'æ¨¡æ¿å•†åº—SEOæ ‡é¢˜',NULL,NULL),(331,'template','template_store_seo_description','专业CMS模板商店，提供海量优质网站模板','text','',0,'æ¨¡æ¿å•†åº—SEOæè¿°',NULL,NULL),(332,'template','template_store_seo_keywords','CMS模板,网站模板,响应式模板','text','',0,'æ¨¡æ¿å•†åº—SEOå…³é”®è¯',NULL,NULL),(333,'ai','ai_editor_paragraph_optimize','1','text','',0,'AI编辑器段落优化',NULL,NULL),(334,'ai','ai_editor_conversation','1','text','',0,'AI编辑器多轮对话',NULL,NULL),(335,'ai','ai_editor_conversation_timeout','1800','text','',0,'多轮对话超时时间(秒)',NULL,NULL),(336,'ai','ai_editor_conversation_max_token','4096','text','',0,'多轮对话最大Token数',NULL,NULL),(337,'ai','ai_editor_format_preserve','1','text','',0,'AI编辑器格式保留',NULL,NULL),(338,'ai','ai_editor_translate','1','text','',0,'AI编辑器翻译功能',NULL,NULL),(339,'ai','ai_editor_template_library','1','text','',0,'AI编辑器模板库',NULL,NULL),(340,'ai','ai_editor_snapshot','1','text','',0,'AI编辑器快照功能',NULL,NULL),(341,'ai','ai_editor_snapshot_max','50','text','',0,'快照最大保留数',NULL,NULL),(342,'ai','ai_editor_shortcut_menu','alt+space','text','',0,'AI编辑器快捷菜单',NULL,NULL),(343,'ai','ai_editor_shortcut_optimize','alt+shift+o','text','',0,'快捷键优化',NULL,NULL),(344,'ai','ai_editor_shortcut_translate','alt+shift+t','text','',0,'快捷键翻译',NULL,NULL),(345,'plugin','plugin_market_url','https://market.aicms.io/api','text','',0,'æ’ä»¶å¸‚åœºURL',NULL,NULL),(346,'plugin','plugin_auto_update_check','1','text','',0,'æ’ä»¶è‡ªåŠ¨æ›´æ–°æ£€æŸ¥',NULL,NULL),(347,'plugin','plugin_security_scan','1','text','',0,'æ’ä»¶å®‰å…¨æ‰«æ',NULL,NULL),(348,'plugin','plugin_max_filesize','52428800','text','',0,'æ’ä»¶æœ€å¤§æ–‡ä»¶å¤§å°(MB)',NULL,NULL),(349,'pwa','pwa_enabled','1','text','',0,'启用PWA离线支持',NULL,NULL),(350,'pwa','pwa_app_name','AI-CMS','text','',0,'PWAåº”ç”¨åç§°',NULL,NULL),(351,'pwa','pwa_app_short_name','AI-CMS','text','',0,'PWAåº”ç”¨çŸ­åç§°',NULL,NULL),(352,'pwa','pwa_theme_color','#0d6efd','text','',0,'PWAä¸»é¢˜è‰²',NULL,NULL),(353,'pwa','pwa_bg_color','#ffffff','text','',0,'PWAèƒŒæ™¯è‰²',NULL,NULL),(354,'pwa','pwa_push_enabled','0','text','',0,'å¯ç”¨PWAæŽ¨é€',NULL,NULL),(355,'content','content_model_diff_enabled','1','text','',0,'启用内容模型差异化功能',NULL,NULL),(356,'content','content_model_fallback_enabled','1','text','',0,'内容模型降级渲染',NULL,NULL),(357,'plugin','plugin_payout_config','{\"default_platform_ratio\":30,\"default_developer_ratio\":70,\"tiers\":[{\"min\":0,\"max\":1000,\"developer_ratio\":70},{\"min\":1000,\"max\":5000,\"developer_ratio\":75},{\"min\":5000,\"max\":999999999,\"developer_ratio\":80}]}','json','',0,'插件分成配置',NULL,NULL);
/*!40000 ALTER TABLE `{prefix}config` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标题',
  `content` longtext COLLATE utf8mb4_unicode_ci COMMENT '内容',
  `excerpt` text COLLATE utf8mb4_unicode_ci COMMENT '摘要',
  `type` tinyint NOT NULL DEFAULT '1' COMMENT '类型:1产品/2案例/3新闻/4下载/5招聘/6单页',
  `model_id` int unsigned NOT NULL DEFAULT '0' COMMENT '内容模型ID(0=未分配/使用旧逻辑)',
  `model_identifier` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'article' COMMENT 'å†…å®¹æ¨¡åž‹æ ‡è¯†',
  `template` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '前台展示模板(空=使用模型默认)',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态:0草稿/1待审/2已发布/-1已删除',
  `publish_time` int unsigned NOT NULL DEFAULT '0' COMMENT '定时发布时间(0立即)',
  `seo_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_keywords` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `custom_fields` json DEFAULT NULL COMMENT 'è‡ªå®šä¹‰å­—æ®µå€¼(JSON)',
  `recommend_weight` decimal(5,2) DEFAULT '0.00' COMMENT '推荐权重(0-100)',
  `recommend_score` decimal(5,2) DEFAULT '0.00' COMMENT '推荐综合评分',
  `seo_description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `ai_seo_json` json DEFAULT NULL COMMENT 'AI SEO优化数据JSON(V2.9.12)',
  `cate_id` int unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `user_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '作者ID',
  `cover` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '封面图',
  `ai_image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'AI配图URL(V2.9.12)',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `is_top` tinyint NOT NULL DEFAULT '0' COMMENT '是否置顶:0否/1是',
  `views` int unsigned NOT NULL DEFAULT '0' COMMENT '浏览量',
  `play_count` int unsigned NOT NULL DEFAULT '0' COMMENT '视频/音频播放量（V2.9.21 D-1）',
  `download_count` int unsigned NOT NULL DEFAULT '0' COMMENT '下载次数（V2.9.20 A-4）',
  `hotness` int unsigned NOT NULL DEFAULT '0' COMMENT '热度值',
  `is_recommend` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否推荐:0否/1是',
  `like_count` int unsigned NOT NULL DEFAULT '0' COMMENT '点赞数',
  `comment_count` int unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `is_paid` tinyint DEFAULT '0' COMMENT '是否付费: 0免费 1付费',
  `pay_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '付费价格',
  `paid_price` decimal(10,2) DEFAULT '0.00' COMMENT '付费价格',
  `paid_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'points' COMMENT '付费类型: points积分 money金额',
  `preview_length` int DEFAULT '500' COMMENT '试读字数',
  `is_chapter` tinyint DEFAULT '0' COMMENT '是否启用章节付费',
  `lang` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'zh-CN' COMMENT '内容语言',
  `translation_of` int DEFAULT '0' COMMENT '翻译源内容ID',
  `min_level_id` int DEFAULT '0' COMMENT '最低访问等级(0=无限制)',
  `chapter_price` decimal(10,2) DEFAULT '0.00' COMMENT 'ç« èŠ‚å•è´­ä»·æ ¼',
  `chapter_count` int unsigned DEFAULT '0' COMMENT 'æ€»ç« èŠ‚æ•°(çˆ¶è®°å½•)',
  `chapter_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'ç« èŠ‚æ ‡é¢˜',
  `quality_score` tinyint DEFAULT '0' COMMENT 'AIè´¨é‡è¯„åˆ†(0-100)',
  `quality_level` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'unscored' COMMENT '质量等级(excellent/good/fair/poor/unscored)',
  `seo_score` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'SEOè¯„åˆ†(0-100, V3.1)',
  `lang_site_id` int DEFAULT '0' COMMENT '所属语言站点ID',
  `is_auto_translated` tinyint DEFAULT '0' COMMENT '是否AI自动翻译:1是0否',
  `slug` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '当前生效URL别名',
  `paid_points` int DEFAULT '0' COMMENT '付费所需积分',
  `paid_preview_ratio` int DEFAULT '20' COMMENT '付费预览比例(%)',
  `paid_download_limit` int DEFAULT '3' COMMENT '付费下载次数限制',
  `paid_author_ratio` int DEFAULT '0' COMMENT '作者分成比例(%)',
  `ab_test_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'AB测试ID',
  `ab_version` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'AB测试版本: A/B',
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
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content` WRITE;
/*!40000 ALTER TABLE `{prefix}content` DISABLE KEYS */;
INSERT INTO `{prefix}content` VALUES (1,'1111273','<p>test测试222</p>\n<p>test测试</p>\n<p>test测试</p>\n<p>test测试</p>\n<p>test测试</p>','',3,0,'article','',0,0,'','',NULL,0.00,0.00,'',NULL,0,1,'',NULL,0,1,0,0,0,0,0,0,0,1776944895,1783917512,1,0.00,0.00,'points',500,0,'zh-CN',0,0,0.00,0,'',0,'unscored',18,0,0,'',0,20,3,0,0,'',NULL);
/*!40000 ALTER TABLE `{prefix}content` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content_action_plan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content_action_plan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int unsigned NOT NULL DEFAULT '0',
  `action` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'publish/unpublish/archive',
  `execute_time` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '0待执行1已执行2已取消3失败',
  `execute_log` text COLLATE utf8mb4_unicode_ci,
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_action` (`action`),
  KEY `idx_time` (`execute_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容行动计划表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content_action_plan` WRITE;
/*!40000 ALTER TABLE `{prefix}content_action_plan` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}content_action_plan` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content_archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content_archive` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content_id` int NOT NULL COMMENT '内容ID',
  `archived_by` int DEFAULT '0' COMMENT '归档操作人',
  `archive_reason` varchar(200) DEFAULT '' COMMENT '归档原因',
  `original_status` varchar(20) DEFAULT '' COMMENT '归档前状态',
  `content_snapshot` json DEFAULT NULL COMMENT '内容快照(JSON)',
  `create_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='内容归档记录表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content_archive` WRITE;
/*!40000 ALTER TABLE `{prefix}content_archive` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}content_archive` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content_audit_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int unsigned NOT NULL DEFAULT '0',
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `operation` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'create/update/delete/audit/publish/unpublish/restore',
  `diff_summary` text COLLATE utf8mb4_unicode_ci COMMENT '变更摘要(JSON)',
  `ip_address` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_operation` (`operation`),
  KEY `idx_create` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容操作日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content_audit_log` WRITE;
/*!40000 ALTER TABLE `{prefix}content_audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}content_audit_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content_ext`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content_ext` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `content_id` bigint unsigned NOT NULL COMMENT '内容ID',
  `type` tinyint NOT NULL COMMENT '内容类型',
  `data` json DEFAULT NULL COMMENT '扩展数据(JSON)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_content_type` (`content_id`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容扩展表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content_ext` WRITE;
/*!40000 ALTER TABLE `{prefix}content_ext` DISABLE KEYS */;
INSERT INTO `{prefix}content_ext` VALUES (1,1,1,'{\"product_price\": \"\", \"product_specs\": \"\", \"product_params\": \"\"}'),(2,1,3,'{\"news_author\": \"\", \"news_source\": \"\"}');
/*!40000 ALTER TABLE `{prefix}content_ext` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content_field` (
  `id` int NOT NULL AUTO_INCREMENT,
  `model_id` int NOT NULL COMMENT 'å†…å®¹æ¨¡åž‹ID',
  `field_name` varchar(50) NOT NULL COMMENT 'å­—æ®µæ ‡è¯†',
  `field_label` varchar(100) NOT NULL COMMENT 'å­—æ®µæ ‡ç­¾',
  `field_type` varchar(30) NOT NULL COMMENT 'å­—æ®µç±»åž‹',
  `field_options` json DEFAULT NULL COMMENT 'å­—æ®µé€‰é¡¹',
  `field_validation` json DEFAULT NULL COMMENT 'éªŒè¯è§„åˆ™',
  `field_layout` json DEFAULT NULL COMMENT 'å¸ƒå±€è®¾ç½®',
  `default_value` text COMMENT 'é»˜è®¤å€¼',
  `placeholder` varchar(200) DEFAULT '' COMMENT 'å ä½ç¬¦',
  `help_text` varchar(500) DEFAULT '' COMMENT 'å¸®åŠ©è¯´æ˜Ž',
  `sort_order` int DEFAULT '0' COMMENT 'æŽ’åº',
  `is_required` tinyint DEFAULT '0' COMMENT 'æ˜¯å¦å¿…å¡«',
  `is_unique` tinyint DEFAULT '0' COMMENT 'æ˜¯å¦å”¯ä¸€',
  `is_searchable` tinyint DEFAULT '0' COMMENT 'æ˜¯å¦å¯æœç´¢',
  `is_list_show` tinyint DEFAULT '0' COMMENT 'æ˜¯å¦åˆ—è¡¨å±•ç¤º',
  `is_system` tinyint DEFAULT '0' COMMENT 'ç³»ç»Ÿå­—æ®µ',
  `status` tinyint DEFAULT '1' COMMENT 'çŠ¶æ€',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_model` (`model_id`),
  KEY `idx_field` (`field_name`),
  KEY `idx_sort` (`sort_order`),
  KEY `idx_type` (`field_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='å†…å®¹æ¨¡åž‹å­—æ®µè¡¨';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content_field` WRITE;
/*!40000 ALTER TABLE `{prefix}content_field` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}content_field` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content_lang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content_lang` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `lang` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '语言代码(en/ja/ko/...)',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '翻译标题',
  `content` longtext COLLATE utf8mb4_unicode_ci COMMENT '翻译正文',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '翻译摘要',
  `seo_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_desc` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `keywords` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '翻译关键词',
  `image_alt` text COLLATE utf8mb4_unicode_ci COMMENT '图片ALT翻译(JSON格式)',
  `error_msg` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '翻译失败错误信息',
  `translate_status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '翻译状态(0=PENDING,1=PROCESSING,2=COMPLETED,3=FAILED)',
  `translate_provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '翻译Provider',
  `translate_time` int unsigned NOT NULL DEFAULT '0' COMMENT '翻译耗时(秒)',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_content_id_lang` (`content_id`,`lang`),
  KEY `idx_lang` (`lang`),
  KEY `idx_status` (`translate_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容多语言翻译版本表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content_lang` WRITE;
/*!40000 ALTER TABLE `{prefix}content_lang` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}content_lang` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content_model`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content_model` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型名称',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型标识(unique)',
  `mobile_partial_suffix` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '移动端详情模板片段后缀(E-1)',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '描述',
  `seo_title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO标题模板',
  `seo_keywords` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词模板',
  `seo_description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO描述模板',
  `template_file` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '前台专属模板文件名',
  `default_list_template` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '默认列表模板(list_{code}.html)',
  `default_detail_template` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '默认详情模板(detail_{code}.html)',
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图标CSS class或URL',
  `type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '关联内容类型(1产品/2案例/3新闻/4下载/5招聘/6单页)',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态(1启用/0禁用)',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  `delete_time` int unsigned DEFAULT '0' COMMENT '软删除时间',
  `is_deleted` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '软删除(0未删除/1已删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_type` (`type`),
  KEY `idx_status_sort` (`status`,`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容模型定义';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content_model` WRITE;
/*!40000 ALTER TABLE `{prefix}content_model` DISABLE KEYS */;
INSERT INTO `{prefix}content_model` VALUES (1,'产品信息','model_product','','用于展示产品详情，支持价格、库存、规格等字段','','','','','','','bi bi-box-seam',1,1,10,1780903524,1780903524,0,0),(2,'企业案例','model_case','','用于展示企业案例/项目，支持客户名称、项目周期等字段','','','','','','','bi bi-briefcase',2,1,20,1780903524,1780903524,0,0),(3,'新闻资讯','model_news','','用于发布新闻文章，支持来源、作者等字段','','','','','','','bi bi-newspaper',3,1,30,1780903524,1780903524,0,0),(4,'软件下载','model_download','','用于软件/资源下载，支持版本号、文件大小、下载次数等字段','','','','','','','bi bi-download',4,1,40,1780903524,1780903524,0,0),(5,'人才招聘','model_job','','用于发布招聘信息，支持薪资范围、工作地点、学历要求等字段','','','','','','','bi bi-people',5,1,50,1780903524,1780903524,0,0),(6,'单页介绍','model_page','','用于单页内容展示，支持副标题、封面图等字段','','','','','','','bi bi-file-earmark-text',6,1,60,1780903524,1780903524,0,0),(7,'图片图集','model_image','','用于图片画廊、作品集展示，支持多图轮播、图片说明等字段','{$title} - 图集 - {$site_name}','{$title},图片,图集,作品集','{$title}图片图集展示页面','content/image_show','list_image','detail_image','bi bi-images',3,1,35,1782034032,1782034032,0,0),(8,'视频内容','model_video','','用于视频内容展示与播放，支持视频链接、时长、封面等字段','{$title} - 视频 - {$site_name}','{$title},视频,播放','{$title}视频播放页面','content/video_show','list_video','detail_video','bi bi-play-btn',3,1,36,1782034032,1782034032,0,0),(10,'文章模型','article','','标准文章模型，适用于新闻、博客、资讯等内容类型','{$title} - {$site_name}','{$title},文章,资讯','{$title}文章详情页','content/article_show','list_article','detail_article','bi bi-file-text',3,1,10,1782292684,1782292684,0,0),(11,'图片模型','image','','图片图集模型，适用于画廊、作品集、相册等视觉内容','{$title} - 图集 - {$site_name}','{$title},图片,图集','{$title}图片展示页','content/image_show','list_image','detail_image','bi bi-images',3,1,20,1782292684,1782292684,0,0),(12,'下载模型','download','','下载资源模型，适用于软件、文档、模板等资源下载','{$title} - 下载 - {$site_name}','{$title},下载,资源','{$title}资源下载页','content/download_show','list_download','detail_download','bi bi-download',4,1,30,1782292684,1782292684,0,0),(13,'产品模型','product','','产品展示模型，适用于商品、服务展示等电商场景','{$title} - 产品 - {$site_name}','{$title},产品,商品','{$title}产品详情页','content/product_show','list_product','detail_product','bi bi-box',1,1,40,1782292684,1782292684,0,0),(14,'视频模型','video','','视频播放模型，适用于视频站、课程、演示等多媒体内容','{$title} - 视频 - {$site_name}','{$title},视频,播放','{$title}视频播放页','content/video_show','list_video','detail_video','bi bi-play-btn',3,1,50,1782292684,1782292684,0,0);
/*!40000 ALTER TABLE `{prefix}content_model` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content_model_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content_model_field` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `model_id` int unsigned NOT NULL DEFAULT '0' COMMENT '模型ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字段名(英文标识)',
  `label` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字段标签(中文显示名)',
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text' COMMENT '字段类型(text/textarea/rich_text/number/select/radio/checkbox/date/datetime/image/file/color/tags/location)',
  `options` text COLLATE utf8mb4_unicode_ci COMMENT '选项(JSON,用于select/radio/checkbox)',
  `default_value` text COLLATE utf8mb4_unicode_ci COMMENT '默认值',
  `placeholder` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '占位提示',
  `validation` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '验证规则(JSON)',
  `is_searchable` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否可搜索(1是/0否)',
  `is_list_show` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '列表页是否显示(1是/0否)',
  `required` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否必填(1是/0否)',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态(1启用/0禁用)',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_model_status_sort` (`model_id`,`status`,`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容模型扩展字段';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content_model_field` WRITE;
/*!40000 ALTER TABLE `{prefix}content_model_field` DISABLE KEYS */;
INSERT INTO `{prefix}content_model_field` VALUES (1,1,'price','价格','number',NULL,'0','请输入产品价格','',0,0,1,10,1,1780903524,1780903524),(2,1,'stock','库存数量','number',NULL,'0','请输入库存数量','',0,0,1,20,1,1780903524,1780903524),(3,1,'spec','产品规格','textarea',NULL,'','请输入产品规格参数','',0,0,0,30,1,1780903524,1780903524),(4,1,'brand','品牌','text',NULL,'','请输入品牌名称','',0,0,0,40,1,1780903524,1780903524),(5,2,'client_name','客户名称','text',NULL,'','请输入客户/公司名称','',0,0,1,10,1,1780903524,1780903524),(6,2,'project_period','项目周期','text',NULL,'','如：2024.01-2024.06','',0,0,0,20,1,1780903524,1780903524),(7,2,'industry','所属行业','select','[\"互联网\",\"金融\",\"教育\",\"医疗\",\"制造\",\"其他\"]','互联网','请选择所属行业','',0,0,0,30,1,1780903524,1780903524),(8,3,'source','文章来源','text',NULL,'','请输入文章来源','',0,0,0,10,1,1780903524,1780903524),(9,3,'author','作者','text',NULL,'','请输入作者姓名','',0,0,0,20,1,1780903524,1780903524),(10,3,'is_top','是否置顶','radio','[\"否\",\"是\"]','0','','',0,0,0,30,1,1780903524,1780903524),(11,4,'version','版本号','text',NULL,'1.0.0','如：1.0.0','',0,0,1,10,1,1780903524,1780903524),(12,4,'file_size','文件大小','text',NULL,'','如：15.6 MB','',0,0,0,20,1,1780903524,1780903524),(13,4,'download_url','下载链接','text',NULL,'','请输入下载链接','',0,0,1,30,1,1780903524,1780903524),(14,5,'salary_range','薪资范围','text',NULL,'','如：15K-25K','',0,0,1,10,1,1780903524,1780903524),(15,5,'location','工作地点','text',NULL,'','如：北京市海淀区','',0,0,1,20,1,1780903524,1780903524),(16,5,'education','学历要求','select','[\"不限\",\"大专\",\"本科\",\"硕士\",\"博士\"]','本科','请选择学历要求','',0,0,1,30,1,1780903524,1780903524),(17,6,'subtitle','副标题','text',NULL,'','请输入副标题','',0,0,0,10,1,1780903524,1780903524),(18,6,'cover_image','封面图','image',NULL,'','请上传封面图片','',0,0,0,20,1,1780903524,1780903524),(19,7,'gallery','图集','image',NULL,'','请上传图片(可多选)','',0,0,1,10,1,1782034032,1782034032),(20,7,'image_description','图片说明','textarea',NULL,'','请输入图片描述','',0,0,0,20,1,1782034032,1782034032),(21,7,'photographer','摄影师','text',NULL,'','请输入摄影师姓名','',0,0,0,30,1,1782034032,1782034032),(22,8,'video_url','视频链接','text',NULL,'','请输入视频播放链接','',0,0,1,10,1,1782034032,1782034032),(23,8,'video_cover','视频封面','image',NULL,'','请上传视频封面图','',0,0,0,20,1,1782034032,1782034032),(24,8,'duration','视频时长','text',NULL,'','如：12:30','',0,0,0,30,1,1782034032,1782034032);
/*!40000 ALTER TABLE `{prefix}content_model_field` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content_model_migration_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content_model_migration_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `model_id` int unsigned NOT NULL DEFAULT '0' COMMENT '模型ID',
  `migration_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '迁移类型(batch_assign/import_from_type/init_fields)',
  `total_count` int unsigned NOT NULL DEFAULT '0' COMMENT '处理总数',
  `success_count` int unsigned NOT NULL DEFAULT '0' COMMENT '成功数',
  `fail_count` int unsigned NOT NULL DEFAULT '0' COMMENT '失败数',
  `error_detail` text COLLATE utf8mb4_unicode_ci COMMENT '错误详情(JSON)',
  `operator` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作人',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_migration_type` (`migration_type`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容模型迁移日志';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content_model_migration_log` WRITE;
/*!40000 ALTER TABLE `{prefix}content_model_migration_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}content_model_migration_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content_model_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content_model_stats` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `model_id` int unsigned NOT NULL DEFAULT '0' COMMENT '内容模型ID',
  `stat_date` date NOT NULL COMMENT '统计日期',
  `total_count` int unsigned NOT NULL DEFAULT '0' COMMENT '内容总数',
  `published_count` int unsigned NOT NULL DEFAULT '0' COMMENT '已发布数',
  `draft_count` int unsigned NOT NULL DEFAULT '0' COMMENT '草稿数',
  `pending_count` int unsigned NOT NULL DEFAULT '0' COMMENT '待审核数',
  `new_count` int unsigned NOT NULL DEFAULT '0' COMMENT '当日新增数',
  `total_views` int unsigned NOT NULL DEFAULT '0' COMMENT '总浏览量',
  `avg_quality_score` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '平均质量分',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_model_date` (`model_id`,`stat_date`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容模型数据统计';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content_model_stats` WRITE;
/*!40000 ALTER TABLE `{prefix}content_model_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}content_model_stats` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content_model_template_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content_model_template_map` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `model_id` int unsigned NOT NULL DEFAULT '0' COMMENT '内容模型ID',
  `template_id` int unsigned NOT NULL DEFAULT '0' COMMENT '模板ID({prefix}template_store)',
  `tag_match` text COLLATE utf8mb4_unicode_ci COMMENT '标签匹配规则(JSON)',
  `priority` tinyint unsigned NOT NULL DEFAULT '50' COMMENT '优先级(1-100,越大越优先)',
  `is_default` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否默认模板(1是/0否)',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态(1启用/0禁用)',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_model_template` (`model_id`,`template_id`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_status_priority` (`status`,`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容模型-模板映射';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content_model_template_map` WRITE;
/*!40000 ALTER TABLE `{prefix}content_model_template_map` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}content_model_template_map` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content_quality_score`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content_quality_score` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int NOT NULL COMMENT '内容ID',
  `completeness_score` int DEFAULT '0' COMMENT '完整性评分(0-100)',
  `readability_score` int DEFAULT '0' COMMENT '可读性评分(0-100)',
  `seo_score` int DEFAULT '0' COMMENT 'SEO优化评分(0-100)',
  `image_match_score` int DEFAULT '0' COMMENT '配图匹配评分(0-100)',
  `tag_accuracy_score` int DEFAULT '0' COMMENT '标签准确评分(0-100)',
  `total_score` int DEFAULT '0' COMMENT '综合评分(0-100)',
  `suggestions` json DEFAULT NULL COMMENT '改进建议(JSON数组)',
  `score_source` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'auto' COMMENT '评分来源(auto/manual/batch)',
  `repair_count` int DEFAULT '0' COMMENT '修复次数',
  `last_repair_time` int unsigned DEFAULT '0' COMMENT '最近修复时间',
  `repair_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'none' COMMENT '修复状态(none/auto/suggested/manual/failed/needs_manual)',
  `create_time` int unsigned DEFAULT '0',
  `update_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_content` (`content_id`),
  KEY `idx_total` (`total_score`),
  KEY `idx_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容质量评分表 - V2.9.33';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content_quality_score` WRITE;
/*!40000 ALTER TABLE `{prefix}content_quality_score` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}content_quality_score` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content_rating`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content_rating` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int unsigned NOT NULL COMMENT '内容ID',
  `member_id` int unsigned NOT NULL COMMENT '评价用户ID',
  `rating` tinyint NOT NULL COMMENT '评分 1-5',
  `title` varchar(255) DEFAULT '' COMMENT '评价标题',
  `content` text COMMENT '评价内容',
  `has_media` tinyint DEFAULT '0' COMMENT '是否有图片/视频 0否1是',
  `media_urls` text COMMENT '图片/视频URL列表JSON',
  `is_anonymous` tinyint DEFAULT '0' COMMENT '是否匿名 0否1是',
  `reply_count` int DEFAULT '0' COMMENT '回复数',
  `like_count` int DEFAULT '0' COMMENT '点赞数',
  `status` tinyint DEFAULT '1' COMMENT '状态:0待审/1通过/2拒绝',
  `create_time` int unsigned DEFAULT '0',
  `update_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_content_member` (`content_id`,`member_id`),
  KEY `idx_content_rating` (`content_id`,`rating`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='内容评价评分表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content_rating` WRITE;
/*!40000 ALTER TABLE `{prefix}content_rating` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}content_rating` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content_recommend_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content_recommend_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int unsigned NOT NULL DEFAULT '0',
  `recommended_content_id` int unsigned NOT NULL DEFAULT '0',
  `user_id` int unsigned DEFAULT '0',
  `source` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'tag/category/relation',
  `impressed` tinyint unsigned NOT NULL DEFAULT '0',
  `clicked` tinyint unsigned NOT NULL DEFAULT '0',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_create` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容推荐日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content_recommend_log` WRITE;
/*!40000 ALTER TABLE `{prefix}content_recommend_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}content_recommend_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content_relation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content_relation` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `content_id` int unsigned NOT NULL DEFAULT '0' COMMENT '主内容ID',
  `relation_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联内容ID',
  `relation_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'related' COMMENT '关系类型(related/previous_next/recommended/similar)',
  `relation_weight` decimal(5,2) NOT NULL DEFAULT '1.00' COMMENT '关联权重(0-1)',
  `is_manual` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否手动关联(1是/0AI自动)',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_content_relation_type` (`content_id`,`relation_id`,`relation_type`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_relation_id` (`relation_id`),
  KEY `idx_relation_type` (`relation_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容关系表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content_relation` WRITE;
/*!40000 ALTER TABLE `{prefix}content_relation` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}content_relation` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content_slug`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content_slug` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content_id` int NOT NULL COMMENT '内容ID',
  `lang_site_id` int NOT NULL COMMENT '语言站点ID',
  `slug` varchar(200) NOT NULL COMMENT 'URL别名',
  `is_active` tinyint DEFAULT '1' COMMENT '是否当前生效',
  `create_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lang_slug` (`lang_site_id`,`slug`),
  KEY `idx_content` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='内容URL别名表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content_slug` WRITE;
/*!40000 ALTER TABLE `{prefix}content_slug` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}content_slug` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content_subscription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content_subscription` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `subscribe_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'category/tag/author',
  `subscribe_id` int unsigned NOT NULL DEFAULT '0',
  `notify_email` tinyint unsigned NOT NULL DEFAULT '0',
  `notify_site` tinyint unsigned NOT NULL DEFAULT '1',
  `digest_frequency` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'instant' COMMENT 'instant/daily/weekly',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_subscription` (`user_id`,`subscribe_type`,`subscribe_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_type_id` (`subscribe_type`,`subscribe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容订阅表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content_subscription` WRITE;
/*!40000 ALTER TABLE `{prefix}content_subscription` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}content_subscription` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content_tag` (
  `content_id` bigint unsigned NOT NULL COMMENT '内容ID',
  `tag_id` int unsigned NOT NULL COMMENT '标签ID',
  PRIMARY KEY (`content_id`,`tag_id`),
  KEY `idx_tag` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容标签关联表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content_tag` WRITE;
/*!40000 ALTER TABLE `{prefix}content_tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}content_tag` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}content_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}content_version` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ä¸»é”®',
  `content_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'å†…å®¹ID',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'æ ‡é¢˜',
  `content` longtext COLLATE utf8mb4_unicode_ci COMMENT 'æ­£æ–‡å†…å®¹',
  `excerpt` text COLLATE utf8mb4_unicode_ci COMMENT 'æ‘˜è¦',
  `cover` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'å°é¢å›¾',
  `cate_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'åˆ†ç±»ID',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT 'çŠ¶æ€',
  `ext_data` text COLLATE utf8mb4_unicode_ci COMMENT 'æ‰©å±•å­—æ®µæ•°æ®(JSON)',
  `tag_ids` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'æ ‡ç­¾IDé›†åˆ',
  `user_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT 'æ“ä½œäººID',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT 'ç‰ˆæœ¬åˆ›å»ºæ—¶é—´',
  PRIMARY KEY (`id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='å†…å®¹ç‰ˆæœ¬åŽ†å²è¡¨';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}content_version` WRITE;
/*!40000 ALTER TABLE `{prefix}content_version` DISABLE KEYS */;
INSERT INTO `{prefix}content_version` VALUES (1,1,'1111223','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"product_price\":\"\",\"product_specs\":\"\",\"product_params\":\"\"}','',1,1779207823),(2,1,'1111223','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"product_price\":\"\",\"product_specs\":\"\",\"product_params\":\"\"}','',1,1779208000),(3,1,'1111223','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"product_price\":\"\",\"product_specs\":\"\",\"product_params\":\"\"}','',1,1779208051),(4,1,'11112234','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779211643),(5,1,'11112234','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779211646),(6,1,'11112234','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779211651),(7,1,'11112234','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779211662),(8,1,'1111223','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779211797),(9,1,'1111223','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779211817),(10,1,'11112235','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779211879),(11,1,'11112235','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779211907),(12,1,'1111223','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779212211),(13,1,'1111223','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779212370),(14,1,'11112239','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779212510),(15,1,'11112233','<p>test测试</p>\r\n<p>test测试</p>\r\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779214652),(16,1,'1111269','<p>test测试</p>\n<p>test测试</p>\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779214697),(17,1,'1111261','<p>test测试</p>\n<p>test测试</p>\n<p>test测试</p>','','',0,0,'{\"news_author\":\"\",\"news_source\":\"\"}','',1,1779246054);
/*!40000 ALTER TABLE `{prefix}content_version` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}coupon_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}coupon_template` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `coupon_name` varchar(100) NOT NULL COMMENT '券名称，如"满100减20"',
  `coupon_type` enum('reduce','discount','free_shipping') NOT NULL COMMENT '满减/discount/免邮',
  `condition_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '门槛金额(免邮券填0)',
  `reduce_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '满减券:减免金额; 折扣券:折扣率(0.9=9折); 免邮券:0',
  `total_stock` int NOT NULL DEFAULT '0' COMMENT '发行总量',
  `remain_stock` int NOT NULL DEFAULT '0' COMMENT '剩余库存',
  `per_user_limit` int NOT NULL DEFAULT '1' COMMENT '每人限领数量',
  `start_time` int unsigned NOT NULL DEFAULT '0' COMMENT '有效期开始时间',
  `end_time` int unsigned NOT NULL DEFAULT '0' COMMENT '有效期结束时间',
  `scope_type` enum('all','category','content') NOT NULL DEFAULT 'all' COMMENT '适用范围:全部/指定分类/指定商品',
  `scope_value` text COMMENT '适用范围值(分类ID/商品ID,JSON数组)',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态:0草稿/1启用/2停用/3已过期',
  `create_time` int unsigned DEFAULT '0',
  `update_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_type_status` (`coupon_type`,`status`),
  KEY `idx_time` (`start_time`,`end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='优惠券模板表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}coupon_template` WRITE;
/*!40000 ALTER TABLE `{prefix}coupon_template` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}coupon_template` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}custom_var`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}custom_var` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `value` text COLLATE utf8mb4_unicode_ci,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `sort` int NOT NULL DEFAULT '0',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='自定义变量表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}custom_var` WRITE;
/*!40000 ALTER TABLE `{prefix}custom_var` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}custom_var` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}custom_whitelist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}custom_whitelist` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `list_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'css/js',
  `category` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'property/value/selector/function/api/object/event',
  `item_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_pattern` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `security_level` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'safe' COMMENT 'safe/approval/forbidden',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `creator_id` int DEFAULT '0',
  `create_time` int unsigned DEFAULT '0',
  `update_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type_name` (`list_type`,`category`,`item_name`),
  KEY `idx_type` (`list_type`),
  KEY `idx_security` (`security_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='自定义CSS/JS白名单表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}custom_whitelist` WRITE;
/*!40000 ALTER TABLE `{prefix}custom_whitelist` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}custom_whitelist` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}data_alert`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}data_alert` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `alert_name` varchar(100) NOT NULL COMMENT '预警名称',
  `alert_metric` varchar(50) NOT NULL COMMENT '预警指标(pv/uv/orders/revenue/error_rate/response_time等)',
  `alert_rule` json NOT NULL COMMENT '预警规则(JSON: operator/threshold/window)',
  `alert_level` varchar(10) DEFAULT 'warning' COMMENT '预警级别(info/warning/critical)',
  `alert_channels` json DEFAULT NULL COMMENT '预警通知渠道(JSON: email/sms/webhook/dingtalk/feishu)',
  `alert_recipients` json DEFAULT NULL COMMENT '预警接收人(JSON)',
  `escalation_config` json DEFAULT NULL COMMENT '升级配置(JSON)',
  `cooldown_minutes` int DEFAULT '60' COMMENT '冷却时间(分钟)',
  `is_active` tinyint DEFAULT '1' COMMENT '是否启用',
  `last_triggered` datetime DEFAULT NULL COMMENT '最后触发时间',
  `trigger_count` int DEFAULT '0' COMMENT '触发次数',
  `resolved_count` int DEFAULT '0' COMMENT '已解决次数',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_metric` (`alert_metric`),
  KEY `idx_level` (`alert_level`),
  KEY `idx_active` (`is_active`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='数据预警规则表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}data_alert` WRITE;
/*!40000 ALTER TABLE `{prefix}data_alert` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}data_alert` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}data_classification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}data_classification` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `table_name` varchar(100) NOT NULL COMMENT '表名',
  `field_name` varchar(100) NOT NULL COMMENT '字段名',
  `classification_level` varchar(10) NOT NULL COMMENT '分级级别(L0/L1/L2/L3/L4)',
  `classification_method` varchar(30) DEFAULT 'manual' COMMENT '分级方式(manual/auto/regex/ai)',
  `classification_reason` text COMMENT '分级原因',
  `protection_measures` json DEFAULT NULL COMMENT '保护措施(JSON)',
  `is_active` tinyint DEFAULT '1' COMMENT '是否启用',
  `reviewed_by` int DEFAULT '0' COMMENT '审核人ID',
  `reviewed_at` datetime DEFAULT NULL COMMENT '审核时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_table_field` (`table_name`,`field_name`),
  KEY `idx_level` (`classification_level`),
  KEY `idx_active` (`is_active`),
  KEY `idx_method` (`classification_method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='数据安全分级表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}data_classification` WRITE;
/*!40000 ALTER TABLE `{prefix}data_classification` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}data_classification` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}data_report_subscription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}data_report_subscription` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `report_id` int NOT NULL COMMENT '报表ID',
  `subscriber_id` int NOT NULL COMMENT '订阅者ID',
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
  `push_count` int DEFAULT '0' COMMENT '推送次数',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_report` (`report_id`),
  KEY `idx_subscriber` (`subscriber_id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`subscription_type`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='数据报告订阅表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}data_report_subscription` WRITE;
/*!40000 ALTER TABLE `{prefix}data_report_subscription` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}data_report_subscription` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}developer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}developer` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `real_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '真实姓名',
  `contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '联系电话',
  `contact_email` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '联系邮箱',
  `introduction` text COLLATE utf8mb4_unicode_ci COMMENT '开发经验介绍',
  `level` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '认证等级:1初级2认证3专业',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态:0待审1通过2驳回3禁用',
  `audit_remark` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '审核备注',
  `total_templates` int unsigned NOT NULL DEFAULT '0' COMMENT '已发布模板数',
  `total_revenue` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '累计收益',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user` (`user_id`),
  KEY `idx_level` (`level`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='开发者信息表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}developer` WRITE;
/*!40000 ALTER TABLE `{prefix}developer` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}developer` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}email_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}email_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `template_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '模板标识',
  `to_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '收件人',
  `subject` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '实际主题',
  `status` tinyint DEFAULT '0' COMMENT '状态: 0排队 1成功 2失败',
  `error_msg` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '错误信息',
  `send_time` int unsigned DEFAULT '0' COMMENT '发送时间',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_to` (`to_email`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件发送日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}email_log` WRITE;
/*!40000 ALTER TABLE `{prefix}email_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}email_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}email_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}email_queue` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `template_code` varchar(100) NOT NULL DEFAULT '',
  `to_email` varchar(255) NOT NULL,
  `vars` text COMMENT '模板变量JSON',
  `status` tinyint DEFAULT '0' COMMENT '0待发 1已发 2失败',
  `retry_count` tinyint DEFAULT '0',
  `max_retries` tinyint DEFAULT '3' COMMENT '最大重试次数',
  `error_msg` varchar(500) DEFAULT '',
  `create_time` int unsigned DEFAULT '0',
  `sent_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='邮件队列';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}email_queue` WRITE;
/*!40000 ALTER TABLE `{prefix}email_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}email_queue` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}email_subscriber`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}email_subscriber` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱地址',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态:1订阅/0退订',
  `source` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '来源页面',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件订阅表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}email_subscriber` WRITE;
/*!40000 ALTER TABLE `{prefix}email_subscriber` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}email_subscriber` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}email_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}email_template` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模板标识: register/forgot_password/comment_notify/payment_success',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模板名称',
  `subject` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '邮件主题（支持变量）',
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '邮件正文HTML（支持变量）',
  `vars` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '可用变量(逗号分隔): username,site_name,content_title等',
  `is_enabled` tinyint DEFAULT '1' COMMENT '启用状态',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件模板表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}email_template` WRITE;
/*!40000 ALTER TABLE `{prefix}email_template` DISABLE KEYS */;
INSERT INTO `{prefix}email_template` VALUES (1,'register','注册欢迎','欢迎注册{{site_name}}','<h2>欢迎加入{{site_name}}！</h2><p>亲爱的{{username}}，恭喜您成功注册。</p><p>您的账号：{{email}}</p>','username,site_name,email',1,1777774069,1777774069),(2,'forgot_password','密码找回','{{site_name}} - 密码找回','<h2>密码找回</h2><p>您好 {{username}}，请点击以下链接重置密码：</p><p><a href=\"{{reset_url}}\">重置密码</a></p><p>链接有效期30分钟。</p>','username,site_name,reset_url',1,1777774069,1777774069),(3,'comment_notify','评论通知','{{site_name}} - 您的文章有新评论','<h2>新评论通知</h2><p>您的文章《{{content_title}}》收到一条新评论：</p><blockquote>{{comment_content}}</blockquote><p>评论者：{{comment_author}}</p>','username,site_name,content_title,comment_content,comment_author',1,1777774069,1777774069),(4,'payment_success','付费成功','{{site_name}} - 付费成功通知','<h2>付费成功</h2><p>您已成功购买《{{content_title}}》，支付金额：{{amount}}元</p><p>感谢您的支持！</p>','username,site_name,content_title,amount',1,1777774069,1777774069),(5,'subscribe_confirm','订阅确认','请确认订阅【{site_name}】','<!DOCTYPE html><html><head><meta charset=\"utf-8\"></head><body style=\"font-family:Arial,sans-serif;background:#f5f5f5;padding:20px\"><div style=\"max-width:600px;margin:0 auto;background:#fff;border-radius:8px;padding:30px\"><h2 style=\"color:#333\">📬 确认订阅</h2><p style=\"color:#666\">您好！感谢您订阅<strong>{site_name}</strong>。</p><p style=\"color:#666\">请点击下方按钮确认：</p><div style=\"text-align:center;margin:30px 0\"><a href=\"{confirm_url}\" style=\"display:inline-block;background:#007bff;color:#fff;padding:12px 32px;border-radius:6px;text-decoration:none;font-size:16px\">确认订阅</a></div></div></body></html>','site_name,confirm_url',1,1780718635,1780718635),(6,'content_notify','新内容通知','【{site_name}】新内容发布：{title}','<!DOCTYPE html><html><head><meta charset=\"utf-8\"></head><body style=\"font-family:Arial,sans-serif;background:#f5f5f5;padding:20px\"><div style=\"max-width:600px;margin:0 auto;background:#fff;border-radius:8px;padding:30px\"><h2 style=\"color:#333\">{title}</h2><p style=\"color:#666;line-height:1.8\">{summary}</p><div style=\"text-align:center;margin:30px 0\"><a href=\"{content_url}\" style=\"display:inline-block;background:#007bff;color:#fff;padding:12px 32px;border-radius:6px;text-decoration:none;font-size:16px\">查看详情</a></div></div></body></html>','site_name,title,summary,content_url',1,1780718635,1780718635),(7,'subscribe_welcome','订阅欢迎','欢迎订阅【{site_name}】','<!DOCTYPE html><html><head><meta charset=\"utf-8\"></head><body style=\"font-family:Arial,sans-serif;background:#f5f5f5;padding:20px\"><div style=\"max-width:600px;margin:0 auto;background:#fff;border-radius:8px;padding:30px\"><h2 style=\"color:#333\">🎉 订阅成功</h2><p style=\"color:#666\">您已成功订阅<strong>{site_name}</strong>，我们将第一时间推送最新内容。</p></div></body></html>','site_name',1,1780718635,1780718635),(14,'content_publish','内容发布通知','【{site_name}】新内容发布：{content_title}','<!DOCTYPE html><html><head><meta charset=\"utf-8\"></head><body style=\"font-family:Arial,sans-serif;background:#f5f5f5;padding:20px\"><div style=\"max-width:600px;margin:0 auto;background:#fff;border-radius:8px;padding:30px\"><h2 style=\"color:#333\">{content_title}</h2><p style=\"color:#666\">{content_summary}</p><div style=\"text-align:center;margin:30px 0\"><a href=\"{content_url}\" style=\"display:inline-block;background:#007bff;color:#fff;padding:12px 32px;border-radius:6px;text-decoration:none;font-size:16px\">查看详情</a></div></div></body></html>','site_name,content_title,content_summary,content_url,content_cover,unsubscribe_url,subscriber_email',1,1780727316,1780727316),(15,'unsubscribe','退订确认','您已成功退订 {site_name} 的邮件通知','<!DOCTYPE html><html><head><meta charset=\"utf-8\"></head><body style=\"font-family:Arial,sans-serif;background:#f5f5f5;padding:20px\"><div style=\"max-width:600px;margin:0 auto;background:#fff;border-radius:8px;padding:30px\"><h2 style=\"color:#333\">退订确认</h2><p style=\"color:#666\">您已成功退订 <strong>{site_name}</strong> 的邮件通知，将不再收到相关内容推送。</p><p style=\"color:#666\">如想重新订阅，请 <a href=\"{subscribe_url}\">点击此处</a>。</p></div></body></html>','site_name,subscribe_url,subscriber_email',1,1780727316,1780727316);
/*!40000 ALTER TABLE `{prefix}email_template` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}encryption_key`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}encryption_key` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '密钥标识(唯一)',
  `key_name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密钥名称(描述用途)',
  `encrypted_value` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '加密后的密钥值(用系统主密钥加密)',
  `algorithm` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AES-256-CBC' COMMENT '加密算法',
  `version` int NOT NULL DEFAULT '1' COMMENT '密钥版本号',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '1=当前使用 2=已轮换(仅解密) 3=已废弃',
  `created_by` int unsigned NOT NULL DEFAULT '0' COMMENT '创建者用户ID',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rotated_at` datetime DEFAULT NULL COMMENT '轮换时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key_id` (`key_id`),
  KEY `idx_status` (`status`),
  KEY `idx_version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='V2.9.35 加密密钥管理';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}encryption_key` WRITE;
/*!40000 ALTER TABLE `{prefix}encryption_key` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}encryption_key` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}favorite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}favorite` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `folder_id` int DEFAULT '0' COMMENT '收藏夹ID',
  `content_id` int unsigned NOT NULL DEFAULT '0',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_content` (`user_id`,`content_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_folder_id` (`folder_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='收藏表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}favorite` WRITE;
/*!40000 ALTER TABLE `{prefix}favorite` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}favorite` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}favorite_folder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}favorite_folder` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT '用户ID',
  `name` varchar(100) NOT NULL COMMENT '收藏夹名称',
  `description` varchar(500) DEFAULT '' COMMENT '收藏夹描述',
  `is_public` tinyint DEFAULT '0' COMMENT '是否公开:0否1是',
  `sort` int DEFAULT '99' COMMENT '排序',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='收藏夹表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}favorite_folder` WRITE;
/*!40000 ALTER TABLE `{prefix}favorite_folder` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}favorite_folder` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}feature_registry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}feature_registry` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sprint_code` varchar(20) NOT NULL COMMENT '所属Sprint代码(R/Q/T2/AI2/UX/DOC)',
  `feature_code` varchar(50) NOT NULL COMMENT '功能点编码',
  `feature_name` varchar(200) NOT NULL COMMENT '功能点名称',
  `service_class` varchar(200) DEFAULT '' COMMENT '对应Service类',
  `controller_route` varchar(200) DEFAULT '' COMMENT '对应Controller路由',
  `status` tinyint DEFAULT '1' COMMENT '状态:1正常0禁用2异常',
  `health_check_url` varchar(500) DEFAULT '' COMMENT '健康检查URL',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_feature` (`sprint_code`,`feature_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='功能点注册表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}feature_registry` WRITE;
/*!40000 ALTER TABLE `{prefix}feature_registry` DISABLE KEYS */;
INSERT INTO `{prefix}feature_registry` VALUES (1,'R','R-1','皮肤&模板补全修复','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(2,'R','R-2','代码架构修复','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(3,'R','R-3','插件&API修复','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(4,'R','R-4','模板&市场修复','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(5,'R','R-5','其他零散修复回归','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(6,'Q','Q-1','功能验收脚本框架','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(7,'Q','Q-2','运行时功能看板','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(8,'Q','Q-3','V2.9.29全量功能回归测试脚本','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(9,'Q','Q-4','持续集成验收流水线','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(10,'Q','Q-5','验收报告自动生成','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(11,'Q','Q-6','模板质量自动检测增强','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(12,'T2','T2-1','模板商店个人中心','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(13,'T2','T2-2','模板收藏夹增强','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(14,'T2','T2-3','模板批量管理工具','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(15,'T2','T2-4','模板商店搜索增强','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(16,'T2','T2-5','模板分类管理增强','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(17,'AI2','AI2-1','AI批量内容改写','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(18,'AI2','AI2-2','AI SEO预览与优化','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(19,'AI2','AI2-3','AI智能配图基础版','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(20,'AI2','AI2-4','AI多风格写作扩展','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(21,'UX','UX-1','移动端体验增强','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(22,'UX','UX-2','页面加载性能优化','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(23,'UX','UX-3','后台交互体验优化','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(24,'DOC','DOC-1','系统配置文档','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(25,'DOC','DOC-2','API开放文档完善','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01'),(26,'DOC','DOC-3','部署运维文档','','',1,'','2026-07-09 17:06:01','2026-07-09 17:06:01');
/*!40000 ALTER TABLE `{prefix}feature_registry` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}form`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}form` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表单名称',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表单唯一标识',
  `fields` json NOT NULL COMMENT '字段配置（JSON数组）',
  `fields_config` json DEFAULT NULL,
  `submit_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '提交' COMMENT '提交按钮文案',
  `success_msg` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '提交成功' COMMENT '提交成功提示',
  `success_action` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'message' COMMENT '提交后动作: message消息 redirect跳转',
  `redirect_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '跳转URL',
  `anti_spam` tinyint DEFAULT '0' COMMENT '防刷: 0无 1验证码 2IP限制',
  `is_enabled` tinyint DEFAULT '1',
  `sort` int DEFAULT '0',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='表单定义表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}form` WRITE;
/*!40000 ALTER TABLE `{prefix}form` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}form` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}form_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}form_data` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `form_id` int NOT NULL,
  `fields_data` json NOT NULL COMMENT '提交数据（JSON）',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '提交者IP',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `is_read` tinyint DEFAULT '0' COMMENT '是否已读',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_form` (`form_id`),
  KEY `idx_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='表单提交数据表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}form_data` WRITE;
/*!40000 ALTER TABLE `{prefix}form_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}form_data` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}h5_user_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}h5_user_config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL COMMENT '用户ID',
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
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}h5_user_config` WRITE;
/*!40000 ALTER TABLE `{prefix}h5_user_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}h5_user_config` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}image_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}image_task` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `task_id` varchar(64) NOT NULL COMMENT 'å¤–éƒ¨ä»»åŠ¡ID(FLUXè¿”å›žçš„id)',
  `provider` varchar(20) NOT NULL DEFAULT 'flux' COMMENT 'Provideræ ‡è¯†(flux/dalle/tongyi_wanxiang)',
  `poll_url` varchar(500) DEFAULT '' COMMENT 'è½®è¯¢URL',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '0pending/1processing/2completed/3failed',
  `prompt` varchar(500) DEFAULT '' COMMENT 'ç”Ÿæˆæç¤ºè¯',
  `result` json DEFAULT NULL COMMENT 'ç”Ÿæˆç»“æžœJSON',
  `attempts` tinyint DEFAULT '0' COMMENT 'è½®è¯¢å°è¯•æ¬¡æ•°',
  `max_attempts` tinyint DEFAULT '30' COMMENT 'æœ€å¤§å°è¯•æ¬¡æ•°(30æ¬¡â‰ˆ90ç§’è¶…æ—¶)',
  `related_type` varchar(30) DEFAULT '' COMMENT 'å…³è”ç±»åž‹(content/batch)',
  `related_id` int unsigned DEFAULT '0' COMMENT 'å…³è”ID',
  `error_msg` varchar(500) DEFAULT '' COMMENT 'é”™è¯¯ä¿¡æ¯',
  `retry_count` tinyint DEFAULT '0' COMMENT 'å¤±è´¥é‡è¯•æ¬¡æ•°(æœ€å¤š3æ¬¡)',
  `local_path` varchar(500) DEFAULT '' COMMENT 'æœ¬åœ°å­˜å‚¨è·¯å¾„(M17 AIé…å›¾URLæœ¬åœ°åŒ–)',
  `create_time` int unsigned DEFAULT '0',
  `update_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_task_id` (`task_id`),
  KEY `idx_status` (`status`),
  KEY `idx_related` (`related_type`,`related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='é…å›¾å¼‚æ­¥ä»»åŠ¡è¡¨';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}image_task` WRITE;
/*!40000 ALTER TABLE `{prefix}image_task` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}image_task` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}invite_relation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}invite_relation` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `inviter_id` int unsigned NOT NULL COMMENT '邀请人',
  `invitee_id` int unsigned NOT NULL COMMENT '被邀请人',
  `invite_code` varchar(32) NOT NULL COMMENT '邀请码',
  `invitee_ip` varchar(45) DEFAULT '' COMMENT '被邀请人IP(防刷审计)',
  `reward_points` int DEFAULT '0' COMMENT '已发放积分',
  `reward_stage` tinyint DEFAULT '0' COMMENT '0注册/1首次签到/2首次付费',
  `create_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_invitee` (`invitee_id`),
  KEY `idx_inviter` (`inviter_id`),
  KEY `idx_code` (`invite_code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='邀请关系表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}invite_relation` WRITE;
/*!40000 ALTER TABLE `{prefix}invite_relation` DISABLE KEYS */;
INSERT INTO `{prefix}invite_relation` VALUES (1,1,0,'494f3b22','',0,0,1779357427);
/*!40000 ALTER TABLE `{prefix}invite_relation` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}lang_pack`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}lang_pack` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lang_code` varchar(10) NOT NULL COMMENT '语言代码(zh-cn/en/jp/ko)',
  `module` varchar(30) DEFAULT 'frontend' COMMENT '模块(frontend/backend/plugin/template)',
  `group_name` varchar(50) DEFAULT 'general' COMMENT '分组名称',
  `entry_key` varchar(200) NOT NULL COMMENT '翻译条目键名',
  `entry_value` text COMMENT '翻译条目值',
  `entry_original` text COMMENT '原始语言值(参考)',
  `is_translated` tinyint DEFAULT '0' COMMENT '是否已翻译:1是0否',
  `is_using_ai` tinyint DEFAULT '0' COMMENT '是否AI翻译:1是0否',
  `is_system` tinyint DEFAULT '0' COMMENT '系统条目:1是0否(不可删除)',
  `sort_order` int DEFAULT '0' COMMENT '排序',
  `version` int DEFAULT '1' COMMENT '版本号',
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
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}lang_pack` WRITE;
/*!40000 ALTER TABLE `{prefix}lang_pack` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}lang_pack` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}lang_pack_snapshot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}lang_pack_snapshot` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lang_code` varchar(10) NOT NULL COMMENT '语言代码',
  `module` varchar(30) DEFAULT 'frontend' COMMENT '模块',
  `version` int NOT NULL COMMENT '版本号',
  `snapshot_data` json NOT NULL COMMENT '快照数据(全量条目JSON)',
  `entry_count` int DEFAULT '0' COMMENT '条目数量',
  `translated_count` int DEFAULT '0' COMMENT '已翻译数量',
  `completion_rate` decimal(5,2) DEFAULT '0.00' COMMENT '完成率(%)',
  `created_by` int DEFAULT '0' COMMENT '创建人(管理员ID)',
  `create_reason` varchar(100) DEFAULT '' COMMENT '创建原因(auto_save/manual/publish)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lang_version` (`lang_code`,`module`,`version`),
  KEY `idx_lang` (`lang_code`),
  KEY `idx_module` (`module`),
  KEY `idx_version` (`version`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='语言包版本快照表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}lang_pack_snapshot` WRITE;
/*!40000 ALTER TABLE `{prefix}lang_pack_snapshot` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}lang_pack_snapshot` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}lang_site`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}lang_site` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lang_code` varchar(10) NOT NULL COMMENT '语言代码(zh-CN/en-US/ja-JP等)',
  `site_name` varchar(100) NOT NULL COMMENT '站点名称',
  `site_domain` varchar(200) DEFAULT '' COMMENT '独立域名',
  `url_prefix` varchar(20) DEFAULT '' COMMENT 'URL前缀(如/en/)',
  `url_mode` varchar(10) DEFAULT 'prefix' COMMENT 'URL模式(prefix/subdomain/domain)',
  `template_id` int DEFAULT '0' COMMENT '关联模板ID',
  `timezone` varchar(50) DEFAULT 'Asia/Shanghai' COMMENT '时区',
  `currency` varchar(10) DEFAULT 'CNY' COMMENT '货币',
  `is_default` tinyint DEFAULT '0' COMMENT '是否默认站点',
  `status` tinyint DEFAULT '1' COMMENT '状态:1启用0禁用',
  `site_config` json DEFAULT NULL COMMENT '站点配置(JSON)',
  `create_time` int unsigned DEFAULT '0',
  `update_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lang` (`lang_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='多语言站点表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}lang_site` WRITE;
/*!40000 ALTER TABLE `{prefix}lang_site` DISABLE KEYS */;
INSERT INTO `{prefix}lang_site` VALUES (1,'zh-CN','默认站点','','','prefix',0,'Asia/Shanghai','CNY',1,1,NULL,1783830086,1783830086);
/*!40000 ALTER TABLE `{prefix}lang_site` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}language`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}language` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '语言代码: zh-CN/en-US',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '语言名称',
  `is_default` tinyint DEFAULT '0' COMMENT '是否默认',
  `is_enabled` tinyint DEFAULT '1' COMMENT '启用状态',
  `sort` int DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='语言表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}language` WRITE;
/*!40000 ALTER TABLE `{prefix}language` DISABLE KEYS */;
INSERT INTO `{prefix}language` VALUES (1,'zh-CN','简体中文',1,1,1),(2,'en-US','English',0,1,2);
/*!40000 ALTER TABLE `{prefix}language` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}licenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}licenses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `license_code` varchar(64) NOT NULL DEFAULT '' COMMENT '许可证编码(唯一)',
  `product_type` varchar(20) NOT NULL DEFAULT '' COMMENT '产品类型: plugin/template',
  `product_code` varchar(100) NOT NULL DEFAULT '' COMMENT '产品编码',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '所属用户',
  `license_type` varchar(20) NOT NULL DEFAULT 'standard' COMMENT '类型: standard/pro/lifetime',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT '状态: active/suspended/revoked/expired',
  `bind_domain` varchar(255) NOT NULL DEFAULT '' COMMENT '绑定域名',
  `valid_from` int unsigned NOT NULL DEFAULT '0' COMMENT '有效期开始',
  `valid_until` int unsigned NOT NULL DEFAULT '0' COMMENT '有效期结束',
  `last_verified` int unsigned NOT NULL DEFAULT '0' COMMENT '最后验证时间',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `license_code` (`license_code`),
  KEY `user_id` (`user_id`),
  KEY `product_type_code` (`product_type`,`product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='许可证表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}licenses` WRITE;
/*!40000 ALTER TABLE `{prefix}licenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}licenses` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}like`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}like` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `content_id` int unsigned NOT NULL DEFAULT '0',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_content` (`user_id`,`content_id`),
  KEY `idx_content` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='点赞记录表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}like` WRITE;
/*!40000 ALTER TABLE `{prefix}like` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}like` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}link` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '网站名称',
  `group_id` int unsigned NOT NULL DEFAULT '0' COMMENT '分组ID',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '网站描述',
  `contact` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '联系人/邮箱',
  `is_apply` tinyint NOT NULL DEFAULT '0' COMMENT '是否申请中:0否/1是',
  `url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '链接地址',
  `logo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'Logo地址',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`,`sort`),
  KEY `idx_group_status` (`group_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='友情链接表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}link` WRITE;
/*!40000 ALTER TABLE `{prefix}link` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}link` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}link_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}link_group` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分组名称',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='友情链接分组表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}link_group` WRITE;
/*!40000 ALTER TABLE `{prefix}link_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}link_group` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `module` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模块',
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作',
  `target` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '操作对象',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'IP地址',
  `data` text COLLATE utf8mb4_unicode_ci COMMENT '操作数据',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_time` (`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=319 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}log` WRITE;
/*!40000 ALTER TABLE `{prefix}log` DISABLE KEYS */;
INSERT INTO `{prefix}log` VALUES (191,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780277048),(192,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780281354),(193,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780282785),(194,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780295145),(195,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780296297),(196,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780299147),(197,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780300604),(198,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780303115),(199,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780303178),(200,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780305232),(201,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780305259),(202,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780341862),(203,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780341872),(204,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780367445),(205,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780372287),(206,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780372303),(207,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780372409),(208,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780372489),(209,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780397871),(210,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780650587),(211,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780652154),(212,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780652742),(213,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780654068),(214,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780655670),(215,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780656557),(216,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780669890),(217,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780807557),(218,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780810913),(219,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780824447),(220,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780824791),(221,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780827065),(222,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780827131),(223,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780831590),(224,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780832225),(225,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780836130),(226,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780845726),(227,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780848636),(228,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780849354),(229,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780851433),(230,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780851816),(231,1,'TemplateStoreController','编辑模板','极简博客','172.18.0.1','{\"name\":\"极简博客\",\"slug\":\"blog-minimal\",\"category_id\":\"3\",\"description\":\"文艺清新的博客模板，注重阅读体验，排版优雅。适合个人博客、技术博客、知识分享类网站。\",\"screenshots\":\"[\",\"price\":\"0\",\"author_name\":\"八界AI官方\",\"version\":\"2.9.12\",\"file_size\":\"1536000\",\"requirements\":\"{\",\"__token__\":\"cc2b5635d38b897eff0e4449562e8551\"}',1780852143),(232,1,'TemplateStoreController','编辑模板','极简博客','172.18.0.1','{\"name\":\"极简博客\",\"slug\":\"blog-minimal\",\"category_id\":\"3\",\"description\":\"文艺清新的博客模板，注重阅读体验，排版优雅。适合个人博客、技术博客、知识分享类网站。\",\"screenshots\":\"\",\"price\":\"0\",\"author_name\":\"八界AI官方\",\"version\":\"2.9.12\",\"file_size\":\"1536000\",\"requirements\":\"\",\"__token__\":\"297f85d308724ee096201ed1b739b4da\"}',1780852406),(233,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780852411),(234,1,'TemplateStoreController','编辑模板','极简博客','172.18.0.1','{\"name\":\"极简博客\",\"slug\":\"blog-minimal\",\"category_id\":\"3\",\"description\":\"文艺清新的博客模板，注重阅读体验，排版优雅。适合个人博客、技术博客、知识分享类网站。\",\"screenshots\":\"\",\"price\":\"0\",\"author_name\":\"八界AI官方\",\"version\":\"2.9.12\",\"file_size\":\"1536000\",\"requirements\":\"\",\"__token__\":\"41b1e746919b4679e60a6bb5636c7469\"}',1780852424),(235,1,'TemplateStoreController','编辑模板','极简博客','172.18.0.1','{\"name\":\"极简博客\",\"slug\":\"blog-minimal\",\"category_id\":\"3\",\"description\":\"文艺清新的博客模板，注重阅读体验，排版优雅。适合个人博客、技术博客、知识分享类网站。\",\"screenshots\":\"\",\"price\":\"0\",\"author_name\":\"八界AI官方\",\"version\":\"2.9.12\",\"file_size\":\"1536000\",\"requirements\":\"\",\"__token__\":\"41b1e746919b4679e60a6bb5636c7469\"}',1780852520),(236,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780852525),(237,1,'TemplateStoreController','编辑模板','极简博客','172.18.0.1','{\"name\":\"极简博客\",\"slug\":\"blog-minimal\",\"category_id\":\"3\",\"description\":\"文艺清新的博客模板，注重阅读体验，排版优雅。适合个人博客、技术博客、知识分享类网站。\",\"screenshots\":\"\",\"price\":\"0\",\"author_name\":\"八界AI官方\",\"version\":\"2.9.12\",\"file_size\":\"1536000\",\"requirements\":\"\"}',1780852533),(238,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780853062),(239,1,'TemplateStoreController','编辑模板','企业商务Pro','172.18.0.1','{\"name\":\"企业商务Pro\",\"slug\":\"corporate-pro\",\"category_id\":\"1\",\"description\":\"专为企业打造的商务风格模板，蓝色主色调，专业可信。包含首页、关于、服务、案例、联系等页面。\",\"screenshots\":\"[\\\"\\/skin\\/corporate\\/preview1.jpg\\\",\\\"\\/skin\\/corporate\\/preview2.jpg\\\"]\",\"price\":\"99\",\"author_name\":\"八界AI官方\",\"version\":\"2.9.12\",\"file_size\":\"3584000\",\"requirements\":\"{\\\"cms\\\":\\\">=2.9.0\\\",\\\"php\\\":\\\">=8.0\\\"}\",\"__token__\":\"6f0936118b45481f1935298b28365222\"}',1780853439),(240,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780855314),(241,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780855365),(242,1,'TemplateStoreController','编辑模板','极简博客','172.18.0.1','{\"name\":\"极简博客\",\"slug\":\"blog-minimal\",\"category_id\":\"3\",\"description\":\"文艺清新的博客模板，注重阅读体验，排版优雅。适合个人博客、技术博客、知识分享类网站。\",\"screenshots\":\"\",\"price\":\"0\",\"author_name\":\"八界AI官方\",\"version\":\"2.9.12\",\"file_size\":\"1536000\",\"req_php\":\">=8.0\",\"req_cms\":\">=2.9.0\"}',1780855434),(243,1,'TemplateStoreController','编辑模板','企业商务Pro','172.18.0.1','{\"name\":\"企业商务Pro\",\"slug\":\"corporate-pro\",\"category_id\":\"1\",\"description\":\"专为企业打造的商务风格模板，蓝色主色调，专业可信。包含首页、关于、服务、案例、联系等页面。\",\"screenshots\":\"\\/skin\\/corporate\\/preview1.jpg\\r\\n\\/skin\\/corporate\\/preview2.jpg\",\"price\":\"99\",\"author_name\":\"八界AI官方\",\"version\":\"2.9.12\",\"file_size\":\"3584000\",\"req_php\":\">=8.0\",\"req_cms\":\">=2.9.0\"}',1780855451),(244,1,'TemplateStoreController','编辑模板','官方默认模板','172.18.0.1','{\"name\":\"官方默认模板\",\"slug\":\"default-official\",\"category_id\":\"1\",\"description\":\"八界AI-CMS官方默认模板，简洁大方，适用于各类企业官网。响应式设计，支持PC和移动端。\",\"screenshots\":\"\\/skin\\/default\\/preview1.jpg\\r\\n\\/skin\\/default\\/preview2.jpg\",\"price\":\"0\",\"author_name\":\"八界AI官方\",\"version\":\"2.9.12\",\"file_size\":\"2048000\",\"req_php\":\">=8.0\",\"req_cms\":\">=2.9.0\"}',1780855455),(245,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780855460),(246,1,'TemplateStoreController','编辑模板','企业商务Pro','172.18.0.1','{\"name\":\"企业商务Pro\",\"slug\":\"corporate-pro\",\"category_id\":\"1\",\"description\":\"专为企业打造的商务风格模板，蓝色主色调，专业可信。包含首页、关于、服务、案例、联系等页面。\",\"screenshots\":\"\",\"price\":\"99\",\"author_name\":\"八界AI官方\",\"version\":\"2.9.12\",\"file_size\":\"3584000\",\"req_php\":\">=8.0\",\"req_cms\":\">=2.9.0\"}',1780855469),(247,1,'TemplateStoreController','编辑模板','官方默认模板','172.18.0.1','{\"name\":\"官方默认模板\",\"slug\":\"default-official\",\"category_id\":\"1\",\"description\":\"八界AI-CMS官方默认模板，简洁大方，适用于各类企业官网。响应式设计，支持PC和移动端。\",\"screenshots\":\"\",\"price\":\"0\",\"author_name\":\"八界AI官方\",\"version\":\"2.9.12\",\"file_size\":\"2048000\",\"req_php\":\">=8.0\",\"req_cms\":\">=2.9.0\"}',1780855474),(248,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780855478),(249,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780856081),(250,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780856205),(251,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780856212),(252,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780856789),(253,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780857186),(254,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780881615),(255,1,'LogController','清理日志','清理90天前的日志，共0条','172.18.0.1','',1780881660),(256,1,'LogController','清理日志','清理7天前的日志，共155条','172.18.0.1','',1780881680),(257,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780882569),(258,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780883277),(259,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780915755),(260,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780923617),(261,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780930042),(262,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780930075),(263,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780933048),(264,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1780934432),(265,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1781249314),(266,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1781763167),(267,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1781766159),(268,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1781766320),(269,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1781771341),(270,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1781772666),(271,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1781795953),(272,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1781796787),(273,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1781801605),(274,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1781803915),(275,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1781805843),(276,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1781808131),(277,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1781808941),(278,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1781983640),(279,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1781985591),(280,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782013961),(281,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782049171),(282,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782058629),(283,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782059697),(284,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782060908),(285,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782060928),(286,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782061382),(287,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782096427),(288,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782108497),(289,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782182318),(290,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782197603),(291,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782307135),(292,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782310208),(293,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782311402),(294,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782315136),(295,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782316752),(296,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782317588),(297,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782318065),(298,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782318417),(299,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782318436),(300,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782318460),(301,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782318847),(302,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782318887),(303,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782318905),(304,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782360583),(305,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782360771),(306,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782360786),(307,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782362157),(308,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782369658),(309,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782380182),(310,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1782414990),(311,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1783695910),(312,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1783748472),(313,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1783748991),(314,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1783749065),(315,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1783749389),(316,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1783749493),(317,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1783766768),(318,1,'cache','清除全部缓存','','172.18.0.1','{\"type\":\"all\"}',1783790837);
/*!40000 ALTER TABLE `{prefix}log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}mail_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}mail_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `subscriber_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联订阅者ID',
  `content_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联内容ID(可空)',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '收件人邮箱',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮件主题',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态: 0=待发送, 1=已发送, 2=失败',
  `error_msg` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '失败原因',
  `sent_at` datetime DEFAULT NULL COMMENT '发送时间',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_subscriber_id` (`subscriber_id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件发送日志';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}mail_log` WRITE;
/*!40000 ALTER TABLE `{prefix}mail_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}mail_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '上传用户ID',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '原始文件名',
  `filepath` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件路径',
  `filetype` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'image' COMMENT '文件类型:image/video/file',
  `mimetype` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'MIME类型',
  `filesize` bigint unsigned NOT NULL DEFAULT '0' COMMENT '文件大小(字节)',
  `cate_id` int unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '替代文本/描述',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_type` (`filetype`),
  KEY `idx_cate` (`cate_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='媒体资源表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}media` WRITE;
/*!40000 ALTER TABLE `{prefix}media` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}media` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}member` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `wechat_openid` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '微信openid',
  `wechat_unionid` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '微信unionid',
  `wechat_nickname` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '微信昵称',
  `wechat_avatar` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '微信头像',
  `wechat_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '微信绑定手机号',
  `mini_login_time` datetime DEFAULT NULL COMMENT '最后小程序登录时间',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '头像',
  `invite_code` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '邀请码',
  `inviter_id` int unsigned DEFAULT '0' COMMENT '邀请人ID',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `last_login_time` int unsigned NOT NULL DEFAULT '0',
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  `level_id` int DEFAULT '1' COMMENT '会员等级ID',
  `points` int DEFAULT '0' COMMENT '当前积分',
  `total_points` int DEFAULT '0' COMMENT '累计获得积分',
  `signin_count` int DEFAULT '0' COMMENT '连续签到天数',
  `last_signin_date` date DEFAULT NULL COMMENT '最后签到日期',
  `grace_end_time` int unsigned DEFAULT '0' COMMENT '降级缓冲期截止时间(0=无缓冲期)',
  `level_expire_time` int unsigned DEFAULT '0' COMMENT '等级有效期时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  UNIQUE KEY `uk_invite_code` (`invite_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='前台会员表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}member` WRITE;
/*!40000 ALTER TABLE `{prefix}member` DISABLE KEYS */;
INSERT INTO `{prefix}member` VALUES (1,'test','test555@163.com','','','','','',NULL,'$2y$12$8.PWlG3NqW3JLwLv26CSP.Ynqfhe9.pFhqsYwOT7G/.T8ZTiwfXLW','test','','',0,1,1780656570,'172.18.0.1',1777220333,1780930064,2,20,20,1,'2026-05-21',0,0);
/*!40000 ALTER TABLE `{prefix}member` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}member_downgrade_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}member_downgrade_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `from_level` int unsigned NOT NULL DEFAULT '0' COMMENT '原等级ID',
  `to_level` int unsigned NOT NULL DEFAULT '0' COMMENT '目标等级ID',
  `action` varchar(20) NOT NULL DEFAULT '' COMMENT '操作: auto_downgrade/auto_upgrade/manual',
  `trigger_condition` varchar(100) NOT NULL DEFAULT '' COMMENT '触发条件: points_insufficient/grace_expired/admin_manual',
  `notified` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否已通知',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_user_time` (`user_id`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会员降级日志';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}member_downgrade_log` WRITE;
/*!40000 ALTER TABLE `{prefix}member_downgrade_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}member_downgrade_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}member_favorite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}member_favorite` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `content_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_content` (`member_id`,`content_id`),
  KEY `idx_content` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员收藏表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}member_favorite` WRITE;
/*!40000 ALTER TABLE `{prefix}member_favorite` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}member_favorite` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}member_level`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}member_level` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '等级名称',
  `min_points` int DEFAULT '0' COMMENT '所需最低积分',
  `max_points` int DEFAULT '9999999' COMMENT '所需积分(最高值)',
  `discount` tinyint DEFAULT '100' COMMENT '付费内容折扣百分比（100=无折扣）',
  `allow_download` tinyint DEFAULT '0' COMMENT '是否允许下载附件',
  `allow_comment_no_review` tinyint DEFAULT '0' COMMENT '是否评论免审核',
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '等级图标',
  `sort` int DEFAULT '0' COMMENT '排序',
  `level_order` int DEFAULT '1' COMMENT '等级排序',
  `is_default` tinyint DEFAULT '0' COMMENT '是否默认等级',
  `is_vip` tinyint NOT NULL DEFAULT '0' COMMENT '是否VIP等级(1=VIP有效期内免费阅读付费内容)',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  `benefits` json DEFAULT NULL COMMENT '等级权益(JSON)',
  `auto_upgrade` tinyint DEFAULT '1' COMMENT '是否自动升级',
  `validity_days` int DEFAULT '0' COMMENT '有效期天数(0为永久)',
  `status` tinyint DEFAULT '1' COMMENT '状态:1启用0禁用',
  PRIMARY KEY (`id`),
  KEY `idx_min_points` (`min_points`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员等级表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}member_level` WRITE;
/*!40000 ALTER TABLE `{prefix}member_level` DISABLE KEYS */;
INSERT INTO `{prefix}member_level` VALUES (1,'注册会员',0,99,100,0,0,'badge-lv1',1,1,1,0,1777457479,1777457479,NULL,1,0,1),(2,'正式会员',100,499,95,0,1,'badge-lv2',2,1,0,0,1777457479,1777457479,NULL,1,0,1),(3,'高级会员',500,1999,90,0,1,'badge-lv3',3,1,0,0,1777457479,1777457479,NULL,1,0,1),(4,'VIP会员',2000,4999,80,0,1,'badge-lv4',4,1,0,0,1777457479,1777457479,NULL,1,0,1),(5,'至尊会员',5000,9999999,70,0,1,'badge-lv5',5,1,0,0,1777457479,1777457479,NULL,1,0,1);
/*!40000 ALTER TABLE `{prefix}member_level` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}member_like`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}member_like` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `content_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_content` (`member_id`,`content_id`),
  KEY `idx_content` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员点赞表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}member_like` WRITE;
/*!40000 ALTER TABLE `{prefix}member_like` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}member_like` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}member_oauth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}member_oauth` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `provider` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '平台:gitee/wechat/qq/weibo',
  `openid` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '平台唯一标识',
  `unionid` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '平台UnionID',
  `access_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'Access Token',
  `refresh_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'Refresh Token',
  `expire_time` int unsigned NOT NULL DEFAULT '0' COMMENT 'Token过期时间',
  `nickname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '平台昵称',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '平台头像',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provider_openid` (`provider`,`openid`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员OAuth绑定表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}member_oauth` WRITE;
/*!40000 ALTER TABLE `{prefix}member_oauth` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}member_oauth` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}member_points_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}member_points_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL COMMENT '会员ID',
  `points` int NOT NULL COMMENT '变动积分数(正增/负减)',
  `balance` int NOT NULL COMMENT '变动后余额',
  `type` varchar(30) NOT NULL COMMENT '类型(login/publish/comment/like/share/pay_read/download/exchange/transfer/admin)',
  `source` varchar(100) DEFAULT '' COMMENT '来源说明',
  `ref_id` int DEFAULT '0' COMMENT '关联ID',
  `create_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_type` (`type`),
  KEY `idx_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会员积分流水表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}member_points_log` WRITE;
/*!40000 ALTER TABLE `{prefix}member_points_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}member_points_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}menu_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}menu_group` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分组名称',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分组标识',
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '图标类名',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态:0禁用1启用',
  `create_time` int unsigned DEFAULT '0',
  `update_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `sort_status` (`sort`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台菜单分组表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}menu_group` WRITE;
/*!40000 ALTER TABLE `{prefix}menu_group` DISABLE KEYS */;
INSERT INTO `{prefix}menu_group` VALUES (1,'内容管理','group_1','bi bi-file-text',10,1,1779393242,1779393242),(2,'用户管理','group_2','bi bi-people',20,1,1779393242,1779393242),(3,'运营管理','group_3','bi bi-shop',30,1,1779393242,1779393242),(4,'系统设置','group_4','bi bi-gear',40,1,1779393243,1779393243),(5,'互动管理','group_5','bi bi-chat-dots',50,1,1779393242,1779393242),(6,'SEO与数据','group_6','bi bi-bar-chart',60,1,1779393242,1779393242),(7,'AI中心','group_7','bi bi-robot',70,1,1779393242,1779393242),(8,'内容生态','group_8','bi bi-globe2',80,1,1779393242,1779393242),(9,'平台扩展','group_9','bi bi-puzzle',90,1,1779393242,1779393242);
/*!40000 ALTER TABLE `{prefix}menu_group` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}menu_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}menu_item` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int unsigned NOT NULL COMMENT '所属分组ID',
  `parent_id` int unsigned NOT NULL DEFAULT '0' COMMENT '父级ID(0为一级)',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '菜单名称',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '链接地址',
  `permission` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '权限标识',
  `active` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '激活标识',
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '图标类名',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态:0禁用1启用',
  `module` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '所属模块',
  `create_time` int unsigned DEFAULT '0',
  `update_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `group_parent_status` (`group_id`,`parent_id`,`status`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=934 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台菜单项表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}menu_item` WRITE;
/*!40000 ALTER TABLE `{prefix}menu_item` DISABLE KEYS */;
INSERT INTO `{prefix}menu_item` VALUES (11,1,1,'信息管理','/admin/content/index','content.*','content','bi bi-file-text',1,1,NULL,1779393242,1780716034),(12,1,1,'分类管理','/admin/cate/index','cate.*','cate','bi bi-folder2',2,1,NULL,1779393242,1780716034),(13,1,1,'标签管理','/admin/tag/index','tag.*','tag','bi bi-tags',3,1,NULL,1779393242,1780716034),(14,1,1,'回收站','/admin/content/recycleBin','content.recycle','recycle','bi bi-trash3',4,1,NULL,1779393242,1780716034),(15,1,1,'媒体资源库','/admin/media/index','media.*','media','bi bi-images',5,1,NULL,1779393242,1780716034),(16,1,1,'内容审核','/admin/review/index','review.*','review','bi bi-patch-check',6,1,NULL,1779393242,1780716034),(21,2,2,'用户列表','/admin/user/index','user.*','user','bi bi-people',10,1,NULL,1779393242,1780716034),(27,2,2,'会员等级','/admin/member_level/index','member_level.*','member_level','bi bi-award',11,1,NULL,1779393242,1780716034),(28,2,2,'积分规则','/admin/points_rule/index','points.*','points_rule','bi bi-star',14,1,NULL,1779393242,1780716034),(29,2,2,'积分商品','/admin/points_product/index','points_product.*','points_product','bi bi-gift',15,1,NULL,1779393242,1780716034),(33,3,3,'轮播图管理','/admin/banner/index','banner.*','banner','bi bi-images',18,1,NULL,1779393242,1780716034),(34,3,3,'友情链接','/admin/link/index','link.*','link','bi bi-link-45deg',19,1,NULL,1779393242,1780716034),(35,3,3,'友链分组','/admin/link_group/index','link.*','link_group','bi bi-folder2-open',20,1,NULL,1779393242,1780716034),(36,3,3,'广告管理','/admin/ad/index','ad.*','ad','bi bi-badge-ad',21,1,NULL,1779393242,1780716034),(41,4,4,'系统配置','/admin/system/config','system.*','system_config','bi bi-gear',76,1,NULL,1779393243,1780716035),(42,4,4,'操作日志','/admin/log/index','system.log','log','bi bi-journal-text',77,1,NULL,1779393243,1780716035),(43,4,4,'数据库备份','/admin/backup/index','backup.*','backup','bi bi-database',78,1,NULL,1779393243,1780716035),(44,4,4,'通知中心','/admin/notification/index','notification.*','notification','bi bi-bell',79,1,NULL,1779393243,1780716035),(47,3,3,'表单管理','/admin/form/index','form.*','form','bi bi-card-checklist',22,1,NULL,1779393242,1780716034),(48,3,3,'优惠券','/admin/coupon/index','coupon.*','coupon','bi bi-ticket-perforated',23,1,NULL,1779393242,1780716034),(49,4,4,'邮件订阅','/admin/email_subscriber/index','email_subscriber.*','email_subscriber','bi bi-envelope',81,1,NULL,1779393243,1780716035),(50,4,4,'访问归档','/admin/visit_archive/index','visit_archive.*','visit_archive','bi bi-archive',82,1,NULL,1779393243,1780716035),(51,5,5,'评论管理','/admin/comment/index','comment.*','comment','bi bi-chat-left-text',25,1,NULL,1779393242,1780716034),(52,5,5,'前台会员','/admin/member/index','member.*','member','bi bi-person-badge',27,1,NULL,1779393242,1780716034),(53,5,5,'付费订单','/admin/paid_order/index','paid_order.*','paid_order','bi bi-credit-card',29,1,NULL,1779393242,1780716034),(54,5,5,'支付管理','/admin/payment/index','payment.*','payment','bi bi-wallet2',33,1,NULL,1779393242,1780716034),(55,5,5,'收入统计','/admin/payment/revenue','payment.*','payment_revenue','bi bi-cash-stack',34,1,NULL,1779393242,1780716034),(56,5,5,'系统通知','/admin/message/system','message.*','message_system','bi bi-bell',30,1,NULL,1779393242,1780716034),(57,5,5,'发送通知','/admin/message/sendSystem','message.*','message_send','bi bi-send-plus',31,1,NULL,1779393242,1780716034),(58,4,4,'验证码配置','/admin/captcha/config','captcha.*','captcha','bi bi-shield-check',83,1,NULL,1779393243,1780716035),(59,4,4,'存储配置','/admin/storage/config','storage.*','storage_config','bi bi-hdd-network',84,1,NULL,1779393243,1780716035),(60,6,6,'数据看板','/admin/dashboard/index','dashboard.*','dashboard','bi bi-speedometer2',45,1,NULL,1779393242,1780716034),(61,6,6,'SEO管理','/admin/seo/index','seo.*','seo','bi bi-search',46,1,NULL,1779393242,1780716034),(62,6,6,'数据导出','/admin/export/index','export.*','export','bi bi-download',50,1,NULL,1779393242,1780716034),(63,6,6,'API令牌','/admin/token/index','token.*','token','bi bi-key',52,1,NULL,1779393242,1780716034),(64,6,6,'SEO关键词','/admin/seo_keyword/index','seo_keyword.*','seo_keyword','bi bi-hash',48,1,NULL,1779393242,1780716034),(65,6,6,'关键词分组','/admin/seo_keyword/group','seo_keyword.*','seo_keyword_group','bi bi-folder',49,1,NULL,1779393242,1780716034),(66,6,6,'流量分析','/admin/traffic/index','traffic.*','traffic','bi bi-graph-up',54,1,NULL,1779393242,1780716034),(67,6,6,'AI统计','/admin/aiStat/index','ai_stat.*','ai_stat','bi bi-robot',55,1,NULL,1779393242,1780716034),(68,6,6,'数据报告','/admin/report/index','report.*','report','bi bi-graph-up-arrow',56,1,NULL,1779393242,1780716034),(69,6,6,'系统监控','/admin/monitor/index','monitor.*','monitor','bi bi-speedometer2',53,1,NULL,1779393242,1780716034),(70,4,4,'菜单管理','/admin/menu_manager/index','menu_manager.*','menu_manager','bi bi-list-nested',85,1,NULL,1779393243,1780716035),(71,7,7,'AI模型管理','/admin/ai_model/index','ai_model.*','ai_model','bi bi-cpu',36,1,NULL,1779393242,1780716034),(72,7,7,'AI调用日志','/admin/ai_log/index','ai_log.*','ai_log','bi bi-journal-code',37,1,NULL,1779393242,1780716034),(73,7,7,'AI批量生成','/admin/ai_batch/index','ai_batch.*','ai_batch','bi bi-magic',38,1,NULL,1779393242,1780716034),(74,7,7,'AI内容模板','/admin/ai_template/index','ai_template.*','ai_template','bi bi-file-earmark-text',39,1,NULL,1779393242,1780716034),(75,7,7,'模板设计器','/admin/template_design/index','template_design.*','template_design','bi bi-palette',40,1,NULL,1779393242,1780716034),(76,7,7,'AI翻译管理','/admin/ai_translation/index','ai_translation.*','ai_translation','bi bi-translate',41,1,NULL,1779393242,1780716034),(77,7,7,'AI配置','/admin/system/aiConfig','ai_config.*','ai_config','bi bi-sliders',43,1,NULL,1779393242,1780716034),(78,7,0,'AI主题管理','/admin/ai_theme/index','ai_theme.*','ai_theme','bi bi-palette',780,1,NULL,0,0),(81,8,8,'采集源管理','/admin/collect_source/index','collect.*','collect_source','bi bi-cloud-download',60,1,NULL,1779393242,1780716035),(82,8,8,'采集日志','/admin/collect_log/index','collect.*','collect_log','bi bi-journal',61,1,NULL,1779393242,1780716035),(83,8,8,'发布平台','/admin/publish_platform/index','publish.*','publish_platform','bi bi-send',62,1,NULL,1779393242,1780716035),(84,8,8,'发布记录','/admin/publish_log/index','publish.*','publish_log','bi bi-clock-history',63,1,NULL,1779393242,1780716035),(85,8,8,'邮件模板','/admin/email_template/index','email.*','email_template','bi bi-envelope-paper',64,1,NULL,1779393242,1780716035),(86,8,8,'邮件日志','/admin/email_log/index','email.*','email_log','bi bi-envelope-check',65,1,NULL,1779393242,1780716035),(91,9,9,'插件管理','/admin/plugin/index','plugin.*','plugin','bi bi-plug',67,1,NULL,1779393242,1780716035),(92,9,9,'多语言管理','/admin/language/index','language.*','language','bi bi-translate',69,1,NULL,1779393243,1780716035),(93,9,9,'模板市场','/admin/theme_market/index','theme_market.*','theme_market','bi bi-palette2',70,1,NULL,1779393243,1780716035),(94,9,9,'API文档','/admin/api_doc/index','apidoc.*','api_doc','bi bi-file-code',74,1,NULL,1779393243,1780716035),(161,1,1,'审批工作流','/admin/workflow/index','workflow.*','workflow','bi bi-journal-check',7,1,NULL,1779393242,1780716034),(162,1,1,'审批记录','/admin/workflow/records','workflow.*','workflow_records','bi bi-clock-history',8,1,NULL,1779393242,1780716034),(210,2,2,'兑换记录','/admin/points_exchange/index','points_exchange.*','points_exchange','bi bi-arrow-left-right',16,1,NULL,1779393242,1780716034),(271,2,2,'权益配置','/admin/member_benefit/index','member_benefit.*','member_benefit','bi bi-stars',12,1,NULL,1779393242,1780716034),(272,2,2,'会员等级管理','/admin/member_benefit/members','member_benefit.*','member_benefit_members','bi bi-people',13,1,NULL,1779393242,1780716034),(480,4,4,'导入管理','/admin/import/index','import.*','import','bi bi-upload',80,1,NULL,1779393243,1780716035),(481,4,4,'内容推送','/admin/push/channel','push.*','push_channel','bi bi-send',86,1,NULL,0,1780716035),(482,4,4,'推送日志','/admin/push/log','push.*','push_log','bi bi-journal-code',87,1,NULL,0,1780716035),(483,4,4,'订阅管理','/admin/subscriber/index','subscriber.*','subscriber','bi bi-envelope-plus',88,1,NULL,0,1780716035),(484,4,4,'邮件日志','/admin/mail_log/index','mail_log.*','mail_log','bi bi-envelope-check',89,1,NULL,0,1780716035),(491,4,0,'退订分析','/admin/subscriber/analysis','subscriber.*','subscriber','bi bi-graph-down',95,1,NULL,0,0),(500,4,0,'内容模型','/admin/content_model/index','content_model.*','content_model','bi bi-layers',85,1,NULL,0,0),(501,4,0,'模板分类','/admin/template_category/index','template_category.*','template_category','bi bi-tags',86,1,NULL,0,0),(502,4,0,'模板安装','/admin/template_install/index','template_install.*','template_install','bi bi-cloud-arrow-down',87,1,NULL,0,0),(503,4,500,'执行记录','/admin/ai_workflow/logs','ai_workflow.logs','ai_workflow','bi bi-clock-history',3,1,NULL,0,0),(504,4,500,'工作流模板','/admin/ai_workflow/templates','ai_workflow.templates','ai_workflow','bi bi-file-earmark-code',4,1,NULL,0,0),(510,5,5,'OAuth配置','/admin/oauth_config/index','oauth.*','oauth_config','bi bi-key-fill',32,1,NULL,1779393242,1780716034),(511,5,5,'邀请排行','/admin/invite/index','invite.*','invite','bi bi-gift',28,1,NULL,1779393242,1780716034),(512,4,0,'模型迁移工具','/admin/content_model_migration/index','content_model_migration.*','content_model_migration','bi bi-arrow-left-right',90,1,NULL,0,0),(513,5,5,'评价管理','/admin/rating/index','rating.*','rating','bi bi-star',26,1,NULL,1779393242,1780716034),(514,4,510,'执行监控','/admin/ai_agent/monitor','ai_agent.monitor','ai_agent','bi bi-activity',4,1,NULL,0,0),(515,7,0,'AI批量管线','/admin/ai_batch/pipeline','ai_batch.*','ai_batch_pipeline','bi bi-diagram-3',102,1,NULL,0,0) -- V2.9.38 已废弃，与 id=73 AI批量生成重复,(520,4,0,'商店分类','/admin/template_store_ops/categoryIndex','template_store_ops.*','template_store_ops','bi bi-folder2-open',88,1,NULL,0,0),(521,4,0,'Banner管理','/admin/template_store_ops/bannerIndex','template_store_ops.*','template_store_ops','bi bi-images',89,1,NULL,0,0),(522,4,0,'推荐位配置','/admin/template_store_ops/recommendIndex','template_store_ops.*','template_store_ops','bi bi-star',90,1,NULL,0,0),(523,4,0,'商店统计','/admin/template_store_ops/statsDashboard','template_store_ops.*','template_store_ops','bi bi-bar-chart-line',91,1,NULL,0,0),(524,4,0,'评论批量管理','/admin/template_store_ops/reviewBatch','template_store_ops.*','template_store_ops','bi bi-chat-dots',92,1,NULL,0,0),(530,4,484,'发送趋势','/admin/mail_log/statistics','mail_log.*','mail_log_statistics','bi bi-graph-up',1,1,NULL,0,0),(531,3,0,'模板订单','/admin/template_order_admin/index','template_order_admin.*','template_order_admin','bi bi-receipt',92,1,NULL,0,0),(532,4,0,'API密钥管理','/admin/api_key/index','api_key.*','api_key','bi bi-key',93,1,NULL,0,0),(533,4,0,'API文档','/admin/api_key/doc','api_key.doc','api_key','bi bi-file-earmark-code',94,1,NULL,0,0),(540,1,0,'系统健康检查','/admin/system_health/index','system_health.*','system_health','bi bi-heart-pulse',95,1,NULL,0,0),(541,3,0,'评价管理','/admin/template_review_admin/index','template_review_admin.*','template_review_admin','bi bi-star',93,1,NULL,0,0),(542,3,0,'统计看板','/admin/template_store_stats/index','template_store_stats.*','template_store_stats','bi bi-bar-chart',94,1,NULL,0,0),(543,3,0,'模板包管理','/admin/template_pack/index','template_pack.*','template_pack','bi bi-box-seam',95,1,NULL,0,0),(544,3,0,'审核工作流','/admin/template_audit_workflow/index','template_audit_workflow.*','template_audit_workflow','bi bi-shield-check',96,1,NULL,0,0),(545,3,0,'推荐位管理','/admin/template_recommend_position/index','template_recommend_position.*','template_recommend_position','bi bi-megaphone',97,1,NULL,0,0),(546,3,0,'结算管理','/admin/template_settlement_admin/index','template_settlement_admin.*','template_settlement_admin','bi bi-cash-coin',98,1,NULL,0,0),(547,3,0,'商店SEO','/admin/template_store_seo/index','template_store_seo.*','template_store_seo','bi bi-search',99,1,NULL,0,0),(550,7,0,'AI编辑器配置','/admin/ai_config/index','ai_config.*','ai_config','bi bi-robot',80,1,NULL,0,0),(551,7,0,'AI模板库','/admin/ai_editor_template/index','ai_editor_template.*','ai_editor_template','bi bi-collection',81,1,NULL,0,0),(552,4,0,'评论管理','/admin/comment_admin/index','comment_admin.*','comment_admin','bi bi-chat-dots',77,1,NULL,0,0),(553,4,0,'操作审计','/admin/content_audit_log/index','content_audit_log.*','content_audit_log','bi bi-journal-text',78,1,NULL,0,0),(554,3,550,'订阅设置','/admin/notify_center/subscriptions','notify_center.subscriptions','notify_center','bi bi-gear',4,1,NULL,0,0),-- (560,2,0,'插件在线安装','/admin/plugin_store/index','plugin_store.*','plugin_store','bi bi-cloud-download',80,1,NULL,0,0) -- 已删除：与911插件市场重复
-- (561,2,0,'插件批量管理','/admin/plugin/batchIndex','plugin.*','plugin','bi bi-list-check',81,1,NULL,0,0) -- 已删除：与91插件管理重复,(562,3,560,'发送日志','/admin/sms/logs','sms.logs','sms','bi bi-clock-history',2,1,NULL,0,0),(570,1,0,'Hook事件文档','/admin/hook_doc/index','hook_doc.*','hook_doc','bi bi-book',85,1,NULL,0,0),(571,5,0,'PWA配置','/admin/pwa_config/index','pwa_config.*','pwa_config','bi bi-phone',90,1,NULL,0,0),(572,5,570,'创建测试','/admin/ab_test/create','ab_test.create','ab_test','bi bi-plus-circle',2,1,NULL,0,0),(573,5,570,'测试结果','/admin/ab_test/results','ab_test.results','ab_test','bi bi-graph-up',3,1,NULL,0,0),(580,5,0,'用户分群','/admin/user_segment/index','user_segment.*','user_segment','bi bi-people',111,1,NULL,0,0),(581,5,580,'分群列表','/admin/user_segment/index','user_segment.index','user_segment','bi bi-list-ul',1,1,NULL,0,0),(582,5,580,'创建分群','/admin/user_segment/create','user_segment.create','user_segment','bi bi-plus-circle',2,1,NULL,0,0),(590,5,0,'运营自动化','/admin/ops_automation/index','ops_automation.*','ops_automation','bi bi-gear-wide-connected',112,1,NULL,0,0),(591,5,590,'自动化流程','/admin/ops_automation/index','ops_automation.index','ops_automation','bi bi-list-ul',1,1,NULL,0,0),(592,5,590,'创建流程','/admin/ops_automation/create','ops_automation.create','ops_automation','bi bi-plus-circle',2,1,NULL,0,0),(600,5,0,'质量监控','/admin/quality_monitor/index','quality_monitor.*','quality_monitor','bi bi-shield-check',113,1,NULL,0,0),(610,6,0,'读写分离监控','/admin/db_rw/index','db_rw.*','db_rw','bi bi-database-gear',115,1,NULL,0,0),(611,6,6,'SEO诊断','/admin/seo_diagnose/index','seo.*','seo_diagnose','bi bi-activity',47,1,NULL,1780716034,1780716034),(620,6,0,'队列监控','/admin/queue/index','queue.*','queue','bi bi-list-ol',116,1,NULL,0,0),(621,6,6,'高级导出','/admin/export/dialog','export_advanced.*','export_dialog','bi bi-file-earmark-arrow-down',51,1,NULL,1779393242,1780716034),(622,6,620,'失败任务','/admin/queue/failed','queue.failed','queue','bi bi-exclamation-triangle',2,1,NULL,0,0),(630,6,0,'Redis监控','/admin/redis/index','redis.*','redis','bi bi-lightning',117,1,NULL,0,0),(640,6,0,'静态资源优化','/admin/asset_optimize/index','asset_optimize.*','asset_optimize','bi bi-file-earmark-zip',118,1,NULL,0,0),(690,6,6,'分享追踪','/admin/social_share/index','social_share.*','social_share','bi bi-share',58,1,NULL,1779393242,1780716034),(691,6,6,'运营分析','/admin/data_dashboard/index','data_dashboard.*','data_dashboard','bi bi-bar-chart-line',57,1,NULL,1780716034,1780716034),(761,7,7,'翻译语言管理','/admin/translate/languages','ai_translation.*','translate_language','bi bi-globe2',42,1,NULL,1780716034,1780716034),(911,9,9,'插件市场','/admin/plugin_market/index','plugin_market.*','plugin_market','bi bi-shop',68,1,NULL,1779393243,1780716035),(931,9,9,'模板商店管理','/admin/template_store/index','template_store.*','template_store','bi bi-shop',71,1,NULL,1780716035,1780716035),(932,9,9,'评论审核','/admin/template_store/reviews','template_store.*','template_reviews','bi bi-star-half',72,1,NULL,1780716035,1780716035),(933,9,9,'模板分类','/admin/template_store/categories','template_store.*','template_categories','bi bi-folder2',73,1,NULL,1780716035,1780716035);
/*!40000 ALTER TABLE `{prefix}menu_item` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}message` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` int unsigned NOT NULL,
  `from_user_id` int unsigned NOT NULL,
  `to_user_id` int unsigned NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint NOT NULL DEFAULT '0',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_conversation` (`conversation_id`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='私信消息表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}message` WRITE;
/*!40000 ALTER TABLE `{prefix}message` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}message` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}message_conversation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}message_conversation` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id_1` int unsigned NOT NULL,
  `user_id_2` int unsigned NOT NULL,
  `last_message_id` int unsigned NOT NULL DEFAULT '0',
  `last_message_time` int unsigned NOT NULL DEFAULT '0',
  `unread_count_1` int unsigned NOT NULL DEFAULT '0',
  `unread_count_2` int unsigned NOT NULL DEFAULT '0',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users` (`user_id_1`,`user_id_2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='私信会话表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}message_conversation` WRITE;
/*!40000 ALTER TABLE `{prefix}message_conversation` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}message_conversation` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}message_system`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}message_system` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `content` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'system',
  `target_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `send_time` int unsigned NOT NULL DEFAULT '0',
  `expire_time` int unsigned NOT NULL DEFAULT '0',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_type_time` (`type`,`send_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统通知表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}message_system` WRITE;
/*!40000 ALTER TABLE `{prefix}message_system` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}message_system` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}message_system_read`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}message_system_read` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `read_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_message_user` (`message_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统通知已读记录表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}message_system_read` WRITE;
/*!40000 ALTER TABLE `{prefix}message_system_read` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}message_system_read` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}mini_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}mini_config` (
  `id` int NOT NULL AUTO_INCREMENT,
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
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}mini_config` WRITE;
/*!40000 ALTER TABLE `{prefix}mini_config` DISABLE KEYS */;
INSERT INTO `{prefix}mini_config` VALUES (1,'appid','',NULL,NULL,'basic','AppID','2026-07-13 02:34:27'),(2,'secret','',NULL,NULL,'basic','AppSecret','2026-07-13 02:34:27'),(3,'mini_name','',NULL,NULL,'basic','小程序名称','2026-07-13 02:34:27'),(4,'theme_color','#0d6efd',NULL,NULL,'theme','主题色','2026-07-13 02:34:27'),(5,'enable_comment','1',NULL,NULL,'function','启用评论','2026-07-13 02:34:27'),(6,'enable_favorite','1',NULL,NULL,'function','启用收藏','2026-07-13 02:34:27'),(7,'enable_like','1',NULL,NULL,'function','启用点赞','2026-07-13 02:34:27'),(8,'api_rate_limit','200',NULL,NULL,'function','API频率限制(次/分)','2026-07-13 02:34:27');
/*!40000 ALTER TABLE `{prefix}mini_config` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}mini_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}mini_message` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL COMMENT '接收用户ID',
  `msg_type` varchar(20) NOT NULL COMMENT '消息类型(system/comment_reply/like_notify/favorite_remind/audit_notify)',
  `msg_title` varchar(200) NOT NULL COMMENT '消息标题',
  `msg_content` text COMMENT '消息内容',
  `msg_data` json DEFAULT NULL COMMENT '消息附加数据(JSON)',
  `platform` varchar(10) DEFAULT 'mini' COMMENT '推送平台(mini/h5/all)',
  `push_channel` varchar(20) DEFAULT 'template' COMMENT '推送渠道(template/subscribe/station/all)',
  `is_read` tinyint DEFAULT '0' COMMENT '是否已读:1是0否',
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
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}mini_message` WRITE;
/*!40000 ALTER TABLE `{prefix}mini_message` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}mini_message` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}mini_page_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}mini_page_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `page_type` varchar(20) NOT NULL COMMENT '页面类型(home/list/detail/search/user/about/contact)',
  `page_name` varchar(100) NOT NULL COMMENT '页面名称',
  `page_layout` json NOT NULL COMMENT '页面布局配置(JSON: 组件列表/顺序/属性)',
  `page_style` json DEFAULT NULL COMMENT '页面样式配置(JSON: 主题色/字体/间距等)',
  `page_template` varchar(50) DEFAULT 'default' COMMENT '页面模板标识',
  `platform` varchar(20) DEFAULT 'all' COMMENT '平台(all/mini/h5)',
  `version` int DEFAULT '1' COMMENT '版本号',
  `is_published` tinyint DEFAULT '0' COMMENT '是否已发布:1是0否',
  `publish_time` datetime DEFAULT NULL COMMENT '发布时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_page_type` (`page_type`),
  KEY `idx_platform` (`platform`),
  KEY `idx_version` (`version`),
  KEY `idx_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='移动端页面配置表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}mini_page_config` WRITE;
/*!40000 ALTER TABLE `{prefix}mini_page_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}mini_page_config` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}mini_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}mini_stats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `stats_date` date NOT NULL COMMENT '统计日期',
  `stats_type` varchar(30) NOT NULL COMMENT '统计类型(page_view/visitor/new_user/duration/bounce/conversion)',
  `page_type` varchar(20) DEFAULT '' COMMENT '页面类型',
  `page_path` varchar(200) DEFAULT '' COMMENT '页面路径',
  `platform` varchar(10) DEFAULT 'mini' COMMENT '平台(mini/h5)',
  `metric_name` varchar(50) NOT NULL COMMENT '指标名称',
  `metric_value` int DEFAULT '0' COMMENT '指标值',
  `metric_data` json DEFAULT NULL COMMENT '指标附加数据(JSON)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_date_type` (`stats_date`,`stats_type`,`page_type`,`page_path`,`platform`,`metric_name`),
  KEY `idx_date` (`stats_date`),
  KEY `idx_type` (`stats_type`),
  KEY `idx_platform` (`platform`),
  KEY `idx_page_type` (`page_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='移动端统计表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}mini_stats` WRITE;
/*!40000 ALTER TABLE `{prefix}mini_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}mini_stats` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}mobile_nav_tab`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}mobile_nav_tab` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Tab名称',
  `icon` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图标类名(Bootstrap Icons)',
  `icon_active` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '激活图标类名',
  `tab_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'custom' COMMENT '类型:home/category/member/message/custom',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '链接URL',
  `require_login` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否需要登录:0否/1是',
  `show_badge` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否显示角标(未读数):0否/1是',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序号',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用:0否/1是',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_enabled_sort` (`is_enabled`,`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='移动端底部导航Tab(V2.9.24)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}mobile_nav_tab` WRITE;
/*!40000 ALTER TABLE `{prefix}mobile_nav_tab` DISABLE KEYS */;
INSERT INTO `{prefix}mobile_nav_tab` VALUES (1,'首页','bi bi-house','bi bi-house-fill','home','/',0,0,1,1,0,0),(2,'分类','bi bi-grid','bi bi-grid-fill','category','/product',0,0,2,1,0,0),(3,'我的','bi bi-person','bi bi-person-fill','member','/member/index',1,0,3,1,0,0),(4,'消息','bi bi-bell','bi bi-bell-fill','message','/message/index',1,1,4,1,0,0);
/*!40000 ALTER TABLE `{prefix}mobile_nav_tab` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}module` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `category` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'core',
  `is_system` tinyint NOT NULL DEFAULT '0',
  `is_enabled` tinyint NOT NULL DEFAULT '1',
  `sort` int NOT NULL DEFAULT '0',
  `config_group` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `menu_ids` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_category` (`category`),
  KEY `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='功能模块注册表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}module` WRITE;
/*!40000 ALTER TABLE `{prefix}module` DISABLE KEYS */;
INSERT INTO `{prefix}module` VALUES (1,'content','内容管理','内容发布、分类、标签、回收站，管理网站所有内容','file-text','core',1,1,1,'','[11,12,13,14,15,16]',0,0),(2,'user','用户管理','后台用户管理、角色权限分配、登录日志','people','core',1,1,2,'','[21]',0,0),(3,'banner','轮播图','首页轮播图管理，支持多位置轮播与排序','images','operation',0,1,10,'','[33]',0,0),(4,'link','友情链接','友情链接及分组管理，支持按组分类','link-45deg','operation',0,1,11,'','[34,35]',0,0),(5,'ad','广告系统','广告位与广告内容管理，支持多种广告位','badge-ad','operation',0,1,12,'','[36]',0,0),(6,'comment','评论系统','前台评论与审核，支持回复与敏感词过滤','chat-left-text','interaction',0,1,20,'','[51]',0,0),(7,'member','前台会员','前台会员注册登录、资料管理与互动','person-badge','interaction',0,1,21,'','[52]',0,0),(8,'seo','SEO管理','Sitemap、robots.txt、结构化数据管理','search','seo_data',0,1,30,'','[61]',0,0),(9,'export','数据导出','Excel/CSV导入导出，支持批量数据操作','download','seo_data',0,1,31,'','[62]',0,0),(10,'token','API令牌','RESTful API Token管理，控制接口访问权限','key','seo_data',0,1,32,'','[63]',0,0),(11,'notification','消息通知','站内通知与提醒，支持消息推送','bell','extension',0,1,40,'','[44]',0,0),(12,'backup','数据库备份','数据库备份与恢复，支持定时自动备份','database','extension',0,1,41,'','[43]',0,0),(13,'ai_model','AI模型管理','AI大模型配置与管理，支持多模型切换','robot','extension',0,1,0,'','',1777457624,1777457624),(14,'member_level','会员等级','会员等级与权益管理，支持自动升级','award','interaction',0,1,0,'','',1777457624,1777457624),(15,'points','积分体系','积分规则与兑换管理，签到/消费/奖励','coin','interaction',0,1,0,'','',1777457624,1777457624),(16,'paid_content','付费阅读','内容付费阅读与订单管理','cash-coin','operation',0,1,0,'','',1777457624,1777457624),(17,'form_builder','表单生成器','自定义表单与数据收集，支持多种字段类型','ui-radios','operation',0,1,0,'','',1777457624,1777457624),(18,'seo_keyword','SEO关键词库','SEO关键词挖掘与优化建议','tags','operation',0,1,0,'','',1777457624,1777487966),(19,'dashboard','数据看板','访问统计与数据分析，实时监控网站流量','graph-up','operation',0,1,0,'','',1777457624,1777488132),(20,'oauth_manage','OAuth管理','第三方登录配置(GitHub/微信/QQ)','box-arrow-in-right','extension',0,1,0,'','',1777457624,1777457624),(21,'payment','微信支付','微信支付/支付宝支付配置与订单管理','credit-card','extension',0,1,0,'','',1777774069,1777774069),(22,'ai_batch','AI批量生成','AI批量改写/翻译/生成内容，支持多模式批量处理','lightning-charge','extension',0,1,0,'','',1777774069,1777774069),(23,'plugin','插件管理','插件安装、启用、配置与市场管理','plugin','extension',0,1,0,'','',1777774069,1777774069),(24,'publish','多平台发布','内容一键发布到微信公众号/微博/头条等平台','broadcast','extension',0,1,0,'','',1777774069,1777774069),(25,'email','邮件系统','邮件发送配置、邮件模板与队列管理','envelope','extension',0,1,0,'','',1777774069,1777774069),(26,'collect','内容采集','自动采集外部内容，支持RSS/API/网页抓取','cloud-download','extension',0,1,0,'','',1777774069,1777774069),(27,'i18n','多语言','多语言内容翻译与语言包管理','translate','extension',0,1,0,'','',1777774069,1777774069),(28,'captcha','验证码','图形验证码/滑块验证码配置','shield-check','extension',0,1,0,'','',1777774069,1777774069),(29,'theme_market','模板市场','模板市场浏览、购买与一键安装','shop','extension',0,1,0,'','',1777774069,1777774069),(40,'ai_image','AI配图','AI自动生成文章配图，支持多风格','image','extension',0,1,50,'','[]',0,0),(41,'ai_quality','AI质量检测','AI内容质量评分与改进建议','check-circle','extension',0,1,51,'','[]',0,0),(42,'ai_seo','AI SEO优化','AI自动优化SEO元数据(标题/描述/关键词)','magic','extension',0,1,52,'','[]',0,0),(43,'social_share','社交分享','微信/微博/QQ分享与传播统计','share','interaction',0,1,55,'','[]',0,0),(44,'invite_points','邀请返积分','邀请好友注册返积分，多级奖励机制','gift','interaction',0,1,56,'','[]',0,0),(45,'coupon','优惠券系统','满减/折扣/免邮券管理，支持自动发放','ticket-perforated','extension',0,1,60,'','[]',0,0),(46,'content_rating','评价评分','内容评价与评分管理，支持多维度评分','star','interaction',0,1,61,'','[]',0,0),(47,'template_design','模板设计器','前台模板可视化配置与AI智能配色','palette','extension',0,1,62,'','[]',0,0);
/*!40000 ALTER TABLE `{prefix}module` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}monitor_alert`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}monitor_alert` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `alert_name` varchar(100) NOT NULL COMMENT '告警名称',
  `monitor_type` varchar(30) NOT NULL COMMENT '监控类型(server/app/db/cache/queue)',
  `monitor_metric` varchar(50) NOT NULL COMMENT '监控指标(cpu/memory/disk/network/response_time/error_rate)',
  `alert_rule` json NOT NULL COMMENT '告警规则(JSON: operator/threshold/duration)',
  `alert_level` varchar(10) DEFAULT 'warning' COMMENT '告警级别(info/warning/critical)',
  `alert_channels` json DEFAULT NULL COMMENT '告警渠道(JSON: email/sms/webhook/dingtalk/feishu)',
  `alert_recipients` json DEFAULT NULL COMMENT '告警接收人(JSON)',
  `escalation_config` json DEFAULT NULL COMMENT '升级配置(JSON)',
  `cooldown_minutes` int DEFAULT '30' COMMENT '冷却时间(分钟)',
  `is_active` tinyint DEFAULT '1' COMMENT '是否启用',
  `last_triggered` datetime DEFAULT NULL COMMENT '最后触发时间',
  `trigger_count` int DEFAULT '0' COMMENT '触发次数',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`monitor_type`),
  KEY `idx_metric` (`monitor_metric`),
  KEY `idx_level` (`alert_level`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='监控告警规则表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}monitor_alert` WRITE;
/*!40000 ALTER TABLE `{prefix}monitor_alert` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}monitor_alert` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}notification` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `receiver_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin' COMMENT '接收者类型:admin/member/system',
  `receiver_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '接收者ID',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system' COMMENT '类型:system/review/publish/title',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '通知标题',
  `content` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '通知内容',
  `link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '跳转链接',
  `is_read` tinyint NOT NULL DEFAULT '0' COMMENT '是否已读:0否/1是',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `notify_channel` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '通知通道: sms/email/in_app/wechat',
  `notify_template_id` int unsigned NOT NULL DEFAULT '0' COMMENT '通知模板ID',
  `notify_priority` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '优先级: high/normal/low',
  `channel_result` json DEFAULT NULL COMMENT '通道返回结果',
  PRIMARY KEY (`id`),
  KEY `idx_receiver_read` (`receiver_type`,`receiver_id`,`is_read`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='消息通知表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}notification` WRITE;
/*!40000 ALTER TABLE `{prefix}notification` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}notification` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}oauth_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}oauth_user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `oauth_provider` varchar(30) NOT NULL DEFAULT '' COMMENT 'OAuth提供商: wechat/qq/github/weibo',
  `oauth_openid` varchar(128) NOT NULL DEFAULT '' COMMENT 'OpenID',
  `oauth_unionid` varchar(128) NOT NULL DEFAULT '' COMMENT 'UnionID(微信专用)',
  `oauth_nickname` varchar(100) NOT NULL DEFAULT '' COMMENT '第三方昵称',
  `oauth_avatar` varchar(500) NOT NULL DEFAULT '' COMMENT '第三方头像URL',
  `oauth_data` json DEFAULT NULL COMMENT '第三方返回的完整用户数据',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(45) NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `login_count` int unsigned NOT NULL DEFAULT '0' COMMENT '登录次数',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态: 1正常 0禁用',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provider_openid` (`oauth_provider`,`oauth_openid`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_oauth_unionid` (`oauth_unionid`),
  KEY `idx_last_login_time` (`last_login_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='第三方登录绑定表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}oauth_user` WRITE;
/*!40000 ALTER TABLE `{prefix}oauth_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}oauth_user` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL DEFAULT '' COMMENT '订单号',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `source` varchar(50) NOT NULL DEFAULT '' COMMENT '来源: plugin/template/member/content',
  `source_id` varchar(100) NOT NULL DEFAULT '' COMMENT '来源ID(插件code/模板code/会员等级ID/内容ID)',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态: 0=待支付 1=已支付 2=已退款 3=已关闭',
  `pay_method` varchar(20) NOT NULL DEFAULT '' COMMENT '支付方式: wechat/alipay',
  `pay_trade_no` varchar(64) NOT NULL DEFAULT '' COMMENT '第三方交易号',
  `paid_time` int unsigned NOT NULL DEFAULT '0' COMMENT '支付时间',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `user_id` (`user_id`),
  KEY `source` (`source`,`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='订单表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}orders` WRITE;
/*!40000 ALTER TABLE `{prefix}orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}orders` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}paid_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}paid_order` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_sn` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '订单号',
  `payment_order_no` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'å…³è”PaymentServiceè®¢å•å·({prefix}orders.order_no)ï¼ŒçœŸé’±æ”¯ä»˜æ—¶å¡«å……',
  `member_id` int NOT NULL COMMENT '购买会员ID',
  `content_id` int NOT NULL COMMENT '购买内容ID',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'content' COMMENT '类型: content内容',
  `price` decimal(10,2) NOT NULL COMMENT '实付金额/积分',
  `pay_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'points' COMMENT '支付方式: points积分 wechat微信 alipay支付宝',
  `status` tinyint DEFAULT '0' COMMENT '状态: 0待支付 1已支付 2已退款 3已关闭',
  `paid_at` int unsigned DEFAULT '0' COMMENT '支付时间',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  `refund_sn` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '退款单号',
  `refund_amount` decimal(10,2) DEFAULT '0.00' COMMENT '退款金额',
  `refund_time` int unsigned DEFAULT '0' COMMENT '退款时间',
  `refund_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '退款原因',
  `transaction_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '微信交易号',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_sn` (`order_sn`),
  UNIQUE KEY `uk_member_content` (`member_id`,`content_id`,`type`),
  KEY `idx_member` (`member_id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_order_no` (`payment_order_no`),
  KEY `idx_member_status` (`member_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='付费订单表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}paid_order` WRITE;
/*!40000 ALTER TABLE `{prefix}paid_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}paid_order` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}payment_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}payment_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_sn` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '订单号',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '类型: request/notify/refund',
  `request_data` text COLLATE utf8mb4_unicode_ci COMMENT '请求数据(JSON)',
  `response_data` text COLLATE utf8mb4_unicode_ci COMMENT '响应数据(JSON)',
  `status` tinyint DEFAULT '1' COMMENT '状态: 1成功 0失败',
  `error_msg` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '错误信息',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `pay_channel` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '支付渠道: alipay/wechat/unionpay',
  `channel_order_no` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '渠道订单号',
  `channel_data` json DEFAULT NULL COMMENT '渠道返回数据',
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_sn`),
  KEY `idx_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}payment_log` WRITE;
/*!40000 ALTER TABLE `{prefix}payment_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}payment_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}performance_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}performance_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '请求URL',
  `method` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GET' COMMENT '请求方法',
  `response_time` int NOT NULL DEFAULT '0' COMMENT '响应时间(毫秒)',
  `db_query_count` int NOT NULL DEFAULT '0' COMMENT 'DB查询次数',
  `db_query_time` int NOT NULL DEFAULT '0' COMMENT 'DB查询总耗时(毫秒)',
  `memory_usage` bigint NOT NULL DEFAULT '0' COMMENT '内存使用(字节)',
  `memory_peak` bigint NOT NULL DEFAULT '0' COMMENT '内存峰值(字节)',
  `status_code` int NOT NULL DEFAULT '200' COMMENT 'HTTP状态码',
  `is_slow` tinyint NOT NULL DEFAULT '0' COMMENT '是否慢请求(>2s)',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `extra` json DEFAULT NULL COMMENT '扩展数据',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at_date` date GENERATED ALWAYS AS (cast(`created_at` as date)) STORED,
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_created_at_date` (`created_at_date`),
  KEY `idx_is_slow` (`is_slow`),
  KEY `idx_response_time` (`response_time`),
  KEY `idx_url` (`url`(255))
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='V2.9.35 性能日志';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}performance_log` WRITE;
/*!40000 ALTER TABLE `{prefix}performance_log` DISABLE KEYS */;
INSERT INTO `{prefix}performance_log` (`id`, `url`, `method`, `response_time`, `db_query_count`, `db_query_time`, `memory_usage`, `memory_peak`, `status_code`, `is_slow`, `user_id`, `ip`, `extra`, `created_at`) VALUES (1,'http://localhost:3000/admin/member_level/index','GET',123,1,0,2033336,2670504,200,0,1,'172.18.0.1',NULL,'2026-07-13 10:32:15'),(2,'http://localhost:3000/assets/js/bootstrap.bundle.min.js.map','GET',385,1,0,206600,807808,500,0,1,'172.18.0.1',NULL,'2026-07-13 11:29:30'),(3,'http://localhost:3000/api/cache/clearByType','POST',369,1,0,193880,773832,200,0,1,'172.18.0.1',NULL,'2026-07-13 11:41:52'),(4,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',169,2,0,2098408,2718032,200,0,1,'172.18.0.1',NULL,'2026-07-13 11:41:54'),(5,'http://localhost:3000/admin/member_level/edit/1','GET',415,2,0,2061312,2722552,200,0,1,'172.18.0.1',NULL,'2026-07-13 11:49:31'),(6,'http://localhost:3000/admin/member_level/index?_=1783914662547','GET',492,2,0,2058792,2644064,200,0,1,'172.18.0.1',NULL,'2026-07-13 11:51:55'),(7,'http://localhost:3000/assets/css/bootstrap.min.css.map','GET',250,1,0,247888,862072,500,0,1,'172.18.0.1',NULL,'2026-07-13 11:52:00'),(8,'http://localhost:3000/api/cache/clearByType','POST',363,1,0,194176,773056,200,0,1,'172.18.0.1',NULL,'2026-07-13 11:57:15'),(9,'http://localhost:3000/admin/member_level/index?_=1783915325104','GET',130,2,0,2079720,2659552,200,0,1,'172.18.0.1',NULL,'2026-07-13 12:02:15'),(10,'http://localhost:3000/admin/member_level/index','GET',332,1,0,2045944,2607616,200,0,1,'172.18.0.1',NULL,'2026-07-13 12:02:17'),(11,'http://localhost:3000/assets/js/bootstrap.bundle.min.js.map','GET',307,1,0,247888,862120,500,0,1,'172.18.0.1',NULL,'2026-07-13 12:05:00'),(12,'http://localhost:3000/assets/js/bootstrap.bundle.min.js.map','GET',138,1,0,247888,862120,500,0,1,'172.18.0.1',NULL,'2026-07-13 12:05:25'),(13,'http://localhost:3000/admin/member_level/edit/2?modal=1','GET',515,2,0,2051216,2695784,200,0,1,'172.18.0.1',NULL,'2026-07-13 12:05:30'),(14,'http://localhost:3000/assets/js/bootstrap.bundle.min.js.map','GET',139,1,0,247888,862120,500,0,1,'172.18.0.1',NULL,'2026-07-13 12:05:49'),(15,'http://localhost:3000/admin/dashboard/index?_=1783915696265','GET',529,1,0,2208304,2810056,200,0,1,'172.18.0.1',NULL,'2026-07-13 12:09:09'),(16,'http://localhost:3000/admin/dashboard/overview','GET',57,8,2,2023328,2748072,200,0,1,'172.18.0.1',NULL,'2026-07-13 12:09:09'),(17,'http://localhost:3000/admin/mail_log/statistics_data?type=trend','GET',40,2,0,2017952,2571480,200,0,1,'172.18.0.1',NULL,'2026-07-13 12:09:49'),(18,'http://localhost:3000/admin/mail_log/statistics_data?type=status','GET',29,2,0,2002720,2554648,200,0,1,'172.18.0.1',NULL,'2026-07-13 12:09:49'),(19,'http://localhost:3000/assets/css/bootstrap.min.css.map','GET',169,1,0,247888,862072,500,0,1,'172.18.0.1',NULL,'2026-07-13 12:28:02'),(20,'http://localhost:3000/admin/content/index?_=1783916882328','GET',599,7,2,2191160,2792976,200,0,1,'172.18.0.1',NULL,'2026-07-13 12:28:10'),(21,'http://localhost:3000/admin/content/edit/1?_=1783916882329','GET',707,12,3,2300936,3207424,200,0,1,'172.18.0.1',NULL,'2026-07-13 12:28:14'),(22,'http://localhost:3000/assets/js/bootstrap.bundle.min.js.map','GET',163,2,0,250768,807584,500,0,1,'172.18.0.1',NULL,'2026-07-13 12:36:27'),(23,'http://localhost:3000/admin?_=1783939094375','GET',516,4,1,2127944,2710024,200,0,1,'172.18.0.1',NULL,'2026-07-13 18:38:21'),(24,'http://localhost:3000/admin/banner/index?_=1783939102893','GET',503,2,0,2076160,2716632,200,0,1,'172.18.0.1',NULL,'2026-07-13 18:39:16'),(25,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',458,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 18:39:24'),(26,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',445,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 18:40:24'),(27,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',425,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 19:01:24'),(28,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',454,2,0,2040152,2635704,200,0,1,'172.18.0.1',NULL,'2026-07-13 19:04:24'),(29,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',426,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 19:20:09'),(30,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',434,3,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 19:22:08'),(31,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',447,2,0,2040152,2635704,200,0,1,'172.18.0.1',NULL,'2026-07-13 19:28:08'),(32,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',417,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 20:21:09'),(33,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',447,2,0,2040184,2635736,200,0,1,'172.18.0.1',NULL,'2026-07-13 20:41:09'),(34,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',429,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 20:52:09'),(35,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',424,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 21:33:09'),(36,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',429,2,0,2040152,2635704,200,0,1,'172.18.0.1',NULL,'2026-07-13 21:35:09'),(37,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',443,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 21:45:09'),(38,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',445,2,0,2040184,2635736,200,0,1,'172.18.0.1',NULL,'2026-07-13 21:49:09'),(39,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',437,2,0,2040152,2635704,200,0,1,'172.18.0.1',NULL,'2026-07-13 21:53:09'),(40,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',441,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 21:56:09'),(41,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',488,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 22:10:09'),(42,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',465,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 22:13:09'),(43,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',506,2,0,2040152,2635704,200,0,1,'172.18.0.1',NULL,'2026-07-13 22:24:09'),(44,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',474,2,0,2040152,2635704,200,0,1,'172.18.0.1',NULL,'2026-07-13 22:27:09'),(45,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',472,2,0,2040152,2635704,200,0,1,'172.18.0.1',NULL,'2026-07-13 22:29:09'),(46,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',483,2,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 22:33:09'),(47,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',468,3,0,2040168,2635720,200,0,1,'172.18.0.1',NULL,'2026-07-13 22:57:09'),(48,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',485,2,0,2040184,2635736,200,0,1,'172.18.0.1',NULL,'2026-07-13 23:04:09'),(49,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',507,2,0,2040184,2635736,200,0,1,'172.18.0.1',NULL,'2026-07-13 23:16:09'),(50,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',442,2,0,2040184,2635736,200,0,1,'172.18.0.1',NULL,'2026-07-13 23:31:09'),(51,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',478,2,0,2040184,2635736,200,0,1,'172.18.0.1',NULL,'2026-07-13 23:55:25'),(52,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',447,1,0,2204632,2734584,500,0,1,'172.18.0.1',NULL,'2026-07-14 10:38:37'),(53,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',430,1,0,2204632,2734584,500,0,1,'172.18.0.1',NULL,'2026-07-14 10:47:44'),(54,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',426,1,0,2204632,2734584,500,0,1,'172.18.0.1',NULL,'2026-07-14 10:50:47'),(55,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',426,1,0,2204632,2734584,500,0,1,'172.18.0.1',NULL,'2026-07-14 10:54:51'),(56,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',535,1,0,2204632,2734584,500,0,1,'172.18.0.1',NULL,'2026-07-14 11:00:57'),(57,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',479,1,0,2204632,2734584,500,0,1,'172.18.0.1',NULL,'2026-07-14 11:04:02'),(58,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',469,1,0,2204632,2734584,500,0,1,'172.18.0.1',NULL,'2026-07-14 11:15:10'),(59,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',448,1,0,2204632,2734584,500,0,1,'172.18.0.1',NULL,'2026-07-14 11:22:10'),(60,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',441,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 11:38:10'),(61,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',454,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 11:41:10'),(62,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',439,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 11:42:10'),(63,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',474,2,0,2137264,2720984,200,0,1,'172.18.0.1',NULL,'2026-07-14 11:52:10'),(64,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',437,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 11:53:10'),(65,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',468,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 11:55:10'),(66,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',436,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 12:21:53'),(67,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',457,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 12:25:57'),(68,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',431,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 12:38:37'),(69,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',408,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 12:48:47'),(70,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',403,3,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 13:20:10'),(71,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',399,2,0,2137264,2720984,200,0,1,'172.18.0.1',NULL,'2026-07-14 13:21:10'),(72,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',458,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 13:49:10'),(73,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',444,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 13:53:10'),(74,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',474,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 14:06:37'),(75,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',463,2,0,2137264,2720936,200,0,1,'172.18.0.1',NULL,'2026-07-14 14:14:41'),(76,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',399,2,0,2137248,2720920,200,0,1,'172.18.0.1',NULL,'2026-07-14 14:23:49'),(77,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',408,2,0,2137264,2720936,200,0,1,'172.18.0.1',NULL,'2026-07-14 14:25:52'),(78,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',405,2,0,2137248,2720920,200,0,1,'172.18.0.1',NULL,'2026-07-14 14:30:57'),(79,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',414,3,0,2137232,2720904,200,0,1,'172.18.0.1',NULL,'2026-07-14 14:32:59'),(80,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',401,2,0,2137248,2720920,200,0,1,'172.18.0.1',NULL,'2026-07-14 14:34:00'),(81,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',414,2,0,2137264,2720936,200,0,1,'172.18.0.1',NULL,'2026-07-14 15:22:10'),(82,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',401,2,0,2137248,2720920,200,0,1,'172.18.0.1',NULL,'2026-07-14 15:35:10'),(83,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',398,2,0,2137232,2720904,200,0,1,'172.18.0.1',NULL,'2026-07-14 15:50:10'),(84,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',511,2,0,2137232,2720904,200,0,1,'172.18.0.1',NULL,'2026-07-14 16:12:47'),(85,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',415,3,0,2137264,2720936,200,0,1,'172.18.0.1',NULL,'2026-07-14 16:16:51'),(86,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',422,2,0,2137248,2720920,200,0,1,'172.18.0.1',NULL,'2026-07-14 16:18:53'),(87,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',408,2,0,2137264,2720936,200,0,1,'172.18.0.1',NULL,'2026-07-14 16:19:54'),(88,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',424,2,0,2137232,2720904,200,0,1,'172.18.0.1',NULL,'2026-07-14 16:20:55'),(89,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',411,3,0,2137248,2720920,200,0,1,'172.18.0.1',NULL,'2026-07-14 16:27:01'),(90,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',408,2,0,2137232,2720904,200,0,1,'172.18.0.1',NULL,'2026-07-14 16:33:07'),(91,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',434,2,0,2137248,2720920,200,0,1,'172.18.0.1',NULL,'2026-07-14 16:58:41'),(92,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',431,2,0,2137248,2720920,200,0,1,'172.18.0.1',NULL,'2026-07-14 17:01:44'),(93,'http://localhost:3000/admin/ad/index?_=1783996535772','GET',112,2,1,2197536,2821856,200,0,1,'172.18.0.1',NULL,'2026-07-14 17:12:03'),(94,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',429,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 17:29:50'),(95,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',458,2,0,2137264,2720984,200,0,1,'172.18.0.1',NULL,'2026-07-14 17:31:52'),(96,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',445,6,1,2163144,3173816,200,0,1,'172.18.0.1',NULL,'2026-07-14 17:40:00'),(97,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',398,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 17:55:09'),(98,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',410,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:01:10'),(99,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',413,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:11:10'),(100,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',436,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:23:40'),(101,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',444,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:27:37'),(102,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',434,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:28:38'),(103,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',484,2,0,2137264,2720984,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:30:40'),(104,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',484,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:32:42'),(105,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',483,6,1,2163160,3173800,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:40:40'),(106,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',452,2,0,2137232,2720952,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:51:43'),(107,'http://localhost:3000/admin/notification/index?is_read=0&ajax=1','GET',499,2,0,2137248,2720968,200,0,1,'172.18.0.1',NULL,'2026-07-14 18:58:50');
/*!40000 ALTER TABLE `{prefix}performance_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}platform_app`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}platform_app` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `app_name` varchar(100) NOT NULL DEFAULT '' COMMENT '应用名称',
  `app_identifier` varchar(100) NOT NULL COMMENT '应用唯一标识(UNIQUE)',
  `app_type` varchar(30) NOT NULL DEFAULT 'web' COMMENT '类型: web/mobile/plugin/integration/other',
  `developer_id` int unsigned NOT NULL DEFAULT '0' COMMENT '开发者ID',
  `description` text COMMENT '应用描述',
  `app_config` json DEFAULT NULL COMMENT '应用配置(回调URL/权限等)',
  `required_permissions` json DEFAULT NULL COMMENT '所需权限列表',
  `api_key` varchar(128) NOT NULL DEFAULT '' COMMENT 'API Key',
  `api_secret` varchar(128) NOT NULL DEFAULT '' COMMENT 'API Secret',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT '状态: pending/approved/rejected/offline/published',
  `version` varchar(20) NOT NULL DEFAULT '1.0.0' COMMENT '当前版本',
  `install_count` int unsigned NOT NULL DEFAULT '0' COMMENT '安装次数',
  `avg_rating` decimal(2,1) NOT NULL DEFAULT '0.0' COMMENT '平均评分',
  `screenshots` json DEFAULT NULL COMMENT '截图URL列表',
  `download_url` varchar(500) NOT NULL DEFAULT '' COMMENT '下载地址',
  `category` varchar(50) NOT NULL DEFAULT '' COMMENT '分类',
  `tags` varchar(255) NOT NULL DEFAULT '' COMMENT '标签',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '排序',
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
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}platform_app` WRITE;
/*!40000 ALTER TABLE `{prefix}platform_app` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}platform_app` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}plugin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}plugin` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '插件名称',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '插件唯一标识',
  `store_id` int DEFAULT '0' COMMENT '商店插件ID',
  `developer_id` int DEFAULT '0' COMMENT '开发者ID',
  `developer_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '开发者名称',
  `audit_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft' COMMENT '审核状态(draft/pending/passed/rejected/online/offline)',
  `audit_comment` text COLLATE utf8mb4_unicode_ci COMMENT '审核意见',
  `audit_time` datetime DEFAULT NULL COMMENT '审核时间',
  `audit_admin_id` int DEFAULT '0' COMMENT '审核管理员ID',
  `auto_audit_score` decimal(5,2) DEFAULT '0.00' COMMENT '自动审核评分(0-100)',
  `category_id` int DEFAULT '0' COMMENT '分类ID',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '价格',
  `rating` decimal(2,1) DEFAULT '0.0' COMMENT '评分',
  `download_count` int DEFAULT '0' COMMENT '下载次数',
  `rating_count` int DEFAULT '0' COMMENT '评分人数',
  `install_count` int DEFAULT '0' COMMENT '安装次数',
  `screenshots` json DEFAULT NULL COMMENT '插件截图',
  `tags` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '插件标签',
  `plugin_docs` text COLLATE utf8mb4_unicode_ci COMMENT '插件文档(Markdown)',
  `is_featured` tinyint DEFAULT '0' COMMENT '是否精选',
  `is_recommended` tinyint DEFAULT '0' COMMENT '是否推荐',
  `version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '1.0.0' COMMENT '版本号',
  `author` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '作者',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '描述',
  `hooks` text COLLATE utf8mb4_unicode_ci COMMENT '注册的Hook列表(JSON)',
  `config` text COLLATE utf8mb4_unicode_ci COMMENT '插件配置(JSON)',
  `config_schema` json DEFAULT NULL COMMENT '插件配置Schema(JSON)',
  `is_enabled` tinyint DEFAULT '0' COMMENT '启用状态: 0禁用 1启用',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  `store_config` json DEFAULT NULL COMMENT 'V2.9.35 PLUG-3 商店配置(商店ID/价格/评分/下载URL/更新检测时间)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件注册表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}plugin` WRITE;
/*!40000 ALTER TABLE `{prefix}plugin` DISABLE KEYS */;
INSERT INTO `{prefix}plugin` VALUES (1,'HelloWorld 示例插件','helloworld',0,0,'','draft',NULL,NULL,0,0.00,0,0.00,0.0,0,0,0,NULL,'',NULL,0,0,'1.0.0','AI-CMS','V2.5插件系统示例插件，演示Hook注册和事件触发','{\"content_after_detail\":\"onContentAfterDetail\",\"dashboard_widget\":\"onDashboardWidget\"}',NULL,NULL,0,1777775583,1777776420,NULL);
/*!40000 ALTER TABLE `{prefix}plugin` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}plugin_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}plugin_category` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分类名称',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '分类描述',
  `icon` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '图标类名',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0=禁用 1=启用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sort` (`sort`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件分类-V2.9.25';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}plugin_category` WRITE;
/*!40000 ALTER TABLE `{prefix}plugin_category` DISABLE KEYS */;
INSERT INTO `{prefix}plugin_category` VALUES (1,'功能增强','扩展系统核心功能的插件','bi bi-plug',10,1,'2026-06-21 00:46:36','2026-06-21 00:46:36'),(2,'SEO优化','搜索引擎优化相关插件','bi bi-search',20,1,'2026-06-21 00:46:36','2026-06-21 00:46:36'),(3,'社交分享','社交平台和分享功能插件','bi bi-share',30,1,'2026-06-21 00:46:36','2026-06-21 00:46:36'),(4,'数据统计','数据分析和统计报表插件','bi bi-bar-chart',40,1,'2026-06-21 00:46:36','2026-06-21 00:46:36'),(5,'内容管理','内容编辑和排版增强插件','bi bi-file-text',50,1,'2026-06-21 00:46:36','2026-06-21 00:46:36'),(6,'安全防护','安全加固和防护插件','bi bi-shield-check',60,1,'2026-06-21 00:46:36','2026-06-21 00:46:36'),(7,'界面美化','主题和界面美化插件','bi bi-palette',70,1,'2026-06-21 00:46:36','2026-06-21 00:46:36'),(8,'第三方集成','第三方服务和API集成','bi bi-cloud',80,1,'2026-06-21 00:46:36','2026-06-21 00:46:36');
/*!40000 ALTER TABLE `{prefix}plugin_category` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}plugin_dependency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}plugin_dependency` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_id` int unsigned NOT NULL COMMENT '主插件ID',
  `depends_on_plugin_id` int unsigned NOT NULL COMMENT '依赖插件ID',
  `min_version` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT '1.0.0' COMMENT '最低版本要求',
  `max_version` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT '*' COMMENT '最高版本要求（*=无限制）',
  `is_required` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0=可选 1=必须',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plugin_dep` (`plugin_id`,`depends_on_plugin_id`),
  KEY `idx_plugin_id` (`plugin_id`),
  KEY `idx_depends_on` (`depends_on_plugin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件依赖关系-V2.9.25';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}plugin_dependency` WRITE;
/*!40000 ALTER TABLE `{prefix}plugin_dependency` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}plugin_dependency` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}plugin_download_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}plugin_download_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_id` int unsigned NOT NULL COMMENT '插件包ID',
  `version` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '下载版本',
  `user_id` int unsigned DEFAULT '0' COMMENT '用户ID（0=匿名）',
  `ip` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'UA',
  `source` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT 'web' COMMENT '来源：web/admin/api',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0=失败 1=成功',
  `error_msg` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '失败原因',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_plugin_id` (`plugin_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件下载日志-V2.9.25';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}plugin_download_log` WRITE;
/*!40000 ALTER TABLE `{prefix}plugin_download_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}plugin_download_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}plugin_hook`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}plugin_hook` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plugin_id` bigint unsigned NOT NULL COMMENT '插件ID',
  `hook_name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '钩子名称',
  `hook_type` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'action' COMMENT '钩子类型: action/filter',
  `callback` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '回调(类名@方法名)',
  `priority` int NOT NULL DEFAULT '100' COMMENT '优先级(越小越先执行)',
  `enabled` tinyint NOT NULL DEFAULT '1' COMMENT '是否启用',
  `exec_count` bigint NOT NULL DEFAULT '0' COMMENT '执行次数',
  `exec_time` bigint NOT NULL DEFAULT '0' COMMENT '累计执行耗时(微秒)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_plugin_id` (`plugin_id`),
  KEY `idx_hook_name` (`hook_name`),
  KEY `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='V2.9.35 插件钩子绑定';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}plugin_hook` WRITE;
/*!40000 ALTER TABLE `{prefix}plugin_hook` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}plugin_hook` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}plugin_install_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}plugin_install_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '插件标识',
  `action` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作类型(install/update/rollback)',
  `version_from` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '旧版本',
  `version_to` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '新版本',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态:0进行中1成功2失败',
  `log` text COLLATE utf8mb4_unicode_ci COMMENT '详细日志',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '操作人',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_plugin` (`plugin_name`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件安装日志表(V2.9.28 P-1)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}plugin_install_log` WRITE;
/*!40000 ALTER TABLE `{prefix}plugin_install_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}plugin_install_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}plugin_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}plugin_order` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL,
  `plugin_id` int NOT NULL,
  `plugin_name` varchar(100) NOT NULL,
  `plugin_version` varchar(20) NOT NULL,
  `price` decimal(10,2) DEFAULT '0.00',
  `member_id` int NOT NULL,
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
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}plugin_order` WRITE;
/*!40000 ALTER TABLE `{prefix}plugin_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}plugin_order` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}plugin_package`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}plugin_package` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '插件标识（目录名）',
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '插件名称',
  `version` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1.0.0' COMMENT '当前版本',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '插件描述',
  `author` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '作者',
  `author_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '作者链接',
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '图标URL',
  `screenshots` json DEFAULT NULL COMMENT '截图URL数组',
  `tags` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '标签，逗号分隔',
  `category_id` int unsigned DEFAULT '0' COMMENT '分类ID',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '价格（0=免费）',
  `is_free` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否免费',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0=下架 1=上架 2=审核中',
  `download_count` int unsigned NOT NULL DEFAULT '0' COMMENT '下载次数',
  `install_count` int unsigned NOT NULL DEFAULT '0' COMMENT '安装次数',
  `rating_avg` decimal(2,1) NOT NULL DEFAULT '5.0' COMMENT '平均评分',
  `rating_count` int unsigned NOT NULL DEFAULT '0' COMMENT '评分人数',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `is_recommended` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否推荐',
  `is_hot` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否热门',
  `signature` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'HMAC-SHA256 签名',
  `signature_method` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT 'HMAC-SHA256' COMMENT '签名算法',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '包文件路径',
  `file_size` int unsigned DEFAULT '0' COMMENT '包文件大小（字节）',
  `file_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '包文件 SHA256 哈希',
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
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}plugin_package` WRITE;
/*!40000 ALTER TABLE `{prefix}plugin_package` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}plugin_package` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}plugin_payout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}plugin_payout` (
  `id` int NOT NULL AUTO_INCREMENT,
  `developer_id` int NOT NULL,
  `developer_name` varchar(100) NOT NULL,
  `order_id` int NOT NULL,
  `plugin_id` int NOT NULL,
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
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}plugin_payout` WRITE;
/*!40000 ALTER TABLE `{prefix}plugin_payout` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}plugin_payout` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}plugin_rating`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}plugin_rating` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_code` varchar(100) NOT NULL DEFAULT '' COMMENT '插件标识',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `rating` tinyint unsigned NOT NULL DEFAULT '5' COMMENT '评分1-5',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '评价内容',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plugin_user` (`plugin_code`,`user_id`),
  KEY `plugin_code` (`plugin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='插件评分评价';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}plugin_rating` WRITE;
/*!40000 ALTER TABLE `{prefix}plugin_rating` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}plugin_rating` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}plugin_update_check`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}plugin_update_check` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '插件标识',
  `current_version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '当前版本',
  `latest_version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '最新版本',
  `has_update` tinyint NOT NULL DEFAULT '0' COMMENT '是否有更新',
  `check_time` int unsigned NOT NULL DEFAULT '0' COMMENT '检查时间',
  `changelog` text COLLATE utf8mb4_unicode_ci COMMENT '更新日志',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plugin` (`plugin_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件更新检查记录表(V2.9.28 P-6)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}plugin_update_check` WRITE;
/*!40000 ALTER TABLE `{prefix}plugin_update_check` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}plugin_update_check` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}plugin_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}plugin_version` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_id` int unsigned NOT NULL COMMENT '插件包ID',
  `version` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '版本号',
  `changelog` text COLLATE utf8mb4_unicode_ci COMMENT '更新日志',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '版本包路径',
  `file_size` int unsigned DEFAULT '0' COMMENT '包大小',
  `file_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'SHA256 哈希',
  `signature` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '签名',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0=废弃 1=可用',
  `is_current` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否当前版本',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plugin_version` (`plugin_id`,`version`),
  KEY `idx_plugin_id` (`plugin_id`),
  KEY `idx_is_current` (`is_current`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件版本历史-V2.9.25';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}plugin_version` WRITE;
/*!40000 ALTER TABLE `{prefix}plugin_version` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}plugin_version` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}points_exchange`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}points_exchange` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL COMMENT 'ç”¨æˆ·ID',
  `product_id` int unsigned NOT NULL COMMENT 'å•†å“ID',
  `points` int unsigned NOT NULL DEFAULT '0' COMMENT 'æ¶ˆè€—ç§¯åˆ†',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT 'çŠ¶æ€:0å¾…å¤„ç† 1å·²å‘æ”¾ 2å·²æ‹’ç»',
  `delivery_info` text COLLATE utf8mb4_unicode_ci COMMENT 'å‘è´§ä¿¡æ¯JSON',
  `remark` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'å¤‡æ³¨',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`,`create_time`),
  KEY `idx_status` (`status`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ç§¯åˆ†å…‘æ¢è®°å½•è¡¨';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}points_exchange` WRITE;
/*!40000 ALTER TABLE `{prefix}points_exchange` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}points_exchange` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}points_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}points_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `points` int NOT NULL COMMENT '变动积分（正增负减）',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '类型: signin/comment/like/favorite/purchase/register/admin_adjust',
  `source_id` int DEFAULT '0' COMMENT '来源ID',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '备注',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_type_time` (`type`,`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分变动记录表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}points_log` WRITE;
/*!40000 ALTER TABLE `{prefix}points_log` DISABLE KEYS */;
INSERT INTO `{prefix}points_log` VALUES (1,1,5,'signin',0,'签到第1天',1778340304),(2,1,5,'signin',0,'签到第1天',1778435313),(3,1,5,'signin',0,'签到第2天',1778548411),(4,1,5,'signin',0,'签到第1天',1779362537);
/*!40000 ALTER TABLE `{prefix}points_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}points_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}points_product` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'å•†å“åç§°',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'å•†å“æè¿°',
  `image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'å•†å“å›¾ç‰‡',
  `points` int unsigned NOT NULL DEFAULT '0' COMMENT 'æ‰€éœ€ç§¯åˆ†',
  `stock` int NOT NULL DEFAULT '0' COMMENT 'åº“å­˜(-1è¡¨ç¤ºæ— é™)',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'virtual' COMMENT 'ç±»åž‹:virtual/physical/coupon',
  `config` text COLLATE utf8mb4_unicode_ci COMMENT 'ç±»åž‹é…ç½®JSON',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT 'æŽ’åº',
  `is_enabled` tinyint NOT NULL DEFAULT '1' COMMENT 'æ˜¯å¦ä¸Šæž¶',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_enabled_sort` (`is_enabled`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ç§¯åˆ†å•†å“è¡¨';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}points_product` WRITE;
/*!40000 ALTER TABLE `{prefix}points_product` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}points_product` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}publish_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}publish_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int NOT NULL COMMENT '内容ID',
  `platform_id` int NOT NULL COMMENT '平台ID',
  `platform` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '发布平台: weixin/toutiao/zhihu',
  `action` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'publish' COMMENT '操作: publish/update/delete/retry',
  `platform_content_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '外部平台内容ID',
  `status` tinyint DEFAULT '0' COMMENT '状态: 0待发布 1已发布 2失败',
  `error_msg` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '错误信息',
  `retry_count` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '重试次数',
  `publish_time` int unsigned DEFAULT '0' COMMENT '发布时间',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_platform` (`platform_id`),
  KEY `platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='发布记录表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}publish_log` WRITE;
/*!40000 ALTER TABLE `{prefix}publish_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}publish_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}publish_platform`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}publish_platform` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '平台标识: wechat_mp/toutiao',
  `display_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '显示名称',
  `config_json` text COLLATE utf8mb4_unicode_ci COMMENT '平台配置(JSON: appid/secret/token等)',
  `is_enabled` tinyint DEFAULT '0' COMMENT '启用状态',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='发布平台配置表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}publish_platform` WRITE;
/*!40000 ALTER TABLE `{prefix}publish_platform` DISABLE KEYS */;
INSERT INTO `{prefix}publish_platform` VALUES (1,'wechat_mp','微信公众号','{\"appid\":\"\",\"secret\":\"\"}',0,1777774068,1777774068),(2,'toutiao','头条号','{\"client_key\":\"\",\"client_secret\":\"\"}',0,1777774069,1777774069);
/*!40000 ALTER TABLE `{prefix}publish_platform` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}push_channel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}push_channel` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '通道名称',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'webhook' COMMENT '通道类型: webhook|wechat_push|broadcast',
  `config` text COLLATE utf8mb4_unicode_ci COMMENT '配置信息JSON: {url, headers, method, format, token}',
  `trigger_mode` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '触发方式: 0=手动, 1=自动(发布时触发)',
  `push_scope` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '推送范围: 空=全部, 分类ID逗号分隔',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态: 0=禁用, 1=启用',
  `last_push_at` datetime DEFAULT NULL COMMENT '最后推送时间',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `platform_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '平台类型(wechat/toutiao/zhihu/weibo)',
  `platform_account_id` int DEFAULT '0' COMMENT '平台账号ID',
  `third_party_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '第三方平台文章URL',
  `reads` int DEFAULT '0' COMMENT '阅读量',
  `likes` int DEFAULT '0' COMMENT '点赞数',
  `comments` int DEFAULT '0' COMMENT '评论数',
  `shares` int DEFAULT '0' COMMENT '转发数',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推送通道配置';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}push_channel` WRITE;
/*!40000 ALTER TABLE `{prefix}push_channel` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}push_channel` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}push_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}push_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `channel_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联推送通道ID',
  `content_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联内容ID',
  `request_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '请求URL',
  `request_body` text COLLATE utf8mb4_unicode_ci COMMENT '请求体JSON',
  `response_code` int NOT NULL DEFAULT '0' COMMENT '响应状态码',
  `response_body` text COLLATE utf8mb4_unicode_ci COMMENT '响应内容摘要',
  `duration_ms` int NOT NULL DEFAULT '0' COMMENT '请求耗时(毫秒)',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态: 0=待发送, 1=成功, 2=失败',
  `error_msg` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '失败原因',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `retried_at` datetime DEFAULT NULL COMMENT '重试时间',
  PRIMARY KEY (`id`),
  KEY `idx_channel_id` (`channel_id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推送日志';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}push_log` WRITE;
/*!40000 ALTER TABLE `{prefix}push_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}push_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}push_retry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}push_retry` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `push_id` int unsigned NOT NULL DEFAULT '0' COMMENT '推送内容ID',
  `channel` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '通道标识',
  `reason` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '入队原因',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '0=待重试 1=成功 -1=失败',
  `retry_count` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '已重试次数',
  `error_msg` text COLLATE utf8mb4_unicode_ci COMMENT '错误信息',
  `next_retry_at` int unsigned NOT NULL DEFAULT '0' COMMENT '下次重试时间戳',
  `created_at` int unsigned NOT NULL DEFAULT '0',
  `updated_at` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_status_next` (`status`,`next_retry_at`),
  KEY `idx_push_id` (`push_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推送重试队列';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}push_retry` WRITE;
/*!40000 ALTER TABLE `{prefix}push_retry` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}push_retry` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}qa_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}qa_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) NOT NULL COMMENT '对话会话ID',
  `member_id` int DEFAULT '0' COMMENT '提问用户ID(0为游客)',
  `question` text NOT NULL COMMENT '用户问题',
  `answer` text COMMENT 'AI回答',
  `answer_source` json DEFAULT NULL COMMENT '回答来源(JSON: 引用的内容ID列表)',
  `confidence` decimal(3,2) DEFAULT '0.00' COMMENT '回答置信度(0-1)',
  `is_helpful` tinyint DEFAULT NULL COMMENT '是否有用(Null未反馈/1有用/0无用)',
  `is_sensitive` tinyint DEFAULT '0' COMMENT '是否敏感问题:1是0否',
  `is_answered` tinyint DEFAULT '1' COMMENT '是否已回答:1是0否',
  `response_time` int DEFAULT '0' COMMENT '响应时间(毫秒)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_answered` (`is_answered`),
  KEY `idx_helpful` (`is_helpful`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='智能问答日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}qa_log` WRITE;
/*!40000 ALTER TABLE `{prefix}qa_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}qa_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}rating_reply`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}rating_reply` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `rating_id` int unsigned NOT NULL COMMENT 'å…³è”è¯„ä»·ID',
  `user_id` int unsigned DEFAULT '0' COMMENT 'å›žå¤ç”¨æˆ·(ç®¡ç†å‘˜ID)',
  `member_id` int unsigned DEFAULT '0' COMMENT 'å›žå¤ä¼šå‘˜ID',
  `content` text NOT NULL COMMENT 'å›žå¤å†…å®¹',
  `create_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_rating` (`rating_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='è¯„ä»·å›žå¤è®°å½•';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}rating_reply` WRITE;
/*!40000 ALTER TABLE `{prefix}rating_reply` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}rating_reply` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}recommend_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}recommend_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content_id` int NOT NULL COMMENT '内容ID',
  `member_id` int DEFAULT '0' COMMENT '用户ID(0为游客)',
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
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}recommend_log` WRITE;
/*!40000 ALTER TABLE `{prefix}recommend_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}recommend_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}report_definition`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}report_definition` (
  `id` int NOT NULL AUTO_INCREMENT,
  `report_name` varchar(100) NOT NULL COMMENT '报表名称',
  `report_type` varchar(30) NOT NULL COMMENT '报表类型(content/user/template/distribute/pay)',
  `data_source` varchar(30) NOT NULL COMMENT '数据源(content/member/template_store/push_channel/order)',
  `metrics` json NOT NULL COMMENT '核心指标(JSON数组)',
  `dimensions` json NOT NULL COMMENT '维度(JSON数组)',
  `filters` json DEFAULT NULL COMMENT '筛选条件(JSON)',
  `group_by` varchar(50) DEFAULT '' COMMENT '分组方式',
  `chart_type` varchar(20) DEFAULT 'bar' COMMENT '图表类型(bar/line/pie/radar/heatmap)',
  `date_range` varchar(20) DEFAULT 'last_30_days' COMMENT '默认时间范围',
  `is_system` tinyint DEFAULT '0' COMMENT '是否系统预置',
  `creator_id` int DEFAULT '0' COMMENT '创建人',
  `create_time` int unsigned DEFAULT '0',
  `update_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`report_type`),
  KEY `idx_creator` (`creator_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='报表定义表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}report_definition` WRITE;
/*!40000 ALTER TABLE `{prefix}report_definition` DISABLE KEYS */;
INSERT INTO `{prefix}report_definition` VALUES (1,'内容发布日报','content','content','[\"publish_count\", \"views\", \"interactions\"]','[\"date\"]',NULL,'','bar','last_7_days',1,0,1783830747,1783830747),(2,'用户增长周报','user','member','[\"new_users\", \"active_users\", \"retention_rate\"]','[\"date\"]',NULL,'','line','last_30_days',1,0,1783830770,1783830770),(3,'内容质量月报','content','content','[\"avg_quality_score\", \"repair_count\", \"tag_accuracy\"]','[\"date\"]',NULL,'','line','last_30_days',1,0,1783830770,1783830770),(4,'模板安装排行','template','template_store','[\"install_count\", \"avg_rating\"]','[\"template\"]',NULL,'','bar','last_30_days',1,0,1783830770,1783830770),(5,'付费收入统计','pay','order','[\"revenue\", \"paid_users\", \"arpu\"]','[\"date\"]',NULL,'','line','last_30_days',1,0,1783830770,1783830770),(6,'分发效果报表','distribute','push_channel','[\"distribute_count\", \"reads\", \"interactions\"]','[\"platform\"]',NULL,'','bar','last_30_days',1,0,1783830770,1783830770),(7,'会员等级分布','user','member','[\"level_count\", \"level_ratio\"]','[\"level\"]',NULL,'','pie','all_time',1,0,1783830770,1783830770),(8,'内容模型使用率','content','content','[\"model_count\", \"model_views\"]','[\"content_model\"]',NULL,'','pie','last_30_days',1,0,1783830770,1783830770),(9,'多语言翻译覆盖率','content','content','[\"translated_count\", \"coverage_rate\"]','[\"lang\"]',NULL,'','bar','all_time',1,0,1783830770,1783830770),(10,'系统健康周报','content','content','[\"cpu_avg\", \"memory_avg\", \"error_rate\"]','[\"date\"]',NULL,'','line','last_7_days',1,0,1783830770,1783830770);
/*!40000 ALTER TABLE `{prefix}report_definition` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}review`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}review` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `content_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `user_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '审核人ID',
  `action` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作:approve通过/reject驳回',
  `remark` text COLLATE utf8mb4_unicode_ci COMMENT '审核意见',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审核记录表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}review` WRITE;
/*!40000 ALTER TABLE `{prefix}review` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}review` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}review_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}review_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int unsigned NOT NULL COMMENT '审核记录ID',
  `step` tinyint NOT NULL DEFAULT '1' COMMENT '步骤序号',
  `reviewer_id` int unsigned NOT NULL DEFAULT '0' COMMENT '审核人ID',
  `action` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '动作:pass/reject/withdraw/transfer',
  `comment` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '审核意见',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_record` (`record_id`,`step`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审核日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}review_log` WRITE;
/*!40000 ALTER TABLE `{prefix}review_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}review_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}review_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}review_record` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `workflow_id` int unsigned NOT NULL COMMENT '流程ID',
  `target_id` int unsigned NOT NULL COMMENT '目标对象ID(如内容ID)',
  `target_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'content' COMMENT '目标类型',
  `current_step` tinyint NOT NULL DEFAULT '1' COMMENT '当前步骤序号',
  `total_steps` tinyint NOT NULL DEFAULT '1' COMMENT '总步骤数',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态:0待审核 1审核中 2已通过 3已拒绝 4已撤回',
  `submitter_id` int unsigned NOT NULL DEFAULT '0' COMMENT '提交者ID',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_target` (`target_id`,`target_type`,`workflow_id`),
  KEY `idx_status` (`status`,`current_step`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审核记录表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}review_record` WRITE;
/*!40000 ALTER TABLE `{prefix}review_record` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}review_record` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}review_workflow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}review_workflow` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '流程名称',
  `module` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'content' COMMENT '适用模块:content/member/comment',
  `steps` text COLLATE utf8mb4_unicode_ci COMMENT '流程步骤JSON [{step:1,role_id:0,name:"一审"},...]',
  `is_default` tinyint NOT NULL DEFAULT '0' COMMENT '是否默认流程:0否1是',
  `is_enabled` tinyint NOT NULL DEFAULT '1' COMMENT '是否启用',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_module` (`module`,`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审核工作流定义表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}review_workflow` WRITE;
/*!40000 ALTER TABLE `{prefix}review_workflow` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}review_workflow` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}search_keyword`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}search_keyword` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `keyword` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'å…³é”®è¯',
  `count` int unsigned NOT NULL DEFAULT '1' COMMENT 'æœç´¢æ¬¡æ•°',
  `last_search_time` int unsigned NOT NULL DEFAULT '0',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_keyword` (`keyword`),
  KEY `idx_count` (`count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='æœç´¢å…³é”®è¯ç»Ÿè®¡';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}search_keyword` WRITE;
/*!40000 ALTER TABLE `{prefix}search_keyword` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}search_keyword` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}security_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}security_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '事件类型: xss/csrf/sqli/file_upload/auth_deny/login_fail/login_success/permission_denied/sensitive_access',
  `severity` tinyint NOT NULL DEFAULT '1' COMMENT '严重级别: 1低 2中 3高 4严重',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联用户ID(0=未登录)',
  `username` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP地址(IPv4/IPv6)',
  `user_agent` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'User-Agent',
  `url` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '请求URL',
  `method` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GET' COMMENT '请求方法',
  `payload` text COLLATE utf8mb4_unicode_ci COMMENT '攻击载荷/请求数据摘要',
  `description` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '事件描述',
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
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}security_log` WRITE;
/*!40000 ALTER TABLE `{prefix}security_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}security_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}seo_deadlinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}seo_deadlinks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '死链URL',
  `status_code` int NOT NULL DEFAULT '0' COMMENT 'HTTP状态码',
  `source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '来源页面',
  `check_time` int unsigned NOT NULL DEFAULT '0' COMMENT '检测时间',
  `is_fixed` tinyint NOT NULL DEFAULT '0' COMMENT '是否已修复:0否/1是',
  PRIMARY KEY (`id`),
  KEY `idx_is_fixed` (`is_fixed`),
  KEY `idx_check_time` (`check_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEO死链检测表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}seo_deadlinks` WRITE;
/*!40000 ALTER TABLE `{prefix}seo_deadlinks` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}seo_deadlinks` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}seo_keyword`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}seo_keyword` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `keyword` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '关键词',
  `group_id` int DEFAULT '0' COMMENT '分组ID',
  `search_volume` int DEFAULT '0' COMMENT '搜索量',
  `difficulty` tinyint DEFAULT '50' COMMENT '难度指数（0-100）',
  `is_sensitive` tinyint DEFAULT '0' COMMENT '是否敏感词',
  `status` tinyint DEFAULT '1' COMMENT '状态: 1启用 0禁用',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_keyword` (`keyword`),
  KEY `idx_group` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEO关键词表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}seo_keyword` WRITE;
/*!40000 ALTER TABLE `{prefix}seo_keyword` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}seo_keyword` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}seo_keyword_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}seo_keyword_group` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分组名称',
  `sort` int DEFAULT '0',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEO关键词分组表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}seo_keyword_group` WRITE;
/*!40000 ALTER TABLE `{prefix}seo_keyword_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}seo_keyword_group` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}share_click`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}share_click` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `content_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联内容ID',
  `source` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分享来源: wechat|weibo|qq|twitter|copy',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '访客IP',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '点击时间',
  PRIMARY KEY (`id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_source` (`source`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分享点击日志';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}share_click` WRITE;
/*!40000 ALTER TABLE `{prefix}share_click` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}share_click` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}share_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}share_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'å†…å®¹ID',
  `channel` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other' COMMENT 'åˆ†äº«æ¸ é“: wechat/weibo/qq/copy/other',
  `member_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'ä¼šå‘˜ID(0=æ¸¸å®¢)',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'ç”¨æˆ·IP',
  `referer` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'æ¥æºé¡µ',
  `created_at` int unsigned NOT NULL DEFAULT '0' COMMENT 'åˆ†äº«æ—¶é—´',
  PRIMARY KEY (`id`),
  KEY `idx_content_channel` (`content_id`,`channel`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_channel` (`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='åˆ†äº«æ—¥å¿—è¡¨-V2.9.9';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}share_log` WRITE;
/*!40000 ALTER TABLE `{prefix}share_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}share_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}signin_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}signin_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `signin_date` date NOT NULL,
  `points` int DEFAULT '0' COMMENT '签到获得积分',
  `consecutive_days` int DEFAULT '1' COMMENT '连续签到天数',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_date` (`member_id`,`signin_date`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='签到记录表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}signin_log` WRITE;
/*!40000 ALTER TABLE `{prefix}signin_log` DISABLE KEYS */;
INSERT INTO `{prefix}signin_log` VALUES (19,1,'2026-05-09',5,1,1778340304),(20,1,'2026-05-11',5,1,1778435313),(21,1,'2026-05-12',5,2,1778548411),(22,1,'2026-05-21',5,1,1779362537);
/*!40000 ALTER TABLE `{prefix}signin_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}sse_client`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}sse_client` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `client_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户端唯一标识(UUID)',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID(0=游客)',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户端IP',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'User-Agent',
  `channels` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system' COMMENT '订阅通道(逗号分隔)',
  `last_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '最后接收的消息ID',
  `last_active` int unsigned NOT NULL DEFAULT '0' COMMENT '最后活跃时间',
  `connect_time` int unsigned NOT NULL DEFAULT '0' COMMENT '连接建立时间',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态(1在线/0离线)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_client_id` (`client_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_status` (`status`),
  KEY `idx_last_active` (`last_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SSE客户端连接';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}sse_client` WRITE;
/*!40000 ALTER TABLE `{prefix}sse_client` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}sse_client` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}sse_message_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}sse_message_queue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID(自增,用作Last-Event-Id)',
  `channel` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system' COMMENT '通道(audit/comment/system/notification)',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '目标用户ID(0=广播)',
  `event_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'message' COMMENT '事件类型',
  `payload` text COLLATE utf8mb4_unicode_ci COMMENT '消息内容(JSON)',
  `is_delivered` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否已投递(1是/0否)',
  `delivered_at` int unsigned NOT NULL DEFAULT '0' COMMENT '投递时间',
  `expires_at` int unsigned NOT NULL DEFAULT '0' COMMENT '过期时间(0=不过期)',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_channel_user` (`channel`,`user_id`),
  KEY `idx_is_delivered` (`is_delivered`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SSE消息队列(DB持久化)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}sse_message_queue` WRITE;
/*!40000 ALTER TABLE `{prefix}sse_message_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}sse_message_queue` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}subscriber`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}subscriber` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱地址',
  `nickname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '昵称(可选)',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态: 0=待确认, 1=已确认, 2=已退订',
  `confirm_token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '确认token(唯一)',
  `source` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '订阅来源: detail_page|footer|admin_add|register',
  `tag` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分组标签',
  `subscribed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '订阅时间',
  `confirmed_at` datetime DEFAULT NULL COMMENT '确认时间',
  `unsubscribed_at` datetime DEFAULT NULL COMMENT '退订时间',
  `invalid_at` datetime DEFAULT NULL COMMENT '标记为无效的时间',
  `fail_count` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '连续发送失败次数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  UNIQUE KEY `uk_confirm_token` (`confirm_token`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮件订阅者';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}subscriber` WRITE;
/*!40000 ALTER TABLE `{prefix}subscriber` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}subscriber` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}tag` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标签名称',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='标签表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}tag` WRITE;
/*!40000 ALTER TABLE `{prefix}tag` DISABLE KEYS */;
INSERT INTO `{prefix}tag` VALUES (1,'123',1,1776952387);
/*!40000 ALTER TABLE `{prefix}tag` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}task` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '任务标题',
  `description` text COMMENT '任务描述',
  `type` varchar(50) DEFAULT 'general' COMMENT '任务类型',
  `priority` tinyint DEFAULT '2' COMMENT '优先级:1低/2中/3高/4紧急',
  `status` varchar(20) DEFAULT 'pending' COMMENT '状态:pending/in_progress/pending_review/completed/cancelled/overdue',
  `assignee_id` int DEFAULT '0' COMMENT '主负责人ID',
  `collaborators` json DEFAULT NULL COMMENT '协作者ID列表(JSON数组)',
  `reviewer_id` int DEFAULT '0' COMMENT '审核人ID',
  `notifiers` json DEFAULT NULL COMMENT '通知人ID列表(JSON数组)',
  `progress` int DEFAULT '0' COMMENT '进度(0-100)',
  `milestones` json DEFAULT NULL COMMENT '里程碑列表(JSON数组)',
  `progress_note` varchar(500) DEFAULT '' COMMENT '最近进度备注',
  `deadline` datetime DEFAULT NULL COMMENT '截止时间',
  `start_time` datetime DEFAULT NULL COMMENT '开始时间',
  `complete_time` datetime DEFAULT NULL COMMENT '完成时间',
  `sort_order` int DEFAULT '0' COMMENT '排序',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_assignee` (`assignee_id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_priority` (`priority`),
  KEY `idx_deadline` (`deadline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='任务表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}task` WRITE;
/*!40000 ALTER TABLE `{prefix}task` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}task` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}task_assign_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}task_assign_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL DEFAULT '0' COMMENT '任务ID',
  `from_user_id` int DEFAULT '0' COMMENT '原负责人ID',
  `to_user_id` int DEFAULT '0' COMMENT '新负责人ID',
  `action` varchar(20) DEFAULT 'assign' COMMENT '动作:assign/reassign/batch_assign/auto_assign',
  `reason` varchar(500) DEFAULT '' COMMENT '原因',
  `operator_id` int DEFAULT '0' COMMENT '操作人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_task` (`task_id`),
  KEY `idx_to_user` (`to_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='任务分配历史表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}task_assign_log` WRITE;
/*!40000 ALTER TABLE `{prefix}task_assign_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}task_assign_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}task_notify_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}task_notify_template` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '模板名称',
  `content` text COMMENT '模板内容(支持{task_title},{deadline},{assignee}等变量)',
  `type` varchar(30) DEFAULT 'reminder' COMMENT '类型:reminder/overdue/stalled/custom',
  `status` tinyint DEFAULT '1' COMMENT '状态:1启用0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='任务通知模板表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}task_notify_template` WRITE;
/*!40000 ALTER TABLE `{prefix}task_notify_template` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}task_notify_template` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}task_progress_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}task_progress_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL DEFAULT '0' COMMENT '任务ID',
  `progress` int DEFAULT '0' COMMENT '进度值',
  `note` varchar(500) DEFAULT '' COMMENT '备注',
  `operator_id` int DEFAULT '0' COMMENT '操作人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_task` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='任务进度历史表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}task_progress_log` WRITE;
/*!40000 ALTER TABLE `{prefix}task_progress_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}task_progress_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}task_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}task_template` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `usage_count` int DEFAULT '0' COMMENT '使用次数',
  `sort_order` int DEFAULT '0' COMMENT '排序',
  `status` tinyint DEFAULT '1' COMMENT '状态:1启用0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort_order`),
  KEY `idx_usage` (`usage_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='任务模板表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}task_template` WRITE;
/*!40000 ALTER TABLE `{prefix}task_template` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}task_template` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_audit_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_audit_config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int unsigned NOT NULL DEFAULT '0' COMMENT '模板ID(0=全局默认)',
  `audit_level` tinyint NOT NULL DEFAULT '2' COMMENT '审核层级:1单级2两级3三级',
  `first_reviewer_id` int unsigned NOT NULL DEFAULT '0' COMMENT '初审人ID',
  `final_reviewer_id` int unsigned NOT NULL DEFAULT '0' COMMENT '终审人ID',
  `need_file_diff` tinyint NOT NULL DEFAULT '1' COMMENT '是否需要版本对比',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审核配置表(V2.9.28 M-5)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_audit_config` WRITE;
/*!40000 ALTER TABLE `{prefix}template_audit_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_audit_config` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_audit_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_id` int NOT NULL DEFAULT '0',
  `auditor_id` int NOT NULL DEFAULT '0',
  `auditor_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `action` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reason` text COLLATE utf8mb4_unicode_ci,
  `reason_id` int NOT NULL DEFAULT '0',
  `prev_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `new_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_auditor` (`auditor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板审核日志';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_audit_log` WRITE;
/*!40000 ALTER TABLE `{prefix}template_audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_audit_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_audit_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_audit_report` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int unsigned NOT NULL DEFAULT '0',
  `code_quality_score` decimal(3,1) NOT NULL DEFAULT '0.0',
  `compatibility_score` decimal(3,1) NOT NULL DEFAULT '0.0',
  `responsive_score` decimal(3,1) NOT NULL DEFAULT '0.0',
  `security_score` decimal(3,1) NOT NULL DEFAULT '0.0',
  `total_score` decimal(3,1) NOT NULL DEFAULT '0.0',
  `issues` text COLLATE utf8mb4_unicode_ci COMMENT '问题详情(JSON)',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '0待审1通过2驳回',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板自动审核报告';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_audit_report` WRITE;
/*!40000 ALTER TABLE `{prefix}template_audit_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_audit_report` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_backup` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int unsigned NOT NULL DEFAULT '0' COMMENT '网站主用户ID',
  `slug` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板标识',
  `backup_name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备份名称',
  `backup_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备份文件路径',
  `config_snapshot` json DEFAULT NULL COMMENT '配置快照JSON',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual' COMMENT '备份类型:manual手动/auto自动',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_member_slug` (`member_id`,`slug`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板备份记录表(V2.9.12)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_backup` WRITE;
/*!40000 ALTER TABLE `{prefix}template_backup` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_backup` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_banner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_banner` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Banner标题',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Banner图片URL',
  `target_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '跳转类型:1外部URL/2模板详情/3分类页面',
  `target_id` int unsigned NOT NULL DEFAULT '0' COMMENT '跳转目标ID',
  `target_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '外部跳转URL',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序号',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `start_time` int unsigned NOT NULL DEFAULT '0' COMMENT '开始展示时间',
  `end_time` int unsigned NOT NULL DEFAULT '0' COMMENT '结束展示时间',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`,`sort`),
  KEY `idx_time` (`start_time`,`end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板商店Banner表(V2.9.24)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_banner` WRITE;
/*!40000 ALTER TABLE `{prefix}template_banner` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_banner` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_batch_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_batch_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `operator_id` int NOT NULL COMMENT '操作人ID',
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
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_batch_log` WRITE;
/*!40000 ALTER TABLE `{prefix}template_batch_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_batch_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_cache_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_cache_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `template_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板路径',
  `template_md5` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'MD5校验值',
  `action` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'refresh' COMMENT '操作类型: refresh/clear/rebuild',
  `file_size` int unsigned NOT NULL DEFAULT '0' COMMENT '文件大小(字节)',
  `operator` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system' COMMENT '操作者',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '操作时间',
  PRIMARY KEY (`id`),
  KEY `idx_template_path` (`template_path`(191)),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板缓存变更日志表(V2.9.23 A-4)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_cache_log` WRITE;
/*!40000 ALTER TABLE `{prefix}template_cache_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_cache_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_cart` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int unsigned NOT NULL DEFAULT '0',
  `template_id` int unsigned NOT NULL DEFAULT '0',
  `quantity` int unsigned NOT NULL DEFAULT '1',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_template` (`member_id`,`template_id`),
  KEY `idx_member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板购物车';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_cart` WRITE;
/*!40000 ALTER TABLE `{prefix}template_cart` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_cart` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_category` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `parent_id` int unsigned NOT NULL DEFAULT '0' COMMENT '父级ID(0=顶级)',
  `level` tinyint DEFAULT '1' COMMENT '层级:1一级2二级',
  `sort_order` int DEFAULT '0' COMMENT '排序',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类维度(content_model/industry/style)',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类标识(unique)',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '描述',
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图标',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态(1启用/0禁用)',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status_sort` (`status`,`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板分类';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_category` WRITE;
/*!40000 ALTER TABLE `{prefix}template_category` DISABLE KEYS */;
INSERT INTO `{prefix}template_category` VALUES (1,0,1,0,'content_model','通用型','cat_model_general','适用于多种内容类型的通用模板','bi bi-grid',10,1,1780903524,1780903524),(2,0,1,0,'content_model','文章型','cat_model_article','专注于文章、博客类内容展示','bi bi-file-text',20,1,1780903524,1780903524),(3,0,1,0,'content_model','产品型','cat_model_product','适用于产品展示、电商类站点','bi bi-box',30,1,1780903524,1780903524),(4,0,1,0,'content_model','图片型','cat_model_gallery','专注于图片画廊、作品集展示','bi bi-images',40,1,1780903524,1780903524),(5,0,1,0,'content_model','下载型','cat_model_download','适用于软件下载、资源分享类站点','bi bi-cloud-download',50,1,1780903524,1780903524),(6,0,1,0,'content_model','视频型','cat_model_video','专注于视频内容展示与播放','bi bi-play-btn',60,1,1780903524,1780903524),(7,0,1,0,'industry','企业官网','cat_ind_enterprise','适用于企业官方网站','bi bi-building',10,1,1780903524,1780903524),(8,0,1,0,'industry','电商','cat_ind_ecommerce','适用于在线商城、电商平台','bi bi-cart',20,1,1780903524,1780903524),(9,0,1,0,'industry','科技','cat_ind_tech','适用于科技公司、IT服务类站点','bi bi-cpu',30,1,1780903524,1780903524),(10,0,1,0,'industry','教育','cat_ind_edu','适用于培训机构、学校、在线课程','bi bi-mortarboard',40,1,1780903524,1780903524),(11,0,1,0,'industry','餐饮','cat_ind_catering','适用于餐厅、酒店、美食类站点','bi bi-cup-hot',50,1,1780903524,1780903524),(12,0,1,0,'industry','医疗','cat_ind_medical','适用于医院、诊所、健康类站点','bi bi-heart-pulse',60,1,1780903524,1780903524),(13,0,1,0,'industry','金融','cat_ind_finance','适用于银行、保险、投资类站点','bi bi-bank',70,1,1780903524,1780903524),(14,0,1,0,'industry','个人博客','cat_ind_blog','适用于个人博客、自媒体站点','bi bi-person',80,1,1780903524,1780903524),(15,0,1,0,'style','简约现代','cat_style_minimal','简洁大气的现代设计风格','bi bi-layout-text-window',10,1,1780903524,1780903524),(16,0,1,0,'style','科技时尚','cat_style_tech','充满科技感的时尚设计风格','bi bi-rocket',20,1,1780903524,1780903524),(17,0,1,0,'style','自然温暖','cat_style_nature','自然温馨、亲和力强设计风格','bi bi-tree',30,1,1780903524,1780903524),(18,0,1,0,'style','活泼创意','cat_style_creative','色彩丰富、富有创意的设计风格','bi bi-palette',40,1,1780903524,1780903524);
/*!40000 ALTER TABLE `{prefix}template_category` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_category_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_category_map` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `template_id` int unsigned NOT NULL DEFAULT '0' COMMENT '模板ID',
  `category_id` int unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否主分类（1=主分类，0=次分类）',
  `confidence` tinyint unsigned NOT NULL DEFAULT '100' COMMENT '匹配置信度（0-100）',
  `created_by` tinyint(1) NOT NULL DEFAULT '1' COMMENT '创建来源（1=人工，2=AI自动）',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tmpl_cat` (`template_id`,`category_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_template_primary` (`template_id`,`is_primary`),
  KEY `idx_category_confidence` (`category_id`,`confidence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板-分类映射';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_category_map` WRITE;
/*!40000 ALTER TABLE `{prefix}template_category_map` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_category_map` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_category_v2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_category_v2` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dimension` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'industry/style/function',
  `parent_id` int unsigned NOT NULL DEFAULT '0',
  `sort` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint unsigned NOT NULL DEFAULT '1',
  `template_count` int unsigned NOT NULL DEFAULT '0',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_dimension` (`dimension`),
  KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板分类v2(三维分类)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_category_v2` WRITE;
/*!40000 ALTER TABLE `{prefix}template_category_v2` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_category_v2` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_color_variant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_color_variant` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int unsigned NOT NULL DEFAULT '0' COMMENT '商店模板ID',
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配色方案名称',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '描述',
  `colors` json DEFAULT NULL COMMENT '色值JSON对象',
  `css_variables` text COLLATE utf8mb4_unicode_ci COMMENT 'CSS变量文本',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否默认:0否/1是',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序号',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_store_sort` (`store_id`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板配色变体表(V2.9.12)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_color_variant` WRITE;
/*!40000 ALTER TABLE `{prefix}template_color_variant` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_color_variant` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_component`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_component` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'navbar/footer/carousel/card/button/form/list/icon/divider/heading',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `preview_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `component_data` json NOT NULL COMMENT 'HTML/CSS/JS配置',
  `config_schema` json DEFAULT NULL COMMENT '可配置参数定义',
  `version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'v1.0.0',
  `author_id` int DEFAULT '0',
  `status` tinyint DEFAULT '1',
  `is_system` tinyint DEFAULT '0',
  `create_time` int unsigned DEFAULT '0',
  `update_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_author` (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板组件库表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_component` WRITE;
/*!40000 ALTER TABLE `{prefix}template_component` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_component` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_coupon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_coupon` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `discount_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `min_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `max_uses` int NOT NULL DEFAULT '0',
  `used_count` int NOT NULL DEFAULT '0',
  `template_ids` text COLLATE utf8mb4_unicode_ci,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板优惠码';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_coupon` WRITE;
/*!40000 ALTER TABLE `{prefix}template_coupon` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_coupon` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_custom_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_custom_config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int unsigned NOT NULL DEFAULT '0' COMMENT '网站主用户ID',
  `slug` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板标识',
  `config_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置键',
  `config_value` text COLLATE utf8mb4_unicode_ci COMMENT '配置值',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  `whitelist_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT '白名单状态',
  `whitelist_audit_time` int unsigned DEFAULT '0' COMMENT '白名单审批时间',
  `whitelist_auditor` int DEFAULT '0' COMMENT '审批人',
  `components` json DEFAULT NULL COMMENT '使用的组件列表',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_slug_key` (`member_id`,`slug`,`config_key`),
  KEY `idx_member_slug` (`member_id`,`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板自定义配置表(V2.9.12)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_custom_config` WRITE;
/*!40000 ALTER TABLE `{prefix}template_custom_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_custom_config` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_daily_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_daily_stats` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int unsigned NOT NULL DEFAULT '0' COMMENT '模板ID（0=全站汇总）',
  `stats_date` date NOT NULL COMMENT '统计日期',
  `view_count` int unsigned NOT NULL DEFAULT '0' COMMENT '浏览次数',
  `unique_visitors` int unsigned NOT NULL DEFAULT '0' COMMENT '独立访客数',
  `install_count` int unsigned NOT NULL DEFAULT '0' COMMENT '安装次数',
  `uninstall_count` int unsigned NOT NULL DEFAULT '0' COMMENT '卸载次数',
  `activate_count` int unsigned NOT NULL DEFAULT '0' COMMENT '激活次数',
  `dau` int unsigned NOT NULL DEFAULT '0' COMMENT 'DAU',
  `mau` int unsigned NOT NULL DEFAULT '0' COMMENT 'MAU（月活，仅每月1日计算）',
  `revenue` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '当日收入',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_date` (`template_id`,`stats_date`),
  KEY `idx_date` (`stats_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板日统计汇总表(V2.9.25 N-2)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_daily_stats` WRITE;
/*!40000 ALTER TABLE `{prefix}template_daily_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_daily_stats` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_dev_upload`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_dev_upload` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int unsigned NOT NULL DEFAULT '0' COMMENT '开发者用户ID',
  `store_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联商店模板ID(审核通过后)',
  `slug` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板标识',
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板名称',
  `version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1.0.0' COMMENT '版本号',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '上传文件路径',
  `screenshots` json DEFAULT NULL COMMENT '预览截图JSON数组',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '模板描述',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0待审核/1通过/2拒绝/3需修改',
  `audit_comment` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '审核意见',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_status` (`status`,`create_time`),
  KEY `idx_store` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='开发者模板上传审核表(V2.9.12)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_dev_upload` WRITE;
/*!40000 ALTER TABLE `{prefix}template_dev_upload` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_dev_upload` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_developer_app`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_developer_app` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `app_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `app_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `app_secret` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint DEFAULT '1' COMMENT '1启用0禁用',
  `last_used_time` int unsigned DEFAULT '0',
  `create_time` int unsigned DEFAULT '0',
  `update_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_key` (`app_key`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板开发者应用表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_developer_app` WRITE;
/*!40000 ALTER TABLE `{prefix}template_developer_app` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_developer_app` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_install`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_install` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int unsigned NOT NULL DEFAULT '0' COMMENT '商店模板ID',
  `member_id` int unsigned NOT NULL DEFAULT '0' COMMENT '网站主用户ID',
  `slug` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板标识',
  `theme_name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板名称',
  `is_active` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否当前激活:0否/1是',
  `install_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '安装路径',
  `quality_on_install` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'install quality score',
  `config` json DEFAULT NULL COMMENT '配置数据JSON',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_slug` (`member_id`,`slug`),
  KEY `idx_member_active` (`member_id`,`is_active`),
  KEY `idx_store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板安装记录表(V2.9.12)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_install` WRITE;
/*!40000 ALTER TABLE `{prefix}template_install` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_install` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_install_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_install_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int unsigned NOT NULL DEFAULT '0' COMMENT '模板ID',
  `member_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `action` tinyint(1) NOT NULL DEFAULT '1' COMMENT '动作:1安装/2卸载/3切换/4基线迁移',
  `source` tinyint(1) NOT NULL DEFAULT '1' COMMENT '来源:1商店/2上传/3恢复',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作IP',
  `extra` json DEFAULT NULL COMMENT '额外信息JSON',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_template_action` (`template_id`,`action`),
  KEY `idx_member` (`member_id`),
  KEY `idx_action_time` (`action`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板安装日志表(V2.9.24)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_install_log` WRITE;
/*!40000 ALTER TABLE `{prefix}template_install_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_install_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_invoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_invoice` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联订单ID',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '申请用户ID',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '发票抬头',
  `tax_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '税号',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '开票金额',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '接收邮箱',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态:0待开1已开2拒绝',
  `invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '发票号码',
  `invoice_file` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '发票文件路径',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板发票申请表(V2.9.28 M-1)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_invoice` WRITE;
/*!40000 ALTER TABLE `{prefix}template_invoice` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_invoice` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_license`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_license` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `license_code` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `template_id` int unsigned NOT NULL DEFAULT '0',
  `order_id` int unsigned NOT NULL DEFAULT '0',
  `member_id` int unsigned NOT NULL DEFAULT '0',
  `license_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'permanent' COMMENT 'permanent/yearly/lifetime',
  `domains` text COLLATE utf8mb4_unicode_ci,
  `expires_at` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint unsigned NOT NULL DEFAULT '1',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_license_code` (`license_code`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板授权';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_license` WRITE;
/*!40000 ALTER TABLE `{prefix}template_license` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_license` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_order` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '订单编号',
  `store_id` int unsigned NOT NULL DEFAULT '0' COMMENT '商店模板ID',
  `member_id` int unsigned NOT NULL DEFAULT '0' COMMENT '购买者用户ID',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `original_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '原始金额',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '优惠金额',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实付金额',
  `coupon_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '优惠码',
  `promotion_id` int unsigned NOT NULL DEFAULT '0' COMMENT '促销ID',
  `license_id` int unsigned NOT NULL DEFAULT '0' COMMENT '授权ID',
  `pay_method` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '支付方式',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0待支付/1已支付/2已退款/3已关闭',
  `pay_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '支付方式',
  `pay_time` int unsigned NOT NULL DEFAULT '0' COMMENT '支付时间',
  `refund_time` int unsigned NOT NULL DEFAULT '0' COMMENT '退款时间',
  `refund_reason` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '退款原因',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_member_status` (`member_id`,`status`),
  KEY `idx_store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板订单表(V2.9.12)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_order` WRITE;
/*!40000 ALTER TABLE `{prefix}template_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_order` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_pack`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_pack` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '包名称',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '包描述',
  `cover` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '封面图',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '打包价格',
  `original_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '原价合计',
  `sort` int unsigned NOT NULL DEFAULT '99' COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板包组合表(V2.9.28 M-4)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_pack` WRITE;
/*!40000 ALTER TABLE `{prefix}template_pack` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_pack` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_pack_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_pack_item` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pack_id` int unsigned NOT NULL DEFAULT '0' COMMENT '包ID',
  `template_id` int unsigned NOT NULL DEFAULT '0' COMMENT '模板ID',
  `sort` int unsigned NOT NULL DEFAULT '99' COMMENT '排序',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pack_template` (`pack_id`,`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板包关联表(V2.9.28 M-4)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_pack_item` WRITE;
/*!40000 ALTER TABLE `{prefix}template_pack_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_pack_item` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_preset_color`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_preset_color` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配色名称',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配色描述',
  `colors` json NOT NULL COMMENT '配色JSON {primary, secondary, bg, text, heading, link, accent}',
  `industry_tags` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '行业标签(逗号分隔)',
  `is_system` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否系统预设(1=系统/0=自定义)',
  `member_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID，0表示系统预设(V2.9.24)',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_industry` (`industry_tags`),
  KEY `idx_sort` (`sort`),
  KEY `idx_member` (`member_id`,`is_system`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='预设配色方案表(V2.9.23 C-4)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_preset_color` WRITE;
/*!40000 ALTER TABLE `{prefix}template_preset_color` DISABLE KEYS */;
INSERT INTO `{prefix}template_preset_color` VALUES (1,'商务蓝','专业稳重的商务蓝色调','{\"bg\": \"#ffffff\", \"link\": \"#2563eb\", \"text\": \"#1f2937\", \"accent\": \"#f59e0b\", \"heading\": \"#0f172a\", \"primary\": \"#1e40af\", \"secondary\": \"#3b82f6\"}','科技,企业,金融',1,0,100,1781672031),(2,'温暖橙','充满活力的暖色系','{\"bg\": \"#fffbeb\", \"link\": \"#ea580c\", \"text\": \"#1c1917\", \"accent\": \"#dc2626\", \"heading\": \"#7c2d12\", \"primary\": \"#ea580c\", \"secondary\": \"#fb923c\"}','电商,餐饮,教育',1,0,90,1781672031),(3,'自然绿','清新自然的绿色系','{\"bg\": \"#f0fdf4\", \"link\": \"#16a34a\", \"text\": \"#14532d\", \"accent\": \"#84cc16\", \"heading\": \"#052e16\", \"primary\": \"#15803d\", \"secondary\": \"#22c55e\"}','农业,环保,健康',1,0,85,1781672031),(4,'典雅紫','神秘高贵的紫色调','{\"bg\": \"#faf5ff\", \"link\": \"#9333ea\", \"text\": \"#1e1b4b\", \"accent\": \"#ec4899\", \"heading\": \"#2e1065\", \"primary\": \"#7e22ce\", \"secondary\": \"#a855f7\"}','美妆,时尚,设计',1,0,80,1781672031),(5,'简约灰','极简现代的灰白调','{\"bg\": \"#ffffff\", \"link\": \"#4b5563\", \"text\": \"#111827\", \"accent\": \"#0ea5e9\", \"heading\": \"#030712\", \"primary\": \"#374151\", \"secondary\": \"#6b7280\"}','设计,艺术,建筑',1,0,75,1781672031),(6,'热情红','激情澎湃的红色调','{\"bg\": \"#fef2f2\", \"link\": \"#dc2626\", \"text\": \"#1f2937\", \"accent\": \"#f59e0b\", \"heading\": \"#7f1d1d\", \"primary\": \"#dc2626\", \"secondary\": \"#ef4444\"}','餐饮,娱乐,体育',1,0,70,1781672031),(7,'医疗青','专业可信的医疗色调','{\"bg\": \"#ecfeff\", \"link\": \"#0891b2\", \"text\": \"#164e63\", \"accent\": \"#14b8a6\", \"heading\": \"#083344\", \"primary\": \"#0e7490\", \"secondary\": \"#06b6d4\"}','医疗,健康,生物',1,0,65,1781672031),(8,'奢华金','尊贵典雅的奢华金调','{\"bg\": \"#fffbeb\", \"link\": \"#b45309\", \"text\": \"#1c1917\", \"accent\": \"#a16207\", \"heading\": \"#451a03\", \"primary\": \"#92400e\", \"secondary\": \"#d97706\"}','珠宝,奢侈品,金融',1,0,60,1781672031),(9,'商务蓝','专业稳重的商务蓝色调','{\"bg\": \"#ffffff\", \"link\": \"#2563eb\", \"text\": \"#1f2937\", \"accent\": \"#f59e0b\", \"heading\": \"#0f172a\", \"primary\": \"#1e40af\", \"secondary\": \"#3b82f6\"}','科技,企业,金融',1,0,100,1781672037),(10,'温暖橙','充满活力的暖色系','{\"bg\": \"#fffbeb\", \"link\": \"#ea580c\", \"text\": \"#1c1917\", \"accent\": \"#dc2626\", \"heading\": \"#7c2d12\", \"primary\": \"#ea580c\", \"secondary\": \"#fb923c\"}','电商,餐饮,教育',1,0,90,1781672037),(11,'自然绿','清新自然的绿色系','{\"bg\": \"#f0fdf4\", \"link\": \"#16a34a\", \"text\": \"#14532d\", \"accent\": \"#84cc16\", \"heading\": \"#052e16\", \"primary\": \"#15803d\", \"secondary\": \"#22c55e\"}','农业,环保,健康',1,0,85,1781672037),(12,'典雅紫','神秘高贵的紫色调','{\"bg\": \"#faf5ff\", \"link\": \"#9333ea\", \"text\": \"#1e1b4b\", \"accent\": \"#ec4899\", \"heading\": \"#2e1065\", \"primary\": \"#7e22ce\", \"secondary\": \"#a855f7\"}','美妆,时尚,设计',1,0,80,1781672037),(13,'简约灰','极简现代的灰白调','{\"bg\": \"#ffffff\", \"link\": \"#4b5563\", \"text\": \"#111827\", \"accent\": \"#0ea5e9\", \"heading\": \"#030712\", \"primary\": \"#374151\", \"secondary\": \"#6b7280\"}','设计,艺术,建筑',1,0,75,1781672037),(14,'热情红','激情澎湃的红色调','{\"bg\": \"#fef2f2\", \"link\": \"#dc2626\", \"text\": \"#1f2937\", \"accent\": \"#f59e0b\", \"heading\": \"#7f1d1d\", \"primary\": \"#dc2626\", \"secondary\": \"#ef4444\"}','餐饮,娱乐,体育',1,0,70,1781672037),(15,'医疗青','专业可信的医疗色调','{\"bg\": \"#ecfeff\", \"link\": \"#0891b2\", \"text\": \"#164e63\", \"accent\": \"#14b8a6\", \"heading\": \"#083344\", \"primary\": \"#0e7490\", \"secondary\": \"#06b6d4\"}','医疗,健康,生物',1,0,65,1781672037),(16,'奢华金','尊贵典雅的奢华金调','{\"bg\": \"#fffbeb\", \"link\": \"#b45309\", \"text\": \"#1c1917\", \"accent\": \"#a16207\", \"heading\": \"#451a03\", \"primary\": \"#92400e\", \"secondary\": \"#d97706\"}','珠宝,奢侈品,金融',1,0,60,1781672037),(17,'商务蓝','专业稳重的商务蓝色调','{\"bg\": \"#ffffff\", \"link\": \"#2563eb\", \"text\": \"#1f2937\", \"accent\": \"#f59e0b\", \"heading\": \"#0f172a\", \"primary\": \"#1e40af\", \"secondary\": \"#3b82f6\"}','科技,企业,金融',1,0,100,1781672072),(18,'温暖橙','充满活力的暖色系','{\"bg\": \"#fffbeb\", \"link\": \"#ea580c\", \"text\": \"#1c1917\", \"accent\": \"#dc2626\", \"heading\": \"#7c2d12\", \"primary\": \"#ea580c\", \"secondary\": \"#fb923c\"}','电商,餐饮,教育',1,0,90,1781672072),(19,'自然绿','清新自然的绿色系','{\"bg\": \"#f0fdf4\", \"link\": \"#16a34a\", \"text\": \"#14532d\", \"accent\": \"#84cc16\", \"heading\": \"#052e16\", \"primary\": \"#15803d\", \"secondary\": \"#22c55e\"}','农业,环保,健康',1,0,85,1781672072),(20,'典雅紫','神秘高贵的紫色调','{\"bg\": \"#faf5ff\", \"link\": \"#9333ea\", \"text\": \"#1e1b4b\", \"accent\": \"#ec4899\", \"heading\": \"#2e1065\", \"primary\": \"#7e22ce\", \"secondary\": \"#a855f7\"}','美妆,时尚,设计',1,0,80,1781672072),(21,'简约灰','极简现代的灰白调','{\"bg\": \"#ffffff\", \"link\": \"#4b5563\", \"text\": \"#111827\", \"accent\": \"#0ea5e9\", \"heading\": \"#030712\", \"primary\": \"#374151\", \"secondary\": \"#6b7280\"}','设计,艺术,建筑',1,0,75,1781672072),(22,'热情红','激情澎湃的红色调','{\"bg\": \"#fef2f2\", \"link\": \"#dc2626\", \"text\": \"#1f2937\", \"accent\": \"#f59e0b\", \"heading\": \"#7f1d1d\", \"primary\": \"#dc2626\", \"secondary\": \"#ef4444\"}','餐饮,娱乐,体育',1,0,70,1781672072),(23,'医疗青','专业可信的医疗色调','{\"bg\": \"#ecfeff\", \"link\": \"#0891b2\", \"text\": \"#164e63\", \"accent\": \"#14b8a6\", \"heading\": \"#083344\", \"primary\": \"#0e7490\", \"secondary\": \"#06b6d4\"}','医疗,健康,生物',1,0,65,1781672072),(24,'奢华金','尊贵典雅的奢华金调','{\"bg\": \"#fffbeb\", \"link\": \"#b45309\", \"text\": \"#1c1917\", \"accent\": \"#a16207\", \"heading\": \"#451a03\", \"primary\": \"#92400e\", \"secondary\": \"#d97706\"}','珠宝,奢侈品,金融',1,0,60,1781672072);
/*!40000 ALTER TABLE `{prefix}template_preset_color` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_price_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_price_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_id` int NOT NULL DEFAULT '0',
  `operator_id` int NOT NULL DEFAULT '0',
  `operator_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `action` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `old_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `new_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `reason` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板价格变更日志';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_price_log` WRITE;
/*!40000 ALTER TABLE `{prefix}template_price_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_price_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_pricing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_pricing` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int unsigned NOT NULL DEFAULT '0',
  `billing_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'one_time' COMMENT 'one_time/recurring/free/trial',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `original_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `recurring_period` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'monthly/yearly',
  `trial_days` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint unsigned NOT NULL DEFAULT '1',
  `sort` int unsigned NOT NULL DEFAULT '0',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_billing` (`template_id`,`billing_type`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板定价';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_pricing` WRITE;
/*!40000 ALTER TABLE `{prefix}template_pricing` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_pricing` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_promotion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_promotion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'discount',
  `discount_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `template_ids` text COLLATE utf8mb4_unicode_ci,
  `category_id` int NOT NULL DEFAULT '0',
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `sort` int NOT NULL DEFAULT '100',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板促销活动';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_promotion` WRITE;
/*!40000 ALTER TABLE `{prefix}template_promotion` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_promotion` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_promotion_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_promotion_activity` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `activity_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activity_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '类型(discount/full_reduction/coupon/bundle/new_user)',
  `discount_rate` decimal(3,2) DEFAULT '1.00',
  `condition_value` decimal(10,2) DEFAULT '0.00',
  `start_time` int unsigned NOT NULL,
  `end_time` int unsigned NOT NULL,
  `target_user_type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'all',
  `template_ids` json DEFAULT NULL,
  `status` tinyint DEFAULT '0' COMMENT '0未开始1进行中2已结束3已终止',
  `create_time` int unsigned DEFAULT '0',
  `update_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`activity_type`),
  KEY `idx_status` (`status`),
  KEY `idx_time` (`start_time`,`end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板促销活动表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_promotion_activity` WRITE;
/*!40000 ALTER TABLE `{prefix}template_promotion_activity` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_promotion_activity` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_quality_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_quality_tag` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_id` int NOT NULL DEFAULT '0',
  `tag_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tag_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'auto',
  `score` decimal(3,1) NOT NULL DEFAULT '0.0',
  `weight` int NOT NULL DEFAULT '100',
  `auditor_id` int NOT NULL DEFAULT '0',
  `auditor_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_tag_type` (`tag_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板质量标签';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_quality_tag` WRITE;
/*!40000 ALTER TABLE `{prefix}template_quality_tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_quality_tag` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_recommend`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_recommend` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `position` tinyint(1) NOT NULL DEFAULT '1' COMMENT '推荐位置:1首页顶部/2热门/3新品/4精选',
  `recommend_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '推荐类型:1手动指定/2自动热门/3自动最新',
  `template_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联模板ID(手动指定时)',
  `title` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '推荐位标题',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '推荐位描述',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序号',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `start_time` int unsigned NOT NULL DEFAULT '0' COMMENT '开始展示时间',
  `end_time` int unsigned NOT NULL DEFAULT '0' COMMENT '结束展示时间',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_position_status` (`position`,`status`,`sort`),
  KEY `idx_template` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板推荐位表(V2.9.24)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_recommend` WRITE;
/*!40000 ALTER TABLE `{prefix}template_recommend` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_recommend` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_recommend_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_recommend_item` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `position_id` int unsigned NOT NULL DEFAULT '0' COMMENT '推荐位ID',
  `template_id` int unsigned NOT NULL DEFAULT '0' COMMENT '模板ID',
  `sort` int unsigned NOT NULL DEFAULT '99' COMMENT '排序',
  `start_time` int unsigned NOT NULL DEFAULT '0' COMMENT '生效时间',
  `end_time` int unsigned NOT NULL DEFAULT '0' COMMENT '失效时间',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_position_template` (`position_id`,`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推荐位模板关联表(V2.9.28 M-6)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_recommend_item` WRITE;
/*!40000 ALTER TABLE `{prefix}template_recommend_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_recommend_item` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_recommend_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_recommend_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT '用户ID',
  `template_id` int NOT NULL COMMENT '推荐模板ID',
  `recommend_strategy` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '推荐策略',
  `recommend_scene` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '推荐场景',
  `impression` tinyint DEFAULT '0',
  `click` tinyint DEFAULT '0',
  `install` tinyint DEFAULT '0',
  `click_time` int unsigned DEFAULT '0',
  `install_time` int unsigned DEFAULT '0',
  `create_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_strategy` (`recommend_strategy`),
  KEY `idx_scene` (`recommend_scene`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板推荐日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_recommend_log` WRITE;
/*!40000 ALTER TABLE `{prefix}template_recommend_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_recommend_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_recommend_position`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_recommend_position` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '推荐位名称',
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标识(home_banner/home_featured/home_hot/guess_like)',
  `type` tinyint NOT NULL DEFAULT '1' COMMENT '类型:1人工2规则3AI',
  `max_count` int unsigned NOT NULL DEFAULT '10' COMMENT '最大展示数',
  `config` json DEFAULT NULL COMMENT '规则配置(JSON)',
  `sort` int unsigned NOT NULL DEFAULT '99' COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推荐位定义表(V2.9.28 M-6)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_recommend_position` WRITE;
/*!40000 ALTER TABLE `{prefix}template_recommend_position` DISABLE KEYS */;
INSERT INTO `{prefix}template_recommend_position` VALUES (1,'首页轮播','home_banner',1,5,'{\"desc\": \"首页顶部轮播展示\"}',1,1,1782110071,1782110071),(2,'精品推荐','home_featured',1,10,'{\"desc\": \"首页精品推荐区域\"}',2,1,1782110071,1782110071),(3,'热门排行','home_hot',2,10,'{\"desc\": true, \"rule\": \"order_by\", \"field\": \"install_count\"}',3,1,1782110071,1782110071),(4,'猜你喜欢','guess_like',3,10,'{\"desc\": \"基于用户行为的AI推荐(预留)\"}',4,1,1782110071,1782110071);
/*!40000 ALTER TABLE `{prefix}template_recommend_position` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_recommend_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_recommend_queue` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `template_id` int unsigned NOT NULL DEFAULT '0',
  `score` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `reason` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'hot/collaborative/category',
  `expire_time` int unsigned DEFAULT NULL,
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_template` (`user_id`,`template_id`),
  KEY `idx_user_score` (`user_id`,`score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板推荐队列表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_recommend_queue` WRITE;
/*!40000 ALTER TABLE `{prefix}template_recommend_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_recommend_queue` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_recommend_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_recommend_rule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '规则名称',
  `rule_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual' COMMENT '规则类型: manual=手动置顶, ai=AI推荐, category=分类热门, festival=节日特推, new_release=新品首发',
  `template_ids` text COLLATE utf8mb4_unicode_ci COMMENT '手动指定的模板ID列表(JSON数组)',
  `category_id` int NOT NULL DEFAULT '0' COMMENT '关联分类ID(分类热门时有效)',
  `priority` int NOT NULL DEFAULT '10' COMMENT '优先级(数字越大越靠前)',
  `ab_group` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A' COMMENT 'A/B测试分组: A/B/ALL',
  `conditions` text COLLATE utf8mb4_unicode_ci COMMENT '触发条件(JSON: 用户标签/时间段/设备等)',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态: 0=禁用 1=启用',
  `start_time` datetime DEFAULT NULL COMMENT '生效开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '生效结束时间',
  `sort` int NOT NULL DEFAULT '100' COMMENT '排序',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rule_type` (`rule_type`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category_id`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板推荐规则表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_recommend_rule` WRITE;
/*!40000 ALTER TABLE `{prefix}template_recommend_rule` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_recommend_rule` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_recommend_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_recommend_stats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_id` int NOT NULL DEFAULT '0' COMMENT '模板ID',
  `rule_id` int NOT NULL DEFAULT '0' COMMENT '触发的规则ID',
  `position` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '推荐位: home/sidebar/detail/search',
  `ab_group` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A' COMMENT 'A/B测试分组',
  `impression_count` int NOT NULL DEFAULT '0' COMMENT '曝光次数',
  `click_count` int NOT NULL DEFAULT '0' COMMENT '点击次数',
  `install_count` int NOT NULL DEFAULT '0' COMMENT '安装次数',
  `stat_date` date NOT NULL COMMENT '统计日期',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_rule_pos_date` (`template_id`,`rule_id`,`position`,`stat_date`),
  KEY `idx_template` (`template_id`),
  KEY `idx_stat_date` (`stat_date`),
  KEY `idx_position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板推荐效果统计表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_recommend_stats` WRITE;
/*!40000 ALTER TABLE `{prefix}template_recommend_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_recommend_stats` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_refund`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_refund` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联订单ID',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '申请用户ID',
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '退款原因',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '退款金额',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态:0待审1通过2拒绝',
  `admin_remark` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '管理员备注',
  `process_time` int unsigned NOT NULL DEFAULT '0' COMMENT '处理时间',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板退款记录表(V2.9.28 M-1)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_refund` WRITE;
/*!40000 ALTER TABLE `{prefix}template_refund` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_refund` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_reject_reason`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_reject_reason` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `sort` int NOT NULL DEFAULT '100',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板驳回理由模板';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_reject_reason` WRITE;
/*!40000 ALTER TABLE `{prefix}template_reject_reason` DISABLE KEYS */;
INSERT INTO `{prefix}template_reject_reason` VALUES (1,'模板设计不完整，缺少必要的页面文件','quality',10,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(2,'模板存在明显的兼容性问题','quality',20,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(3,'模板代码质量不达标，存在安全风险','quality',30,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(4,'模板截图与实际效果不符','quality',40,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(5,'模板涉及版权问题，请提供授权证明','copyright',50,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(6,'模板描述信息不完整','general',60,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(7,'模板分类选择不正确','general',70,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(8,'模板定价不合理','general',80,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(9,'模板存在重复内容','quality',90,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(10,'其他原因（请在审核意见中说明）','other',100,1,'2026-06-21 06:09:31','2026-06-21 06:09:31'),(11,'代码存在语法错误，无法通过编译','code',1,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(12,'代码不符合PHP编码规范(PSR-12)','code',2,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(13,'存在硬编码的数据库连接信息','code',3,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(14,'页面布局在移动端显示异常','design',1,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(15,'颜色对比度不符合无障碍标准','design',2,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(16,'页面加载速度过慢(>3秒)','design',3,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(17,'存在SQL注入风险','safety',1,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(18,'存在XSS跨站脚本风险','safety',2,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(19,'文件上传缺少安全校验','safety',3,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(20,'模板描述与实际功能不符','other',1,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(21,'缺少使用文档或README','other',2,1,'2026-06-22 14:34:31','2026-06-22 14:34:31'),(22,'与其他已上架模板重复度过高','other',3,1,'2026-06-22 14:34:31','2026-06-22 14:34:31');
/*!40000 ALTER TABLE `{prefix}template_reject_reason` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_review`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_review` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int unsigned NOT NULL DEFAULT '0' COMMENT '商店模板ID',
  `member_id` int unsigned NOT NULL DEFAULT '0' COMMENT '评论者用户ID',
  `rating` tinyint(1) NOT NULL DEFAULT '5' COMMENT '评分1-5',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '评论内容',
  `images` json DEFAULT NULL COMMENT '评论图片URL数组',
  `reply` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '管理员回复(V2.9.24)',
  `reply_time` int unsigned NOT NULL DEFAULT '0' COMMENT '回复时间(V2.9.24)',
  `is_audited` tinyint(1) NOT NULL DEFAULT '0' COMMENT '审核状态:0待审核/1通过/2拒绝',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '审核状态:0待审核/1通过/2拒绝(兼容字段)',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_store_member` (`store_id`,`member_id`),
  KEY `idx_store_audit` (`store_id`,`is_audited`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板评分评论表(V2.9.12)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_review` WRITE;
/*!40000 ALTER TABLE `{prefix}template_review` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_review` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_review_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_review_report` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `review_id` int unsigned NOT NULL DEFAULT '0' COMMENT '被举报评价ID',
  `reporter_id` int unsigned NOT NULL DEFAULT '0' COMMENT '举报人ID',
  `reason` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '举报原因',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态:0待处理1已通过(隐藏)2已驳回',
  `admin_remark` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '处理备注',
  `process_time` int unsigned NOT NULL DEFAULT '0',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_review` (`review_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板评价举报表(V2.9.28 M-2)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_review_report` WRITE;
/*!40000 ALTER TABLE `{prefix}template_review_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_review_report` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_section_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_section_config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `theme_slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板标识',
  `member_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `page_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'index' COMMENT '页面类型: index/detail/list',
  `sections` json NOT NULL COMMENT '区块配置JSON[{id,name,visible,sort}]',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_theme_page` (`theme_slug`,`member_id`,`page_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='前台区块配置表(V2.9.23 C-2)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_section_config` WRITE;
/*!40000 ALTER TABLE `{prefix}template_section_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_section_config` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_settlement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_settlement` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `batch_no` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '结算批次号',
  `period_start` date NOT NULL COMMENT '结算周期开始',
  `period_end` date NOT NULL COMMENT '结算周期结束',
  `total_orders` int unsigned NOT NULL DEFAULT '0' COMMENT '订单总数',
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '订单总金额',
  `commission_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '平台佣金',
  `settlement_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '应结金额',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态: 0待审核/1已审核/2已打款/3已关闭',
  `auditor` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '审核人',
  `audit_time` int unsigned NOT NULL DEFAULT '0' COMMENT '审核时间',
  `remark` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_batch_no` (`batch_no`),
  KEY `idx_period` (`period_start`,`period_end`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='结算报表表(V2.9.25 N-3)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_settlement` WRITE;
/*!40000 ALTER TABLE `{prefix}template_settlement` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_settlement` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_settlement_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_settlement_rule` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `developer_id` int unsigned NOT NULL DEFAULT '0' COMMENT '开发者用户ID',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT '30.00' COMMENT '平台抽成比例(%)',
  `min_withdraw` decimal(10,2) NOT NULL DEFAULT '100.00' COMMENT '最低提现金额',
  `settle_cycle` tinyint NOT NULL DEFAULT '1' COMMENT '结算周期:1月结2季结3年结',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_developer` (`developer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='结算规则表(V2.9.28 M-7)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_settlement_rule` WRITE;
/*!40000 ALTER TABLE `{prefix}template_settlement_rule` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_settlement_rule` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_store` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板唯一标识',
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板名称',
  `category_id` int unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '模板描述',
  `seo_title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `seo_keywords` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `screenshots` json DEFAULT NULL COMMENT '预览截图JSON数组',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '售价(0表示免费)',
  `billing_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'free' COMMENT '计费类型: free/one_time/subscription',
  `price_original` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '原价',
  `price_sale` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '促销价',
  `author_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '作者名称',
  `author_id` int unsigned NOT NULL DEFAULT '0' COMMENT '开发者用户ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0待审核/1上架/2下架/3拒绝',
  `review_status` tinyint DEFAULT '0' COMMENT 'å®¡æ ¸çŠ¶æ€:0è‰ç¨¿1å¾…åˆå®¡2å¾…ç»ˆå®¡3é€šè¿‡4é©³å›ž',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否推荐:0否/1是',
  `is_recommended` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否推荐(0否/1是)',
  `is_published` tinyint DEFAULT '0' COMMENT 'æ˜¯å¦å·²å‘å¸ƒ',
  `banner_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商店首页轮播Banner图',
  `quality_score` int NOT NULL DEFAULT '0' COMMENT 'AI质量评分(0-100)',
  `last_quality_check` int unsigned DEFAULT '0' COMMENT '最近质量检查时间',
  `recommend_weight` int DEFAULT '0' COMMENT '推荐权重(0-100)',
  `developer_id` int DEFAULT '0' COMMENT '开发者用户ID',
  `upload_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft' COMMENT '上传状态(draft/pending_audit/approved/rejected)',
  `reject_reason` text COLLATE utf8mb4_unicode_ci COMMENT '驳回原因',
  `pack_validation_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT '打包校验状态',
  `validation_report` json DEFAULT NULL COMMENT '校验报告',
  `install_count` int unsigned NOT NULL DEFAULT '0' COMMENT '安装次数',
  `install_count_7d` int unsigned NOT NULL DEFAULT '0' COMMENT '近7天安装数(B-5排行用)',
  `view_count` int unsigned DEFAULT '0' COMMENT 'æµè§ˆæ¬¡æ•°',
  `rating_avg` decimal(2,1) NOT NULL DEFAULT '5.0' COMMENT '平均评分(1-5)',
  `rating_count` int unsigned NOT NULL DEFAULT '0' COMMENT '评分人数',
  `version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1.0.0' COMMENT '版本号',
  `requirements` json DEFAULT NULL COMMENT '环境要求JSON',
  `file_size` int unsigned NOT NULL DEFAULT '0' COMMENT '文件大小(字节)',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  `support_models` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '[]' COMMENT '支持的模型类型(JSON数组)',
  `quality_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT '质量状态(pending/passed/failed/repairing)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_category_status` (`category_id`,`status`),
  KEY `idx_featured` (`is_featured`,`status`),
  KEY `idx_author` (`author_id`),
  KEY `idx_rating` (`rating_avg`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板商店表(V2.9.12)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_store` WRITE;
/*!40000 ALTER TABLE `{prefix}template_store` DISABLE KEYS */;
INSERT INTO `{prefix}template_store` VALUES (1,'default-official','官方默认模板',1,'八界AI-CMS官方默认模板，简洁大方，适用于各类企业官网。响应式设计，支持PC和移动端。','','','','[]',0.00,'free',0.00,0.00,'八界AI官方',0,1,0,1,0,0,'',92,0,0,0,'draft',NULL,'pending',NULL,128,0,0,4.8,56,'2.9.12','{\"cms\": \">=2.9.0\", \"php\": \">=8.0\"}',2048000,1780201507,1780855474,'[\"article\",\"image\",\"download\",\"product\",\"video\"]','pending'),(2,'corporate-pro','企业商务Pro',1,'专为企业打造的商务风格模板，蓝色主色调，专业可信。包含首页、关于、服务、案例、联系等页面。','','','','[]',99.00,'free',0.00,0.00,'八界AI官方',0,1,0,1,0,0,'',95,0,0,0,'draft',NULL,'pending',NULL,86,0,0,4.9,42,'2.9.12','{\"cms\": \">=2.9.0\", \"php\": \">=8.0\"}',3584000,1780201507,1780855469,'[\"article\",\"image\",\"download\",\"product\",\"video\"]','pending'),(3,'blog-minimal','极简博客',3,'文艺清新的博客模板，注重阅读体验，排版优雅。适合个人博客、技术博客、知识分享类网站。','','','','[]',0.00,'free',0.00,0.00,'八界AI官方',0,1,0,0,0,0,'',88,0,0,0,'draft',NULL,'pending',NULL,215,0,0,4.6,103,'2.9.12','{\"cms\": \">=2.9.0\", \"php\": \">=8.0\"}',1536000,1780201507,1780855434,'[\"article\",\"image\",\"download\",\"product\",\"video\"]','pending');
/*!40000 ALTER TABLE `{prefix}template_store` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_store_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_store_category` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int NOT NULL DEFAULT '0' COMMENT '父分类ID(0=顶级)',
  `level` tinyint NOT NULL DEFAULT '1' COMMENT '分类层级(1=顶级)',
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `slug` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类标识',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '分类描述',
  `meta_title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `meta_description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `meta_keywords` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `icon` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '图标类名',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序号',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用:0否/1是',
  `is_visible` tinyint(1) NOT NULL DEFAULT '1' COMMENT '前台是否可见:0隐藏/1显示(V2.9.24)',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_enabled_sort` (`is_enabled`,`sort`),
  KEY `idx_visible_sort` (`is_enabled`,`is_visible`,`sort`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_level` (`level`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板商店分类表(V2.9.12)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_store_category` WRITE;
/*!40000 ALTER TABLE `{prefix}template_store_category` DISABLE KEYS */;
INSERT INTO `{prefix}template_store_category` VALUES (1,0,1,'企业商务','corporate','企业官网、商务展示类模板','','','','bi bi-briefcase',1,1,1,0,0),(2,0,1,'电商促销','ecommerce','在线商城、促销活动类模板','','','','bi bi-cart',2,1,1,0,0),(3,0,1,'博客文艺','blog','个人博客、文学创作类模板','','','','bi bi-journal-text',3,1,1,0,0),(4,0,1,'门户资讯','portal','新闻门户、资讯聚合类模板','','','','bi bi-newspaper',4,1,1,0,0),(5,0,1,'医疗健康','medical','医院诊所、健康管理类模板','','','','bi bi-heart-pulse',5,1,1,0,0),(6,0,1,'教育培训','education','学校机构、在线教育类模板','','','','bi bi-mortarboard',6,1,1,0,0),(7,0,1,'餐饮美食','catering','餐厅酒店、美食推荐类模板','','','','bi bi-cup-hot',7,1,1,0,0),(8,0,1,'金融理财','finance','银行保险、投资理财类模板','','','','bi bi-bank',8,1,1,0,0),(9,0,1,'科技互联网','technology','科技公司、SaaS产品类模板','','','','bi bi-cpu',9,1,1,0,0),(10,0,1,'房产家居','realestate','房产中介、家居装修类模板','','','','bi bi-house-door',10,1,1,0,0);
/*!40000 ALTER TABLE `{prefix}template_store_category` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_tag` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '标签名称',
  `type` varchar(50) NOT NULL COMMENT '标签类型(industry/style/function/custom)',
  `color` varchar(20) DEFAULT '#1890ff' COMMENT '标签颜色',
  `sort` int DEFAULT '99' COMMENT '排序',
  `status` tinyint DEFAULT '1' COMMENT '状态:1启用0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='模板标签表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_tag` WRITE;
/*!40000 ALTER TABLE `{prefix}template_tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_tag` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_tag_relation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_tag_relation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_id` int NOT NULL COMMENT '模板ID',
  `tag_id` int NOT NULL COMMENT '标签ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_tag` (`template_id`,`tag_id`),
  KEY `idx_tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='模板标签关系表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_tag_relation` WRITE;
/*!40000 ALTER TABLE `{prefix}template_tag_relation` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_tag_relation` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_usage_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_usage_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int unsigned NOT NULL DEFAULT '0' COMMENT '模板ID',
  `member_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `event_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '事件类型: view/preview/install/activate/custom',
  `device` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pc' COMMENT '设备: pc/mobile/tablet',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'UA',
  `referer` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '来源页',
  `extra` json DEFAULT NULL COMMENT '额外信息JSON',
  `create_date` date NOT NULL COMMENT '日期（用于汇总）',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_template_date` (`template_id`,`create_date`),
  KEY `idx_event_date` (`event_type`,`create_date`),
  KEY `idx_member` (`member_id`),
  KEY `idx_date` (`create_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板使用日志表(V2.9.25 N-2)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_usage_log` WRITE;
/*!40000 ALTER TABLE `{prefix}template_usage_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_usage_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_user_action`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_user_action` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `template_id` int unsigned NOT NULL DEFAULT '0',
  `action` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'view/download/buy/favorite',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_action` (`action`,`create_time`),
  KEY `idx_user_action` (`user_id`,`action`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板用户行为表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_user_action` WRITE;
/*!40000 ALTER TABLE `{prefix}template_user_action` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_user_action` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_user_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_user_profile` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `stat_date` date NOT NULL COMMENT '统计日期',
  `dimension` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '维度(region/hobby/hour)',
  `dimension_value` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '维度值',
  `user_count` int unsigned NOT NULL DEFAULT '0' COMMENT '用户数',
  `download_count` int unsigned NOT NULL DEFAULT '0' COMMENT '下载数',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_date_dimension` (`stat_date`,`dimension`,`dimension_value`),
  KEY `idx_date` (`stat_date`),
  KEY `idx_dimension` (`dimension`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板用户画像聚合表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_user_profile` WRITE;
/*!40000 ALTER TABLE `{prefix}template_user_profile` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_user_profile` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_version_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_version_record` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_id` int NOT NULL DEFAULT '0',
  `version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `changelog` text COLLATE utf8mb4_unicode_ci,
  `file_snapshot` longtext COLLATE utf8mb4_unicode_ci,
  `file_diff` longtext COLLATE utf8mb4_unicode_ci,
  `grayscale_percent` tinyint NOT NULL DEFAULT '100',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `operator_id` int NOT NULL DEFAULT '0',
  `operator_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_version` (`version`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模板版本记录';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_version_record` WRITE;
/*!40000 ALTER TABLE `{prefix}template_version_record` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_version_record` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}template_withdraw`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}template_withdraw` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `developer_id` int unsigned NOT NULL DEFAULT '0' COMMENT '开发者ID',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '提现金额',
  `fee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '手续费',
  `actual_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际到账金额',
  `account_info` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '收款账户信息(JSON)',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态:0待审1打款中2已完成3已驳回',
  `admin_remark` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '管理员备注',
  `process_time` int unsigned NOT NULL DEFAULT '0' COMMENT '处理时间',
  `confirm_time` int unsigned NOT NULL DEFAULT '0' COMMENT '到账确认时间',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_developer` (`developer_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='提现申请表(V2.9.28 M-7)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}template_withdraw` WRITE;
/*!40000 ALTER TABLE `{prefix}template_withdraw` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}template_withdraw` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}theme_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}theme_analytics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `theme_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `event_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `event_data` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_theme_event` (`theme_id`,`event_type`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}theme_analytics` WRITE;
/*!40000 ALTER TABLE `{prefix}theme_analytics` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}theme_analytics` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}theme_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}theme_config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `theme` varchar(50) NOT NULL DEFAULT 'default' COMMENT '主题标识',
  `scope` enum('global','page','component') NOT NULL DEFAULT 'global' COMMENT '配置范围',
  `scope_id` int unsigned NOT NULL DEFAULT '0' COMMENT '范围ID',
  `config_key` varchar(100) NOT NULL COMMENT '配置键名',
  `config_value` text COMMENT '配置值',
  `config_type` enum('color','text','number','image','select','boolean','json') NOT NULL DEFAULT 'text' COMMENT '值类型',
  `label` varchar(100) DEFAULT '' COMMENT '显示标签',
  `description` varchar(255) DEFAULT '' COMMENT '配置说明',
  `sort` int NOT NULL DEFAULT '0',
  `create_time` int unsigned DEFAULT '0',
  `update_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_theme_scope_key` (`theme`,`scope`,`scope_id`,`config_key`),
  KEY `idx_theme_scope` (`theme`,`scope`,`scope_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='前台主题配置表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}theme_config` WRITE;
/*!40000 ALTER TABLE `{prefix}theme_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}theme_config` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}theme_customization`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}theme_customization` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `theme_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `variant_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `custom_data` json NOT NULL,
  `is_active` tinyint unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_theme_variant` (`theme_id`,`variant_name`),
  KEY `idx_theme_active` (`theme_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}theme_customization` WRITE;
/*!40000 ALTER TABLE `{prefix}theme_customization` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}theme_customization` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}theme_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}theme_info` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '主题标识',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'frontend' COMMENT '类型: frontend/admin',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '主题名称',
  `version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '1.0.0' COMMENT '版本号',
  `author` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '作者',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '描述',
  `thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '缩略图',
  `is_installed` tinyint DEFAULT '1' COMMENT '是否已安装',
  `installed_version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '1.0.0' COMMENT '已安装版本',
  `update_available` tinyint DEFAULT '0' COMMENT '有可用更新',
  `industry` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'è¡Œä¸šç±»åž‹(S15)',
  `style_tag` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'é£Žæ ¼æ ‡ç­¾(S15)',
  `is_market` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'æ˜¯å¦ä¸Šæž¶å¸‚åœº(0=å¦,1=æ˜¯,S15)',
  `market_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'å¸‚åœºè¿œç¨‹URL(S15)',
  `avg_rating` decimal(2,1) unsigned NOT NULL DEFAULT '0.0' COMMENT 'å¹³å‡è¯„åˆ†(1-5æ˜Ÿ,S15)',
  `install_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'å®‰è£…æ¬¡æ•°(S15)',
  `screenshots` json DEFAULT NULL COMMENT 'æˆªå›¾URLæ•°ç»„(S15)',
  `store_id` int unsigned NOT NULL DEFAULT '0' COMMENT '来源商店模板ID，0表示非商店模板(V2.9.12)',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code_type` (`code`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='主题信息表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}theme_info` WRITE;
/*!40000 ALTER TABLE `{prefix}theme_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}theme_info` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}theme_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}theme_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `theme_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'ä¸»é¢˜ID',
  `action` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'æ“ä½œç±»åž‹(install/rollback/update/rate)',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'æ“ä½œç”¨æˆ·ID',
  `detail` json DEFAULT NULL COMMENT 'æ“ä½œè¯¦æƒ…',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT 'åˆ›å»ºæ—¶é—´',
  PRIMARY KEY (`id`),
  KEY `idx_theme_id` (`theme_id`),
  KEY `idx_action` (`action`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ä¸»é¢˜æ“ä½œæ—¥å¿—è¡¨(S14+S16)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}theme_log` WRITE;
/*!40000 ALTER TABLE `{prefix}theme_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}theme_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}theme_pending`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}theme_pending` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ä¸»é¢˜æ ‡è¯†',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ä¸»é¢˜åç§°',
  `version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '1.0.0' COMMENT 'ç‰ˆæœ¬å·',
  `developer_id` int DEFAULT '0' COMMENT 'å¼€å‘è€…ç”¨æˆ·ID',
  `developer_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'å¼€å‘è€…åç§°',
  `developer_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'å¼€å‘è€…é‚®ç®±',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'æè¿°',
  `industry` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'è¡Œä¸š',
  `tags` json DEFAULT NULL COMMENT 'æ ‡ç­¾',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'çŠ¶æ€: pending/approved/rejected',
  `schema_score` int DEFAULT '0' COMMENT 'Schemaè§„èŒƒè¯„åˆ†',
  `quality_score` decimal(4,1) DEFAULT '0.0' COMMENT 'CSSè´¨é‡è¯„åˆ†',
  `xss_high` tinyint DEFAULT '0' COMMENT 'æ˜¯å¦æœ‰é«˜å±XSS',
  `file_size` int DEFAULT '0' COMMENT 'ZIPæ–‡ä»¶å¤§å°(å­—èŠ‚)',
  `package_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'å¸‚åœºåŒ…è·¯å¾„',
  `is_auto_passed` tinyint DEFAULT '0' COMMENT 'æ˜¯å¦è‡ªåŠ¨é€šè¿‡',
  `audit_reason` text COLLATE utf8mb4_unicode_ci COMMENT 'å®¡æ ¸ç†ç”±',
  `admin_id` int DEFAULT '0' COMMENT 'å®¡æ ¸ç®¡ç†å‘˜ID',
  `audit_time` int unsigned DEFAULT '0' COMMENT 'å®¡æ ¸æ—¶é—´',
  `theme_json` json DEFAULT NULL COMMENT 'theme.jsonå®Œæ•´å†…å®¹',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_developer` (`developer_id`),
  KEY `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ç¬¬ä¸‰æ–¹ä¸»é¢˜å¾…å®¡æ ¸è¡¨';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}theme_pending` WRITE;
/*!40000 ALTER TABLE `{prefix}theme_pending` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}theme_pending` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}theme_rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}theme_rate` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'ç”¨æˆ·ID',
  `theme_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'ä¸»é¢˜ID',
  `rating` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'è¯„åˆ†(1-5æ˜Ÿ)',
  `is_favorite` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'æ˜¯å¦æ”¶è—(0=å¦,1=æ˜¯)',
  `comment` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'è¯„ä»·å†…å®¹',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT 'åˆ›å»ºæ—¶é—´',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT 'æ›´æ–°æ—¶é—´',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_theme` (`user_id`,`theme_id`),
  KEY `idx_theme_id` (`theme_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ä¸»é¢˜è¯„åˆ†æ”¶è—è¡¨(S16)';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}theme_rate` WRITE;
/*!40000 ALTER TABLE `{prefix}theme_rate` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}theme_rate` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}translation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}translation` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `lang_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '语言代码',
  `group` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'common' COMMENT '分组: common/admin/frontend',
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '原文/翻译键',
  `translation` text COLLATE utf8mb4_unicode_ci COMMENT '译文',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lang_key` (`lang_code`,`group`,`key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='翻译表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}translation` WRITE;
/*!40000 ALTER TABLE `{prefix}translation` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}translation` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}translation_memory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}translation_memory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `source_text` text NOT NULL COMMENT '源文本',
  `target_text` text NOT NULL COMMENT '目标文本',
  `source_lang` varchar(10) NOT NULL COMMENT '源语言',
  `target_lang` varchar(10) NOT NULL COMMENT '目标语言',
  `context_type` varchar(50) DEFAULT '' COMMENT '上下文类型(content/field/lang_pack/template)',
  `context_id` int DEFAULT '0' COMMENT '上下文ID',
  `quality_score` decimal(3,1) DEFAULT '0.0' COMMENT '翻译质量评分(0-5)',
  `use_count` int DEFAULT '1' COMMENT '使用次数',
  `is_confirmed` tinyint DEFAULT '0' COMMENT '是否人工确认:1是0否',
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
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}translation_memory` WRITE;
/*!40000 ALTER TABLE `{prefix}translation_memory` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}translation_memory` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `email_verified` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '邮箱是否已验证',
  `register_ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '注册IP',
  `register_source` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '注册来源: username|email',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '头像',
  `bio` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '个人简介',
  `notify_settings` text COLLATE utf8mb4_unicode_ci COMMENT '通知偏好设置 JSON',
  `lang_pref` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '偏好语言代码',
  `role_id` tinyint NOT NULL DEFAULT '3' COMMENT '角色:1超管/2管理员/3编辑',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态:0禁用/1启用',
  `last_login_time` int unsigned NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '最后登录IP',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}user` WRITE;
/*!40000 ALTER TABLE `{prefix}user` DISABLE KEYS */;
INSERT INTO `{prefix}user` VALUES (1,'admin','admin@aicms.com',0,'','','$2y$12$BoP4lCWrvqlrujRh.WL6mucqyNiXNy777ksfxV6MOCC6sHxenOGZW','超级管理员','','',NULL,'',1,1,1783914985,'172.18.0.1',1776933035,1783914985);
/*!40000 ALTER TABLE `{prefix}user` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}user_chapter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}user_chapter` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int unsigned NOT NULL,
  `content_id` int unsigned NOT NULL COMMENT '章节content_id',
  `parent_id` int unsigned DEFAULT '0' COMMENT '父内容id',
  `order_sn` varchar(50) DEFAULT '' COMMENT '订单号',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '购买价格',
  `create_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_chapter` (`member_id`,`content_id`),
  KEY `idx_content` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户已购章节';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}user_chapter` WRITE;
/*!40000 ALTER TABLE `{prefix}user_chapter` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}user_chapter` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}user_coupon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}user_coupon` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int unsigned NOT NULL,
  `template_id` int unsigned NOT NULL,
  `code` varchar(32) NOT NULL COMMENT '优惠券码',
  `coupon_type` enum('reduce','discount','free_shipping') NOT NULL COMMENT '冗余:券类型',
  `condition_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '冗余:门槛金额',
  `reduce_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '冗余:减免金额/折扣率',
  `status` tinyint DEFAULT '0' COMMENT '0未使用/1已使用/2已过期/3已作废/4已退还',
  `used_at` int unsigned DEFAULT '0' COMMENT '使用时间',
  `used_order_id` int unsigned DEFAULT '0' COMMENT '使用的订单ID',
  `expire_at` int unsigned DEFAULT '0' COMMENT '过期时间',
  `create_time` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_member_status` (`member_id`,`status`),
  KEY `idx_expire` (`expire_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='用户优惠券表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}user_coupon` WRITE;
/*!40000 ALTER TABLE `{prefix}user_coupon` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}user_coupon` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}visit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}visit_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `content_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '内容ID(0为首页/列表)',
  `visitor_id` int unsigned DEFAULT '0' COMMENT '会员id,0=游客',
  `session_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'IP地址',
  `ua` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'User-Agent',
  `visit_time` int unsigned NOT NULL DEFAULT '0' COMMENT '访问时间',
  `page_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '访问页面',
  `source_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'direct' COMMENT '来源类型: direct/search/social/referral/other',
  `referrer` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '来源URL',
  `event_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'visit' COMMENT 'äº‹ä»¶ç±»åž‹: visit/share/click',
  `share_channel` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'åˆ†äº«æ¸ é“: wechat/weibo/qq/copy',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`),
  KEY `idx_visit_time` (`visit_time`),
  KEY `idx_visitor_time` (`visitor_id`,`visit_time`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_session_id` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1020 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='访问日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}visit_log` WRITE;
/*!40000 ALTER TABLE `{prefix}visit_log` DISABLE KEYS */;
INSERT INTO `{prefix}visit_log` VALUES (1,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778167013,'http://localhost:3000/member/login','direct','','visit',''),(2,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778167110,'http://localhost:3000/member/profile','direct','','visit',''),(3,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778167113,'http://localhost:3000/points','direct','','visit',''),(4,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778167131,'http://localhost:3000/member/profile','direct','','visit',''),(5,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778167135,'http://localhost:3000/member/points','direct','','visit',''),(6,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778168769,'http://localhost:3000/member/login','direct','','visit',''),(7,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778168785,'http://localhost:3000/member/profile','direct','','visit',''),(8,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778168789,'http://localhost:3000/points','direct','','visit',''),(9,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778168979,'http://localhost:3000/signin','direct','','visit',''),(10,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169043,'http://localhost:3000/signin','direct','','visit',''),(11,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169047,'http://localhost:3000/points','direct','','visit',''),(12,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169049,'http://localhost:3000/signin','direct','','visit',''),(13,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169053,'http://localhost:3000/member/points','direct','','visit',''),(14,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169055,'http://localhost:3000/signin','direct','','visit',''),(15,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169111,'http://localhost:3000/member/login','direct','','visit',''),(16,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169112,'http://localhost:3000/member/login','direct','','visit',''),(17,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169121,'http://localhost:3000/member/profile','direct','','visit',''),(18,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169125,'http://localhost:3000/points','direct','','visit',''),(19,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169128,'http://localhost:3000/signin','direct','','visit',''),(20,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169234,'http://localhost:3000/member/login','direct','','visit',''),(21,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169236,'http://localhost:3000/member/login','direct','','visit',''),(22,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169244,'http://localhost:3000/member/profile','direct','','visit',''),(23,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169247,'http://localhost:3000/points','direct','','visit',''),(24,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169250,'http://localhost:3000/signin','direct','','visit',''),(25,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169258,'http://localhost:3000/signin','direct','','visit',''),(26,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169354,'http://localhost:3000/member/login','direct','','visit',''),(27,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169372,'http://localhost:3000/member/profile','direct','','visit',''),(28,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169375,'http://localhost:3000/points','direct','','visit',''),(29,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169377,'http://localhost:3000/signin','direct','','visit',''),(30,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169522,'http://localhost:3000/signin','direct','','visit',''),(31,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169562,'http://localhost:3000/member/login','direct','','visit',''),(32,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169569,'http://localhost:3000/member/profile','direct','','visit',''),(33,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169573,'http://localhost:3000/points','direct','','visit',''),(34,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169575,'http://localhost:3000/signin','direct','','visit',''),(35,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169580,'http://localhost:3000/signin','direct','','visit',''),(36,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169581,'http://localhost:3000/signin','direct','','visit',''),(37,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169601,'http://localhost:3000/case','direct','','visit',''),(38,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169609,'http://localhost:3000/news','direct','','visit',''),(39,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169620,'http://localhost:3000/news?cate_id=3','direct','','visit',''),(40,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169777,'http://localhost:3000/points','direct','','visit',''),(41,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778169781,'http://localhost:3000/signin','direct','','visit',''),(42,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778170485,'http://localhost:3000/signin','direct','','visit',''),(43,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778170493,'http://localhost:3000/points','direct','','visit',''),(44,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778171457,'http://localhost:3000/member/login','direct','','visit',''),(45,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778202945,'http://localhost:3000/member/login','direct','','visit',''),(46,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778208413,'http://localhost:3000/member/login','direct','','visit',''),(47,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778211648,'http://localhost:3000/member/login','direct','','visit',''),(48,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778212716,'http://localhost:3000/member/login','direct','','visit',''),(49,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778212718,'http://localhost:3000/member/login','direct','','visit',''),(50,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778218340,'http://localhost:3000/member/login','direct','','visit',''),(51,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778218341,'http://localhost:3000/product','direct','','visit',''),(52,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778218343,'http://localhost:3000/','direct','','visit',''),(53,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778218930,'http://localhost:3000/','direct','','visit',''),(54,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778228771,'http://localhost:3000/','direct','','visit',''),(55,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778235427,'http://localhost:3000/','direct','','visit',''),(56,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778235427,'http://localhost:3000/','direct','','visit',''),(57,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778237981,'http://localhost:3000/','direct','','visit',''),(58,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778244628,'http://localhost:3000/','direct','','visit',''),(59,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778244816,'http://localhost:3000/','direct','','visit',''),(60,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778244849,'http://localhost:3000/case','direct','','visit',''),(61,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778277418,'http://localhost:3000/case','direct','','visit',''),(62,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279178,'http://localhost:3000/case','direct','','visit',''),(63,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279184,'http://localhost:3000/points','direct','','visit',''),(64,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279187,'http://localhost:3000/member/login','direct','','visit',''),(65,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279197,'http://localhost:3000/member/profile','direct','','visit',''),(66,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279200,'http://localhost:3000/points','direct','','visit',''),(67,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279204,'http://localhost:3000/signin','direct','','visit',''),(68,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279217,'http://localhost:3000/member/points','direct','','visit',''),(69,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279219,'http://localhost:3000/signin','direct','','visit',''),(70,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279229,'http://localhost:3000/download','direct','','visit',''),(71,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778279240,'http://localhost:3000/job','direct','','visit',''),(72,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778280779,'http://localhost:3000/case','direct','','visit',''),(73,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778280780,'http://localhost:3000/news','direct','','visit',''),(74,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778280782,'http://localhost:3000/','direct','','visit',''),(75,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778293952,'http://localhost:3000/','direct','','visit',''),(76,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778328585,'http://localhost:3000/','direct','','visit',''),(77,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778332579,'http://localhost:3000/','direct','','visit',''),(78,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778332582,'http://localhost:3000/case','direct','','visit',''),(79,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778332586,'http://localhost:3000/case','direct','','visit',''),(80,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778332587,'http://localhost:3000/download','direct','','visit',''),(81,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778332590,'http://localhost:3000/job','direct','','visit',''),(82,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778332592,'http://localhost:3000/member/login','direct','','visit',''),(83,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778332593,'http://localhost:3000/member/register','direct','','visit',''),(84,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778335388,'http://localhost:3000/member/register','direct','','visit',''),(85,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778335389,'http://localhost:3000/points','direct','','visit',''),(86,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778335391,'http://localhost:3000/member/login','direct','','visit',''),(87,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778335402,'http://localhost:3000/member/profile','direct','','visit',''),(88,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778335406,'http://localhost:3000/points','direct','','visit',''),(89,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778335408,'http://localhost:3000/signin','direct','','visit',''),(90,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778335583,'http://localhost:3000/member/points','direct','','visit',''),(91,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778335587,'http://localhost:3000/signin','direct','','visit',''),(92,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778335594,'http://localhost:3000/signin','direct','','visit',''),(93,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778338642,'http://localhost:3000/','direct','','visit',''),(94,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778338645,'http://localhost:3000/','direct','','visit',''),(95,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778339468,'http://localhost:3000/','direct','','visit',''),(96,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778339487,'http://localhost:3000/news','direct','','visit',''),(97,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778339492,'http://localhost:3000/points','direct','','visit',''),(98,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778339495,'http://localhost:3000/member/login','direct','','visit',''),(99,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778339503,'http://localhost:3000/member/profile','direct','','visit',''),(100,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778339507,'http://localhost:3000/points','direct','','visit',''),(101,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778339508,'http://localhost:3000/signin','direct','','visit',''),(102,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778339517,'http://localhost:3000/signin','direct','','visit',''),(103,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340047,'http://localhost:3000/member/login','direct','','visit',''),(104,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340056,'http://localhost:3000/member/profile','direct','','visit',''),(105,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340058,'http://localhost:3000/points','direct','','visit',''),(106,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340060,'http://localhost:3000/signin','direct','','visit',''),(107,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340067,'http://localhost:3000/signin','direct','','visit',''),(108,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340157,'http://localhost:3000/news','direct','','visit',''),(109,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340302,'http://localhost:3000/signin','direct','','visit',''),(110,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340308,'http://localhost:3000/signin','direct','','visit',''),(111,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340312,'http://localhost:3000/member/points','direct','','visit',''),(112,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340317,'http://localhost:3000/signin','direct','','visit',''),(113,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778340322,'http://localhost:3000/points','direct','','visit',''),(114,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778348300,'http://localhost:3000/points','direct','','visit',''),(115,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778378968,'http://localhost:3000/product/1','direct','','visit',''),(116,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778379109,'http://localhost:3000/product/1','direct','','visit',''),(117,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778379247,'http://localhost:3000/product/1','direct','','visit',''),(118,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778379359,'http://localhost:3000/product/1','direct','','visit',''),(119,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778379376,'http://localhost:3000/product/1','direct','','visit',''),(120,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778379405,'http://localhost:3000/product/1','direct','','visit',''),(121,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778380301,'http://localhost:3000/points','direct','','visit',''),(122,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778380302,'http://localhost:3000/member/login','direct','','visit',''),(123,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778380339,'http://localhost:3000/','direct','','visit',''),(124,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778380482,'http://localhost:3000/','direct','','visit',''),(125,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778385624,'http://localhost:3000/','direct','','visit',''),(126,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778385631,'http://localhost:3000/','direct','','visit',''),(127,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778386175,'http://localhost:3000/product/1','direct','','visit',''),(128,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778387307,'http://localhost:3000/','direct','','visit',''),(129,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778387309,'http://localhost:3000/','direct','','visit',''),(130,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389278,'http://localhost:3000/product/1','direct','','visit',''),(131,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389394,'http://localhost:3000/','direct','','visit',''),(132,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389397,'http://localhost:3000/','direct','','visit',''),(133,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389398,'http://localhost:3000/','direct','','visit',''),(134,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389403,'http://localhost:3000/','direct','','visit',''),(135,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389420,'http://localhost:3000/product/1','direct','','visit',''),(136,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389424,'http://localhost:3000/','direct','','visit',''),(137,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389669,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(138,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389675,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(139,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389677,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(140,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389678,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(141,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389680,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(142,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389681,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(143,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389682,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(144,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389683,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(145,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389685,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(146,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389691,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news','visit',''),(147,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778389712,'http://localhost:3000/product','referral','http://localhost:3000/news?cate_id=3','visit',''),(148,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390028,'http://localhost:3000/product','referral','http://localhost:3000/news?cate_id=3','visit',''),(149,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390035,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(150,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390040,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(151,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390042,'http://localhost:3000/case','referral','http://localhost:3000/product?cate_id=1','visit',''),(152,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390043,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(153,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390044,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(154,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390045,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(155,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390046,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(156,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390048,'http://localhost:3000/case?cate_id=2','referral','http://localhost:3000/case','visit',''),(157,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390051,'http://localhost:3000/case?cate_id=2','referral','http://localhost:3000/case','visit',''),(158,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390052,'http://localhost:3000/case?cate_id=2','referral','http://localhost:3000/case','visit',''),(159,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390053,'http://localhost:3000/product','referral','http://localhost:3000/case?cate_id=2','visit',''),(160,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390055,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(161,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390059,'http://localhost:3000/product','referral','http://localhost:3000/product?cate_id=1','visit',''),(162,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390060,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(163,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390347,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(164,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390351,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product?cate_id=1','visit',''),(165,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390354,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product?cate_id=1','visit',''),(166,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390357,'http://localhost:3000/case','referral','http://localhost:3000/product?cate_id=1','visit',''),(167,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390357,'http://localhost:3000/case?cate_id=2','referral','http://localhost:3000/case','visit',''),(168,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390360,'http://localhost:3000/case?cate_id=2','referral','http://localhost:3000/case','visit',''),(169,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390363,'http://localhost:3000/job','referral','http://localhost:3000/case?cate_id=2','visit',''),(170,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390367,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(171,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390374,'http://localhost:3000/points','referral','http://localhost:3000/job','visit',''),(172,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778390375,'http://localhost:3000/points','referral','http://localhost:3000/points','visit',''),(173,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778391096,'http://localhost:3000/points','referral','http://localhost:3000/points','visit',''),(174,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778391097,'http://localhost:3000/','referral','http://localhost:3000/points','visit',''),(175,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778428868,'http://localhost:3000/','referral','http://localhost:3000/member/login','visit',''),(176,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778428870,'http://localhost:3000/','referral','http://localhost:3000/member/login','visit',''),(177,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778428887,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(178,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778428888,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(179,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778428909,'http://localhost:3000/','referral','http://localhost:3000/points','visit',''),(180,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778428910,'http://localhost:3000/','referral','http://localhost:3000/points','visit',''),(181,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778428910,'http://localhost:3000/','referral','http://localhost:3000/points','visit',''),(182,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778428910,'http://localhost:3000/','referral','http://localhost:3000/points','visit',''),(183,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778432810,'http://localhost:3000/member/login','direct','','visit',''),(184,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778432819,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(185,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778432822,'http://localhost:3000/points','referral','http://localhost:3000/member/profile','visit',''),(186,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778435282,'http://localhost:3000/member/level','direct','','visit',''),(187,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778435294,'http://localhost:3000/member/level','direct','','visit',''),(188,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778435309,'http://localhost:3000/points','referral','http://localhost:3000/member/level','visit',''),(189,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778435310,'http://localhost:3000/signin','referral','http://localhost:3000/points','visit',''),(190,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778435317,'http://localhost:3000/signin','referral','http://localhost:3000/signin','visit',''),(191,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778435321,'http://localhost:3000/','referral','http://localhost:3000/signin','visit',''),(192,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778435322,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(193,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436595,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product/1','visit',''),(194,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436599,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product/1','visit',''),(195,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436658,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(196,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436659,'http://localhost:3000/points','referral','http://localhost:3000/case','visit',''),(197,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436661,'http://localhost:3000/signin','referral','http://localhost:3000/points','visit',''),(198,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436668,'http://localhost:3000/member/profile','referral','http://localhost:3000/signin','visit',''),(199,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436670,'http://localhost:3000/member/login','referral','http://localhost:3000/member/profile','visit',''),(200,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436673,'http://localhost:3000/member/login','referral','http://localhost:3000/member/login','visit',''),(201,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436674,'http://localhost:3000/job','referral','http://localhost:3000/member/login','visit',''),(202,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436675,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(203,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436676,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(204,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436676,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(205,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436678,'http://localhost:3000/download','referral','http://localhost:3000/','visit',''),(206,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436678,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(207,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436681,'http://localhost:3000/product','referral','http://localhost:3000/product?cate_id=1','visit',''),(208,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436682,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(209,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436682,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(210,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436683,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(211,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436684,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(212,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436685,'http://localhost:3000/news','referral','http://localhost:3000/','visit',''),(213,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436685,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(214,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436688,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(215,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778436689,'http://localhost:3000/','referral','http://localhost:3000/case','visit',''),(216,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437048,'http://localhost:3000/product','referral','http://localhost:3000/download','visit',''),(217,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437048,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(218,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437049,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(219,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437049,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(220,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437050,'http://localhost:3000/case','referral','http://localhost:3000/download','visit',''),(221,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437050,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(222,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437051,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(223,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437051,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(224,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437052,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(225,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437052,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(226,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437053,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(227,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437056,'http://localhost:3000/points','referral','http://localhost:3000/download','visit',''),(228,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437057,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(229,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437057,'http://localhost:3000/member/register','referral','http://localhost:3000/member/login','visit',''),(230,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437059,'http://localhost:3000/','referral','http://localhost:3000/member/register','visit',''),(231,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437060,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(232,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437205,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(233,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437205,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(234,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778437266,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news','visit',''),(235,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778465650,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(236,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778465651,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(237,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778465652,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(238,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778465658,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(239,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778467022,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(240,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778467839,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(241,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471746,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(242,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471792,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(243,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471792,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(244,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471793,'http://localhost:3000/member/login','referral','http://localhost:3000/product','visit',''),(245,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471799,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(246,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471805,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/profile','visit',''),(247,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471810,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/profile','visit',''),(248,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471967,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/profile','visit',''),(249,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471981,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/profile','visit',''),(250,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778471982,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/profile','visit',''),(251,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778473420,'http://localhost:3000/','referral','http://localhost:3000/admin/member_benefit/members','visit',''),(252,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778473422,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(253,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778473430,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(254,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778473434,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(255,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778475943,'http://localhost:3000/member/login','referral','http://localhost:3000/member/profile','visit',''),(256,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778475963,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(257,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778475979,'http://localhost:3000/member/exchange','direct','','visit',''),(258,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778475982,'http://localhost:3000/points','referral','http://localhost:3000/member/exchange','visit',''),(259,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778475983,'http://localhost:3000/signin','referral','http://localhost:3000/points','visit',''),(260,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778476438,'http://localhost:3000/','referral','http://localhost:3000/admin/member_benefit/members','visit',''),(261,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778476440,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(262,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477478,'http://localhost:3000/','referral','http://localhost:3000/admin','visit',''),(263,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477482,'http://localhost:3000/','referral','http://localhost:3000/admin','visit',''),(264,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477603,'http://localhost:3000/','referral','http://localhost:3000/admin','visit',''),(265,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477610,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(266,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477617,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(267,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477625,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(268,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477636,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(269,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477637,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(270,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477639,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(271,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477642,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(272,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477642,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(273,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477664,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(274,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477665,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(275,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477666,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(276,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477674,'http://localhost:3000/member/login','direct','','visit',''),(277,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778477710,'http://localhost:3000/','referral','http://localhost:3000/admin','visit',''),(278,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778479144,'http://localhost:3000/member/login','direct','','visit',''),(279,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778479153,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(280,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778479157,'http://localhost:3000/member/level','direct','','visit',''),(281,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778479163,'http://localhost:3000/member/level','direct','','visit',''),(282,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480324,'http://localhost:3000/member/level','direct','','visit',''),(283,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480327,'http://localhost:3000/member/level','direct','','visit',''),(284,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480329,'http://localhost:3000/member/level','direct','','visit',''),(285,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480337,'http://localhost:3000/member/level','direct','','visit',''),(286,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480403,'http://localhost:3000/product','referral','http://localhost:3000/member/level','visit',''),(287,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480405,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(288,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480406,'http://localhost:3000/','referral','http://localhost:3000/case','visit',''),(289,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480409,'http://localhost:3000/news','referral','http://localhost:3000/','visit',''),(290,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480413,'http://localhost:3000/job','referral','http://localhost:3000/news','visit',''),(291,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480414,'http://localhost:3000/points','referral','http://localhost:3000/job','visit',''),(292,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480416,'http://localhost:3000/signin','referral','http://localhost:3000/points','visit',''),(293,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480421,'http://localhost:3000/member/points','referral','http://localhost:3000/signin','visit',''),(294,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480487,'http://localhost:3000/member/level','direct','','visit',''),(295,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480702,'http://localhost:3000/member/level','direct','','visit',''),(296,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480709,'http://localhost:3000/member/level','direct','','visit',''),(297,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480764,'http://localhost:3000/member/level','direct','','visit',''),(298,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480766,'http://localhost:3000/product','referral','http://localhost:3000/member/level','visit',''),(299,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480767,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(300,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480769,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(301,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480772,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(302,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480773,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(303,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480773,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(304,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480773,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(305,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480774,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(306,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480774,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(307,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480776,'http://localhost:3000/points','referral','http://localhost:3000/job','visit',''),(308,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480777,'http://localhost:3000/','referral','http://localhost:3000/points','visit',''),(309,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480777,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(310,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480791,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(311,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480791,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(312,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480792,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(313,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480792,'http://localhost:3000/product','referral','http://localhost:3000/download','visit',''),(314,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480793,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(315,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480795,'http://localhost:3000/news','referral','http://localhost:3000/','visit',''),(316,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480797,'http://localhost:3000/job','referral','http://localhost:3000/news','visit',''),(317,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480797,'http://localhost:3000/','referral','http://localhost:3000/job','visit',''),(318,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480797,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(319,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480798,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(320,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480799,'http://localhost:3000/points','referral','http://localhost:3000/','visit',''),(321,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480800,'http://localhost:3000/member/profile','referral','http://localhost:3000/points','visit',''),(322,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480802,'http://localhost:3000/member/points','referral','http://localhost:3000/member/profile','visit',''),(323,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480804,'http://localhost:3000/member/exchange','referral','http://localhost:3000/member/points','visit',''),(324,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778480806,'http://localhost:3000/points','referral','http://localhost:3000/member/exchange','visit',''),(325,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778520173,'http://localhost:3000/','referral','http://localhost:3000/admin/rating/index','visit',''),(326,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778520174,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(327,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778520175,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(328,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778520175,'http://localhost:3000/news','referral','http://localhost:3000/product','visit',''),(329,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778520176,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(330,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778520176,'http://localhost:3000/','referral','http://localhost:3000/download','visit',''),(331,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778520177,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(332,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778520179,'http://localhost:3000/points','referral','http://localhost:3000/','visit',''),(333,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521029,'http://localhost:3000/product','referral','http://localhost:3000/points','visit',''),(334,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521029,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(335,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521031,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(336,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521037,'http://localhost:3000/','referral','http://localhost:3000/news','visit',''),(337,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521037,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(338,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521038,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(339,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521038,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(340,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521039,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(341,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521040,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(342,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521040,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(343,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521043,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(344,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521042,'http://localhost:3000/','referral','http://localhost:3000/job','visit',''),(345,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521043,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(346,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521046,'http://localhost:3000/member/register','referral','http://localhost:3000/','visit',''),(347,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521047,'http://localhost:3000/member/register','referral','http://localhost:3000/member/register','visit',''),(348,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521048,'http://localhost:3000/member/login','referral','http://localhost:3000/member/register','visit',''),(349,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521049,'http://localhost:3000/download','referral','http://localhost:3000/member/login','visit',''),(350,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521049,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(351,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521050,'http://localhost:3000/product','referral','http://localhost:3000/job','visit',''),(352,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778521050,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(353,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526909,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(354,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526909,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(355,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526909,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(356,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526910,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(357,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526910,'http://localhost:3000/download','referral','http://localhost:3000/job','visit',''),(358,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526911,'http://localhost:3000/news','referral','http://localhost:3000/download','visit',''),(359,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526911,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(360,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526912,'http://localhost:3000/case','referral','http://localhost:3000/download','visit',''),(361,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526912,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(362,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526913,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(363,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526914,'http://localhost:3000/job','referral','http://localhost:3000/','visit',''),(364,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526916,'http://localhost:3000/news','referral','http://localhost:3000/download','visit',''),(365,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526916,'http://localhost:3000/download','referral','http://localhost:3000/job','visit',''),(366,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526916,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(367,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526917,'http://localhost:3000/points','referral','http://localhost:3000/download','visit',''),(368,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526917,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(369,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526918,'http://localhost:3000/member/register','referral','http://localhost:3000/member/login','visit',''),(370,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526919,'http://localhost:3000/case','referral','http://localhost:3000/member/register','visit',''),(371,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526919,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(372,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526920,'http://localhost:3000/product','direct','','visit',''),(373,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778526920,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(374,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527266,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(375,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527266,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(376,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527267,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(377,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527267,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(378,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527268,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(379,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527269,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(380,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527270,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(381,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527270,'http://localhost:3000/member/register','referral','http://localhost:3000/member/login','visit',''),(382,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527271,'http://localhost:3000/points','referral','http://localhost:3000/member/register','visit',''),(383,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527272,'http://localhost:3000/signin','referral','http://localhost:3000/points','visit',''),(384,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527282,'http://localhost:3000/member/points','referral','http://localhost:3000/signin','visit',''),(385,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527283,'http://localhost:3000/points','referral','http://localhost:3000/member/points','visit',''),(386,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778527284,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(387,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548128,'http://localhost:3000/','direct','','visit',''),(388,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548130,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(389,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548145,'http://localhost:3000/points','referral','http://localhost:3000/member/points','visit',''),(390,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548145,'http://localhost:3000/member/points','referral','http://localhost:3000/signin','visit',''),(391,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548209,'http://localhost:3000/','direct','','visit',''),(392,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548211,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(393,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548212,'http://localhost:3000/job','referral','http://localhost:3000/case','visit',''),(394,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548213,'http://localhost:3000/download','referral','http://localhost:3000/job','visit',''),(395,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548213,'http://localhost:3000/news','referral','http://localhost:3000/download','visit',''),(396,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548214,'http://localhost:3000/job','referral','http://localhost:3000/news','visit',''),(397,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548216,'http://localhost:3000/points','referral','http://localhost:3000/job','visit',''),(398,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548217,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(399,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548217,'http://localhost:3000/member/register','referral','http://localhost:3000/member/login','visit',''),(400,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548219,'http://localhost:3000/','referral','http://localhost:3000/member/register','visit',''),(401,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548220,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(402,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548338,'http://localhost:3000/','direct','','visit',''),(403,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548340,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(404,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548340,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(405,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548341,'http://localhost:3000/','referral','http://localhost:3000/case','visit',''),(406,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548341,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(407,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548341,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(408,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548342,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(409,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548342,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(410,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548342,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(411,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548396,'http://localhost:3000/case','referral','http://localhost:3000/member/register','visit',''),(412,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548396,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(413,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548397,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(414,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548398,'http://localhost:3000/member/login','referral','http://localhost:3000/product?cate_id=1','visit',''),(415,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548405,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(416,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548409,'http://localhost:3000/points','referral','http://localhost:3000/member/profile','visit',''),(417,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548410,'http://localhost:3000/signin','referral','http://localhost:3000/points','visit',''),(418,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548414,'http://localhost:3000/signin','referral','http://localhost:3000/signin','visit',''),(419,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548418,'http://localhost:3000/member/points','referral','http://localhost:3000/signin','visit',''),(420,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548421,'http://localhost:3000/member/points','referral','http://localhost:3000/signin','visit',''),(421,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548421,'http://localhost:3000/member/points','referral','http://localhost:3000/signin','visit',''),(422,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548425,'http://localhost:3000/member/exchange','referral','http://localhost:3000/member/points','visit',''),(423,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548427,'http://localhost:3000/member/points','referral','http://localhost:3000/member/exchange','visit',''),(424,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548428,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/points','visit',''),(425,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548429,'http://localhost:3000/news','referral','http://localhost:3000/member/profile','visit',''),(426,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548432,'http://localhost:3000/news','referral','http://localhost:3000/member/profile','visit',''),(427,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548433,'http://localhost:3000/news','referral','http://localhost:3000/member/profile','visit',''),(428,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548433,'http://localhost:3000/news','referral','http://localhost:3000/member/profile','visit',''),(429,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548434,'http://localhost:3000/news','referral','http://localhost:3000/member/profile','visit',''),(430,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548434,'http://localhost:3000/news','referral','http://localhost:3000/member/profile','visit',''),(431,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548434,'http://localhost:3000/news','referral','http://localhost:3000/member/profile','visit',''),(432,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548436,'http://localhost:3000/news','referral','http://localhost:3000/member/profile','visit',''),(433,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548436,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(434,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548437,'http://localhost:3000/download','referral','http://localhost:3000/case','visit',''),(435,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548437,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(436,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548438,'http://localhost:3000/product','referral','http://localhost:3000/job','visit',''),(437,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548438,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(438,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548439,'http://localhost:3000/job','referral','http://localhost:3000/product','visit',''),(439,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548440,'http://localhost:3000/download','referral','http://localhost:3000/job','visit',''),(440,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548441,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(441,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548442,'http://localhost:3000/news','referral','http://localhost:3000/job','visit',''),(442,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548442,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(443,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548443,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(444,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548444,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(445,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548448,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(446,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548449,'http://localhost:3000/member/register','referral','http://localhost:3000/member/login','visit',''),(447,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548449,'http://localhost:3000/member/login','referral','http://localhost:3000/member/register','visit',''),(448,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548450,'http://localhost:3000/','referral','http://localhost:3000/member/login','visit',''),(449,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548451,'http://localhost:3000/','referral','http://localhost:3000/member/login','visit',''),(450,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548452,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(451,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548453,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(452,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548453,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(453,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548454,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(454,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548455,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(455,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548455,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(456,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548456,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(457,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548459,'http://localhost:3000/download','referral','http://localhost:3000/','visit',''),(458,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548459,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(459,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548461,'http://localhost:3000/job?cate_id=5','referral','http://localhost:3000/job','visit',''),(460,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548461,'http://localhost:3000/job?cate_id=5','referral','http://localhost:3000/job?cate_id=5','visit',''),(461,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548466,'http://localhost:3000/member/exchange','referral','http://localhost:3000/job?cate_id=5','visit',''),(462,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548467,'http://localhost:3000/member/points','referral','http://localhost:3000/member/exchange','visit',''),(463,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548468,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/points','visit',''),(464,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548602,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/points','visit',''),(465,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548602,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/points','visit',''),(466,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548602,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/points','visit',''),(467,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548602,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/points','visit',''),(468,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548603,'http://localhost:3000/news','referral','http://localhost:3000/member/profile','visit',''),(469,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548603,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(470,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548604,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(471,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548604,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(472,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548606,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(473,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548749,'http://localhost:3000/member/points','referral','http://localhost:3000/signin','visit',''),(474,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548752,'http://localhost:3000/signin','referral','http://localhost:3000/member/points','visit',''),(475,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548898,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(476,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548898,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(477,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548898,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(478,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548898,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(479,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548899,'http://localhost:3000/download','referral','http://localhost:3000/','visit',''),(480,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548900,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(481,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548901,'http://localhost:3000/','referral','http://localhost:3000/job','visit',''),(482,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548907,'http://localhost:3000/signin','referral','http://localhost:3000/member/points','visit',''),(483,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548908,'http://localhost:3000/points','referral','http://localhost:3000/signin','visit',''),(484,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548909,'http://localhost:3000/','referral','http://localhost:3000/points','visit',''),(485,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778548909,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(486,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778549372,'http://localhost:3000/','referral','http://localhost:3000/job','visit',''),(487,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778549373,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(488,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778549373,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(489,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778549373,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(490,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778549374,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(491,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778549374,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(492,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778549375,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(493,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778549375,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(494,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778549375,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(495,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778551014,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(496,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778551051,'http://localhost:3000/','direct','','visit',''),(497,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553056,'http://localhost:3000/','referral','http://localhost:3000/admin/system/config?tab=basic','visit',''),(498,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553058,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(499,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553059,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(500,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553061,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product?cate_id=1','visit',''),(501,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553062,'http://localhost:3000/download','referral','http://localhost:3000/product?cate_id=1','visit',''),(502,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553062,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(503,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553063,'http://localhost:3000/job?cate_id=5','referral','http://localhost:3000/job','visit',''),(504,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553502,'http://localhost:3000/case','referral','http://localhost:3000/job?cate_id=5','visit',''),(505,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553502,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(506,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553502,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(507,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553502,'http://localhost:3000/','referral','http://localhost:3000/news','visit',''),(508,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553503,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(509,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553509,'http://localhost:3000/','direct','','visit',''),(510,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553513,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(511,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553513,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(512,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553517,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(513,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553520,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(514,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553521,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(515,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553526,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(516,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553527,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(517,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553529,'http://localhost:3000/download','referral','http://localhost:3000/','visit',''),(518,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553529,'http://localhost:3000/news','referral','http://localhost:3000/download','visit',''),(519,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553530,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(520,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553530,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(521,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553530,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(522,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553531,'http://localhost:3000/download','referral','http://localhost:3000/','visit',''),(523,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553531,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(524,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553532,'http://localhost:3000/news','referral','http://localhost:3000/job','visit',''),(525,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553532,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(526,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553533,'http://localhost:3000/download','referral','http://localhost:3000/case','visit',''),(527,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553533,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(528,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553534,'http://localhost:3000/points','referral','http://localhost:3000/download','visit',''),(529,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553537,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(530,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553539,'http://localhost:3000/member/register','referral','http://localhost:3000/member/login','visit',''),(531,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553540,'http://localhost:3000/member/login','referral','http://localhost:3000/member/register','visit',''),(532,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553557,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(533,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553560,'http://localhost:3000/job','referral','http://localhost:3000/member/profile','visit',''),(534,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553566,'http://localhost:3000/member/login','referral','http://localhost:3000/job','visit',''),(535,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778553567,'http://localhost:3000/job','referral','http://localhost:3000/member/login','visit',''),(536,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554164,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(537,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554165,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(538,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554165,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(539,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554166,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(540,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554166,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(541,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554167,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(542,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554167,'http://localhost:3000/','referral','http://localhost:3000/case','visit',''),(543,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554168,'http://localhost:3000/news','referral','http://localhost:3000/','visit',''),(544,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554169,'http://localhost:3000/','referral','http://localhost:3000/news','visit',''),(545,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554228,'http://localhost:3000/job','referral','http://localhost:3000/member/login','visit',''),(546,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554229,'http://localhost:3000/job','referral','http://localhost:3000/member/login','visit',''),(547,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554229,'http://localhost:3000/job','referral','http://localhost:3000/member/login','visit',''),(548,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554230,'http://localhost:3000/job','referral','http://localhost:3000/member/login','visit',''),(549,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554230,'http://localhost:3000/job','referral','http://localhost:3000/member/login','visit',''),(550,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554230,'http://localhost:3000/job','referral','http://localhost:3000/member/login','visit',''),(551,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554230,'http://localhost:3000/job','referral','http://localhost:3000/member/login','visit',''),(552,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554232,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(553,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554243,'http://localhost:3000/','referral','http://localhost:3000/news','visit',''),(554,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554244,'http://localhost:3000/download','referral','http://localhost:3000/','visit',''),(555,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554245,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(556,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554246,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(557,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554247,'http://localhost:3000/download','referral','http://localhost:3000/job','visit',''),(558,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554248,'http://localhost:3000/points','referral','http://localhost:3000/download','visit',''),(559,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554249,'http://localhost:3000/news','referral','http://localhost:3000/points','visit',''),(560,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554249,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(561,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554250,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(562,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554250,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(563,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554251,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(564,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554251,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(565,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554252,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(566,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554252,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(567,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554587,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(568,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554587,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news','visit',''),(569,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554736,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(570,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554737,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(571,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554737,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(572,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554737,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(573,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554876,'http://localhost:3000/','referral','http://localhost:3000/admin','visit',''),(574,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554877,'http://localhost:3000/','referral','http://localhost:3000/admin','visit',''),(575,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554879,'http://localhost:3000/','referral','http://localhost:3000/admin','visit',''),(576,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554880,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(577,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554880,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(578,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554881,'http://localhost:3000/news','referral','http://localhost:3000/product','visit',''),(579,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554881,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(580,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554882,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(581,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554883,'http://localhost:3000/member/login','referral','http://localhost:3000/job','visit',''),(582,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554896,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(583,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554898,'http://localhost:3000/job','referral','http://localhost:3000/member/profile','visit',''),(584,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554899,'http://localhost:3000/download','referral','http://localhost:3000/job','visit',''),(585,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554900,'http://localhost:3000/news','referral','http://localhost:3000/download','visit',''),(586,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554900,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(587,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554901,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(588,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554901,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(589,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554903,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(590,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554904,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(591,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554904,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(592,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554905,'http://localhost:3000/download','referral','http://localhost:3000/product','visit',''),(593,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554905,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(594,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554906,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(595,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554906,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(596,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554907,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(597,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554907,'http://localhost:3000/product','referral','http://localhost:3000/job','visit',''),(598,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554908,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(599,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554909,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(600,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554909,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(601,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554909,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(602,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554910,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(603,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554910,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(604,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554910,'http://localhost:3000/news','referral','http://localhost:3000/job','visit',''),(605,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554911,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(606,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554912,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(607,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554912,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(608,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554916,'http://localhost:3000/','direct','','visit',''),(609,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554920,'http://localhost:3000/news','referral','http://localhost:3000/','visit',''),(610,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554921,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(611,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554922,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(612,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554923,'http://localhost:3000/job','referral','http://localhost:3000/product','visit',''),(613,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554924,'http://localhost:3000/download','referral','http://localhost:3000/job','visit',''),(614,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554925,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(615,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554925,'http://localhost:3000/news','referral','http://localhost:3000/download','visit',''),(616,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554925,'http://localhost:3000/','referral','http://localhost:3000/case','visit',''),(617,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554929,'http://localhost:3000/points','referral','http://localhost:3000/','visit',''),(618,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554929,'http://localhost:3000/','referral','http://localhost:3000/points','visit',''),(619,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554932,'http://localhost:3000/','referral','http://localhost:3000/points','visit',''),(620,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554932,'http://localhost:3000/member/login','referral','http://localhost:3000/','visit',''),(621,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554939,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(622,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554941,'http://localhost:3000/job','referral','http://localhost:3000/member/profile','visit',''),(623,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554955,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(624,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554955,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(625,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554956,'http://localhost:3000/job','referral','http://localhost:3000/case','visit',''),(626,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554957,'http://localhost:3000/download','referral','http://localhost:3000/job','visit',''),(627,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554958,'http://localhost:3000/news','referral','http://localhost:3000/download','visit',''),(628,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554959,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(629,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554959,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(630,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554959,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(631,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554960,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(632,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554960,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(633,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778554961,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(634,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556394,'http://localhost:3000/','direct','','visit',''),(635,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556397,'http://localhost:3000/','direct','','visit',''),(636,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556399,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(637,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556400,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(638,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556401,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(639,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556404,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(640,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556407,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(641,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556408,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(642,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556410,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(643,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556410,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(644,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556410,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(645,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556413,'http://localhost:3000/points','referral','http://localhost:3000/','visit',''),(646,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556414,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(647,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556421,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/login','visit',''),(648,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556421,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/profile','visit',''),(649,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556421,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/profile','visit',''),(650,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556422,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/profile','visit',''),(651,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556422,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/profile','visit',''),(652,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556423,'http://localhost:3000/job','referral','http://localhost:3000/member/profile','visit',''),(653,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556423,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(654,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556423,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(655,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556423,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(656,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556424,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(657,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556424,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(658,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556424,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(659,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556424,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(660,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556424,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(661,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556425,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(662,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556425,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(663,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556426,'http://localhost:3000/news','referral','http://localhost:3000/job','visit',''),(664,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556426,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(665,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556426,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(666,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556426,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(667,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556426,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(668,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556427,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(669,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556427,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(670,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556427,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(671,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556427,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(672,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556427,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(673,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556428,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(674,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556428,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(675,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556429,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(676,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556429,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(677,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556429,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(678,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556429,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(679,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556429,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(680,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556430,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(681,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556430,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(682,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556430,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(683,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556430,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(684,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556430,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(685,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556431,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(686,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556431,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(687,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556432,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(688,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556432,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(689,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556432,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(690,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556432,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(691,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556432,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(692,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556433,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(693,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556433,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(694,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556433,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(695,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556433,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(696,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556433,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(697,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556434,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(698,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556434,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(699,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556434,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(700,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556435,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(701,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556435,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(702,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556435,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(703,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556435,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(704,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556436,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(705,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556436,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(706,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556436,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(707,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556436,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(708,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556436,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(709,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556436,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(710,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556437,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(711,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556437,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(712,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556438,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(713,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556438,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(714,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556438,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(715,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556438,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(716,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556438,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(717,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556439,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(718,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556439,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(719,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556439,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(720,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556439,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(721,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556440,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(722,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556440,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(723,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556441,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(724,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556441,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(725,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556441,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(726,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556441,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(727,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556441,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(728,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556442,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(729,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556442,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(730,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556442,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(731,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556442,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(732,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556442,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(733,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556443,'http://localhost:3000/download','referral','http://localhost:3000/download','visit',''),(734,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556444,'http://localhost:3000/','referral','http://localhost:3000/download','visit',''),(735,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556444,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(736,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556444,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(737,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556445,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(738,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556445,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(739,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556445,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(740,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556445,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(741,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556445,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(742,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556446,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(743,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556446,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(744,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556447,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(745,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556447,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(746,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556447,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(747,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556447,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(748,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556447,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(749,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556448,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(750,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556448,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(751,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556448,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(752,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556448,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(753,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556449,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(754,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556449,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(755,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556450,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(756,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556450,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(757,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556450,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(758,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556451,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(759,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556451,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(760,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556451,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(761,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556452,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(762,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556452,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(763,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556453,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(764,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556453,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(765,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556453,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(766,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556453,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(767,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556454,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(768,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556454,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(769,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556454,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(770,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556455,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(771,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556455,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(772,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556456,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(773,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556456,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(774,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556456,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(775,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556457,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(776,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556457,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(777,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556457,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(778,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556457,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(779,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556458,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(780,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556459,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(781,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556459,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(782,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556459,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(783,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556459,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(784,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556460,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(785,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556460,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(786,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556461,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(787,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556461,'http://localhost:3000/news','referral','http://localhost:3000/news','visit',''),(788,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556462,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(789,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556462,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(790,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556462,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(791,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556463,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(792,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556463,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(793,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556463,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(794,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556464,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(795,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556464,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(796,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556465,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(797,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556465,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(798,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556465,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(799,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556465,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(800,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556466,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(801,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556466,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(802,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556466,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(803,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556466,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(804,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556466,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(805,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556467,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(806,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556467,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(807,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556468,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(808,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556676,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news','visit',''),(809,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556676,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(810,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556676,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(811,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556677,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(812,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556677,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(813,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556678,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(814,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556678,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(815,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556678,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(816,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556678,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(817,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556679,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(818,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556679,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(819,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556679,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(820,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556679,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(821,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556680,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(822,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556680,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(823,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556681,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(824,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556681,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(825,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556681,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(826,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556682,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(827,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556682,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(828,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556682,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(829,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556682,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(830,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556683,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(831,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556892,'http://localhost:3000/news?cate_id=3','referral','http://localhost:3000/news?cate_id=3','visit',''),(832,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556895,'http://localhost:3000/case','referral','http://localhost:3000/news?cate_id=3','visit',''),(833,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556896,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(834,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556899,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(835,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556900,'http://localhost:3000/product','referral','http://localhost:3000/download','visit',''),(836,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556902,'http://localhost:3000/download','referral','http://localhost:3000/product','visit',''),(837,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556904,'http://localhost:3000/news','referral','http://localhost:3000/download','visit',''),(838,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556904,'http://localhost:3000/job','referral','http://localhost:3000/news','visit',''),(839,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556907,'http://localhost:3000/','referral','http://localhost:3000/job','visit',''),(840,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556909,'http://localhost:3000/news','referral','http://localhost:3000/','visit',''),(841,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556910,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(842,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556911,'http://localhost:3000/case','referral','http://localhost:3000/download','visit',''),(843,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556911,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(844,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556913,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(845,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556917,'http://localhost:3000/points','referral','http://localhost:3000/product?cate_id=1','visit',''),(846,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556920,'http://localhost:3000/signin','referral','http://localhost:3000/points','visit',''),(847,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556924,'http://localhost:3000/news','referral','http://localhost:3000/signin','visit',''),(848,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556925,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(849,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556926,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(850,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556928,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(851,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556928,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(852,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556932,'http://localhost:3000/member/points','referral','http://localhost:3000/','visit',''),(853,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556935,'http://localhost:3000/member/profile','referral','http://localhost:3000/member/points','visit',''),(854,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556938,'http://localhost:3000/member/exchange','referral','http://localhost:3000/member/profile','visit',''),(855,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556939,'http://localhost:3000/job','referral','http://localhost:3000/member/exchange','visit',''),(856,0,1,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778556943,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(857,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778565942,'http://localhost:3000/job','referral','http://localhost:3000/job','visit',''),(858,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778565945,'http://localhost:3000/job?cate_id=5','referral','http://localhost:3000/job','visit',''),(859,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778565948,'http://localhost:3000/case','referral','http://localhost:3000/job?cate_id=5','visit',''),(860,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778565948,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(861,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778565949,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(862,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778566098,'http://localhost:3000/download','referral','http://localhost:3000/','visit',''),(863,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778566099,'http://localhost:3000/news','referral','http://localhost:3000/download','visit',''),(864,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778566101,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(865,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778566104,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(866,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778566123,'http://localhost:3000/download','referral','http://localhost:3000/','visit',''),(867,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778566125,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(868,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778566904,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(869,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778569897,'http://localhost:3000/case','referral','http://localhost:3000/job','visit',''),(870,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778574443,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(871,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778574444,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(872,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778576724,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(873,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778579123,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(874,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778579488,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(875,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778579492,'http://localhost:3000/news','referral','http://localhost:3000/','visit',''),(876,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778579492,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(877,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778579494,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(878,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778579497,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(879,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778580992,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(880,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778583377,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(881,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778583426,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(882,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778583461,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(883,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778583684,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(884,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778648899,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(887,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651277,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(888,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651281,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(889,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651284,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(890,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651286,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(891,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651287,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(892,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651289,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(893,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651290,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(894,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651293,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(895,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651294,'http://localhost:3000/download','referral','http://localhost:3000/job','visit',''),(896,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651296,'http://localhost:3000/points','referral','http://localhost:3000/download','visit',''),(897,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651297,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(898,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651298,'http://localhost:3000/member/register','referral','http://localhost:3000/member/login','visit',''),(899,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651299,'http://localhost:3000/news','referral','http://localhost:3000/member/register','visit',''),(900,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651300,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(904,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651857,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(905,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651860,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(906,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778651861,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(907,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652024,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(916,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652749,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(917,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652750,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(918,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652756,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(919,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652757,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(920,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652759,'http://localhost:3000/case','referral','http://localhost:3000/download','visit',''),(921,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652759,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(922,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652762,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(923,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652764,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(924,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652766,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(925,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652769,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(926,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652770,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(927,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778652772,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(931,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778656401,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(932,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778656402,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(933,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778656404,'http://localhost:3000/product','referral','http://localhost:3000/download','visit',''),(934,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778656406,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(935,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778656407,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(936,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778656409,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(937,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659257,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(938,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659262,'http://localhost:3000/product','referral','http://localhost:3000/news','visit',''),(939,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659333,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(940,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659337,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(941,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659687,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(942,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659687,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(943,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659689,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(944,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659922,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(945,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659925,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(946,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659926,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(947,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659928,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(948,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778659947,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(949,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660370,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(950,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660501,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(951,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660506,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(952,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660905,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(953,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660907,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(954,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660909,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(955,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660913,'http://localhost:3000/case','referral','http://localhost:3000/product?cate_id=1','visit',''),(956,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660914,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(957,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660917,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(958,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660923,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(959,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660927,'http://localhost:3000/points','referral','http://localhost:3000/job','visit',''),(960,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660930,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(961,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660934,'http://localhost:3000/case','referral','http://localhost:3000/member/login','visit',''),(962,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660935,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(963,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660937,'http://localhost:3000/news','referral','http://localhost:3000/product','visit',''),(964,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660938,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(965,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660940,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(966,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660941,'http://localhost:3000/news','referral','http://localhost:3000/product','visit',''),(967,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660942,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(968,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660943,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(969,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660947,'http://localhost:3000/','referral','http://localhost:3000/job','visit',''),(970,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660950,'http://localhost:3000/','referral','http://localhost:3000/job','visit',''),(971,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660952,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(972,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660953,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(973,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660956,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(974,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660957,'http://localhost:3000/product','referral','http://localhost:3000/download','visit',''),(975,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660958,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(976,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660959,'http://localhost:3000/download','referral','http://localhost:3000/product','visit',''),(977,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660961,'http://localhost:3000/job','referral','http://localhost:3000/download','visit',''),(978,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660962,'http://localhost:3000/points','referral','http://localhost:3000/job','visit',''),(979,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660963,'http://localhost:3000/member/login','referral','http://localhost:3000/points','visit',''),(980,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660964,'http://localhost:3000/member/register','referral','http://localhost:3000/member/login','visit',''),(981,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660965,'http://localhost:3000/','referral','http://localhost:3000/member/register','visit',''),(982,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778660969,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(983,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664077,'http://localhost:3000/product','referral','http://localhost:3000/product','visit',''),(984,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664078,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(985,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664081,'http://localhost:3000/news','referral','http://localhost:3000/case','visit',''),(986,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664083,'http://localhost:3000/download','referral','http://localhost:3000/news','visit',''),(987,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664086,'http://localhost:3000/','referral','http://localhost:3000/download','visit',''),(988,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664089,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(989,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664091,'http://localhost:3000/product?cate_id=1','referral','http://localhost:3000/product','visit',''),(990,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664093,'http://localhost:3000/case','referral','http://localhost:3000/product?cate_id=1','visit',''),(991,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664095,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(992,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664097,'http://localhost:3000/news','referral','http://localhost:3000/product','visit',''),(993,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664098,'http://localhost:3000/case','referral','http://localhost:3000/news','visit',''),(994,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664103,'http://localhost:3000/','referral','http://localhost:3000/case','visit',''),(995,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664106,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(996,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778664552,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(997,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778677125,'http://localhost:3000/case','direct','','visit',''),(998,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778677126,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(999,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778677130,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(1000,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778677131,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(1001,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778687343,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(1002,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778687345,'http://localhost:3000/case','referral','http://localhost:3000/','visit',''),(1003,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778687346,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(1004,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778687349,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(1005,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778693741,'http://localhost:3000/','referral','http://localhost:3000/product','visit',''),(1006,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778693742,'http://localhost:3000/product','referral','http://localhost:3000/','visit',''),(1007,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778693744,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(1008,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778700747,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(1009,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778700751,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(1010,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778724543,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(1011,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778736794,'http://localhost:3000/case','referral','http://localhost:3000/case','visit',''),(1012,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778736797,'http://localhost:3000/product','referral','http://localhost:3000/case','visit',''),(1013,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778736798,'http://localhost:3000/case','referral','http://localhost:3000/product','visit',''),(1014,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778736800,'http://localhost:3000/','referral','http://localhost:3000/case','visit',''),(1015,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778736800,'http://localhost:3000/','referral','http://localhost:3000/','visit',''),(1016,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778758362,'http://localhost:3000/','referral','http://localhost:3000/admin/system/config?tab=system','visit',''),(1017,0,0,NULL,'172.18.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36',1778758439,'http://localhost:3000/','referral','http://localhost:3000/admin/system/config?tab=system','visit','');
/*!40000 ALTER TABLE `{prefix}visit_log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}visit_log_archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}visit_log_archive` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `period` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '归档周期 如:2026-04',
  `period_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'month' COMMENT '周期类型',
  `pv` int unsigned NOT NULL DEFAULT '0' COMMENT '月PV',
  `uv` int unsigned NOT NULL DEFAULT '0' COMMENT '月UV',
  `content_stats` text COLLATE utf8mb4_unicode_ci COMMENT '内容访问排行(JSON)',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_period` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='访问日志归档表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}visit_log_archive` WRITE;
/*!40000 ALTER TABLE `{prefix}visit_log_archive` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}visit_log_archive` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}webhook_endpoint`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}webhook_endpoint` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '端点名称',
  `url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '推送URL',
  `secret` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '签名密钥',
  `events` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '监听事件列表(JSON数组)',
  `is_active` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '是否激活',
  `retry_count` tinyint unsigned NOT NULL DEFAULT '3' COMMENT '最大重试次数',
  `timeout_seconds` int unsigned NOT NULL DEFAULT '10' COMMENT '超时时间(秒)',
  `fail_count` int unsigned NOT NULL DEFAULT '0' COMMENT '连续失败次数',
  `last_sent_at` int unsigned DEFAULT NULL COMMENT '最后推送时间',
  `last_status` tinyint DEFAULT NULL COMMENT '最后状态:1成功0失败',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Webhook端点表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}webhook_endpoint` WRITE;
/*!40000 ALTER TABLE `{prefix}webhook_endpoint` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}webhook_endpoint` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `{prefix}webhook_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `{prefix}webhook_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `endpoint_id` int unsigned NOT NULL DEFAULT '0' COMMENT '端点ID',
  `event_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '事件名称',
  `payload` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '推送数据(JSON)',
  `response_code` int NOT NULL DEFAULT '0' COMMENT '响应状态码',
  `response_body` text COLLATE utf8mb4_unicode_ci COMMENT '响应内容',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态:0待推送1推送中2成功3失败',
  `attempt` int unsigned NOT NULL DEFAULT '1' COMMENT '第几次重试',
  `duration_ms` int unsigned NOT NULL DEFAULT '0' COMMENT '耗时(毫秒)',
  `error_message` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '错误消息',
  `create_time` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_endpoint` (`endpoint_id`),
  KEY `idx_status` (`status`),
  KEY `idx_event` (`event_name`),
  KEY `idx_create` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Webhook推送日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `{prefix}webhook_log` WRITE;
/*!40000 ALTER TABLE `{prefix}webhook_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `{prefix}webhook_log` ENABLE KEYS */;
UNLOCK TABLES;
-- ============================================
-- 以下为核心表种子数据（Docker dump 已包含表结构，此处不再重复定义）
-- ============================================

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


SET FOREIGN_KEY_CHECKS = 1;
