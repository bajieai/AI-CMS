<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\member\MemberPointsService;

/**
 * 积分管理控制器 — V2.9.34 MEM-2
 */
class MemberPointsController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $memberId = (int)$this->request->param('member_id', 0);
        $params = $this->request->param();
        $service = new MemberPointsService();
        $logs = $service->getLogs($memberId, $params);
        $stats = $service->getStats();
        $this->assign('logs', $logs);
        $this->assign('stats', $stats);
        $this->assign('member_id', $memberId);
        $this->assign('menuActive', 'member_points');
        return $this->view('/member_points/index');
    }

    public function adjust()
    {
        $memberId = (int)$this->request->post('member_id', 0);
        $points = (int)$this->request->post('points', 0);
        $reason = (string)$this->request->post('reason', '');
        $service = new MemberPointsService();
        if ($points >= 0) {
            $result = $service->addPoints($memberId, $points, 'manual', $reason);
        } else {
            $result = $service->deductPoints($memberId, abs($points), 'manual', $reason);
        }
        if ($result['success'] ?? false) {
            return $this->success('调整成功', $result);
        }
        return $this->error($result['message'] ?? '调整失败');
    }
}
