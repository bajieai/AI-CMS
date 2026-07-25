<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ops\OpsAutomationService;
use think\facade\Json;

/**
 * 运营自动化控制器
 * V2.9.38 OPS-DEEP-3
 */
class OpsAutomationController extends AdminBaseController
{
    protected OpsAutomationService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new OpsAutomationService();
    }

    public function index()
    {
        $page = (int) $this->request->param('page', 1);
        try {
            $query = \think\facade\Db::name('ops_automation_flow');
            $total = $query->count();
            $list = $query->order('id', 'desc')->page($page, 20)->select()->toArray();
        } catch (\Throwable $e) {
            $total = 0;
            $list = [];
        }
        return $this->view('ops_automation/index', ['total' => $total, 'list' => $list, 'page' => $page]);
    }

    public function create()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $id = $this->service->createFlow($data);
            return Json::success('创建成功', ['id' => $id]);
        }
        return $this->view('ops_automation/create');
    }

    public function enable()
    {
        $id = (int) $this->request->param('id', 0);
        $this->service->enableFlow($id);
        return Json::success('已启用');
    }

    public function disable()
    {
        $id = (int) $this->request->param('id', 0);
        $this->service->disableFlow($id);
        return Json::success('已禁用');
    }

    public function test()
    {
        $id = (int) $this->request->param('id', 0);
        $result = $this->service->testFlow($id);
        return Json::success('测试完成', $result);
    }
}
