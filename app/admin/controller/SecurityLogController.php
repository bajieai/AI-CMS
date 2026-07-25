<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\SecurityLogService;
use think\App;
use think\Request;

/**
 * V2.9.35 SEC-6: 安全日志控制器
 */
class SecurityLogController extends AdminBaseController
{
    protected SecurityLogService $logService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->logService = new SecurityLogService();
    }

    /**
     * 日志列表页
     */
    public function index(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $pageSize = (int) $request->get('page_size', 20);
        $filters = [
            'event_type' => $request->get('event_type', ''),
            'severity' => $request->get('severity', ''),
            'ip' => $request->get('ip', ''),
            'start_date' => $request->get('start_date', ''),
            'end_date' => $request->get('end_date', ''),
        ];

        $result = $this->logService->getLogs($page, $pageSize, array_filter($filters));

        return $this->view('/security_log/index', [
            'logs' => $result['list'] ?? [],
            'total' => $result['total'] ?? 0,
            'page' => $page,
            'pageSize' => $pageSize,
            'filters' => $filters,
            'eventTypes' => $this->logService->getEventTypes(),
            'severities' => ['low', 'medium', 'high', 'critical'],
        ]);
    }

    /**
     * 日志详情
     */
    public function detail(int $id)
    {
        $log = $this->logService->getLogById($id);
        if (!$log) {
            return json(['code' => 1, 'msg' => '日志不存在']);
        }
        return json(['code' => 0, 'data' => $log]);
    }

    /**
     * 导出日志
     */
    public function export(Request $request)
    {
        $filters = [
            'event_type' => $request->get('event_type', ''),
            'severity' => $request->get('severity', ''),
            'start_date' => $request->get('start_date', ''),
            'end_date' => $request->get('end_date', ''),
        ];

        $csv = $this->logService->exportLogs(array_filter($filters));

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="security_log_' . date('Ymd') . '.csv"',
        ]);
    }

    /**
     * 日志统计
     */
    public function stats(Request $request)
    {
        $days = (int) $request->get('days', 7);
        $stats = $this->logService->getStats($days);
        return json(['code' => 0, 'data' => $stats]);
    }
}
