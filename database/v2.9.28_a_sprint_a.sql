-- AI-CMS V2.9.28 Sprint A 数据库变更脚本
-- 主题：AI编辑器功能增强（A-1~A-8）
-- 包含：对话记录表/模板库表/版本快照表

SET NAMES utf8mb4;
SET @db = DATABASE();

-- ============================================================
-- A-2: AI编辑器对话记录表
-- content_id注释修正（小产v2审核问题1）：
--   0=未关联内容，仅允许临时对话
--   不支持跨content对话
-- 增加 session_token_total 冗余字段（小扣建议）
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_ai_editor_conversation` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '会话标识',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `content_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联内容ID(0=未关联内容，仅允许临时对话，不支持跨content对话)',
    `role` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '角色(user/assistant)',
    `content` TEXT COMMENT '对话内容',
    `token_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '本轮Token数量',
    `session_token_total` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '会话累计Token总数',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_session` (`session_id`),
    KEY `idx_content` (`content_id`),
    KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI编辑器对话记录表(V2.9.28 A-2)';

-- ============================================================
-- A-5: AI编辑器模板库
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_ai_editor_template` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '模板名称',
    `description` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '模板描述',
    `prompt` TEXT COMMENT 'Prompt模板(含变量占位符)',
    `category` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '分类',
    `industry` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '行业标签',
    `tags` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '标签(逗号分隔)',
    `example_output` TEXT COMMENT '示例输出',
    `sort` INT UNSIGNED NOT NULL DEFAULT 99 COMMENT '排序',
    `is_system` TINYINT NOT NULL DEFAULT 0 COMMENT '是否系统预制',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建用户(0=系统)',
    `use_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用次数',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_category` (`category`),
    KEY `idx_user` (`user_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI编辑器模板库(V2.9.28 A-5)';

-- A-5: 初始化20个系统预制模板（Q10: 由CodeBuddy预生成）
INSERT IGNORE INTO `i8j_ai_editor_template` (`name`, `description`, `prompt`, `category`, `industry`, `tags`, `example_output`, `sort`, `is_system`, `user_id`, `status`, `create_time`, `update_time`) VALUES
('营销文案生成', '生成吸引人的营销文案', '请根据以下信息生成一段营销文案：\n产品名称：{product_name}\n目标受众：{target_audience}\n核心卖点：{selling_points}\n文案风格：吸引眼球、简洁有力', 'marketing', 'ecommerce', '营销,文案,广告', '【限时特惠】{product_name}，专为{target_audience}打造！{selling_points}，让每一次选择都物超所值。', 1, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('产品描述优化', '优化产品描述使其更具吸引力', '请优化以下产品描述，使其更专业、更有吸引力：\n{content}\n要求：突出产品优势、使用场景化描述', 'marketing', 'ecommerce', '产品,描述,优化', '这款产品采用XX工艺，精选优质材料...', 2, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('新闻稿撰写', '撰写标准格式的新闻稿', '请撰写一篇新闻稿：\n标题：{title}\n事件：{event}\n时间：{date}\n地点：{location}\n要求：客观、正式、信息完整', 'news', 'enterprise', '新闻,稿件,公关', '{date}，{location}讯——{title}。据悉，{event}...', 3, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('博客文章生成', '生成博客文章框架', '请围绕以下主题撰写一篇博客文章：\n主题：{topic}\n字数：{word_count}\n风格：{style}\n要求：开头吸引人、内容有价值、结尾有总结', 'blog', 'blog', '博客,文章,内容', '在这个信息爆炸的时代，{topic}成为了热门话题...', 4, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('邮件营销模板', '生成邮件营销内容', '请撰写一封营销邮件：\n收件人：{recipient}\n产品：{product}\n目的：{purpose}\n要求：标题吸引人、正文简洁、CTA明确', 'email', 'enterprise', '邮件,营销,EDM', '亲爱的{recipient}，\n\n我们很高兴向您介绍{product}...', 5, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('SEO摘要生成', '生成SEO友好的内容摘要', '请为以下内容生成SEO友好的摘要(150字以内)：\n{content}\n要求：包含核心关键词、吸引点击、适合搜索引擎', 'seo', 'enterprise', 'SEO,摘要,优化', '本文深入探讨{topic}，为您揭示...', 6, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('社交媒体文案', '生成社交媒体发布文案', '请为以下内容生成社交媒体文案：\n平台：{platform}\n内容：{content}\n要求：符合平台调性、带话题标签、互动性强', 'social', 'ecommerce', '社交媒体,文案,互动', '刚刚了解到{content}，太赞了！#话题标签', 7, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('产品说明书', '撰写产品使用说明书', '请撰写产品使用说明书：\n产品：{product}\n功能：{features}\n要求：步骤清晰、语言简洁、安全提示', 'manual', 'enterprise', '产品,说明书,文档', '一、产品概述\n{product}是一款{features}的产品...', 8, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('教育课程大纲', '生成教育培训课程大纲', '请生成课程大纲：\n课程名称：{course_name}\n目标学员：{target}\n课时数：{hours}\n要求：循序渐进、知识点清晰', 'education', 'education', '教育,课程,培训', '第一讲：{course_name}基础\n第二讲：进阶知识...', 9, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('医疗科普文章', '撰写通俗易懂的医疗科普', '请撰写医疗科普文章：\n主题：{topic}\n读者：普通大众\n要求：科学准确、通俗易懂、有实用建议', 'article', 'medical', '医疗,科普,健康', '关于{topic}，很多人都有疑问...', 10, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('金融分析报告', '生成金融数据分析报告', '请撰写金融分析报告：\n分析对象：{target}\n数据：{data}\n要求：客观分析、数据支撑、有结论建议', 'report', 'finance', '金融,分析,报告', '一、市场概况\n根据数据，{target}近期表现...', 11, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('旅游攻略生成', '生成旅游目的地攻略', '请生成旅游攻略：\n目的地：{destination}\n天数：{days}\n要求：行程合理、必去景点、美食推荐', 'guide', 'tourism', '旅游,攻略,出行', '第一天：抵达{destination}，建议游览...', 12, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('电商详情页文案', '生成电商产品详情页文案', '请为电商产品生成详情页文案：\n产品：{product}\n卖点：{features}\n要求：分模块展示、图文并茂、转化率高', 'ecommerce', 'ecommerce', '电商,详情页,转化', '【产品亮点】\n{features}\n【使用场景】\n...', 13, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('技术文档撰写', '撰写技术API文档', '请撰写技术文档：\n功能：{feature}\n接口：{api}\n要求：参数说明、示例代码、注意事项', 'tech', 'enterprise', '技术,文档,API', '## 接口说明\n{api}\n\n## 请求参数\n...', 14, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('品牌故事撰写', '撰写品牌故事', '请撰写品牌故事：\n品牌：{brand}\n历史：{history}\n价值观：{values}\n要求：感人、真实、有记忆点', 'brand', 'enterprise', '品牌,故事,营销', '{brand}的故事始于{history}...', 15, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('FAQ常见问题', '生成FAQ问答', '请生成FAQ：\n主题：{topic}\n常见问题数：{count}\n要求：问题典型、答案简洁', 'faq', 'enterprise', 'FAQ,问答,帮助', 'Q1: {topic}是什么？\nA: ...', 16, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('视频脚本撰写', '生成短视频脚本', '请撰写短视频脚本：\n主题：{topic}\n时长：{duration}秒\n平台：{platform}\n要求：开头3秒抓眼球、节奏紧凑', 'video', 'ecommerce', '视频,脚本,短视频', '【0-3秒】开场白\n【3-15秒】核心内容...', 17, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('活动策划方案', '生成活动策划方案', '请生成活动策划方案：\n活动类型：{type}\n预算：{budget}\n人数：{participants}\n要求：创意、可执行、有ROI分析', 'event', 'enterprise', '活动,策划,方案', '一、活动概述\n{type}活动，预计{participants}人参加...', 18, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('用户评测文案', '生成用户评测文案', '请撰写产品评测文案：\n产品：{product}\n使用体验：{experience}\n要求：真实客观、优缺点对比', 'review', 'ecommerce', '评测,产品,体验', '用了{product}一周后，我的真实感受...', 19, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('招聘JD撰写', '生成招聘职位描述', '请撰写招聘JD：\n职位：{position}\n要求：{requirements}\n公司：{company}\n要求：吸引人、职责清晰、要求合理', 'hr', 'enterprise', '招聘,JD,HR', '我们正在寻找{position}！\n在{company}，你将...', 20, 1, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ============================================================
-- A-7: AI编辑器版本快照表
-- 增加 content_hash 字段（Q6: 全量快照，50版本上限）
-- ============================================================
CREATE TABLE IF NOT EXISTS `i8j_ai_editor_snapshot` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `content_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '内容ID',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `version` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '版本号(自增)',
    `content` LONGTEXT COMMENT '内容快照',
    `content_hash` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '内容哈希(sha256)',
    `operation_type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '操作类型(continue/rewrite/expand/translate/optimize)',
    `operation_desc` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '操作描述',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_content` (`content_id`),
    KEY `idx_user` (`user_id`),
    UNIQUE KEY `uk_content_version` (`content_id`, `version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI编辑器版本快照表(V2.9.28 A-7)';

-- ============================================================
-- 菜单项
-- ============================================================
INSERT IGNORE INTO `i8j_menu_item` (`id`, `group_id`, `parent_id`, `name`, `url`, `permission`, `active`, `icon`, `sort`, `status`) VALUES
(550, 1, 0, 'AI编辑器配置', '/admin/ai_config/index', 'ai_config.*', 'ai_config', 'bi bi-robot', 80, 1),
(551, 1, 0, 'AI模板库', '/admin/ai_editor_template/index', 'ai_editor_template.*', 'ai_editor_template', 'bi bi-collection', 81, 1);

-- ============================================================
-- 系统配置
-- ============================================================
INSERT IGNORE INTO `i8j_config` (`name`, `value`, `group`) VALUES
('ai_editor_paragraph_optimize', '1', 'ai'),
('ai_editor_conversation', '1', 'ai'),
('ai_editor_conversation_timeout', '1800', 'ai'),
('ai_editor_conversation_max_token', '4096', 'ai'),
('ai_editor_format_preserve', '1', 'ai'),
('ai_editor_translate', '1', 'ai'),
('ai_editor_template_library', '1', 'ai'),
('ai_editor_snapshot', '1', 'ai'),
('ai_editor_snapshot_max', '50', 'ai'),
('ai_editor_shortcut_menu', 'alt+space', 'ai'),
('ai_editor_shortcut_optimize', 'alt+shift+o', 'ai'),
('ai_editor_shortcut_translate', 'alt+shift+t', 'ai');
