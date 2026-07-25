<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\compliance\AuditLogQueryService;
use app\common\service\compliance\ComplianceReportService;
use app\common\service\compliance\DataClassificationService;

/**
 * 合规管理后台控制器 - V2.9.40 COMPLIANCE2
 */
class ComplianceController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 合规中心首页
     */
    public function index()
    {
        $reportService = new ComplianceReportService();
        $gdprScore = $reportService->generateGdprReport()['overall_score'] ?? 0;
        $securityScore = $reportService->generateSecurityReport()['overall_score'] ?? 0;

        $classificationService = new DataClassificationService();
        $classStats = $classificationService->getStats();

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => [
                'gdpr_score'     => $gdprScore,
                'security_score' => $securityScore,
                'classification' => $classStats,
            ]]);
        }

        $this->assign('gdpr_score', $gdprScore);
        $this->assign('security_score', $securityScore);
        $this->assign('classification', $classStats);
        return $this->view('/compliance/index');
    }

    /**
     * 审计日志查询
     */
    public function auditLog()
    {
        $queryService = new AuditLogQueryService();

        if ($this->request->isPost()) {
            $filters = $this->request->post();
            $result = $queryService->search($filters);
            return json(['code' => 0, 'msg' => 'success', 'data' => $result]);
        }

        $filterOptions = $queryService->getFilterOptions();
        $riskStats = $queryService->getRiskStats();

        $this->assign('filter_options', $filterOptions);
        $this->assign('risk_stats', $riskStats);
        return $this->view('/compliance/audit_log');
    }

    /**
     * 审计日志详情
     */
    public function auditDetail(int $id)
    {
        $queryService = new AuditLogQueryService();
        $detail = $queryService->getDetail($id);

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $detail]);
        }

        $this->assign('detail', $detail);
        return $this->view('/compliance/audit_detail');
    }

    /**
     * GDPR合规报告
     */
    public function gdprReport()
    {
        $reportService = new ComplianceReportService();
        $report = $reportService->generateGdprReport();

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $report]);
        }

        $this->assign('report', $report);
        return $this->view('/compliance/gdpr_report');
    }

    /**
     * 数据安全报告
     */
    public function securityReport()
    {
        $reportService = new ComplianceReportService();
        $report = $reportService->generateSecurityReport();

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $report]);
        }

        $this->assign('report', $report);
        return $this->view('/compliance/security_report');
    }

    /**
     * 审计统计报告
     */
    public function auditReport()
    {
        $period = $this->request->get('period', 'monthly');
        $reportService = new ComplianceReportService();
        $report = $reportService->generateAuditReport($period);

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $report]);
        }

        $this->assign('report', $report);
        return $this->view('/compliance/audit_report');
    }

    /**
     * 数据分级分类管理
     */
    public function classification()
    {
        $service = new DataClassificationService();

        if ($this->request->isPost()) {
            $itemId = (int) $this->request->post('item_id', 0);
            $itemType = $this->request->post('item_type', 'content');
            $level = $this->request->post('level', 'public');
            $reason = $this->request->post('reason', '');

            $service->classify($itemId, $itemType, $level, $reason);
            return json(['code' => 0, 'msg' => '分类标记成功']);
        }

        $list = $service->getList();
        $stats = $service->getStats();

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => ['list' => $list, 'stats' => $stats]]);
        }

        $this->assign('list', $list);
        $this->assign('stats', $stats);
        return $this->view('/compliance/classification');
    }

    /**
     * 导出审计日志
     */
    public function export()
    {
        $filters = $this->request->get();
        $format = $this->request->get('format', 'csv');

        $queryService = new AuditLogQueryService();
        $content = $queryService->export($filters, $format);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="audit_log_' . date('Ymd') . '.csv"');
        echo $content;
        exit;
    }
}
