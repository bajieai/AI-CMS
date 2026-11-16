<?php
declare(strict_types=1);

namespace app\common\service\agent;

use think\facade\Db;
use think\facade\Log;

/**
 * AI 助手 · 对外通道服务（S2-T3 / S2-T4 / S2-T5）
 *
 * 依据 动作层规格 v1.2：
 *  - §4.4 ACP-06 / ACP-07 REST 接口：GET actions / POST invoke / GET invoke/:id / POST batch
 *  - §4.1 硬要求：API / CLI / MCP / 对话窗调【同一个】ActionExecutor，严禁两套实现
 *  - §4.12 R-5：dry-run 零落库；幂等键 24h；逐条事务 + 汇总报告
 *  - §4.12 R-4：scope 判定统一走 ScopeMatcher::allows()（越权 403）
 *
 * 职责边界（本类不写业务逻辑）：
 *  - 归一化 action_id（兼容对外 FQN `aicms.content.article.list`）
 *  - 做 scope / high_risk 前置判定，拒绝时经 ActionExecutor::deny() 留审计
 *  - 把执行器的返回映射为「HTTP 状态码 + 统一 {code,msg,data} 响应体」
 *  - 提供 request_id 反查（只回摘要与哈希，不回原始参数，S-8 密钥不出域）
 *
 * 说明：channel 由调用方传入（api / cli / mcp / window），本类不写死，保证四通道同源。
 */
class AgentInvokeService
{
    /** 能力发现协议版本（GET /actions → data.version） */
    public const API_VERSION = '1.0';

    /** 批量上限（S-7 限流与超时：单次批量不超过 100 条，逐条独立审计） */
    public const MAX_BATCH_ITEMS = 100;

    private ActionExecutor $executor;

    public function __construct(?ActionExecutor $executor = null)
    {
        $this->executor = $executor ?? new ActionExecutor();
    }

    /**
     * A-1 能力发现（GET /actions 与 CLI agent:actions、MCP tools/list 同源）
     */
    public function discover(bool $withSchema = true): array
    {
        $actions = ActionRegistry::all($withSchema);

        return [
            'version'   => self::API_VERSION,
            'product'   => ActionRegistry::PRODUCT,
            'count'     => count($actions),
            'actions'   => $actions,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 单动作调用（POST /invoke 与 CLI agent:run 同源）
     *
     * @param string       $rawActionId    原生 ID 或对外 FQN
     * @param array        $params         动作参数
     * @param array|string $grantedScopes  i8j_api_key.scopes（原始存储值或数组）
     * @param array        $ctx            channel / actor_type / actor_id / agent_name / idempotency_key / dry_run / confirm_token
     * @return array{status:int,body:array}
     */
    public function invoke(string $rawActionId, array $params, $grantedScopes, array $ctx = []): array
    {
        $ctx['channel'] = (string)($ctx['channel'] ?? 'api');

        $actionId = self::normalizeActionId($rawActionId);
        if ($actionId === '') {
            return $this->badRequest('action_id 不能为空', $rawActionId);
        }

        // 定义缺失/被禁用：交给执行器统一走 404 + 审计（不留无痕失败）
        $def = ActionRegistry::get($actionId);
        if ($def === null) {
            return $this->wrap($this->executor->invoke($actionId, $params, $ctx));
        }

        // S-2b / 202：W1 无 high_risk 动作；一旦出现必须先人工确认，禁止 AI 自主执行
        if ((string)($def['risk_level'] ?? 'read') === 'high_risk') {
            $message = empty($ctx['confirm_token'])
                ? 'high_risk 动作待人工确认（202），未执行（S-2b）'
                : 'confirm_token 无效，high_risk 动作待人工确认（202），未执行（S-2b）';
            return $this->wrap($this->executor->deny($actionId, $params, $ctx, 202, $message, 'rejected'));
        }

        // R-4 / A-3：scope 越权 403（拒绝也必须留审计）
        $required = (string)($def['scope_required'] ?? '');
        if (!ScopeMatcher::allows($grantedScopes, $required)) {
            return $this->wrap($this->executor->deny(
                $actionId,
                $params,
                $ctx,
                403,
                '越权：缺少 scope ' . $required . '（R-4 越权一律 403）',
                'rejected'
            ));
        }

        return $this->wrap($this->executor->invoke($actionId, $params, $ctx));
    }

    /**
     * 批量调用（POST /batch）
     *
     * 逐条走 invoke()，每条独立审计；单条失败不影响其他条目（A-5 灌数场景）。
     * 幂等键域：条目自带 idempotency_key 优先，缺省用 `<batch_id>_<index>` 派生，保证整批可重跑。
     *
     * @param array $items [{action_id, params, idempotency_key}]
     * @return array{status:int,body:array}
     */
    public function batch(array $items, $grantedScopes, array $ctx = []): array
    {
        if ($items === []) {
            return $this->badRequest('items 不能为空');
        }
        if (count($items) > self::MAX_BATCH_ITEMS) {
            return $this->badRequest('单次批量不超过 ' . self::MAX_BATCH_ITEMS . ' 条（S-7）');
        }

        $batchId = 'batch_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
        $results = [];
        $success = 0;
        $failed = 0;

        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                $failed++;
                $results[] = [
                    'index'     => $index,
                    'action_id' => '',
                    'status'    => 400,
                    'code'      => 400,
                    'msg'       => '条目格式非法，应为 {action_id, params}',
                    'data'      => [],
                ];
                continue;
            }

            $itemCtx = $ctx;
            $itemCtx['idempotency_key'] = (string)($item['idempotency_key'] ?? '') ?: ($batchId . '_' . $index);

            $one = $this->invoke(
                (string)($item['action_id'] ?? ''),
                is_array($item['params'] ?? null) ? $item['params'] : [],
                $grantedScopes,
                $itemCtx
            );

            if ((int)$one['status'] === 200) {
                $success++;
            } else {
                $failed++;
            }

            $results[] = ['index' => $index, 'status' => (int)$one['status']] + $one['body'];
        }

        return [
            'status' => 200,
            'body'   => [
                'code'   => 0,
                'msg'    => 'ok',
                'data'   => [
                    'batch_id' => $batchId,
                    'total'    => count($items),
                    'success'  => $success,
                    'failed'   => $failed,
                    'dry_run'  => !empty($ctx['dry_run']),
                    'results'  => $results,
                ],
                'meta'   => [
                    'channel'    => (string)($ctx['channel'] ?? 'api'),
                    'queried_at' => date('Y-m-d H:i:s'),
                ],
            ],
        ];
    }

