<?php
declare(strict_types=1);

namespace app\common\service\agent;

use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;

/**
 * AI 助手 · 统一动作执行入口（ACP-03 硬要求：API / CLI / MCP / 对话窗共用本入口）
 *
 * 职责链（顺序固定）：
 *   ① S-3 黑名单 + S-6 注入扫描（安全，先于一切）
 *   ② Registry 取定义（不存在/禁用 → 404）
 *   ③ R-5 幂等检查（重复键直接重放首次结果）
 *   ④ input_schema 必填校验
 *   ⑤ S-7 限流
 *   ⑥ 执行 handler（计时）
 *   ⑦ meta 组装（data_origin / queried_at / elapsed_ms / source_table / field_map_applied）
 *   ⑧ ACP-09 审计留痕（无漏记）
 *   ⑨ 幂等结果落库（24h）
 *
 * 设计说明（D-1 field_map 口径）：
 *   handler 直接输出【对外名】（PRD/白名单字段名），数据正确性不依赖运行期二次改名；
 *   field_map 作为元数据声明随 GET /actions 暴露，供验收方做「逆映射逐字段比对」（E-1.2），
 *   故此处只打 field_map_applied 标记，不做列改名，避免双重映射导致字段丢失。
 */
class ActionExecutor
{
    /** S-3 高危黑名单（动作 ID 模式，命中即拒） */
    private const BLACKLIST_PATTERNS = [
        '/secret|password|api_key|apikey/i',   // 改密钥类
        '/drop|truncate|purge/i',              // 删库清表类
        '/permission|role_assign|grant/i',     // 改权限类
        '/pay.*(close|disable)/i',             // 关支付类
        '/license.*(modify|reset)/i',          // 改授权许可类
        '/batch_delete|bulk_remove/i',         // 批量删除类
        '/publish_online|release_online/i',    // 发布上线类
    ];

    /** S-6 注入特征（params 任意字符串值命中即拒） */
    private const INJECTION_PATTERNS = [
        '/\b(union\s+select|drop\s+table|delete\s+from|insert\s+into|update\s+\w+\s+set)\b/i',
        '/(--|\/\*|\*\/)\s*(select|drop|delete|update|insert)\b/i',
        '/(<\?php|<script|<\?=)/i',
        '/\{(if|volist|foreach|\$[a-z_]+)\s*[\}\s]/i',
        '/\b(sleep|benchmark|load_file)\s*\(/i',
        '/into\s+outfile/i',
    ];

    /** action_id → handler 方法（与 actions.php 24 个一一对应） */
    private const HANDLER_MAP = [
        'content.article.list' => 'contentArticleList',
        'content.article.read' => 'contentArticleRead',
        'content.category.tree' => 'contentCategoryTree',
        'template.store.list' => 'templateStoreList',
        'template.store.detail' => 'templateStoreDetail',
        'template.install.list' => 'templateInstallList',
        'template.license.list' => 'templateLicenseList',
        'theme.market.list' => 'themeMarketList',
        'theme.record.list' => 'themeRecordList',
        'theme.record.detail' => 'themeRecordDetail',
        'theme.palette.list' => 'themePaletteList',
        'theme.version.history' => 'themeVersionHistory',
        'member.list' => 'memberList',
        'member.detail' => 'memberDetail',
        'config.get' => 'configGet',
        'system.version' => 'systemVersion',
        'system.health' => 'systemHealth',
        'ai.log.list' => 'aiLogList',
        'api.log.list' => 'apiLogList',
        'stat.overview' => 'statOverview',
        'report.definition.list' => 'reportDefinitionList',
        'page.context' => 'pageContext',
        'mockdata.content.batch_create' => 'mockdataContentBatchCreate',
        'mockdata.template.batch_create' => 'mockdataTemplateBatchCreate',
    ];

    private ActionHandlers $handlers;

    public function __construct()
    {
        $this->handlers = new ActionHandlers();
    }

