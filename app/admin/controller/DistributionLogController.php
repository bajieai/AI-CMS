<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\channel\DistributionLogService;

/**
 * 分发日志控制器 — V2.9.34 CD-3
 */
class DistributionLogController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $params = $this->request->param();
        $service = new DistributionLogService();
        $list = $service->getLogs($params);
        $overview = $service->getEffectOverview();
        $ranking = $service->getEffectRanking();
        $this->assign('list', $list);
        $this->assign('overview', $overview);
        $this->assign('ranking', $ranking);
        $this->assign('menuActive', 'distribution_log');
        return $this->view('/distribution_log/index');
    }
}
