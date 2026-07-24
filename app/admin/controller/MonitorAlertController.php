<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\sys\SystemMonitorService;
use app\common\service\sys\MonitorAlertService;

class MonitorAlertController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $server = SystemMonitorService::getServerStatus();
        $app = SystemMonitorService::getApplicationStatus();
        $db = SystemMonitorService::getDatabaseStatus();
        $cache = SystemMonitorService::getCacheStatus();
        $queue = SystemMonitorService::getQueueStatus();
        $health = SystemMonitorService::getHealthCheck();

        return $this->view('/sys/monitor', compact('server', 'app', 'db', 'cache', 'queue', 'health'));
    }

    public function alertList()
    {
        $page = (int)($this->request->param('page', 1));
        $result = MonitorAlertService::getAlertList($page, 20);
        return $this->view('/sys/alert', $result);
    }

    public function create()
    {
        return $this->view('/sys/alert_form');
    }

    public function save()
    {
        $data = $this->request->post();
        $id = MonitorAlertService::createAlert($data);
        return json(['code' => 0, 'msg' => '创建成功', 'data' => ['id' => $id]]);
    }

    public function edit()
    {
        $id = (int)$this->request->param('id');
        $alert = MonitorAlertService::getAlert($id);
        return $this->view('/sys/alert_form', compact('alert'));
    }

    public function update()
    {
        $id = (int)$this->request->post('id');
        $data = $this->request->post();
        MonitorAlertService::updateAlert($id, $data);
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    public function delete()
    {
        $id = (int)$this->request->post('id');
        MonitorAlertService::deleteAlert($id);
        return json(['code' => 0, 'msg' => '删除成功']);
    }
}
