<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\sys\AutoScaleService;

class AutoScaleController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $config = AutoScaleService::getConfig();
        $current = AutoScaleService::getCurrentScale();
        $history = AutoScaleService::getScaleHistory();
        return $this->view('/sys/auto_scale', compact('config', 'current', 'history'));
    }

    public function save()
    {
        $config = $this->request->post();
        AutoScaleService::saveConfig($config);
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    public function scaleUp()
    {
        $service = $this->request->post('service', 'php-worker');
        $count = (int)$this->request->post('count', 1);
        $result = AutoScaleService::scaleUp($service, $count);
        return json(['code' => 0, 'msg' => '扩容成功', 'data' => $result]);
    }

    public function scaleDown()
    {
        $service = $this->request->post('service', 'php-worker');
        $count = (int)$this->request->post('count', 1);
        $result = AutoScaleService::scaleDown($service, $count);
        return json(['code' => 0, 'msg' => '缩容成功', 'data' => $result]);
    }
}
