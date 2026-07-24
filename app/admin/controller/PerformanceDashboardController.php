<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\PerformanceReportService;
use think\App;
use think\facade\View;

/**
 * V2.9.35 PERF-5: 性能监控看板控制器
 */
class PerformanceDashboardController extends AdminBaseController
{
    protected PerformanceReportService $reportService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->reportService = new PerformanceReportService();
    }

    public function index()
    {
        $overview = $this->reportService->getOverview();
        $trend = $this->reportService->getTrend();

        View::assign([
            'overview' => $overview,
            'trend'    => $trend,
        ]);

        return $this->view('/performance_dashboard/index');
    }

    public function slowQueries()
    {
        $list = $this->reportService->getTopSlowRequests();
        return json(['code' => 0, 'data' => $list]);
    }

    public function report()
    {
        $overview = $this->reportService->getOverview();
        $trend = $this->reportService->getTrend();
        $topSlow = $this->reportService->getTopSlowRequests();
        $urlPerf = $this->reportService->getUrlPerformance();

        return json(['code' => 0, 'data' => [
            'overview'  => $overview,
            'trend'     => $trend,
            'top_slow'  => $topSlow,
            'url_perf'  => $urlPerf,
        ]]);
    }
}
