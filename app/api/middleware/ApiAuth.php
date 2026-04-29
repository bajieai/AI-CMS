<?php
declare(strict_types=1);

namespace app\api\middleware;

use app\common\model\ApiToken as ApiTokenModel;
use think\facade\Cache;

/**
 * API Token认证中间件
 * 支持Bearer和HMAC双模式，auth_type互斥验证
 */
class ApiAuth
{
    public function handle($request, \Closure $next)
    {
        // 加载数据库系统配置（确保API能读取comment/seo等配置，容错）
        try {
            load_cms_configs();
        } catch (\Throwable) {
            // 配置表可能尚未创建，降级跳过
        }

        $authHeader = $request->header('Authorization', '');

        if (empty($authHeader)) {
            return json(['code' => 401, 'msg' => '缺少Authorization头'], 401);
        }

        $parts = explode(' ', $authHeader, 2);
        if (count($parts) !== 2) {
            return json(['code' => 401, 'msg' => 'Authorization格式错误'], 401);
        }

        $scheme = strtolower($parts[0]);
        $token = $parts[1];

        $tokenModel = ApiTokenModel::where('token_hash', hash('sha256', $token))->where('status', 1)->find();

        if (!$tokenModel) {
            return json(['code' => 401, 'msg' => 'Token无效或已禁用'], 401);
        }

        if ($tokenModel->isExpired()) {
            return json(['code' => 401, 'msg' => 'Token已过期'], 401);
        }

        // auth_type 互斥验证
        if ($tokenModel->auth_type === 'hmac') {
            if ($scheme !== 'hmac') {
                return json(['code' => 401, 'msg' => '此Token仅支持HMAC签名认证'], 401);
            }
            // HMAC签名验证
            $timestamp = $request->header('X-Timestamp', '');
            $nonce = $request->header('X-Nonce', '');
            $signature = $request->header('X-Signature', '');

            if (empty($signature)) {
                return json(['code' => 401, 'msg' => '缺少X-Signature签名头'], 401);
            }
            if (empty($timestamp) || empty($nonce)) {
                return json(['code' => 401, 'msg' => 'HMAC模式需要X-Timestamp和X-Nonce头'], 401);
            }
            // 时间窗口验证（5分钟内有效）
            if (abs(time() - (int) $timestamp) > 300) {
                return json(['code' => 401, 'msg' => '请求时间戳超出有效范围'], 401);
            }
            // Nonce防重放（1小时内不可重复）
            $nonceKey = 'api_nonce_' . $nonce;
            if (Cache::get($nonceKey)) {
                return json(['code' => 401, 'msg' => '请求已过期或重复'], 401);
            }
            Cache::set($nonceKey, 1, 3600);

            $expected = hash_hmac('sha256', $this->getSignPayload($request), $tokenModel->secret_key);
            if (!hash_equals($expected, $signature)) {
                return json(['code' => 401, 'msg' => 'HMAC签名验证失败'], 401);
            }
        } else {
            if ($scheme !== 'bearer') {
                return json(['code' => 401, 'msg' => '此Token仅支持Bearer认证'], 401);
            }
            // Bearer模式：token_hash已匹配即通过
        }

        // 速率限制检查
        $rateKey = 'api_rate_' . $tokenModel->id . '_' . date('YmdH');
        $rateCount = Cache::get($rateKey, 0);
        if ($rateCount >= $tokenModel->rate_limit) {
            return json(['code' => 429, 'msg' => '请求过于频繁，请稍后再试'], 429);
        }
        Cache::inc($rateKey);
        Cache::expire($rateKey, 3600);

        // 更新最后使用时间
        $tokenModel->last_used_time = time();
        $tokenModel->save();

        // 将Token信息注入请求
        $request->apiToken = $tokenModel;
        $request->apiScopes = explode(',', $tokenModel->scopes);

        return $next($request);
    }

    /**
     * 获取HMAC签名的负载数据
     */
    protected function getSignPayload($request): string
    {
        $method = strtoupper($request->method());
        $uri = $request->url(true);
        $timestamp = $request->header('X-Timestamp', '');
        $nonce = $request->header('X-Nonce', '');
        $body = $request->getContent();
        return $method . "\n" . $uri . "\n" . $timestamp . "\n" . $nonce . "\n" . $body;
    }
}