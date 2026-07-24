<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\ContentActionPlan;

/**
 * 内容行动计划管理控制器 - V2.9.29 Sprint I-3
 */
class ContentActionPlanController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $status = $this->request->get('status', '');
        $query = ContentActionPlan::order('id', 'desc');
        if ($status !== '') $query->where('status', (int) $status);
        $list = $query->paginate(15);
        $this->assign('list', $list);
        $this->assign('status', $status);
        return $this->view('/content_action_plans');
    }

    public function cancel(int $id = 0)
    {
        ContentActionPlan::where('id', $id)->update(['status' => 2]);
        return $this->success('已取消');
    }
}
