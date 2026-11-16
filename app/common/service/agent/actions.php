<?php
declare(strict_types=1);

/**
 * AI 助手 · 24 个动作代码侧定义（S1-T4）
 *
 * 依据：
 *  - 动作层规格 v1.2 §4.2（24 个完整 ID 全集，一个不改 —— RD-4）
 *  - 动作层规格 v1.2 §4.12 R-1（12 domain / 16 verb / 2~3 段正则 / 两段限制）
 *  - W1规格补充 §2（20 条白名单三元组的必返回字段，照抄不自拍）
 *  - v1.1 开发计划 §3.3（数据源矩阵）/ §3.4（field_map 逐条）/ §五 D-2/D-4 裁定
 *
 * 字段名口径（D-1 裁定 a）：output_schema 对外用 PRD/白名单字段名，
 * 对内经 field_map 映射到真实表字段；meta 回传 field_map_applied。
 *
 * source_table 口径（D-2 裁定 a，四类枚举）：
 *   真实表名 | runtime:php | router:i8j_menu_item | filesystem:git
 *   theme.version.history 按 D-4 裁定 a 取 i8j_theme_log（操作历史，非版本快照）
 *
 * risk_level：W1 仅 read 三态（RD-5），mockdata 两动作 risk_level=read
 * 但 scope=aicms:mockdata:batch 需单独授权、默认不授予（R-4 默认集合不含）。
 */

