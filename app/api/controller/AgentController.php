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

namespace app\api\controller;

use app\common\service\agent\AgentInvokeService;
use think\Response;

/**
 * AI 助手 · Agent 对外通道（ACP-06 / ACP-07 · V2.9.63 S2-T3 ~ S2-T5）
 *
 * 路由见 app/api/route/app.php → agent/v1/*：
 *  - GET  /api/agent/v1/actions      能力发现（公开只读元数据，A-1 ≥20 个动作）
 *  - POST /api/agent/v1/invoke       单动作调用（Bearer 机器身份 + scope 403 + 幂等 + dry-run）
 *  - GET  /api/agent/v1/invoke/:id   按 request_id 反查执行结果与审计
 *  - POST /api/agent/v1/batch        批量调用（同一幂等键域，用于灌模拟数据）
 *
 * 硬要求（动作层规格 v1.2 §4.4）：
 *  - 状态码语义：200 成功 / 202 待确认 / 400 Schema 失败 / 403 越权 / 404 动作或记录不存在 / 429 限流
 *  - 每个响应含 request_id，可在 i8j_agent_audit 反查
 *  - 不回显密钥、不回显堆栈（错误信息由 ActionExecutor 脱敏）
 *
 * @api_group Agent通道
 * @api_desc 供外部 AI 工具（外部 AI 工具）以机器身份调用 CMS 动作层
 */
class AgentController extends BaseController
{
    private ?AgentInvokeService $agentService = null;

    /**
     * 能力发现
     *
     * @api 动作能力发现
     * @api_desc 返回全部可调用动作（input_schema / scope / risk_level），供外部工具自动构造调用
     * @return json {code:0, data:{version, product, count, actions:[]}}
     * @api_auth no
     */
    public function actions(): Response
    {
        return $this->success('ok', $this->agent()->discover());
    }

    /**
     * 单动作调用
     *
     * @api 动作调用
     * @api_desc 执行单个只读动作；支持 dry_run（零落库）与 idempotency_key（重复调用只执行一次）
     * @param string $action_id       原生 ID 或对外 FQN（aicms.content.article.list）
     * @param object $params          动作参数（须满足 input_schema.required）
     * @param bool   $dry_run         仅预览不落库（默认 false）
     * @param string $idempotency_key 幂等键（必传，R-5）
     * @param string $confirm_token   高危动作二次确认令牌（W1 无高危动作）
     * @return json 200 成功 / 202 待确认 / 400 参数或 Schema 失败 / 403 越权 / 404 动作不存在 / 429 限流
     * @api_auth yes (Bearer 机器身份)
     */
    public function invoke(): Response
    {
        $payload = $this->payload();

        $actionId = (string)($payload['action_id'] ?? '');
        if ($actionId === '') {
            return $this->respond([
                'status' => 400,
                'body'   => ['code' => 400, 'msg' => 'action_id 不能为空', 'data' => []],
            ]);
        }

        $idempotencyKey = $this->idempotencyKey($payload);
        if ($idempotencyKey === '') {
            return $this->respond([
                'status' => 400,
                'body'   => ['code' => 400, 'msg' => 'idempotency_key 必传（R-5 幂等，可用 UUID）', 'action_id' => $actionId, 'data' => []],
            ]);
        }

        $params = is_array($payload['params'] ?? null) ? $payload['params'] : [];

        return $this->respond(
            $this->agent()->invoke($actionId, $params, $this->grantedScopes(), $this->buildCtx($payload, $idempotencyKey))
        );
    }

    /**
     * 执行结果反查
     *
     * @api 执行结果反查
     * @api_desc 按 request_id 查询审计记录（result / duration_ms / diff_summary）与幂等状态
     * @param string $id request_id
     * @return json {code:0, data:{...审计字段}}
     * @api_auth yes (Bearer 机器身份)
     */
    public function result(string $id): Response
    {
        return $this->respond($this->agent()->findByRequestId($id));
    }

    /**
     * 批量调用
     *
     * @api 批量动作调用
     * @api_desc 逐条执行、逐条审计、汇总报告；单条失败不影响其他条目（灌模拟数据场景）
     * @param array $items [{action_id, params, idempotency_key}]
     * @return json {code:0, data:{batch_id, total, success, failed, results:[]}}
     * @api_auth yes (Bearer 机器身份)
     */
    public function batch(): Response
    {
        $payload = $this->payload();
        $items = $payload['items'] ?? null;

        if (!is_array($items) || $items === []) {
            return $this->respond([
                'status' => 400,
                'body'   => ['code' => 400, 'msg' => 'items 不能为空（格式：[{action_id, params, idempotency_key}]）', 'data' => []],
            ]);
        }

        return $this->respond(
            $this->agent()->batch($items, $this->grantedScopes(), $this->buildCtx($payload, ''))
        );
    }

    // ==================== 内部 ====================

    private function agent(): AgentInvokeService
    {
        return $this->agentService ??= new AgentInvokeService();
    }

    /** 解析请求体：优先 JSON 原文，回退 param() */
    private function payload(): array
    {
        $raw = $this->request->getContent();
        if (is_string($raw) && $raw !== '') {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                return $json;
            }
        }

        $all = $this->request->param();
        return is_array($all) ? $all : [];
    }

    /** 幂等键：body.idempotency_key 优先，其次 Idempotency-Key 头 */
    private function idempotencyKey(array $payload): string
    {
        $key = trim((string)($payload['idempotency_key'] ?? ''));
        if ($key === '') {
            $key = trim((string)$this->request->header('Idempotency-Key', ''));
        }
        return mb_substr($key, 0, 64);
    }

    /** 当前机器身份的 scopes（由 AgentAuth 中间件注入；R-4） */
    private function grantedScopes(): array
    {
        $scopes = $this->request->apiScopes ?? [];
        return is_array($scopes) ? $scopes : [];
    }

    /** 执行上下文：四通道同源的 channel / actor 信息 */
    private function buildCtx(array $payload, string $idempotencyKey): array
    {
        $key = $this->request->agentKey ?? [];

        return [
            'channel'         => 'api',
            'actor_type'      => 'agent',
            'actor_id'        => (string)($key['id'] ?? ''),
            'agent_name'      => (string)($key['name'] ?? ''),
            'idempotency_key' => $idempotencyKey,
            'dry_run'         => !empty($payload['dry_run']),
            'confirm_token'   => (string)($payload['confirm_token'] ?? ''),
        ];
    }

    /**
     * 输出统一 envelope + 语义化 HTTP 状态码（§4.4：200/202/400/403/404/429）
     *
     * 注：此处不使用 success()/error()（固定 HTTP 200），因为 Agent 通道验收需按状态码判定。
     */
    private function respond(array $result): Response
    {
        $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;
        $status = (int)($result['status'] ?? 200);
        if ($status < 200 || $status > 599) {
            $status = 500;
        }

        $body = $result['body'] ?? ['code' => $status, 'msg' => 'error', 'data' => []];
        if (!is_array($body)) {
            $body = ['code' => $status, 'msg' => 'error', 'data' => []];
        }

        return json($body, $status, [], ['json_encode_param' => $flags]);
    }
}
