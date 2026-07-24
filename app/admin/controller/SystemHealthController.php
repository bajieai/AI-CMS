<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\SystemHealthService;

class SystemHealthController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    public function index()
    {
        $service = new SystemHealthService();
        $health = $service->getHealthStatus();
        $this->assign(['health' => $health, 'menuActive' => 'system_health']);
        return $this->view('/system_health/index');
    }
}