return [
    // ============================== 内容域（3）==============================

    [
        'action_id' => 'content.article.list',
        'domain' => 'content', 'verb' => 'list',
        'display_name' => '文章列表查询',
        'description' => '按条件查询内容列表；支持 count_only 计数、按状态过滤、关键词搜索（Q-01/Q-02/Q-04/Q-05）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:content:read',
        'source_table' => 'i8j_content',
        'data_origin' => 'real',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'count_only' => ['type' => 'boolean', 'description' => '仅返回计数（Q-01/Q-04）'],
                'status' => ['type' => 'string', 'enum' => ['draft', 'published', 'pending'], 'description' => '状态过滤（Q-04 用 draft）'],
                'keyword' => ['type' => 'string', 'description' => '标题关键词（Q-05）'],
                'cate_id' => ['type' => 'integer'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 5],
                'order' => ['type' => 'string', 'default' => 'create_time desc'],
            ],
            'required' => [],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'total_count' => ['type' => 'integer', 'description' => '满足条件的总数（Q-01/Q-04 必返）'],
                'rows' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                    'id' => ['type' => 'integer'],
                    'title' => ['type' => 'string'],
                    'create_time' => ['type' => 'string'],
                    'status' => ['type' => 'string'],
                ]]],
            ],
            'required' => ['total_count', 'rows'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => 'Q-01/Q-02/Q-04/Q-05 白名单复用',
    ],

    [
        'action_id' => 'content.article.read',
        'domain' => 'content', 'verb' => 'read',
        'display_name' => '文章摘要读取',
        'description' => '按 id 读取单条内容摘要（Q-06；read=摘要口径）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:content:read',
        'source_table' => 'i8j_content',
        'data_origin' => 'real',
        'input_schema' => [
            'type' => 'object',
            'properties' => ['id' => ['type' => 'integer', 'description' => '内容 id（Q-06 抽取）']],
            'required' => ['id'],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'content_digest' => ['type' => 'string', 'description' => '摘要，表内无字段，现算（field_map compute）'],
                'status' => ['type' => 'string'],
                'create_time' => ['type' => 'string'],
            ],
            'required' => ['id', 'title', 'content_digest', 'status', 'create_time'],
        ],
        'field_map' => [
            'content_digest' => ['compute' => 'digest:content,120'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => 'Q-06',
    ],

    [
        'action_id' => 'content.category.tree',
        'domain' => 'content', 'verb' => 'tree',
        'display_name' => '栏目树查询',
        'description' => '返回栏目树及各栏目文章数（Q-03；article_count 为聚合计算字段）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:content:read',
        'source_table' => 'i8j_cate',
        'data_origin' => 'real',
        'input_schema' => ['type' => 'object', 'properties' => [], 'required' => []],
        'output_schema' => [
            'type' => 'object',
            'properties' => ['rows' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'parent_id' => ['type' => 'integer'],
                'article_count' => ['type' => 'integer', 'description' => '聚合计算：GROUP BY cate_id 计数，表内无字段'],
            ]]]],
            'required' => ['rows'],
        ],
        'field_map' => [
            'article_count' => ['compute' => 'aggregate:content.count_by_cate'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => 'Q-03；聚合一次 GROUP BY 防止 N+1',
    ],

    // ============================== 模板域（4）==============================

    [
        'action_id' => 'template.store.list',
        'domain' => 'template', 'verb' => 'list',
        'display_name' => '模板商店列表',
        'description' => '模板商店条目列表/计数（Q-10）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:template:read',
        'source_table' => 'i8j_template_store',
        'data_origin' => 'real',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'count_only' => ['type' => 'boolean'],
                'keyword' => ['type' => 'string'],
                'paid_only' => ['type' => 'boolean', 'description' => 'price>0 过滤（Q-04 付费模板口径）'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 5],
            ],
            'required' => [],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'total_count' => ['type' => 'integer'],
                'rows' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'price' => ['type' => 'number'],
                    'status' => ['type' => 'string'],
                ]]],
            ],
            'required' => ['total_count', 'rows'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => 'Q-10',
    ],

    [
        'action_id' => 'template.store.detail',
        'domain' => 'template', 'verb' => 'detail',
        'display_name' => '模板详情',
        'description' => '按 id 读取模板完整明细（detail=明细口径）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:template:read',
        'source_table' => 'i8j_template_store',
        'data_origin' => 'real',
        'input_schema' => [
            'type' => 'object',
            'properties' => ['id' => ['type' => 'integer']],
            'required' => ['id'],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'price' => ['type' => 'number'],
                'status' => ['type' => 'string'],
                'version' => ['type' => 'string'],
                'description' => ['type' => 'string'],
            ],
            'required' => ['id', 'name', 'status'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => '',
    ],

    [
        'action_id' => 'template.install.list',
        'domain' => 'template', 'verb' => 'list',
        'display_name' => '模板安装记录',
        'description' => '最近安装的模板记录（Q-11）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:template:read',
        'source_table' => 'i8j_template_install',
        'data_origin' => 'real',
        'input_schema' => [
            'type' => 'object',
            'properties' => ['limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 5]],
            'required' => [],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => ['rows' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                'id' => ['type' => 'integer'],
                'template_name' => ['type' => 'string', 'description' => '对外名，真实字段 theme_name'],
                'install_time' => ['type' => 'string', 'description' => '对外名，真实字段 create_time(int 时间戳)'],
                'version' => ['type' => 'string', 'description' => '安装表无此字段，取 template_store.version'],
            ]]]],
            'required' => ['rows'],
        ],
        'field_map' => [
            'template_name' => ['from' => 'theme_name'],
            'install_time' => ['from' => 'create_time', 'type' => 'datetime'],
            'version' => ['from' => '__join__:template_store.version'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => 'Q-11；D-1 field_map 三处映射',
    ],

    [
        'action_id' => 'template.license.list',
        'domain' => 'template', 'verb' => 'list',
        'display_name' => '模板授权记录',
        'description' => '模板授权列表（Q-12/Q-14）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:template:read',
        'source_table' => 'i8j_template_license',
        'data_origin' => 'real',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'status' => ['type' => 'string'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10],
            ],
            'required' => [],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => ['rows' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                'id' => ['type' => 'integer'],
                'license_code' => ['type' => 'string'],
                'status' => ['type' => 'string'],
                'expires' => ['type' => 'string', 'description' => '对外名，真实字段 expires_at'],
            ]]]],
            'required' => ['rows'],
        ],
        'field_map' => [
            'expires' => ['from' => 'expires_at', 'type' => 'datetime'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => 'Q-12/Q-14；expires_at→expires',
    ],

    // ============================== 主题域（5）==============================

    [
        'action_id' => 'theme.market.list',
        'domain' => 'theme', 'verb' => 'list',
        'display_name' => '主题市场列表',
        'description' => '主题市场在售主题（is_market=1）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:theme:read',
        'source_table' => 'i8j_theme_info',
        'data_origin' => 'real',
        'input_schema' => [
            'type' => 'object',
            'properties' => ['limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10]],
            'required' => [],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => ['rows' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'version' => ['type' => 'string'],
                'avg_rating' => ['type' => 'number'],
                'install_count' => ['type' => 'integer'],
            ]]]],
            'required' => ['rows'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => '',
    ],

    [
        'action_id' => 'theme.record.list',
        'domain' => 'theme', 'verb' => 'list',
        'display_name' => '主题生成记录',
        'description' => 'AI 主题生成记录列表（Q-07/Q-08；status in (-1,-2,4) 即失败集）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:theme:read',
        'source_table' => 'i8j_ai_theme_record',
        'data_origin' => 'real',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'count_only' => ['type' => 'boolean'],
                'status' => ['type' => 'string', 'description' => 'record 状态；failed 表示失败集 (-1,-2,4)'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 5],
            ],
            'required' => [],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'total_count' => ['type' => 'integer'],
                'rows' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string', 'description' => '对外名，真实字段 theme_name'],
                    'status' => ['type' => 'integer'],
                    'quality_score' => ['type' => 'integer'],
                    'error_msg' => ['type' => 'string'],
                    'create_time' => ['type' => 'string', 'description' => '对外名，真实字段 created_at(datetime)'],
                ]]],
            ],
            'required' => ['total_count', 'rows'],
        ],
        'field_map' => [
            'name' => ['from' => 'theme_name'],
            'create_time' => ['from' => 'created_at', 'type' => 'datetime'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => 'Q-07/Q-08',
    ],

    [
        'action_id' => 'theme.record.detail',
        'domain' => 'theme', 'verb' => 'detail',
        'display_name' => '主题生成记录明细',
        'description' => '按 id 读取主题生成记录（含校验结果）（Q-09）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:theme:read',
        'source_table' => 'i8j_ai_theme_record',
        'data_origin' => 'real',
        'input_schema' => [
            'type' => 'object',
            'properties' => ['id' => ['type' => 'integer']],
            'required' => ['id'],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'status' => ['type' => 'integer'],
                'quality_score' => ['type' => 'integer'],
                'validate_result' => ['type' => 'object', 'description' => '校验结果 JSON'],
                'error_msg' => ['type' => 'string'],
                'name' => ['type' => 'string', 'description' => '对外名，真实字段 theme_name'],
            ],
            'required' => ['id', 'status', 'quality_score', 'validate_result', 'error_msg'],
        ],
        'field_map' => [
            'name' => ['from' => 'theme_name'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => 'Q-09',
    ],

    [
        'action_id' => 'theme.palette.list',
        'domain' => 'theme', 'verb' => 'list',
        'display_name' => '主题配色列表',
        'description' => 'AI 主题配色方案列表',
        'risk_level' => 'read',
        'scope_required' => 'aicms:theme:read',
        'source_table' => 'i8j_ai_theme_palette',
        'data_origin' => 'real',
        'input_schema' => ['type' => 'object', 'properties' => [], 'required' => []],
        'output_schema' => [
            'type' => 'object',
            'properties' => ['rows' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'colors' => ['type' => 'string'],
            ]]]],
            'required' => ['rows'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => '',
    ],

    [
        'action_id' => 'theme.version.history',
        'domain' => 'theme', 'verb' => 'history',
        'display_name' => '主题操作历史',
        'description' => '主题操作历史（install/rollback/update/rate）。注意：本动作返回【操作历史】而非文件版本快照，真实版本快照在 Git/文件系统（D-4 裁定 a）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:theme:read',
        'source_table' => 'i8j_theme_log',
        'data_origin' => 'real',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'theme_name' => ['type' => 'string', 'description' => '主题名，用于过滤某主题的操作历史'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10],
            ],
            'required' => [],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => ['rows' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                'id' => ['type' => 'integer'],
                'theme_name' => ['type' => 'string', 'description' => '对外名，表内为 theme_id，JOIN theme_info.name'],
                'action' => ['type' => 'string'],
                'detail' => ['type' => 'string'],
                'create_time' => ['type' => 'string'],
            ]]]],
            'required' => ['rows'],
        ],
        'field_map' => [
            'theme_name' => ['from' => '__join__:theme_info.name'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => 'D-4 裁定 a：操作历史（i8j_theme_log），非 Git 版本快照；theme_id→theme_info.name',
    ],

    // ============================== 会员域（2，两段 ID）==============================

    [
        'action_id' => 'member.list',
        'domain' => 'member', 'verb' => 'list',
        'display_name' => '会员列表',
        'description' => '会员列表/计数（Q-12/Q-13；两段 ID）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:member:read',
        'source_table' => 'i8j_member',
        'data_origin' => 'real',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'count_only' => ['type' => 'boolean'],
                'keyword' => ['type' => 'string'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 5],
                'order' => ['type' => 'string', 'default' => 'create_time desc'],
            ],
            'required' => [],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'total_count' => ['type' => 'integer'],
                'rows' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                    'id' => ['type' => 'integer'],
                    'username' => ['type' => 'string'],
                    'create_time' => ['type' => 'string'],
                ]]],
            ],
            'required' => ['total_count', 'rows'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => 'Q-12/Q-13',
    ],

    [
        'action_id' => 'member.detail',
        'domain' => 'member', 'verb' => 'detail',
        'display_name' => '会员明细',
        'description' => '按 id 读取会员完整明细（两段 ID）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:member:read',
        'source_table' => 'i8j_member',
        'data_origin' => 'real',
        'input_schema' => [
            'type' => 'object',
            'properties' => ['id' => ['type' => 'integer']],
            'required' => ['id'],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'username' => ['type' => 'string'],
                'email' => ['type' => 'string'],
                'create_time' => ['type' => 'string'],
            ],
            'required' => ['id', 'username'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => '',
    ],

    // ============================== 配置/系统域（3，两段 ID ×3）==============================

    [
        'action_id' => 'config.get',
        'domain' => 'config', 'verb' => 'get',
        'display_name' => '配置读取',
        'description' => '按 group 读取配置；密钥类字段一律脱敏为布尔 SET/EMPTY（Q-16；两段 ID）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:config:read',
        'source_table' => 'i8j_config',
        'data_origin' => 'real',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'group' => ['type' => 'string', 'default' => 'ai', 'description' => '配置分组（Q-16 用 ai）'],
            ],
            'required' => [],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'group' => ['type' => 'string'],
                'items' => ['type' => 'object', 'description' => '键值对；密钥键只含 model_name/provider/api_base_set(bool)/api_key_set(bool)'],
            ],
            'required' => ['group', 'items'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => 'Q-16；S-8 密钥不出域：仅返回 api_key_set 布尔，绝不返回密钥值',
    ],

    [
        'action_id' => 'system.version',
        'domain' => 'system', 'verb' => 'version',
        'display_name' => '系统版本',
        'description' => '系统版本号与构建时间（Q-14；两段 ID）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:system:read',
        'source_table' => 'runtime:php',
        'data_origin' => 'real',
        'input_schema' => ['type' => 'object', 'properties' => [], 'required' => []],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'version' => ['type' => 'string'],
                'build_time' => ['type' => 'string'],
            ],
            'required' => ['version', 'build_time'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => 'D-2 裁定 a：source_table=runtime:php（无表动作）',
    ],

    [
        'action_id' => 'system.health',
        'domain' => 'system', 'verb' => 'health',
        'display_name' => '系统健康检查',
        'description' => 'PHP/MySQL/磁盘/服务状态探测（Q-15；两段 ID）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:system:read',
        'source_table' => 'runtime:php',
        'data_origin' => 'real',
        'input_schema' => ['type' => 'object', 'properties' => [], 'required' => []],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'php_version' => ['type' => 'string'],
                'mysql_version' => ['type' => 'string'],
                'disk_free' => ['type' => 'string'],
                'service_status' => ['type' => 'array', 'items' => ['type' => 'object']],
            ],
            'required' => ['php_version', 'mysql_version', 'disk_free', 'service_status'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => 'D-2 裁定 a：runtime 探测含 mysql/disk 检查，统一取值 runtime:php',
    ],

    // ============================== 日志/统计/页面域（5）==============================

    [
        'action_id' => 'ai.log.list',
        'domain' => 'ai', 'verb' => 'list',
        'display_name' => 'AI 调用日志',
        'description' => 'AI 调用日志列表（Q-17）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:ai:read',
        'source_table' => 'i8j_ai_log',
        'data_origin' => 'real',
        'input_schema' => [
            'type' => 'object',
            'properties' => ['limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10]],
            'required' => [],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => ['rows' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                'id' => ['type' => 'integer'],
                'model_name' => ['type' => 'string', 'description' => '对外名，真实字段 model_id JOIN ai_model'],
                'action' => ['type' => 'string', 'description' => '对外名，真实字段 task_type'],
                'result' => ['type' => 'string', 'description' => '对外名，真实字段 status(1/2/3) 枚举映射 success/failed/degraded'],
                'duration_ms' => ['type' => 'integer'],
                'create_time' => ['type' => 'string'],
            ]]]],
            'required' => ['rows'],
        ],
        'field_map' => [
            'model_name' => ['from' => '__join__:ai_model.model_name'],
            'action' => ['from' => 'task_type'],
            'result' => ['from' => 'status', 'enum' => ['1' => 'success', '2' => 'failed', '3' => 'degraded']],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => 'Q-17；D-1 field_map 三处映射',
    ],

    [
        'action_id' => 'api.log.list',
        'domain' => 'api', 'verb' => 'list',
        'display_name' => 'API 调用日志',
        'description' => 'HTTP API 调用日志列表（Q-18）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:api:read',
        'source_table' => 'i8j_api_log',
        'data_origin' => 'real',
        'input_schema' => [
            'type' => 'object',
            'properties' => ['limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10]],
            'required' => [],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => ['rows' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                'id' => ['type' => 'integer'],
                'endpoint' => ['type' => 'string'],
                'method' => ['type' => 'string'],
                'status' => ['type' => 'integer', 'description' => '对外名，真实字段 status_code'],
                'ip' => ['type' => 'string', 'description' => '对外名，真实字段 ip_address'],
                'create_time' => ['type' => 'string'],
            ]]]],
            'required' => ['rows'],
        ],
        'field_map' => [
            'ip' => ['from' => 'ip_address'],
            'status' => ['from' => 'status_code'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => 'Q-18；D-1 field_map 两处映射',
    ],

    [
        'action_id' => 'stat.overview',
        'domain' => 'stat', 'verb' => 'overview',
        'display_name' => '数据概览',
        'description' => '全站四项关键统计（Q-19；两段 ID）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:stat:read',
        'source_table' => 'i8j_content,i8j_member,i8j_ai_theme_record',
        'data_origin' => 'real',
        'input_schema' => ['type' => 'object', 'properties' => [], 'required' => []],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'article_count' => ['type' => 'integer'],
                'member_count' => ['type' => 'integer'],
                'theme_count' => ['type' => 'integer'],
                'today_generate' => ['type' => 'integer'],
            ],
            'required' => ['article_count', 'member_count', 'theme_count', 'today_generate'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => 'Q-19；4 表聚合（60s 缓存）',
    ],

    [
        'action_id' => 'report.definition.list',
        'domain' => 'report', 'verb' => 'list',
        'display_name' => '报表定义列表',
        'description' => '预置报表定义列表',
        'risk_level' => 'read',
        'scope_required' => 'aicms:report:read',
        'source_table' => 'i8j_report_definition',
        'data_origin' => 'real',
        'input_schema' => ['type' => 'object', 'properties' => [], 'required' => []],
        'output_schema' => [
            'type' => 'object',
            'properties' => ['rows' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string', 'description' => '对外名，真实字段 report_name'],
                'type' => ['type' => 'string', 'description' => '对外名，真实字段 report_type'],
            ]]]],
            'required' => ['rows'],
        ],
        'field_map' => [
            'name' => ['from' => 'report_name'],
            'type' => ['from' => 'report_type'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => 'report_name/report_type 映射',
    ],

    [
        'action_id' => 'page.context',
        'domain' => 'page', 'verb' => 'context',
        'display_name' => '页面上下文',
        'description' => '当前后台页面的名称、模块与可用只读动作清单（Q-20；两段 ID）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:page:read',
        'source_table' => 'router:i8j_menu_item',
        'data_origin' => 'real',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'current_path' => ['type' => 'string', 'description' => '前端上传的当前路径，不含任何 Cookie/会话凭证（S-19 §2.5）'],
            ],
            'required' => ['current_path'],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'page_name' => ['type' => 'string'],
                'module' => ['type' => 'string'],
                'available_actions' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'required' => ['page_name', 'module', 'available_actions'],
        ],
        'enabled' => 1, 'timeout' => 10, 'rate_limit' => 60,
        'remark' => 'Q-20；缺 current_path 回 Q-20-NO-CTX；D-2 裁定 a：router:i8j_menu_item',
    ],

    // ============================== 批量模拟数据域（2，写 mock 域）==============================

    [
        'action_id' => 'mockdata.content.batch_create',
        'domain' => 'mockdata', 'verb' => 'batch_create',
        'display_name' => '批量创建模拟内容',
        'description' => '批量创建模拟内容数据（S-9 物理隔离：只写 i8j_mock_content，禁止触碰真实业务表）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:mockdata:batch',
        'source_table' => 'i8j_mock_content',
        'data_origin' => 'mock',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'count' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 500],
                'dry_run' => ['type' => 'boolean', 'default' => false, 'description' => 'W1 仅接口占位，不落库只返回计划'],
            ],
            'required' => ['count'],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'created' => ['type' => 'integer'],
                'dry_run' => ['type' => 'boolean'],
                'diff_summary' => ['type' => 'string'],
            ],
            'required' => ['created'],
        ],
        'enabled' => 1, 'timeout' => 30, 'rate_limit' => 10,
        'remark' => 'S-9：只写 mock 表；R-4：scope 默认不授予，须单独授权',
    ],

    [
        'action_id' => 'mockdata.template.batch_create',
        'domain' => 'mockdata', 'verb' => 'batch_create',
        'display_name' => '批量创建模拟模板',
        'description' => '批量创建模拟模板数据（S-9 物理隔离：只写 i8j_mock_template）',
        'risk_level' => 'read',
        'scope_required' => 'aicms:mockdata:batch',
        'source_table' => 'i8j_mock_template',
        'data_origin' => 'mock',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'count' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 500],
                'dry_run' => ['type' => 'boolean', 'default' => false],
            ],
            'required' => ['count'],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'created' => ['type' => 'integer'],
                'dry_run' => ['type' => 'boolean'],
                'diff_summary' => ['type' => 'string'],
            ],
            'required' => ['created'],
        ],
        'enabled' => 1, 'timeout' => 30, 'rate_limit' => 10,
        'remark' => 'S-9：只写 mock 表；R-4：scope 默认不授予，须单独授权',
    ],
];