    /**
     * 统一执行入口
     *
     * @param string $actionId 原生 action_id
     * @param array  $params   动作参数
     * @param array  $ctx      channel / actor_type / actor_id / agent_name / idempotency_key / dry_run / q_id
     */
    public function invoke(string $actionId, array $params = [], array $ctx = []): array
    {
        $requestId = $this->newRequestId();
        $startedAt = microtime(true);
        $auditBase = [
            'action_id'      => $actionId,
            'channel'        => (string)($ctx['channel'] ?? 'api'),
            'actor_type'     => (string)($ctx['actor_type'] ?? 'agent'),
            'actor_id'       => (string)($ctx['actor_id'] ?? ''),
            'agent_name'     => (string)($ctx['agent_name'] ?? ''),
            'q_id'           => (string)($ctx['q_id'] ?? ''),
            'idempotency_key' => (string)($ctx['idempotency_key'] ?? ''),
            'params'         => $params,
        ];
        $idemKey = $auditBase['idempotency_key'];
        $dryRun = !empty($ctx['dry_run']);

        // ① S-3 黑名单
        foreach (self::BLACKLIST_PATTERNS as $pattern) {
            if (preg_match($pattern, $actionId)) {
                return $this->fail($requestId, 403, 'S-3 高危动作默认禁用：' . $actionId, $auditBase, $startedAt);
            }
        }

        // ①' S-6 注入扫描
        $injectionAt = $this->scanInjection($params);
        if ($injectionAt !== null) {
            return $this->fail($requestId, 400, 'S-6 参数含非法特征，已拒绝（字段：' . $injectionAt . '）', $auditBase, $startedAt);
        }

        // ② Registry
        $def = ActionRegistry::get($actionId);
        if ($def === null) {
            return $this->fail($requestId, 404, '动作不存在或未启用：' . $actionId, $auditBase, $startedAt);
        }

        // ③ R-5 幂等
        if ($idemKey !== '') {
            $hit = $this->findIdempotent($actionId, $idemKey);
            if ($hit !== null) {
                $this->writeAudit($requestId, $def, $params, $auditBase, 'success', 0, 'idempotent-replay');
                $replay = json_decode((string)$hit['response_json'], true);
                if (is_array($replay)) {
                    $replay['request_id'] = $requestId;
                    $replay['meta']['idempotent_replay'] = true;
                    return $replay;
                }
            }
        }

        // ④ schema 必填校验
        $missing = $this->validateParams($def, $params);
        if ($missing !== []) {
            $resp = $this->fail($requestId, 400, '参数不完整，缺少：' . implode(',', $missing), $auditBase, $startedAt);
            $resp['missing'] = $missing;
            return $resp;
        }

        // ⑤ S-7 限流
        $limit = (int)($def['rate_limit'] ?? 60);
        if (!$this->allowRate($actionId, $auditBase['actor_id'], $limit)) {
            return $this->fail($requestId, 429, "S-7 超出限流：{$actionId}（{$limit}/分钟）", $auditBase, $startedAt);
        }

        // ⑥ 执行
        $method = self::HANDLER_MAP[$actionId] ?? null;
        if ($method === null || !method_exists($this->handlers, $method)) {
            return $this->fail($requestId, 501, '动作已注册但执行器未实现：' . $actionId, $auditBase, $startedAt);
        }

        try {
            $result = $this->handlers->{$method}($params);
        } catch (\Throwable $e) {
            Log::warning('ActionExecutor handler failed: ' . $actionId . ' - ' . $e->getMessage());
            return $this->fail($requestId, 500, '查询出错：' . $this->safeError($e->getMessage()), $auditBase, $startedAt);
        }

        $elapsed = (int)round((microtime(true) - $startedAt) * 1000);

        $response = [
            'code' => 0,
            'message' => 'ok',
            'request_id' => $requestId,
            'action_id' => $actionId,
            'data' => $result['data'] ?? [],
            'meta' => [
                'data_origin' => (string)($def['data_origin'] ?? 'real'),
                'queried_at' => date('Y-m-d H:i:s'),
                'elapsed_ms' => $elapsed,
                'source_table' => (string)($def['source_table'] ?? ''),
                'field_map_applied' => !empty($def['field_map']),
                'risk_level' => (string)($def['risk_level'] ?? 'read'),
                'channel' => $auditBase['channel'],
                'dry_run' => $dryRun,
            ],
        ];

        // ⑧ 审计
        $this->writeAudit($requestId, $def, $params, $auditBase, 'success', $elapsed, '');

        // ⑨ 幂等落库
        if ($idemKey !== '') {
            $this->saveIdempotent($actionId, $idemKey, $requestId, $response);
        }

        return $response;
    }

    // ==================== 内部：安全 ====================

    /** S-6 递归扫描 params 字符串值；返回命中的字段路径 */
    private function scanInjection(array $params, string $path = ''): ?string
    {
        foreach ($params as $k => $v) {
            $cur = $path === '' ? (string)$k : $path . '.' . $k;
            if (is_array($v)) {
                $r = $this->scanInjection($v, $cur);
                if ($r !== null) {
                    return $r;
                }
                continue;
            }
            if (!is_string($v)) {
                continue;
            }
            foreach (self::INJECTION_PATTERNS as $pattern) {
                if (preg_match($pattern, $v)) {
                    return $cur;
                }
            }
        }
        return null;
    }

    /** 错误信息脱敏：不泄露 SQL / 绝对路径 / 堆栈（§4.4） */
    private function safeError(string $msg): string
    {
        $msg = (string)preg_replace('/SQLSTATE\[[^\]]*\][^,]*/i', 'DB_ERROR', $msg);
        $msg = (string)preg_replace('#[A-Za-z]:\\\\[^\s]+#', '[PATH]', $msg);
        $msg = (string)preg_replace('#/(var|home|www)/[^\s]+#', '[PATH]', $msg);
        return mb_substr($msg, 0, 200);
    }

    // ==================== 内部：参数校验 ====================

