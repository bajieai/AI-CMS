<?php
declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;
use think\Response;

/**
 * 后台认证中间件（app级别中间件）
 * 注意：由于 MultiApp 会先执行 app 级中间件再回到全局中间件管道，
 * 而 SessionInit 是全局中间件，排在 MultiApp 之后，
 * 因此 app 级中间件执行时 session 尚未初始化。
 * 这里需要手动确保 session 已初始化。
 */
class AdminAuth
{
    /**
     * 静态标记：当前请求周期内 session 是否已手动初始化
     * 避免同一请求内多次初始化（多中间件场景）
     * V2.9.4 性能优化：替代反射检测 Store::$init 属性
     */
    protected static bool $sessionInitialized = false;

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
     * 
     * V2.9.4 性能优化：使用静态标记 + try-catch 替代反射检测
     * - 静态标记：同一请求周期内只初始化一次
     * - try-catch：检测 session 是否已初始化，无反射开销
     */
    protected function ensureSessionInit(Request $request): void
    {
        // 同一请求周期内只初始化一次
        if (self::$sessionInitialized) {
            return;
        }

        $session = app('session');
        
        // 轻量级检测：尝试获取 session ID，如果已初始化则不会抛异常
        try {
            $session->getId();
            // getId() 成功说明 session 已初始化
            self::$sessionInitialized = true;
            return;
        } catch (\Throwable) {
            // 未初始化，继续初始化流程
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
        
        self::$sessionInitialized = true;
    }

    /**
     * 重置初始化标记（用于测试或长生命周期进程）
     */
    public static function resetInitFlag(): void
    {
        self::$sessionInitialized = false;
    }
}
