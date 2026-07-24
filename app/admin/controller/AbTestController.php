<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ops\AbTestService;
use think\facade\Json;

/**
 * A/B测试控制器
 * V2.9.38 OPS-DEEP-1
 */
class AbTestController extends AdminBaseController
{
    protected AbTestService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new AbTestService();
    }

    public function index()
    {
        $page = (int) $this->request->param('page', 1);
        $query = \app\common\model\AbTest::where('id', '>', 0);
        $total = $query->count();
        $list = $query->order('id', 'desc')->page($page, 20)->select()->toArray();
        return $this->view('ab_test/index', ['total' => $total, 'list' => $list, 'page' => $page]);
    }

    public function create()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['created_by'] = $this->adminId ?? 0;
            $id = $this->service->createTest($data);
            return Json::success('创建成功', ['id' => $id]);
        }
        return $this->view('ab_test/create');
    }

    public function start()
    {
        $id = (int) $this->request->param('id', 0);
        $this->service->startTest($id);
        return Json::success('测试已启动');
    }

    public function pause()
    {
        $id = (int) $this->request->param('id', 0);
        $this->service->pauseTest($id);
        return Json::success('测试已暂停');
    }

    public function stop()
    {
        $id = (int) $this->request->param('id', 0);
        $this->service->stopTest($id);
        return Json::success('测试已停止');
    }

    public function results()
    {
        $id = (int) $this->request->param('id', 0);
        $result = $this->service->analyzeResult($id);
        return $this->view('ab_test/results', ['result' => $result, 'test_id' => $id]);
    }

    public function applyWinner()
    {
        $id = (int) $this->request->param('id', 0);
        $this->service->applyWinner($id);
        return Json::success('已应用获胜版本');
    }
}
