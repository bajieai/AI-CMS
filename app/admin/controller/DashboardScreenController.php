<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\report\DashboardScreenService;

/**
 * 数据可视化大屏控制器 — V2.9.34 DR-2
 */
class DashboardScreenController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $screenId = (int)$this->request->param('screen_id', 1);
        $service = new DashboardScreenService();
        $data = $service->getScreen($screenId);
        $this->assign('data', $data);
        $this->assign('screenId', $screenId);
        $this->assign('menuActive', 'dashboard_screen');
        return $this->view('/dashboard_screen/index');
    }

    public function contentScreen()
    {
        $service = new DashboardScreenService();
        $data = $service->getContentScreenData();
        return json($data);
    }

    public function userScreen()
    {
        $service = new DashboardScreenService();
        $data = $service->getUserScreenData();
        return json($data);
    }

    public function revenueScreen()
    {
        $service = new DashboardScreenService();
        $data = $service->getRevenueScreenData();
        return json($data);
    }
}
