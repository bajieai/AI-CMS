<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\PluginHookService;
use think\App;
use think\Request;

/**
 * V2.9.35 PLUG-2: 插件钩子管理控制器
 */
class PluginHookController extends AdminBaseController
{
    protected PluginHookService $hookService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->hookService = new PluginHookService();
    }

    /**
     * 钩子列表页
     */
    public function index(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $pageSize = (int) $request->get('page_size', 20);
        $pluginId = (int) $request->get('plugin_id', 0);

        $result = $this->hookService->getHooks($page, $pageSize, $pluginId);

        return $this->view('/plugin_hook/index', [
            'hooks' => $result['list'] ?? [],
            'total' => $result['total'] ?? 0,
            'page' => $page,
            'pageSize' => $pageSize,
            'pluginId' => $pluginId,
            'plugins' => $this->hookService->getPluginList(),
        ]);
    }

    /**
     * 钩子详情
     */
    public function detail(int $id)
    {
        $hook = $this->hookService->getHookById($id);
        if (!$hook) {
            return json(['code' => 1, 'msg' => '钩子不存在']);
        }
        return json(['code' => 0, 'data' => $hook]);
    }

    /**
     * 注册钩子
     */
    public function register(Request $request)
    {
        $data = $request->post();
        $required = ['hook_name', 'plugin_id', 'callback'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return json(['code' => 1, 'msg' => "缺少字段: {$field}"]);
            }
        }

        $result = $this->hookService->registerHook($data);
        return json($result);
    }

    /**
     * 取消注册钩子
     */
    public function unregister(int $id)
    {
        $result = $this->hookService->unregisterHook($id);
        return json($result);
    }

    /**
     * 更新钩子优先级
     */
    public function updatePriority(Request $request)
    {
        $id = (int) $request->post('id');
        $priority = (int) $request->post('priority', 10);

        $result = $this->hookService->updatePriority($id, $priority);
        return json($result);
    }

    /**
     * 钩子性能分析
     */
    public function performance(Request $request)
    {
        $days = (int) $request->get('days', 7);
        $stats = $this->hookService->getPerformanceStats($days);
        return json(['code' => 0, 'data' => $stats]);
    }

    /**
     * 系统预置钩子列表
     */
    public function systemHooks()
    {
        $hooks = $this->hookService->getSystemHooks();
        return json(['code' => 0, 'data' => $hooks]);
    }
}
