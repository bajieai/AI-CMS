<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\SecurityLogService;
use app\common\service\SecurityReportService;
use think\App;
use think\facade\View;

/**
 * V2.9.35 SEC-6: 安全审计控制器
 */
class SecurityAuditController extends AdminBaseController
{
    protected SecurityReportService $reportService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->reportService = new SecurityReportService();
    }

    /**
     * 审计报告页
     */
    public function report()
    {
        $type = $this->request->get('type', 'daily');

        $report = match($type) {
            'weekly'  => $this->reportService->generateWeeklyReport(),
            'monthly' => $this->reportService->generateMonthlyReport(),
            default   => $this->reportService->generateDailyReport(),
        };

        View::assign([
            'report' => $report,
            'type'   => $type,
        ]);

        return $this->view('/security_audit/report');
    }
}