    /**
     * 按 request_id 反查执行结果与审计记录（GET /invoke/:id）
     *
     * S-8：只回摘要 / 哈希 / 状态，不回传原始请求参数。
     *
     * @return array{status:int,body:array}
     */
    public function findByRequestId(string $requestId): array
    {
        $requestId = trim($requestId);
        if ($requestId === '') {
            return $this->badRequest('request_id 不能为空');
        }

        try {
            $row = Db::name('agent_audit')->where('request_id', $requestId)->order('id', 'desc')->find();
        } catch (\Throwable $e) {
            Log::error('AgentAudit query failed: ' . $e->getMessage());
            return ['status' => 500, 'body' => ['code' => 500, 'msg' => '审计查询失败', 'data' => []]];
        }

        if (!$row) {
            return [
                'status' => 404,
                'body'   => ['code' => 404, 'msg' => '未找到该 request_id 的执行记录', 'data' => ['request_id' => $requestId]],
            ];
        }

        $idem = null;
        try {
            $idem = Db::name('agent_idempotency')->where('request_id', $requestId)->find() ?: null;
        } catch (\Throwable $e) {
            $idem = null;
        }

        $payload = null;
        if ($idem && !empty($idem['response_json'])) {
            $decoded = json_decode((string)$idem['response_json'], true);
            if (is_array($decoded)) {
                $payload = $decoded['data'] ?? null;
            }
        }

        return [
            'status' => 200,
            'body'   => [
                'code'       => 0,
                'msg'        => 'ok',
                'request_id' => $requestId,
                'data'       => [
                    'request_id'       => (string)$row['request_id'],
                    'action_id'        => (string)$row['action_id'],
                    'channel'          => (string)$row['channel'],
                    'actor_type'       => (string)$row['actor_type'],
                    'actor_id'         => (string)$row['actor_id'],
                    'agent_name'       => (string)$row['agent_name'],
                    'q_id'             => (string)$row['q_id'],
                    'alias_id'         => (int)$row['alias_id'],
                    'idempotency_key'  => (string)$row['idempotency_key'],
                    'result'           => (string)$row['result'],
                    'error_msg'        => (string)$row['error_msg'],
                    'duration_ms'      => (int)$row['duration_ms'],
                    'diff_summary'     => (string)$row['diff_summary'],
                    'request_digest'   => (string)$row['request_digest'],
                    'response_digest'  => (string)$row['response_digest'],
                    'confirm_actor'    => (string)$row['confirm_actor'],
                    'confirm_at'       => (int)$row['confirm_at'],
                    'diff_viewed'      => (int)$row['diff_viewed'],
                    'create_time'      => date('Y-m-d H:i:s', (int)$row['create_time']),
                    'result_payload'   => $payload,
                    'idempotency'      => $idem ? [
                        'key'        => (string)$idem['idempotency_key'],
                        'status'     => (string)$idem['status'],
                        'expires_at' => date('Y-m-d H:i:s', (int)$idem['expires_at']),
                    ] : null,
                ],
            ],
        ];
    }

