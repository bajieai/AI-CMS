<?php
declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;
use think\Response;

/**
 * V2.9.5 前台CSRF保护中间件
 * 参考 AdminCsrf 实现，适配前台场景
 * - 放行安全请求方法（GET/HEAD/OPTIONS）
 * - 放行登录、注册、OAuth回调、支付回调路径
 * - 支持表单 __token__ 字段和 X-CSRF-TOKEN Header
 * - 验证失败返回 419 状态码及友好提示
 */
class FrontCsrfMiddleware
{
    /**
     * 无需CSRF验证的路径关键字（小写匹配）
     */
    protected array $exceptPaths = [
        'login',
        'logout',
        'register',
        'oauth',
        'callback',
        'notify',
        'webhook',
        'pay',
        'payment',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // 安全请求方法自动放行
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        // 放行例外路径
        $path = strtolower($request->pathinfo());
        foreach ($this->exceptPaths as $except) {
            if (str_contains($path, $except)) {
                return $next($request);
            }
        }

        // 获取请求中的Token（优先Header，其次POST/PUT字段）
        $tokenName = '__token__';
        $requestToken = $request->header('X-CSRF-TOKEN')
            ?: $request->post($tokenName)
            ?: $request->put($tokenName);
        $sessionToken = session($tokenName);

        // 验证Token
        if (empty($sessionToken) || empty($requestToken) || $requestToken !== $sessionToken) {
            $newToken = $this->regenerateToken();

            if ($request->isAjax()) {
                return json([
                    'code' => 419,
                    'msg'  => '页面已过期，请刷新后重试',
                    'data' => ['token' => $newToken],
                ], 419);
            }

            // 返回友好错误页面或重定向
            return response($this->renderErrorPage('页面已过期，请刷新后重试'), 419);
        }

        return $next($request);
    }

    /**
     * 重新生成CSRF Token
     */
    protected function regenerateToken(): string
    {
        $token = md5(uniqid((string) mt_rand(), true));
        session('__token__', $token);
        return $token;
    }

    /**
     * 渲染友好错误页面HTML
     */
    protected function renderErrorPage(string $message): string
    {
        return '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>请求过期 - 419</title>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <style>body{display:flex;align-items:center;justify-content:center;height:100vh;background:#f8fafc}.error-box{text-align:center;padding:2rem;background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.08);max-width:400px;width:90%}</style>
</head>
<body>
    <div class="error-box">
        <h1 class="display-4 text-muted mb-3">419</h1>
        <p class="text-secondary mb-4">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>
        <button class="btn btn-primary" onclick="location.reload()">刷新页面</button>
        <a href="/" class="btn btn-outline-secondary ms-2">返回首页</a>
    </div>
</body>
</html>';
    }
}
