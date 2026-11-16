-- =====================================================================
-- V2.9.63 AI 助手 · 动作底座迁移（S1-T1）
-- 依据: 动作层规格说明书 v1.2
--       §4.1 i8j_agent_action / §4.6 i8j_agent_audit + i8j_agent_idempotency
--       §4.9 i8j_agent_action_alias (字段 alias_id,action_id,pattern,priority,params_rule,enabled)
-- 基线: R-1~R-6 v1.2 默认值（架构师可改写枚举/正则/配置，不动表结构）
-- 编码: UTF-8 无 BOM；表名前缀 {prefix} 由导入器替换为 i8j_
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) 动作注册表（A-4 裁定：schema 定义在代码 actions.php，本表只存运行时可变项；
--    input_schema/output_schema 列保留以兼容上游基线与后续覆盖扩展）
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}agent_action` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `action_id` varchar(100) NOT NULL DEFAULT '' COMMENT '原生ID 2~3段 R-1 正则 ^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*){1,2}$',
  `domain` varchar(30) NOT NULL DEFAULT '' COMMENT '12 domain 枚举 R-1',
  `verb` varchar(30) NOT NULL DEFAULT '' COMMENT '16 verb 枚举 R-1',
  `display_name` varchar(100) NOT NULL DEFAULT '' COMMENT '展示名',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '描述(MCP tools 描述来源)',
  `risk_level` varchar(20) NOT NULL DEFAULT 'read' COMMENT '风险级: read/write/high_risk (W1 仅 read 三态)',
  `scope_required` varchar(100) NOT NULL DEFAULT 'aicms:*:read' COMMENT '所需 scope R-4 三段冒号',
  `dry_run_supported` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'W1 仅接口占位 R-5',
  `rollback_supported` tinyint(1) NOT NULL DEFAULT 0,
  `undo_action_id` varchar(100) NOT NULL DEFAULT '' COMMENT '补偿动作',
  `rate_limit` int(10) NOT NULL DEFAULT 60 COMMENT '每分钟调用上限 S-7',
  `timeout` int(10) NOT NULL DEFAULT 10 COMMENT '秒 不复用 config/ai.php:56 的300s R-5',
  `enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '运行时开关(A-4 DB可覆盖项)',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注(A-4 DB可覆盖项)',
  `sort` int(10) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `create_time` int(10) NOT NULL DEFAULT 0,
  `update_time` int(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_action_id` (`action_id`),
  KEY `idx_domain` (`domain`),
  KEY `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI助手动作注册表(ACP-03)';

-- ---------------------------------------------------------------------
-- 2) 别名表（R-2: DB 为准、config/agent_whitelist.php 仅 seed）
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}agent_action_alias` (
  `alias_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pattern` varchar(255) NOT NULL DEFAULT '' COMMENT '问法模式(R-2 字段名 pattern)',
  `action_id` varchar(100) NOT NULL DEFAULT '' COMMENT '映射的动作ID',
  `params_rule` text COMMENT '参数抽取规则 JSON',
  `q_id` varchar(20) NOT NULL DEFAULT '' COMMENT '白名单编号 Q-01~Q-20(S-19 §2)',
  `priority` int(10) NOT NULL DEFAULT 100,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `create_time` int(10) NOT NULL DEFAULT 0,
  `update_time` int(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`alias_id`),
  UNIQUE KEY `uk_pattern` (`pattern`),
  KEY `idx_action_id` (`action_id`),
  KEY `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='意图路由别名表(R-2)';