    /**
     * action_id 归一化：接受对外 FQN（aicms.xxx）与原生 ID（xxx.yyy）
     */
    public static function normalizeActionId(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (str_starts_with($raw, ActionRegistry::PRODUCT . '.')) {
            try {
                return ActionRegistry::fromFqn($raw);
            } catch (\Throwable $e) {
                return $raw;
            }
        }
        return $raw;
    }

    /**
     * CLI 通道身份解析（S-1：机器身份独立；CLI 只在本机运行，不等同于对外暴露的会话）
     *
     * - 带 --token=<api_key>：按该机器身份的 scopes 判定（与 API 完全一致，可验证越权 403）
     * - 不带 token：视为本机管理员代操作（兼容别名 admin:all），审计 actor_type=human
     *
     * @return array{ok:bool,message:string,scopes:array,actor_type:string,actor_id:string,agent_name:string}
     */
    public static function resolveCliIdentity(string $apiKey = ''): array
    {
        $apiKey = trim($apiKey);

        if ($apiKey === '') {
            return [
                'ok'         => true,
                'message'    => 'ok',
                'scopes'     => [ScopeMatcher::COMPAT_ALL],
                'actor_type' => 'human',
                'actor_id'   => 'cli',
                'agent_name' => 'local-cli',
            ];
        }

        $verified = (new AgentTokenService())->verify($apiKey, '127.0.0.1');
        if (empty($verified['ok'])) {
            return [
                'ok'         => false,
                'message'    => (string)($verified['message'] ?? '机器身份校验失败'),
                'scopes'     => [],
                'actor_type' => 'agent',
                'actor_id'   => '',
                'agent_name' => '',
            ];
        }

        $key = $verified['key'];

        return [
            'ok'         => true,
            'message'    => 'ok',
            'scopes'     => is_array($key['scopes_arr'] ?? null) ? $key['scopes_arr'] : [],
            'actor_type' => 'agent',
            'actor_id'   => (string)($key['id'] ?? ''),
            'agent_name' => (string)($key['name'] ?? ''),
        ];
    }

    // ==================== 内部：响应装配 ====================

    /**
     * 执行器响应 → {status, body}
     * 执行器 code=0 视为成功（HTTP 200），其余 code 直接作为 HTTP 状态码（400/403/404/429/500/501）
     */
    private function wrap(array $resp): array
    {
        $code = (int)($resp['code'] ?? 0);
        $status = $code === 0 ? 200 : $code;

        $body = $resp;
        unset($body['message']);
        $body['msg'] = (string)($resp['message'] ?? ($code === 0 ? 'ok' : 'error'));
        if (!array_key_exists('data', $body)) {
            $body['data'] = [];
        }

        return ['status' => $this->normalizeHttpStatus($status), 'body' => $body];
    }

    /** 兜底：非标准状态码一律 500，避免 HTTP 层抛异常 */
    private function normalizeHttpStatus(int $status): int
    {
        return ($status >= 200 && $status <= 599) ? $status : 500;
    }

    private function badRequest(string $msg, string $actionId = ''): array
    {
        return [
            'status' => 400,
            'body'   => [
                'code'      => 400,
                'msg'       => $msg,
                'action_id' => $actionId,
                'data'      => [],
            ],
        ];
    }
}
