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
     * 缓存 ReflectionProperty 实例（类元数据，跨请求安全）
     * 避免每次请求重复创建反射对象
     */
    protected static ?\ReflectionProperty $initProp = null;

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
     * V2.9.4 修复：移除 static $sessionInitialized（PHP-FPM 跨请求持久化导致 bug）
     * 改为每次请求都通过反射检测 Store::$init，同时缓存 ReflectionProperty 减少反射开销。
     * 同一请求内若 Store::$init 已为 true（全局 SessionInit 已执行），直接跳过。
     */
    protected function ensureSessionInit(Request $request): void
    {
        $session = app('session');

        // 使用反射检测 Store::$init 属性（true=已初始化, null=未初始化）
        try {
            if (self::$initProp === null) {
                $ref = new \ReflectionClass($session);
                self::$initProp = $ref->getProperty('init');
                self::$initProp->setAccessible(true);
            }
            if (self::$initProp->getValue($session) === true) {
                return;
            }
        } catch (\Throwable) {
            // 反射失败，继续初始化流程
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