-- ---------------------------------------------------------------------
-- 3) 审计表（R-3: 不双写 i8j_api_log，以 request_id 关联；≥180 天归档）
--    字段照抄 §4.6 全集；result 枚举含 confirmed/rejected/expired(S-2b)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}agent_audit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` varchar(64) NOT NULL DEFAULT '' COMMENT '请求唯一ID,关联 i8j_api_log',
  `channel` varchar(20) NOT NULL DEFAULT '' COMMENT '通道: api/cli/mcp/window',
  `actor_type` varchar(20) NOT NULL DEFAULT 'agent' COMMENT 'human|agent',
  `agent_name` varchar(100) NOT NULL DEFAULT '' COMMENT '智能体名称(BYO-Agent S-2b)',
  `actor_id` varchar(100) NOT NULL DEFAULT '' COMMENT 'api_key_id 或用户ID',
  `action_id` varchar(100) NOT NULL DEFAULT '',
  `q_id` varchar(20) NOT NULL DEFAULT '' COMMENT '白名单命中编号',
  `alias_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '外部别名解析记录 R-2',
  `idempotency_key` varchar(64) NOT NULL DEFAULT '',
  `request_digest` varchar(64) NOT NULL DEFAULT '' COMMENT '请求摘要哈希(S-8 不落原文)',
  `response_digest` varchar(64) NOT NULL DEFAULT '' COMMENT '响应摘要哈希',
  `diff_summary` text COMMENT 'dry-run 变更摘要(W3 真实现)',
  `prompt_digest` varchar(64) NOT NULL DEFAULT '' COMMENT '问法摘要哈希',
  `model_name` varchar(100) NOT NULL DEFAULT '' COMMENT 'W1 固定空(零模型)',
  `result` varchar(20) NOT NULL DEFAULT '' COMMENT 'success/failed/confirmed/rejected/expired',
  `error_msg` varchar(500) NOT NULL DEFAULT '',
  `duration_ms` int(10) NOT NULL DEFAULT 0,
  `confirm_actor` varchar(100) NOT NULL DEFAULT '' COMMENT '确认人(S-2b)',
  `confirm_at` int(10) NOT NULL DEFAULT 0,
  `diff_viewed` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否查看 diff',
  `create_time` int(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_request_id` (`request_id`),
  KEY `idx_action_id` (`action_id`),
  KEY `idx_actor` (`actor_type`, `actor_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI助手动作审计表(ACP-09,≥180天)';

-- ---------------------------------------------------------------------
-- 4) 幂等表（R-5: 唯一键 (action_id, idempotency_key)，24h 过期）
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}agent_idempotency` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `action_id` varchar(100) NOT NULL DEFAULT '',
  `idempotency_key` varchar(64) NOT NULL DEFAULT '',
  `request_id` varchar(64) NOT NULL DEFAULT '' COMMENT '首次执行的请求ID',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending/running/success/failed',
  `response_digest` varchar(64) NOT NULL DEFAULT '',
  `response_json` mediumtext COMMENT '首次执行响应(重放用)',
  `expires_at` int(10) NOT NULL DEFAULT 0 COMMENT '24h 过期 R-5',
  `create_time` int(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_action_key` (`action_id`, `idempotency_key`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI助手幂等表(R-5,24h)';

-- ---------------------------------------------------------------------
-- 4.1) 模拟数据域（A-6 裁定：物理隔离，不 ALTER 主表、不往真实业务表写 mock 行）
--      mockdata.* 只写这两张表；只读动作按 params 决定读主表还是 mock 表
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}mock_content` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '模拟标题',
  `content` mediumtext COMMENT '模拟正文',
  `status` tinyint(4) NOT NULL DEFAULT 2 COMMENT '0草稿/1待审/2已发布',
  `cate_id` int(10) NOT NULL DEFAULT 0,
  `batch_key` varchar(64) NOT NULL DEFAULT '' COMMENT '灌数批次键(幂等)',
  `data_origin` varchar(20) NOT NULL DEFAULT 'mock' COMMENT '固定 mock',
  `create_time` int(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_batch` (`batch_key`),
  KEY `idx_origin` (`data_origin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模拟内容域(A-6 物理隔离)';

CREATE TABLE IF NOT EXISTS `{prefix}mock_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '模拟模板名',
  `description` varchar(500) NOT NULL DEFAULT '',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `batch_key` varchar(64) NOT NULL DEFAULT '' COMMENT '灌数批次键(幂等)',
  `data_origin` varchar(20) NOT NULL DEFAULT 'mock',
  `create_time` int(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_batch` (`batch_key`),
  KEY `idx_origin` (`data_origin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模拟模板域(A-6 物理隔离)';

-- ---------------------------------------------------------------------
-- 5) seed：24 个动作的运行时可变项（INSERT IGNORE 幂等，可重复导入）
--    schema/input/output/field_map 定义在代码 app/common/service/agent/actions.php（A-4 裁定）
--    本表仅存 enabled/timeout/rate_limit/remark 可变项；description 为冗余展示字段
-- ---------------------------------------------------------------------
INSERT IGNORE INTO `{prefix}agent_action`
(`action_id`,`domain`,`verb`,`display_name`,`description`,`risk_level`,`scope_required`,`dry_run_supported`,`rollback_supported`,`undo_action_id`,`rate_limit`,`timeout`,`enabled`,`remark`,`sort`,`status`,`create_time`,`update_time`) VALUES
('content.article.list','content','list','文章列表查询','支持计数/状态过滤/关键词搜索','read','aicms:content:read',0,0,'',60,10,1,'Q-01/Q-02/Q-04/Q-05',10,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('content.article.read','content','read','文章摘要读取','按 id 读取内容摘要','read','aicms:content:read',0,0,'',60,10,1,'Q-06',11,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('content.category.tree','content','tree','栏目树查询','栏目树及各栏目文章数(聚合)','read','aicms:content:read',0,0,'',60,10,1,'Q-03',12,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('template.store.list','template','list','模板商店列表','模板商店条目列表/计数','read','aicms:template:read',0,0,'',60,10,1,'Q-10',20,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('template.store.detail','template','detail','模板详情','按 id 读取模板完整明细','read','aicms:template:read',0,0,'',60,10,1,'',21,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('template.install.list','template','list','模板安装记录','最近安装的模板记录','read','aicms:template:read',0,0,'',60,10,1,'Q-11',22,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('template.license.list','template','list','模板授权记录','模板授权列表','read','aicms:template:read',0,0,'',60,10,1,'Q-12/Q-14',23,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('theme.market.list','theme','list','主题市场列表','主题市场在售主题(is_market=1)','read','aicms:theme:read',0,0,'',60,10,1,'',30,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('theme.record.list','theme','list','主题生成记录','AI 主题生成记录列表','read','aicms:theme:read',0,0,'',60,10,1,'Q-07/Q-08',31,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('theme.record.detail','theme','detail','主题生成记录明细','按 id 读取记录含校验结果','read','aicms:theme:read',0,0,'',60,10,1,'Q-09',32,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('theme.palette.list','theme','list','主题配色列表','AI 主题配色方案列表','read','aicms:theme:read',0,0,'',60,10,1,'',33,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('theme.version.history','theme','history','主题操作历史','操作历史非文件版本快照(D-4a)','read','aicms:theme:read',0,0,'',60,10,1,'D-4裁定a',34,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('member.list','member','list','会员列表','会员列表/计数(两段ID)','read','aicms:member:read',0,0,'',60,10,1,'Q-12/Q-13',40,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('member.detail','member','detail','会员明细','按 id 读取会员明细(两段ID)','read','aicms:member:read',0,0,'',60,10,1,'',41,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('config.get','config','get','配置读取','按 group 读取配置且密钥脱敏(两段ID)','read','aicms:config:read',0,0,'',60,10,1,'Q-16 S-8密钥不出域',50,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('system.version','system','version','系统版本','版本号与构建时间(两段ID)','read','aicms:system:read',0,0,'',60,10,1,'Q-14 D-2a runtime',51,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('system.health','system','health','系统健康检查','PHP/MySQL/磁盘/服务探测(两段ID)','read','aicms:system:read',0,0,'',60,10,1,'Q-15 D-2a runtime',52,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('ai.log.list','ai','list','AI 调用日志','AI 调用日志列表','read','aicms:ai:read',0,0,'',60,10,1,'Q-17',60,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('api.log.list','api','list','API 调用日志','HTTP API 调用日志列表','read','aicms:api:read',0,0,'',60,10,1,'Q-18',61,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('stat.overview','stat','overview','数据概览','全站四项关键统计(两段ID)','read','aicms:stat:read',0,0,'',60,10,1,'Q-19 60s缓存',62,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('report.definition.list','report','list','报表定义列表','预置报表定义列表','read','aicms:report:read',0,0,'',60,10,1,'',63,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('page.context','page','context','页面上下文','当前页面名称/模块/可用动作(两段ID)','read','aicms:page:read',0,0,'',60,10,1,'Q-20 D-2a router',64,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('mockdata.content.batch_create','mockdata','batch_create','批量创建模拟内容','只写 i8j_mock_content 物理隔离','read','aicms:mockdata:batch',1,0,'',10,30,1,'S-9 默认不授予',90,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('mockdata.template.batch_create','mockdata','batch_create','批量创建模拟模板','只写 i8j_mock_template 物理隔离','read','aicms:mockdata:batch',1,0,'',10,30,1,'S-9 默认不授予',91,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP());
