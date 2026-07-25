<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\UsageStatsService;
use app\common\service\TemplateStoreOpsService;

/**
 * V2.9.25 N-2: 使用统计控制器
 */
class UsageStatsController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 使用统计首页
     */
    public function index()
    {
        $service = new UsageStatsService();
        $start = $this->request->get('start', '');
        $end = $this->request->get('end', '');

        $overview = $service->getOverview($start, $end);
        $trend = $service->getUsageTrend($start, $end);
        $hotTemplates = $service->getHotTemplatesByViews(10, $start);

        $this->assign([
            'overview' => $overview,
            'trend' => $trend,
            'hotTemplates' => $hotTemplates,
            'menuActive' => 'usage_stats',
        ]);
        return $this->view('/stats/usage_stats');
    }

    /**
     * 安装趋势页（N-1）
     */
    public function installTrend()
    {
        $service = new TemplateStoreOpsService();
        $start = $this->request->get('start', '');
        $end = $this->request->get('end', '');

        $stats = $service->getDashboardStats($start, $end);

        $this->assign([
            'stats' => $stats,
            'menuActive' => 'install_trend',
        ]);
        return $this->view('/stats/install_trend');
    }
}
