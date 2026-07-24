<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ai\ContentQualityScoreService;
use app\common\service\ai\ContentRepairPipelineService;
use app\common\service\ContentQualityDashboardService;

/**
 * 内容质量管理控制器 — V2.9.33 AI5
 */
class ContentQualityController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * AI5-1: 单篇内容质量评分
     */
    public function score(int $id)
    {
        $service = new ContentQualityScoreService();
        $result = $service->score($id, 'manual');

        if ($this->request->isAjax()) {
            return json($result);
        }

        $this->assign('result', $result);
        $this->assign('content_id', $id);
        $this->assign('menuActive', 'content_quality');
        return $this->view('/content_quality/score');
    }

    /**
     * AI5-1: 批量评分
     */
    public function batchScore()
    {
        $ids = $this->request->post('ids', []);
        if (empty($ids)) {
            return $this->error('请选择要评分的内容');
        }

        $service = new ContentQualityScoreService();
        $results = $service->batchScore($ids);

        $lowCount = 0;
        foreach ($results as $r) {
            if (($r['success'] ?? false) && ($r['scores']['total'] ?? 100) < 60) $lowCount++;
        }

        return $this->success("评分完成，低分内容（<60分）：{$lowCount} 条", ['results' => $results]);
    }

    /**
     * AI5-2: 修复单篇内容
     */
    public function repair(int $id)
    {
        $mode = $this->request->post('mode', 'suggested');
        $service = new ContentRepairPipelineService();
        $report = $service->repair($id, $mode);

        if ($this->request->isAjax()) {
            return json(['success' => true, 'report' => $report]);
        }

        $this->assign('report', $report);
        $this->assign('menuActive', 'content_quality');
        return $this->view('/content_quality/repair');
    }

    /**
     * AI5-2: 批量修复
     */
    public function batchRepair()
    {
        $ids = $this->request->post('ids', []);
        if (empty($ids)) {
            return $this->error('请选择要修复的内容');
        }

        $service = new ContentRepairPipelineService();
        $result = $service->batchRepair($ids);

        return $this->success("批量修复完成: 成功{$result['success']}篇，失败{$result['failed']}篇", $result);
    }

    /**
     * AI5-5: 内容质量看板
     */
    public function dashboard()
    {
        $service = new ContentQualityDashboardService();
        $overview = $service->getOverview();
        $trend = $service->getTrend(30);
        $dimensions = $service->getDimensionDistribution();
        $lowTop10 = $service->getLowScoreTop10();
        $issues = $service->getHighFrequencyIssues();

        $this->assign([
            'overview'   => $overview,
            'trend'      => $trend,
            'dimensions' => $dimensions,
            'lowTop10'   => $lowTop10,
            'issues'     => $issues,
            'menuActive' => 'content_quality',
        ]);

        return $this->view('/content_quality/dashboard');
    }

    /**
     * AI5-5: 导出数据
     */
    public function export()
    {
        $format = $this->request->get('format', 'excel');
        $service = new ContentQualityDashboardService();
        $data = $service->exportData($format);

        return $this->success('导出成功', $data);
    }
}
