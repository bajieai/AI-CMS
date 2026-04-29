<?php
declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\facade\Config;
use think\Request;
use think\Response;

/**
 * 后台权限中间件（app级别中间件）
 */
class AdminPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        // 登录/登出相关路径跳过权限检查
        $path = strtolower($request->pathinfo());
        if (str_contains($path, 'login') || str_contains($path, 'logout')) {
            return $next($request);
        }

        $roleId = (int) session('role_id');
        
        // 超级管理员(role_id=1)直接跳过权限检查
        if ($roleId === 1) {
            return $next($request);
        }

        // 获取当前路由的权限标识（兼容完整类名和简写）
        $controller = $request->controller();
        // 提取纯控制器名（去除命名空间前缀）
        if (str_contains($controller, '\\')) {
            $controller = substr($controller, strrpos($controller, '\\') + 1);
        }
        // 去除 Controller 后缀并转为蛇形命名
        $controller = str_replace('Controller', '', $controller);
        $controller = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $controller));
        $action = strtolower($request->action());
        $permission = $controller . '.' . $action;

        // 获取角色权限
        $permissions = Config::get('permission.roles.' . $roleId . '.permissions', []);

        // 通配符权限
        if ($permissions === '*') {
            return $next($request);
        }

        // 检查具体权限
        $hasPermission = false;
        foreach ($permissions as $perm) {
            if ($perm === $permission) {
                $hasPermission = true;
                break;
            }
            if (str_ends_with($perm, '.*')) {
                $prefix = substr($perm, 0, -2);
                if (str_starts_with($permission, $prefix)) {
                    $hasPermission = true;
                    break;
                }
            }
        }

        if (!$hasPermission) {
            if ($request->isAjax() || $request->isPost()) {
                return json([
                    'code' => 3,
                    'msg' => '权限不足',
                    'data' => null,
                ]);
            }
            return redirect('/admin')->with('error', '权限不足');
        }

        return $next($request);
    }
}
