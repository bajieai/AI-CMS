<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\template\TemplateAutoAuditService;

/**
 * 模板统计详情控制器 - V2.9.29 Sprint T-6
 */
class TemplateStatsDetailController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function detail(int $id = 0)
    {
        $this->assign('id', $id);
        return $this->view('/template_stats_detail');
    }

    public function compare()
    {
        $ids = $this->request->get('ids', '');
        $this->assign('ids', $ids);
        return $this->view('/template_stats_compare');
    }

    public function auditReport(int $id = 0)
    {
        $service = new TemplateAutoAuditService();
        $report = $service->getReport($id);
        $this->assign('report', $report);
        return $this->view('/template_audit_report');
    }
}
