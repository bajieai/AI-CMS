<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\sys\LogManageService;

class LogManageController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $page = (int)($this->request->param('page', 1));
        $filter = $this->request->param();
        $result = LogManageService::getLogs($filter, $page, 20);
        $stats = LogManageService::getLogStats();
        return $this->view('/sys/log_manage', array_merge($result, ['stats' => $stats]));
    }

    public function search()
    {
        $keyword = $this->request->param('keyword', '');
        $result = LogManageService::searchLogs($keyword);
        return json(['code' => 0, 'data' => $result]);
    }

    public function archive()
    {
        $date = $this->request->post('date');
        $count = LogManageService::archiveLogs($date);
        return json(['code' => 0, 'msg' => "已归档 {$count} 条日志"]);
    }

    public function clean()
    {
        $days = (int)$this->request->post('days', 90);
        $count = LogManageService::cleanOldLogs($days);
        return json(['code' => 0, 'msg' => "已清理 {$count} 条旧日志"]);
    }
}
