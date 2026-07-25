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

use app\common\model\MiniConfig;
use think\facade\Cache;
use think\Request;
use think\Response;

/**
 * 小程序API认证中间件
 * - Token验证 (Header: X-Mini-Token)
 * - P1: 频率限制 (从i8j_mini_config读取api_rate_limit，默认200次/分钟)
 * - IP+用户ID双重限制
 */
class MiniAuthMiddleware
{
    /**
     * 无需认证的路由（白名单）
     */
    protected array $publicActions = [
        'mini/v1/user/login',
        'mini/v1/system/config',
        'mini/v1/system/menu',
        'mini/v1/system/site',
        'mini/v1/system/version',
    ];

    public function handle(Request $request, \Closure $next): Response
    {
        $path = strtolower($request->pathinfo());

        // 频率限制 (对所有小程序API生效)
        $this->rateLimit($request);

        // 白名单跳过Token验证
        if ($this->isPublic($path)) {
            return $next($request);
        }

        // Token验证
        $token = $request->header('X-Mini-Token', '');
        if (empty($token)) {
            return $this->jsonError('缺少认证Token', 401);
        }

        $userId = Cache::get('mini_token:' . $token);
        if (empty($userId)) {
            return $this->jsonError('Token无效或已过期', 401);
        }

        // 绑定userId到请求
        $request->withInput(json_encode(['mini_user_id' => (int) $userId]));

        return $next($request);
    }

    /**
     * 频率限制: IP+用户ID双重限制
     */
    protected function rateLimit(Request $request): void
    {
        $rateLimit = (int) MiniConfig::getValue('api_rate_limit', '200');
        $ip = $request->ip();
        $token = $request->header('X-Mini-Token', '');
        $userId = $token ? (string) Cache::get('mini_token:' . $token, '0') : '0';

        // IP限制
        $ipKey = 'mini_rate_ip:' . $ip;
        $ipCount = (int) Cache::get($ipKey, 0);
        if ($ipCount >= $rateLimit) {
            json([
                'code' => 429,
                'message' => '请求过于频繁，请稍后再试',
                'data' => new \stdClass(),
                'timestamp' => time(),
                'request_id' => $this->generateRequestId(),
            ])->send();
            exit;
        }

        // 用户ID限制 (已登录用户)
        if ($userId !== '0') {
            $userKey = 'mini_rate_user:' . $userId;
            $userCount = (int) Cache::get($userKey, 0);
            if ($userCount >= $rateLimit) {
                json([
                    'code' => 429,
                    'message' => '请求过于频繁，请稍后再试',
                    'data' => new \stdClass(),
                    'timestamp' => time(),
                    'request_id' => $this->generateRequestId(),
                ])->send();
                exit;
            }
            Cache::inc($userKey);
            if ($userCount === 0) {
                Cache::expire($userKey, 60);
            }
        }

        Cache::inc($ipKey);
        if ($ipCount === 0) {
            Cache::expire($ipKey, 60);
        }
    }

    /**
     * 检查是否为公开路由
     */
    protected function isPublic(string $path): bool
    {
        foreach ($this->publicActions as $action) {
            if (str_contains($path, $action)) {
                return true;
            }
        }
        return false;
    }

    /**
     * JSON错误响应
     */
    protected function jsonError(string $message, int $code = 1): Response
    {
        return json([
            'code' => $code,
            'message' => $message,
            'data' => new \stdClass(),
            'timestamp' => time(),
            'request_id' => $this->generateRequestId(),
        ]);
    }

    /**
     * 生成请求ID
     */
    protected function generateRequestId(): string
    {
        return bin2hex(random_bytes(8));
    }
}
