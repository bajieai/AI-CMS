<?php
declare(strict_types=1);
namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\admin\SystemHealthService;

class SystemHealthController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    public function index()
    {
        $checks = SystemHealthService::checkAll();
        $allOk = true;
        foreach ($checks as $check) { if ($check['status'] !== 'ok') { $allOk = false; break; } }
        $this->assign(['checks' => $checks, 'all_ok' => $allOk]);
        return $this->view('/system_health_index');
    }
}
