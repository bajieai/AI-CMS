<?php
declare(strict_types=1);

namespace app\common\controller;

use app\common\model\Log as LogModel;
use app\common\service\CacheService;
use app\common\service\TemplateService;
use think\App;
use think\facade\Cache;
use think\facade\Config;

// V2.9.2 Logo相关: 直接使用Config模型避免命名冲突
use app\common\model\Config as CmsConfig;

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
     * 键为控制器蛇形名，值为菜单 active 标识
     */
    protected static array $menuMap = [
        'index'       => 'dashboard',
        'content'     => 'content',
        'cate'        => 'cate',
        'tag'         => 'tag',
        'user'        => 'user',
        'system'      => 'system',
        'log'         => 'log',
        'recycle'     => 'recycle',
        'media'       => 'media',
        'banner'      => 'banner',
        'link'        => 'link',
        'review'      => 'review',
        'backup'      => 'backup',
        // V2.3 新增模块
        'comment'     => 'comment',
        'member'      => 'member',
        'seo'         => 'seo',
        'export'      => 'export',
        'token'       => 'token',
        'notification'     => 'notification',
        'ad'               => 'ad',
        'link_group'       => 'link_group',
        // V2.4 新增模块
        'ai_model'         => 'ai_model',
        'ai_log'           => 'ai_log',
        'dashboard'        => 'dashboard',
        'member_level'     => 'member_level',
        'paid_order'       => 'paid_order',
        'form'             => 'form',
        'import'           => 'import',
        'points_rule'      => 'points_rule',
        'seo_keyword'      => 'seo_keyword',
        'visit_archive'    => 'visit_archive',
        'email_subscriber' => 'email_subscriber',
        // V2.5 新增模块
        'payment'          => 'payment',
        'ai_batch'         => 'ai_batch',
        'collect_source'   => 'collect_source',
        'collect_log'      => 'collect_log',
        'publish_platform' => 'publish_platform',
        'publish_log'      => 'publish_log',
        'email_template'   => 'email_template',
        'email_log'        => 'email_log',
        'plugin'           => 'plugin',
        'language'         => 'language',
        'theme_market'     => 'theme_market',
        // V2.6 AI内容模板
        'ai_template'      => 'ai_template',
        // V2.9 新增模块
        'coupon'           => 'coupon',
        'rating'           => 'rating',
        'template_design'  => 'template_design',
        // V2.9.1 新增模块
        'report'           => 'report',
        'api_doc'          => 'api_doc',
        // V2.9.2 新增模块
        'ai_translation'   => 'ai_translation',
        'plugin_market'    => 'plugin_market',
        'member_benefit'   => 'member_benefit',
        'monitor'          => 'monitor',
        // V2.9.9 新增模块
        'social_share'     => 'social_share',
        'workflow'         => 'workflow',
    ];

    /**
     * 精确菜单高亮映射（控制器.方法 → active标识）
     * 当同一控制器下不同方法需要高亮不同菜单项时使用
     */
    protected static array $menuActionMap = [
        'system.config'         => 'system_config',
        'seo_keyword.group'     => 'seo_keyword_group',
        // V2.5 精确菜单映射
        'payment.config'        => 'payment',
        'payment.revenue'       => 'payment_revenue',
        // V2.9.2 精确菜单映射
        'export.dialog'         => 'export_dialog',
        // V2.9.9 精确菜单映射
        'workflow.records'      => 'workflow_records',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    protected function initialize(): void
    {
        // 加载数据库系统配置到ThinkPHP Config（容错）
        try {
            load_cms_configs();
        } catch (\Throwable) {
            // 配置表可能尚未创建，降级跳过
        }

        // 设置后台模板路径（TemplateService 动态解析：template/admin/{$admin_theme}/）
        $adminPath = TemplateService::getAdminPath();
        $this->app->config->set([
            'view_path' => $adminPath,
        ], 'view');
        // 强制视图引擎重新读取配置（驱动实例会缓存配置，必须刷新）
        $this->app->view->engine()->config(['view_path' => $adminPath]);

        // ===== 编码根治：强制所有响应输出为 UTF-8 =====
        // 1. 设置视图引擎编码
        $this->app->view->engine()->config(['view_charset' => 'UTF-8']);
        // 2. 强制全局响应编码（覆盖任何可能的外部配置干扰）
        $this->app->config->set([
            'default_charset' => 'utf-8',
            'default_return_type' => 'html',
        ], 'app');
        // 3. 发送 Content-Type 响应头（确保传输层不丢失编码信息）
        header('Content-Type: text/html; charset=utf-8');
        // 4. 设置数据库连接编码（确保 ORM 层写入时使用正确编码）
        try {
            \think\facade\Db::execute("SET NAMES utf8mb4");
        } catch (\Throwable) {
            // 数据库未连接时静默跳过
        }

        // 注入当前用户角色信息到所有视图
        $roleId = (int) session('role_id');
        $this->app->view->assign('is_super_admin', $roleId === 1);

        // V2.4 多模板风格：注入后台主题变量到所有视图
        $adminTheme = TemplateService::getAdminTheme();
        $this->app->view->assign([
            'admin_theme'      => $adminTheme,
            'admin_theme_path' => '/template/admin/' . $adminTheme . '/',
            // V2.6 静态资源分离：skin目录指向public/skin/，浏览器可直接访问
            'skin_admin'       => '/skin/admin/' . $adminTheme . '/',
        ]);

        // 自动注入当前菜单高亮标识（兼容完整类名返回）
        $controller = $this->request->controller();
        if (str_contains($controller, '\\')) {
            $controller = substr($controller, strrpos($controller, '\\') + 1);
        }
        $controller = str_replace('Controller', '', $controller);
        // 驼峰转蛇形（如 LinkGroup → link_group），与菜单配置保持一致
        $controller = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $controller));

        // 优先使用 控制器.方法名 精确匹配，回退到控制器级匹配
        $action = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $this->request->action()));
        $actionKey = $controller . '.' . $action;
        $menuActive = self::$menuActionMap[$actionKey] ?? (self::$menuMap[$controller] ?? '');
        $this->app->view->assign('menuActive', $menuActive);

        // 根据角色权限过滤菜单并注入视图（使用静态缓存避免同一请求重复计算）
        $filteredMenus = $this->getFilteredMenus($roleId);
        $this->app->view->assign('sidebarMenus', $filteredMenus);

        // V2.6 双栏菜单：注入菜单JSON数据供 admin-sidebar.js 使用
        $this->app->view->assign('menuDataJson', json_encode($filteredMenus, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG));

        // V2.9.2 注入网站Logo及相关配置到后台视图
        $cmsConfigs = CmsConfig::whereIn('name', ['site_logo', 'logo_icon_only', 'logo_name'])->column('value', 'name');
        $siteLogo   = $cmsConfigs['site_logo'] ?? '';
        $iconOnly   = ($cmsConfigs['logo_icon_only'] ?? '') === '1';
        $brandName  = $cmsConfigs['logo_name'] ?: '八界AI-CMS';
        $this->app->view->assign([
            'site_logo'      => $siteLogo,
            'logo_icon_only' => $iconOnly,
            'logo_name'      => $cmsConfigs['logo_name'] ?? '',
            'brand_name'     => $brandName,       // 自定义品牌名称
        ]);
    }

    /**
     * 判断是否为 PJAX 局部刷新请求（排除 PJAX 的普通 AJAX 检测）
     * PJAX 请求携带 X-PJAX 头，需要渲染完整 HTML 由中间件提取内容区，
     * 不能像普通 AJAX 那样提前返回 JSON 数据
     */
    protected function isRealAjax(): bool
    {
        return $this->request->isAjax() && !$this->request->header('X-PJAX');
    }

    /**
     * 获取已过滤的菜单（带静态缓存）
     * 功能开关对所有人生效（含超管），权限过滤仅对非超管生效
     */
    protected function getFilteredMenus(int $roleId): array
    {
        $cacheKey = 'admin_filtered_menus_' . $roleId;

        return Cache::tag(CacheService::TAG_CONFIG)->remember($cacheKey, function () use ($roleId) {
            $menus = Config::get('menu', []);

            // 1. 功能开关过滤 — 对所有人生效（含超管）
            $hiddenMenuIds = $this->getDisabledModuleMenuIds();
            if (!empty($hiddenMenuIds)) {
                $menus = $this->removeDisabledMenus($menus, $hiddenMenuIds);
            }

            // 2. 权限过滤 — 仅对非超管生效
            if ($roleId !== 1) {
                $permissions = Config::get('permission.roles.' . $roleId . '.permissions', []);
                if ($permissions !== '*') {
                    $menus = $this->filterMenu($menus, (array) $permissions);
                }
            }

            return $menus;
        }, 3600);
    }

    /**
     * 获取已禁用模块关联的菜单ID列表
     */
    protected function getDisabledModuleMenuIds(): array
    {
        try {
            $disabledModules = \app\common\model\Module::getDisabledMenuIds();
            return $disabledModules;
        } catch (\Throwable) {
            // 模块表可能尚未创建，降级返回空数组
            return [];
        }
    }

    /**
     * 从菜单中移除已禁用模块关联的菜单项
     */
    protected function removeDisabledMenus(array $menus, array $hiddenMenuIds): array
    {
        $result = [];
        foreach ($menus as $group) {
            $filteredChildren = [];
            foreach ($group['children'] ?? [] as $item) {
                if (!in_array($item['id'] ?? 0, $hiddenMenuIds, true)) {
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
        $controller = $this->request->controller();
        if (str_contains($controller, '\\')) {
            $controller = substr($controller, strrpos($controller, '\\') + 1);
        }
        $controller = str_replace('Controller', '', $controller);
        $controller = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $controller));
        $action = strtolower($this->request->action());
        
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
        ], 200, [], ['json_encode_param' => JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE]);
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
        ], 200, [], ['json_encode_param' => JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE]);
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
