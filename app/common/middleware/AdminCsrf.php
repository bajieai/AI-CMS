<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;
use think\Response;
use think\exception\ValidateException;

/**
 * 后台CSRF保护中间件
 * 为所有后台POST/PUT/DELETE/PATCH请求提供CSRF防护
 * 支持表单 __token__ 字段和 X-CSRF-TOKEN Header 两种传递方式
 * Token验证通过后不立即销毁，以兼容多个AJAX请求共用一个Token的场景
 * 验证失败时自动生成新Token返回给客户端，支持AJAX自动恢复
 */
class AdminCsrf
{
    public function handle(Request $request, Closure $next): Response
    {
        // 安全请求方法自动放行
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        // 登录/登出相关路径跳过CSRF检查
        $path = strtolower($request->pathinfo());
        if (str_contains($path, 'login') || str_contains($path, 'logout')) {
            return $next($request);
        }

        // 获取请求中的Token（优先Header，其次POST字段）
        $tokenName = '__token__';
        $requestToken = $request->header('X-CSRF-TOKEN') ?: $request->post($tokenName);
        $sessionToken = session($tokenName);

        // 验证Token
        if (empty($sessionToken) || empty($requestToken) || $requestToken !== $sessionToken) {
            // 验证失败时重新生成Token，方便客户端自动恢复
            $newToken = $this->regenerateToken();

            if ($request->isAjax()) {
                return json([
                    'code' => 403,
                    'msg'  => 'CSRF验证失败，请刷新页面后重试',
                    'data' => ['token' => $newToken],
                ]);
            }
            throw new ValidateException('CSRF验证失败，请刷新页面后重试');
        }

        // 注意：此处不销毁Session中的Token，以兼容同一页面多个AJAX操作
        // 如需严格防止重放攻击，可在关键操作（如资金类）中额外使用单次Token

        return $next($request);
    }

    /**
     * 重新生成CSRF Token
     * 会话级持久化策略：只在首次或失效时生成，避免并发请求覆盖
     */
    protected function regenerateToken(): string
    {
        $token = md5(uniqid((string) mt_rand(), true));
        session('__token__', $token);
        return $token;
    }
}
