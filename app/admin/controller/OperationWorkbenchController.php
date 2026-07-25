<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\operation\OperationWorkbenchService;

/**
 * 运营工作台控制器 — V2.9.34 OPS2-1/OPS2-2
 */
class OperationWorkbenchController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $service = new OperationWorkbenchService();
        $overview = $service->getOverview();
        $todoList = $service->getTodoList();
        $metricCards = $service->getMetricCards();
        $alerts = $service->getAlerts();
        $this->assign('overview', $overview);
        $this->assign('todoList', $todoList);
        $this->assign('metricCards', $metricCards);
        $this->assign('alerts', $alerts);
        $this->assign('menuActive', 'operation_workbench');
        return $this->view('/operation_workbench/index');
    }

    public function weeklyReport()
    {
        $service = new OperationWorkbenchService();
        $result = $service->getWeeklyReport();
        return json($result);
    }

    public function calendar()
    {
        $month = (int)$this->request->param('month', (int)date('n'));
        $service = new OperationWorkbenchService();
        $result = $service->getCalendar($month);
        return json($result);
    }
}
