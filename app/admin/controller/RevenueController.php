<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\RevenueService;
use app\common\service\ReportService;
use app\common\service\TemplateStoreOpsService;
use app\common\service\UsageStatsService;

/**
 * V2.9.25 N-3/N-4: 营收统计 + 数据导出控制器
 */
class RevenueController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 营收统计首页
     */
    public function index()
    {
        $service = new RevenueService();
        $start = $this->request->get('start', '');
        $end = $this->request->get('end', '');

        $overview = $service->getOverview($start, $end);

        $this->assign([
            'overview' => $overview,
            'menuActive' => 'revenue_stats',
        ]);
        return $this->view('/stats/revenue');
    }

    /**
     * 结算列表
     */
    public function settlements()
    {
        $service = new RevenueService();
        $page = (int)$this->request->get('page', 1);
        $result = $service->getSettlementList($page);

        $this->assign([
            'list' => $result['list'],
            'total' => $result['total'],
            'menuActive' => 'revenue_stats',
        ]);
        return $this->view('/stats/settlements');
    }

    /**
     * 创建结算批次
     */
    public function createSettlement()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }

        $start = $this->request->post('start', '');
        $end = $this->request->post('end', '');
        $rate = (float)$this->request->post('commission_rate', '0.1');

        $service = new RevenueService();
        $result = $service->createSettlement($start, $end, $rate);
        return json($result);
    }

    /**
     * 审核结算
     */
    public function auditSettlement()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }

        $id = (int)$this->request->post('id', 0);
        $remark = $this->request->post('remark', '');

        $service = new RevenueService();
        $result = $service->auditSettlement($id, $this->adminName ?? 'admin', $remark);
        return json($result);
    }

    /**
     * 数据导出页面（N-4）
     */
    public function export()
    {
        $this->assign([
            'menuActive' => 'data_export',
        ]);
        return $this->view('/stats/export');
    }

    /**
     * 执行导出
     */
    public function doExport()
    {
        $type = $this->request->get('type', 'install_trend');
        $format = $this->request->get('format', 'csv');
        $start = $this->request->get('start', '');
        $end = $this->request->get('end', '');

        $reportService = new ReportService();
        $data = [];

        switch ($type) {
            case 'install_trend':
                $opsService = new TemplateStoreOpsService();
                $data = $opsService->getDashboardStats($start, $end);
                break;
            case 'usage_stats':
                $usageService = new UsageStatsService();
                $data = $usageService->getOverview($start, $end);
                $data['usage_trend'] = $usageService->getUsageTrend($start, $end);
                break;
            case 'revenue':
                $revService = new RevenueService();
                $data = $revService->getOverview($start, $end);
                break;
            default:
                return json(['code' => 1, 'msg' => '不支持的导出类型']);
        }

        $result = $reportService->smartExport($type, $data, $format);
        $filename = $type . '_' . date('Ymd') . '.' . $result['ext'];

        return response($result['content'], 200, [
            'Content-Type' => $result['mime'],
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
