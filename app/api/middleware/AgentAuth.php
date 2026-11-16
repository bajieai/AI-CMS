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

namespace app\api\middleware;

use app\common\service\agent\AgentTokenService;

/**
 * Agent 机器身份鉴权中间件（ACP-05 / S2-T2 · 对外通道 S2-T4）
 *
 * 依据 动作层规格 v1.2 §4.3 / §4.7：
 *  - S-1：机器身份独立、可吊销，禁止把管理员会话 Cookie / Token 给外部工具
 *  - R-4：scopes 统一由 ScopeMatcher::parse 注入，供动作层做 403 判定
 *  - S-7：来源 IP 白名单 + 每分钟限流
 *
 * 入参：Authorization: Bearer <api_key>（兼容 X-API-Key 头，便于 MCP / CLI 适配）
 * 出参：401 身份缺失/无效/已吊销、403 IP 不在白名单、429 超限流
 * 注入：$request->agentKey（i8j_api_key 行）、$request->apiScopes（数组）
 */
class AgentAuth
{
    public function handle($request, \Closure $next)
    {
        $token = $this->extractToken($request);
        if ($token === '') {
            return $this->deny(401, '缺少机器身份：请使用 Authorization: Bearer <api_key>（S-1）');
        }

        $service = new AgentTokenService();
        $verified = $service->verify($token, (string)$request->ip());

        if (empty($verified['ok'])) {
            $code = (int)($verified['code'] ?? 401);
            return $this->deny($code, (string)($verified['message'] ?? '身份校验失败'));
        }

        $key = $verified['key'];
        if (!is_array($key)) {
            return $this->deny(401, '身份无效');
        }

        // 注入动作层所需上下文（R-4：scopes 已由 AgentTokenService 用 ScopeMatcher::parse 解析）
        $request->agentKey = $key;
        $request->apiScopes = is_array($key['scopes_arr'] ?? null) ? $key['scopes_arr'] : [];

        return $next($request);
    }

    /** 提取 api_key：Bearer 优先，其次 X-API-Key */
    private function extractToken($request): string
    {
        $auth = trim((string)$request->header('Authorization', ''));
        if ($auth !== '') {
            if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
                return trim($matches[1]);
            }
            // 兼容 HMAC 前缀写法：HMAC <api_key>（W1 不校验签名，仅取身份，签名归 W2）
            if (preg_match('/^(?:HMAC|Token)\s+(.*)$/i', $auth, $matches)) {
                return trim($matches[1]);
            }
        }

        return trim((string)$request->header('X-API-Key', ''));
    }

    /** 统一拒绝响应：HTTP 状态码与 body.code 一致，不回显密钥/堆栈 */
    private function deny(int $code, string $msg): \think\Response
    {
        $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;
        $status = ($code >= 400 && $code <= 599) ? $code : 401;

        return json(['code' => $status, 'msg' => $msg, 'data' => []], $status, [], ['json_encode_param' => $flags]);
    }
}
