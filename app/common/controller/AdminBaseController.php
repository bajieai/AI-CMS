<?php
declare(strict_types=1);

namespace app\common\controller;

use app\common\model\Log as LogModel;
use think\App;
use think\facade\Config;

/**
 * 后台管理基类控制器
 * 所有admin应用的控制器继承此类
 */
abstract class AdminBaseController extends \think\BaseController
{
    /**
     * 当前登录用户
     */
    protected ?array $currentUser = null;

    /**
     * 无需登录的方法
     */
    protected array $noNeedLogin = [];

    /**
     * 无需权限的方法
     */
    protected array $noNeedPermission = [];

    /**
     * 菜单高亮映射（静态缓存，避免每次实例化都重建）
     */
    protected static array $menuMap = [
        'index'    => 'dashboard',
        'content'  => 'content',
        'cate'     => 'cate',
        'tag'      => 'tag',
        'user'     => 'user',
        'system'   => 'system',
        'log'      => 'log',
        'recycle'  => 'recycle',
        'media'    => 'media',
        'banner'   => 'banner',
        'link'     => 'link',
        'review'   => 'review',
        'backup'   => 'backup',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->initialize();
    }

    protected function initialize(): void
    {
        // 注入当前用户角色信息到所有视图
        $roleId = (int) session('role_id');
        $this->app->view->assign('is_super_admin', $roleId === 1);

        // 自动注入当前菜单高亮标识
        $controller = strtolower(str_replace('Controller', '', $this->request->controller()));
        $this->app->view->assign('menuActive', self::$menuMap[$controller] ?? '');

        // 根据角色权限过滤菜单并注入视图（使用静态缓存避免同一请求重复计算）
        $filteredMenus = $this->getFilteredMenus($roleId);
        $this->app->view->assign('sidebarMenus', $filteredMenus);
    }

    /**
     * 获取已过滤的菜单（带静态缓存）
     */
    protected function getFilteredMenus(int $roleId): array
    {
        // 超级管理员直接返回完整菜单，无需过滤
        if ($roleId === 1) {
            static $superAdminMenus = null;
            if ($superAdminMenus === null) {
                $superAdminMenus = Config::get('menu', []);
            }
            return $superAdminMenus;
        }

        // 按角色缓存过滤后的菜单（同一请求内）
        static $filteredCache = [];
        if (isset($filteredCache[$roleId])) {
            return $filteredCache[$roleId];
        }

        $menus = Config::get('menu', []);
        $permissions = Config::get('permission.roles.' . $roleId . '.permissions', []);

        if ($permissions === '*') {
            $filteredCache[$roleId] = $menus;
        } else {
            $filteredCache[$roleId] = $this->filterMenu($menus, (array) $permissions);
        }

        return $filteredCache[$roleId];
    }

    /**
     * 根据权限过滤菜单
     */
    protected function filterMenu(array $menus, array $permissions): array
    {
        if ($permissions === '*') {
            return $menus;
        }

        $result = [];
        foreach ($menus as $group) {
            $filteredChildren = [];
            foreach ($group['children'] ?? [] as $item) {
                if ($this->checkMenuPermission($permissions, $item['permission'] ?? '')) {
                    $filteredChildren[] = $item;
                }
            }
            if (!empty($filteredChildren)) {
                $group['children'] = $filteredChildren;
                $result[] = $group;
            }
        }
        return $result;
    }

    /**
     * 检查菜单权限是否匹配
     */
    protected function checkMenuPermission(array $permissions, string $menuPermission): bool
    {
        if (empty($menuPermission)) {
            return true;
        }
        foreach ($permissions as $perm) {
            if ($perm === $menuPermission) {
                return true;
            }
            // content.* 匹配 content.list
            if (str_ends_with($perm, '.*')) {
                $prefix = substr($perm, 0, -2);
                if (str_starts_with($menuPermission, $prefix . '.')) {
                    return true;
                }
            }
            // content.list 匹配 content.*
            if (str_ends_with($menuPermission, '.*')) {
                $prefix = substr($menuPermission, 0, -2);
                if (str_starts_with($perm, $prefix . '.')) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 获取当前登录用户
     */
    protected function getCurrentUser(): ?array
    {
        if ($this->currentUser !== null) {
            return $this->currentUser;
        }

        $userId = session('user_id');
        if (empty($userId)) {
            return null;
        }

        $this->currentUser = [
            'id' => $userId,
            'username' => session('username'),
            'role_id' => session('role_id'),
            'nickname' => session('nickname'),
        ];

        return $this->currentUser;
    }

    /**
     * 检查是否已登录
     */
    protected function checkLogin(): bool
    {
        $action = strtolower($this->request->action());
        if (in_array($action, array_map('strtolower', $this->noNeedLogin))) {
            return true;
        }

        if (empty(session('user_id'))) {
            return false;
        }

        return true;
    }

    /**
     * 检查权限
     */
    protected function checkPermission(): bool
    {
        $action = strtolower($this->request->action());
        if (in_array($action, array_map('strtolower', $this->noNeedPermission))) {
            return true;
        }

        $roleId = (int) session('role_id');
        
        // 超级管理员(role_id=1)直接跳过权限检查
        if ($roleId === 1) {
            return true;
        }

        // 获取当前路由的权限标识
        $permission = $this->getPermissionKey();
        $permissions = Config::get('permission.roles.' . $roleId . '.permissions', []);

        // 通配符权限
        if ($permissions === '*') {
            return true;
        }

        // 检查具体权限
        foreach ($permissions as $perm) {
            if ($perm === $permission || str_ends_with($perm, '.*')) {
                $prefix = substr($perm, 0, -2);
                if (str_starts_with($permission, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 获取当前路由的权限标识
     */
    protected function getPermissionKey(): string
    {
        $app = $this->request->app();
        $controller = $this->request->controller();
        $action = $this->request->action();
        
        $controller = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $controller));
        
        return $controller . '.' . $action;
    }

    /**
     * 成功响应
     */
    protected function success(string $msg = '操作成功', mixed $data = [], int $code = 0): \think\Response
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ]);
    }

    /**
     * 失败响应
     */
    protected function error(string $msg = '操作失败', int $code = 1, mixed $data = []): \think\Response
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ]);
    }

    /**
     * 记录操作日志
     * @param string $action 操作类型
     * @param string $desc 操作对象描述
     * @param array $data 操作数据
     */
    protected function recordLog(string $action, string $desc = '', array $data = []): void
    {
        $user = $this->getCurrentUser();

        try {
            LogModel::create([
                'user_id' => $user['id'] ?? 0,
                'module'  => $this->request->controller(),
                'action'  => $action,
                'target'  => $desc,
                'ip'      => $this->request->ip(),
                'data'    => !empty($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : '',
            ]);
        } catch (\Throwable $e) {
            // 日志记录失败不应影响主业务流程
            // 降级方案：写入PHP错误日志，确保操作可追溯
            error_log(
                '[LOG_FALLBACK] ' . date('Y-m-d H:i:s') . ' | '
                . ($user['username'] ?? 'guest') . ' | '
                . $this->request->controller() . '.' . $action . ' | '
                . $desc . ' | Error: ' . $e->getMessage(),
                0
            );
        }
    }
}
