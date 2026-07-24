<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\PluginManagerService;
use think\App;
use think\facade\View;

/**
 * V2.9.35 PLUG-1: 插件管理控制器（区别于V2.9.23 PluginController）
 */
class PluginManagerController extends AdminBaseController
{
    protected PluginManagerService $managerService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->managerService = new PluginManagerService();
    }

    public function index()
    {
        $list = $this->managerService->getList();
        $available = $this->managerService->scanAvailable();
        View::assign(['list' => $list, 'available' => $available]);
        return $this->view('/plugin_v2/index');
    }

    public function detail()
    {
        $id = (int) $this->request->get('id', 0);
        $plugin = $this->managerService->getDetail($id);
        View::assign(['plugin' => $plugin]);
        return $this->view('/plugin_v2/detail');
    }

    public function install()
    {
        $identifier = $this->request->post('identifier', '');
        $result = $this->managerService->install($identifier);
        return json(['code' => $result['success'] ? 0 : 1, 'msg' => $result['message']]);
    }

    public function uninstall()
    {
        $id = (int) $this->request->post('id', 0);
        $result = $this->managerService->uninstall($id);
        return json(['code' => $result['success'] ? 0 : 1, 'msg' => $result['message']]);
    }

    public function enable()
    {
        $id = (int) $this->request->post('id', 0);
        $result = $this->managerService->toggleStatus($id, 1);
        return json(['code' => $result['success'] ? 0 : 1, 'msg' => $result['message']]);
    }

    public function disable()
    {
        $id = (int) $this->request->post('id', 0);
        $result = $this->managerService->toggleStatus($id, 2);
        return json(['code' => $result['success'] ? 0 : 1, 'msg' => $result['message']]);
    }
}