    /** 返回缺失的必填字段名数组 */
    public function validateParams(array $def, array $params): array
    {
        $required = $def['input_schema']['required'] ?? [];
        $missing = [];
        foreach ($required as $field) {
            if (!array_key_exists($field, $params) || $params[$field] === '' || $params[$field] === null) {
                $missing[] = $field;
            }
        }
        return $missing;
    }

    /** S-7 限流：Cache 分钟窗口计数 */
    private function allowRate(string $actionId, string $actorId, int $limit): bool
    {
        if ($limit <= 0) {
            return true;
        }
        $key = 'agent_rate_' . md5($actionId . '|' . $actorId . '|' . date('YmdHi'));
        try {
            $n = (int)Cache::get($key, 0);
            if ($n >= $limit) {
                return false;
            }
            Cache::set($key, $n + 1, 70);
            return true;
        } catch (\Throwable $e) {
            Log::warning('Rate limiter degraded: ' . $e->getMessage());
            return true;
        }
    }

    // ==================== 内部：幂等（R-5）====================

    private function findIdempotent(string $actionId, string $key): ?array
    {
        try {
            return Db::name('agent_idempotency')
                ->where('action_id', $actionId)
                ->where('idempotency_key', $key)
                ->where('expires_at', '>', time())
                ->find() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function saveIdempotent(string $actionId, string $key, string $requestId, array $response): void
    {
        try {
            Db::name('agent_idempotency')->insert([
                'action_id' => $actionId,
                'idempotency_key' => $key,
                'request_id' => $requestId,
                'status' => 'success',
                'response_digest' => hash('xxh3', json_encode($response, JSON_UNESCAPED_UNICODE)),
                'response_json' => json_encode($response, JSON_UNESCAPED_UNICODE),
                'expires_at' => time() + 86400,
                'create_time' => time(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Idempotency save failed: ' . $e->getMessage());
        }
    }

    // ==================== 内部：审计（ACP-09）====================

    private function writeAudit(string $requestId, array $def, array $params, array $base, string $result, int $elapsedMs, string $note): void
    {
        try {
            Db::name('agent_audit')->insert([
                'request_id' => $requestId,
                'channel' => (string)$base['channel'],
                'actor_type' => (string)$base['actor_type'],
                'agent_name' => (string)$base['agent_name'],
                'actor_id' => (string)$base['actor_id'],
                'action_id' => (string)($def['action_id'] ?? $base['action_id']),
                'q_id' => (string)$base['q_id'],
                'alias_id' => 0,
                'idempotency_key' => (string)$base['idempotency_key'],
                'request_digest' => hash('xxh3', json_encode($params, JSON_UNESCAPED_UNICODE)),
                'response_digest' => '',
                'diff_summary' => $note,
                'prompt_digest' => '',
                'model_name' => '',
                'result' => $result,
                'error_msg' => '',
                'duration_ms' => $elapsedMs,
                'confirm_actor' => '',
                'confirm_at' => 0,
                'diff_viewed' => 0,
                'create_time' => time(),
            ]);
        } catch (\Throwable $e) {
            // 审计失败不吞业务结果，但必须落到文件日志（S-5 无漏记兜底）
            Log::error('Audit write failed: ' . $e->getMessage());
        }
    }

    // ==================== 内部：失败响应 ====================

    private function fail(string $requestId, int $code, string $message, array $base, float $startedAt): array
    {
        $elapsed = (int)round((microtime(true) - $startedAt) * 1000);
        try {
            Db::name('agent_audit')->insert([
                'request_id' => $requestId,
                'channel' => (string)$base['channel'],
                'actor_type' => (string)$base['actor_type'],
                'agent_name' => (string)$base['agent_name'],
                'actor_id' => (string)$base['actor_id'],
                'action_id' => (string)$base['action_id'],
                'q_id' => (string)$base['q_id'],
                'alias_id' => 0,
                'idempotency_key' => (string)$base['idempotency_key'],
                'request_digest' => hash('xxh3', json_encode($base['params'] ?? [], JSON_UNESCAPED_UNICODE)),
                'response_digest' => '',
                'diff_summary' => '',
                'prompt_digest' => '',
                'model_name' => '',
                'result' => 'failed',
                'error_msg' => mb_substr($message, 0, 500),
                'duration_ms' => $elapsed,
                'confirm_actor' => '',
                'confirm_at' => 0,
                'diff_viewed' => 0,
                'create_time' => time(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Audit write failed (fail path): ' . $e->getMessage());
        }

        return [
            'code' => $code,
            'message' => $message,
            'request_id' => $requestId,
            'action_id' => (string)$base['action_id'],
            'data' => [],
            'meta' => [
                'data_origin' => 'real',
                'queried_at' => date('Y-m-d H:i:s'),
                'elapsed_ms' => $elapsed,
                'source_table' => '',
                'field_map_applied' => false,
            ],
        ];
    }

    // ==================== 内部：工具 ====================

    private function newRequestId(): string
    {
        return 'req_' . date('YmdHis') . '_' . bin2hex(random_bytes(6));
    }
}
