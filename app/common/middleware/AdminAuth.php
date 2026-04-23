<?php
declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;
use think\Response;
use think\middleware\SessionInit;

/**
 * 后台认证中间件（app级别中间件）
 * 注意：由于 MultiApp 会先执行 app 级中间件再回到全局中间件管道，
 * 而 SessionInit 是全局中间件，排在 MultiApp 之后，
 * 因此 app 级中间件执行时 session 尚未初始化。
 * 这里需要手动确保 session 已初始化。
 */
class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // 确保 session 已初始化
        $this->ensureSessionInit($request);

        // 检查是否已安装
        if (!file_exists(root_path() . 'install.lock')) {
            return redirect('/install.php');
        }

        // 获取当前路径（通过 admin.php 入口时 pathinfo 包含应用前缀如 "admin/login"）
        $path = strtolower($request->pathinfo());
        
        // 登录/登出相关路径跳过认证检查
        if (str_contains($path, 'login') || str_contains($path, 'logout')) {
            return $next($request);
        }

        // 检查是否已登录
        if (empty(session('user_id'))) {
            if ($request->isAjax()) {
                return json([
                    'code' => 2,
                    'msg' => '请先登录',
                    'data' => null,
                ]);
            }
            return redirect('/admin/login');
        }

        return $next($request);
    }

    /**
     * 确保 session 已初始化
     * 在 app 级中间件中，全局的 SessionInit 可能还未执行，
     * 需要手动读取 cookie 中的 session ID 并初始化 session。
     */
    protected function ensureSessionInit(Request $request): void
    {
        $session = app('session');
        
        // 如果 session 已经初始化过，跳过
        if ($session->all()) {
            return;
        }

        // 从 cookie 获取 session ID
        $cookieName = $session->getName();
        $sessionId = $request->cookie($cookieName);
        
        if ($sessionId) {
            $session->setId($sessionId);
        }
        
        $session->init();
        
        // 将 session 绑定到 request
        $request->withSession($session);
    }
}
