<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ops\ContentQualityMonitorService;
use think\facade\Json;

/**
 * 质量监控中心控制器
 * V2.9.38 OPS-DEEP-4
 */
class QualityMonitorController extends AdminBaseController
{
    protected ContentQualityMonitorService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new ContentQualityMonitorService();
    }

    public function index()
    {
        try {
            $overview = $this->service->getOverview();
        } catch (\Throwable $e) {
            $overview = [];
        }
        return $this->view('quality_monitor/index', ['overview' => $overview]);
    }

    public function trend()
    {
        $days = (int) $this->request->param('days', 30);
        $trend = $this->service->getQualityTrend($days);
        return Json::success('ok', ['trend' => $trend]);
    }

    public function lowQuality()
    {
        $page = (int) $this->request->param('page', 1);
        $threshold = (int) $this->request->param('threshold', 60);
        $result = $this->service->getLowQualityContents($threshold, $page);
        return $this->view('quality_monitor/low_quality', $result);
    }

    public function report()
    {
        $period = $this->request->param('period', 'daily');
        $report = $this->service->generateReport($period);
        return Json::success('ok', $report);
    }

    public function alertConfig()
    {
        if ($this->request->isPost()) {
            $config = $this->request->post();
            $this->service->setAlertConfig($config);
            return Json::success('配置已保存');
        }
        $config = $this->service->getAlertConfig();
        return $this->view('quality_monitor/alert_config', ['config' => $config]);
    }
}
