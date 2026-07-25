<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ops\UserSegmentService;
use think\facade\Json;

/**
 * 用户分群控制器
 * V2.9.38 OPS-DEEP-2
 */
class UserSegmentController extends AdminBaseController
{
    protected UserSegmentService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new UserSegmentService();
    }

    public function index()
    {
        $page = (int) $this->request->param('page', 1);
        try {
            $query = \think\facade\Db::name('user_segment');
            $total = $query->count();
            $list = $query->order('id', 'desc')->page($page, 20)->select()->toArray();
        } catch (\Throwable $e) {
            $total = 0;
            $list = [];
        }
        return $this->view('user_segment/index', ['total' => $total, 'list' => $list, 'page' => $page]);
    }

    public function create()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $id = $this->service->createSegment($data);
            return Json::success('创建成功', ['id' => $id]);
        }
        return $this->view('user_segment/create');
    }

    public function compute()
    {
        $id = (int) $this->request->param('id', 0);
        $mode = $this->request->param('mode', 'realtime');
        $result = $this->service->computeSegment($id, $mode);
        return Json::success('计算完成', $result);
    }

    public function members()
    {
        $id = (int) $this->request->param('id', 0);
        $page = (int) $this->request->param('page', 1);
        $result = $this->service->getSegmentMembers($id, $page);
        return $this->view('user_segment/members', $result);
    }

    public function profile()
    {
        $id = (int) $this->request->param('id', 0);
        $profile = $this->service->getSegmentProfile($id);
        return Json::success('ok', $profile);
    }
}
