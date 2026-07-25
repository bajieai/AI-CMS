<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\perf\DbReadWriteService;
use think\facade\Json;

/**
 * 读写分离监控控制器
 * V2.9.38 PERF-II-1
 */
class DbRwController extends AdminBaseController
{
    protected DbReadWriteService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new DbReadWriteService();
    }

    public function index()
    {
        $status = $this->service->getStatus();
        $delay = $this->service->getDelay();
        $stats = $this->service->getQueryStats();
        return $this->view('db_rw/index', ['status' => $status, 'delay' => $delay, 'stats' => $stats]);
    }

    public function forceMaster()
    {
        $this->service->forceMaster('');
        return Json::success('已切换到主库模式');
    }

    public function failover()
    {
        $result = $this->service->autoFailover();
        return Json::success($result ? '故障转移已启用' : '所有从库正常', ['failover' => $result]);
    }
}
