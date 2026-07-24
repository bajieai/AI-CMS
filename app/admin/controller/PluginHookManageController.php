<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\PluginHookService;
use think\App;
use think\facade\View;

/**
 * V2.9.35 PLUG-2: 插件钩子管理控制器（列表展示）
 *
 * 职责划分：
 * - PluginHookManageController（本类）: 钩子列表展示页面（只读视图）
 * - PluginHookController: 钩子CRUD操作（注册/取消注册/优先级/性能分析/系统钩子）
 *
 * 路由映射：
 * - GET plugin_hook/index → PluginHookManageController@index（列表展示）
 * - GET plugin_hook/detail/:id → PluginHookController@detail（详情API）
 * - POST plugin_hook/register → PluginHookController@register（注册API）
 * - POST plugin_hook/unregister/:id → PluginHookController@unregister（取消注册API）
 */
class PluginHookManageController extends AdminBaseController
{
    protected PluginHookService $hookService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->hookService = new PluginHookService();
    }

    public function index()
    {
        $list = $this->hookService->getHookList();
        $stats = $this->hookService->getHookStats();
        View::assign(['list' => $list, 'stats' => $stats]);
        return $this->view('/plugin_hook/index');
    }
}
