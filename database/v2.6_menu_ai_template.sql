-- =====================================================
-- AI-CMS V2.6 升级脚本
-- 1. AI内容模板表 (i8j_ai_template)
-- 2. ai_batch_task 新增 template_id 关联字段
-- =====================================================

-- -----------------------------------------------------
-- AI内容模板表
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `i8j_ai_template` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL COMMENT '模板名称',
    `description` varchar(500) DEFAULT '' COMMENT '模板描述',
    `generate_mode` enum('nlp','example') DEFAULT 'nlp' COMMENT '生成模式: nlp自然语言/example参考示例',
    `cate_id` int UNSIGNED DEFAULT 0 COMMENT '默认内容分类ID',
    `model_id` int UNSIGNED DEFAULT 0 COMMENT '默认AI模型ID',
    `style` varchar(30) DEFAULT 'default' COMMENT '写作风格: default/formal/casual/marketing/technical',
    
    -- 生成规则
    `title_rule` text COMMENT '标题生成规则(NL描述)',
    `content_rule` text COMMENT '内容生成规则(NL描述)',
    `keyword_hint` varchar(500) DEFAULT '' COMMENT '默认关键词提示',
    
    -- 自定义字段配置 JSON
    -- [{"name":"产品型号","type":"text","rule":"含型号参数"}, ...]
    `fields_config` text COMMENT '自定义字段配置JSON',
    
    -- 配图配置 JSON
    -- {"thumb":"0","images":"0","count":0,"source":"0"}
    -- thumb: 0不上传 1AI生图 2图库选
    -- images: 0不配图 1AI配图 2随机配图
    `image_config` text COMMENT '配图配置JSON',
    
    -- 发布默认值
    `publisher` varchar(50) DEFAULT '' COMMENT '默认作者',
    `contact` varchar(100) DEFAULT '' COMMENT '默认联系方式',
    
    -- 参考示例（example模式专用）
    `example_title` varchar(255) DEFAULT '' COMMENT '示例标题',
    `example_content` longtext COMMENT '示例正文内容（用于风格学习）',
    
    -- 控制
    `default_batch` smallint UNSIGNED DEFAULT 10 COMMENT '默认批量数量(1-100)',
    `status` tinyint DEFAULT 1 COMMENT '状态: 0禁用 1启用',
    `sort` int DEFAULT 0 COMMENT '排序权重',
    `create_time` datetime DEFAULT NULL,
    `update_time` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_cate` (`cate_id`),
    KEY `idx_mode` (`generate_mode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI内容生成模板表(V2.6)';

-- -----------------------------------------------------
-- ai_batch_task 新增 template_id 关联字段
-- -----------------------------------------------------
ALTER TABLE `i8j_ai_batch_task`
ADD COLUMN `template_id` int UNSIGNED DEFAULT 0 COMMENT '关联AI模板ID' AFTER `id`,
ADD KEY `idx_template` (`template_id`);
