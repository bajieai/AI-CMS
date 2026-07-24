<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\report\ContentAnalysisService;

/**
 * 内容分析报表控制器 — V2.9.34 DR-4
 */
class ContentAnalysisController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $service = new ContentAnalysisService();
        $production = $service->getProductionAnalysis();
        $consumption = $service->getConsumptionAnalysis();
        $interaction = $service->getInteractionAnalysis();
        $seo = $service->getSeoAnalysis();
        $this->assign('production', $production);
        $this->assign('consumption', $consumption);
        $this->assign('interaction', $interaction);
        $this->assign('seo', $seo);
        $this->assign('menuActive', 'content_analysis');
        return $this->view('/report/analysis');
    }

    public function production()
    {
        $service = new ContentAnalysisService();
        $result = $service->getProductionAnalysis();
        return json($result);
    }

    public function consumption()
    {
        $service = new ContentAnalysisService();
        $result = $service->getConsumptionAnalysis();
        return json($result);
    }

    public function interaction()
    {
        $service = new ContentAnalysisService();
        $result = $service->getInteractionAnalysis();
        return json($result);
    }

    public function seo()
    {
        $service = new ContentAnalysisService();
        $result = $service->getSeoAnalysis();
        return json($result);
    }
}
