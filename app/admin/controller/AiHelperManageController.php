<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ai\AiRecommendService;
use app\common\service\ai\AiQaService;
use app\common\service\ai\AiReportAnalysisService;

/**
 * AI助手管理
 * V2.9.37 AI-HELPER
 */
class AiHelperManageController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 推荐引擎配置
     */
    public function recommend()
    {
        $service = new AiRecommendService();
        $config = $service->getConfig();
        $stats = $service->getStats();
        return $this->view('/ai_helper_recommend', ['config' => $config, 'stats' => $stats]);
    }

    /**
     * 问答管理
     */
    public function qa()
    {
        $service = new AiQaService();
        $stats = $service->getStats();
        return $this->view('/ai_helper_qa', ['stats' => $stats]);
    }

    /**
     * 报表解读
     */
    public function report()
    {
        $service = new AiReportAnalysisService();
        $reportType = $this->request->get('type', 'content');
        $startDate = $this->request->get('start', date('Y-m-d', strtotime('-7 days')));
        $endDate = $this->request->get('end', date('Y-m-d'));
        $analysis = $service->analyze($reportType, ['start' => $startDate, 'end' => $endDate]);
        return $this->view('/ai_helper_report', [
            'analysis' => $analysis, 'report_type' => $reportType,
            'start_date' => $startDate, 'end_date' => $endDate,
        ]);
    }

    /**
     * 自然语言查询
     */
    public function naturalQuery()
    {
        $query = $this->request->post('query', '');
        if (empty($query)) {
            return json(['success' => false, 'msg' => '请输入查询']);
        }
        $service = new AiReportAnalysisService();
        $result = $service->naturalLanguageQuery($query);
        return json(['success' => true, 'data' => $result]);
    }
}
