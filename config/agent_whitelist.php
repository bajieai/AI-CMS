<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

/**
 * AI 助手 · 白名单问法清单（S3-T1 · ACP-15 / E-1）
 *
 * 依据 S-19《W1 规格补充》§2 逐条照抄：问法 → 动作 → 必返回字段 三元组，一个不改。
 * R-2 裁定：本表仅作 seed —— `i8j_agent_action_alias` 有记录时以 DB 为准；
 * 别名表为空时由 IntentRouter 将本表播种进 DB（此后 DB 增删即为运行时权威）。
 *
 * 匹配规则（S-19 §3.1 七步算法）与全部话术见 `app/common/service/agent/IntentRouter.php`。
 */
return [
    // 协议版本（别名播种时写入 params_rule.seed_version）
    'version' => '1.0',

    // Q-01 ~ Q-20 三元组（S-19 §2，一字不改）
    'items' => [
        // ---- 内容域（6 条） ----
        [
            'q_id' => 'Q-01',
            'keywords' => ['有多少文章', '文章数量', '文章统计'],
            'action_id' => 'content.article.list',
            'params' => ['count_only' => true],
            'return_fields' => ['total_count'],
            'example' => '有多少文章？',
        ],
        [
            'q_id' => 'Q-02',
            'keywords' => ['最新文章', '最近发布的文章'],
            'action_id' => 'content.article.list',
            'params' => ['order' => 'create_time desc', 'limit' => 5],
            'return_fields' => ['id', 'title', 'create_time', 'status'],
            'example' => '最新文章有哪些？',
        ],
        [
            'q_id' => 'Q-03',
            'keywords' => ['这个栏目', '栏目有哪些', '栏目列表'],
            'action_id' => 'content.category.tree',
            'params' => [],
            'return_fields' => ['id', 'name', 'parent_id', 'article_count'],
            'example' => '这个栏目有哪些文章？',
        ],
        [
            'q_id' => 'Q-04',
            'keywords' => ['待审核', '多少草稿', '草稿数量'],
            'action_id' => 'content.article.list',
            'params' => ['status' => 'draft'],
            'return_fields' => ['total_count', 'id', 'title'],
            'example' => '有多少草稿？',
        ],
        [
            'q_id' => 'Q-05',
            'keywords' => ['搜索'],
            'action_id' => 'content.article.list',
            'params' => ['_extract' => 'keyword'],
            'return_fields' => ['id', 'title', 'create_time'],
            'example' => '搜索《建站教程》',
        ],
        [
            'q_id' => 'Q-06',
            'keywords' => ['这篇文章', 'id='],
            'action_id' => 'content.article.read',
            'params' => ['_extract' => 'id'],
            'return_fields' => ['id', 'title', 'content_digest', 'status', 'create_time'],
            'example' => 'id=5 这篇文章',
        ],

        // ---- 主题/模板域（5 条） ----
        [
            'q_id' => 'Q-07',
            'keywords' => ['主题记录', '生成过多少套', '主题列表'],
            'action_id' => 'theme.record.list',
            'params' => [],
            'return_fields' => ['total_count', 'id', 'name', 'status', 'quality_score', 'create_time'],
            'example' => '生成过多少套主题？',
        ],
        [
            'q_id' => 'Q-08',
            'keywords' => ['生成失败', '哪些没通过', '失败的'],
            'action_id' => 'theme.record.list',
            'params' => ['status' => 'failed'],
            'return_fields' => ['id', 'name', 'status', 'error_msg'],
            'example' => '哪些主题生成失败了？',
        ],
        [
            'q_id' => 'Q-09',
            'keywords' => ['这个主题为什么没过', '校验结果'],
            'action_id' => 'theme.record.detail',
            'params' => ['_extract' => 'id'],
            'return_fields' => ['id', 'status', 'quality_score', 'validate_result', 'error_msg'],
            'example' => 'id=3 这个主题为什么没过',
        ],
        [
            'q_id' => 'Q-10',
            'keywords' => ['模板市场', '有多少模板'],
            'action_id' => 'template.store.list',
            'params' => [],
            'return_fields' => ['total_count', 'id', 'name', 'price', 'status'],
            'example' => '有多少模板？',
        ],
        [
            'q_id' => 'Q-11',
            'keywords' => ['已安装的模板', '模板安装记录'],
            'action_id' => 'template.install.list',
            'params' => [],
            'return_fields' => ['id', 'template_name', 'install_time', 'version'],
            'example' => '已安装的模板有哪些？',
        ],

        // ---- 用户/系统域（5 条） ----
        [
            'q_id' => 'Q-12',
            'keywords' => ['有多少会员', '用户数量'],
            'action_id' => 'member.list',
            'params' => ['count_only' => true],
            'return_fields' => ['total_count'],
            'example' => '有多少会员？',
        ],
        [
            'q_id' => 'Q-13',
            'keywords' => ['最新注册', '新用户'],
            'action_id' => 'member.list',
            'params' => ['order' => 'create_time desc', 'limit' => 5],
            'return_fields' => ['id', 'username', 'create_time'],
            'example' => '最新注册的用户有哪些？',
        ],
        [
            'q_id' => 'Q-14',
            'keywords' => ['系统版本', '是什么版本'],
            'action_id' => 'system.version',
            'params' => [],
            'return_fields' => ['version', 'build_time'],
            'example' => '系统是什么版本？',
        ],
        [
            'q_id' => 'Q-15',
            'keywords' => ['系统健康', '状态怎么样', '有没有异常'],
            'action_id' => 'system.health',
            'params' => [],
            'return_fields' => ['php_version', 'mysql_version', 'disk_free', 'service_status'],
            'example' => '系统状态怎么样？',
        ],
        [
            'q_id' => 'Q-16',
            'keywords' => ['AI 配置', '用了什么模型', '模型列表'],
            'action_id' => 'config.get',
            'params' => ['group' => 'ai'],
            'return_fields' => ['model_name', 'provider', 'api_base_set', 'api_key_set'],
            'example' => 'AI 用了什么模型？',
        ],

        // ---- 日志/统计域（4 条） ----
        [
            'q_id' => 'Q-17',
            'keywords' => ['AI 调用日志', '最近调用了什么'],
            'action_id' => 'ai.log.list',
            'params' => ['limit' => 10],
            'return_fields' => ['id', 'model_name', 'action', 'result', 'duration_ms', 'create_time'],
            'example' => '最近 AI 调用日志',
        ],
        [
            'q_id' => 'Q-18',
            'keywords' => ['接口日志', 'API 调用记录'],
            'action_id' => 'api.log.list',
            'params' => ['limit' => 10],
            'return_fields' => ['id', 'endpoint', 'method', 'status', 'ip', 'create_time'],
            'example' => '最近的 API 调用记录',
        ],
        [
            'q_id' => 'Q-19',
            'keywords' => ['总体统计', '数据概览'],
            'action_id' => 'stat.overview',
            'params' => [],
            'return_fields' => ['article_count', 'member_count', 'theme_count', 'today_generate'],
            'example' => '数据概览',
        ],
        [
            'q_id' => 'Q-20',
            'keywords' => ['这个页面', '本页', '这里能做什么'],
            'action_id' => 'page.context',
            'params' => ['_extract' => 'current_path'],
            'return_fields' => ['page_name', 'module', 'available_actions'],
            'example' => '这个页面能做什么？',
        ],
    ],

    // 未命中/异常话术（S-19 §4，一字不改）
    'phrases' => [
        'miss' => '暂不支持这个问法。W1 只开放 20 类查询，你可以问：有多少文章、主题生成失败的有哪些、系统版本、最近 AI 调用日志……（完整清单可点「能做什么」查看）',
        'param_missing' => '要查这个需要指定 **{missing_field}**，例如：{example}',
        'not_implemented' => '这个查询已规划但还没上线（{q_id}），W1 暂不支持',
        'error' => '查询出错了：{safe_error}。已记录 request_id={request_id}，可让开发方查审计日志',
        'write_intent' => 'W1 只能查不能改。要执行「{guess_action}」需要等 W3 的写动作上线，或者用 CLI：`php think agent:run --action=...`',
    ],

    // Q-20 上下文缺失的专用返回码（S-19 §2.5）
    'q20_no_ctx' => 'Q-20-NO-CTX',
    'q20_no_ctx_message' => '没拿到当前页面，请刷新后重试',

    // 写意图触发关键词（S3-T3：命中即引导 CLI，不进白名单匹配）
    'write_intent_keywords' => [
        '帮我改', '帮我修改', '帮我删除', '帮我新增', '帮我创建', '帮我发布', '帮我上线',
        '修改标题', '修改配置', '改一下', '删掉', '删除这个', '新增一个', '创建一个',
        '发布', '上线', '下架', '更新为', '设置为', '关闭', '开启', '禁用', '启用',
    ],
];
