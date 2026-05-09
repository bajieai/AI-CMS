<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\MonitorService;

/**
 * 性能监控面板控制器 - V2.9.2 M24
 * 仅admin角色可访问
 */
class MonitorController extends AdminBaseController
{
    /**
     * 监控面板
     */
    public function index()
    {
        $service = new MonitorService();

        $this->assign('system', $service->getSystemMetrics());
        $this->assign('php', $service->getPhpMetrics());
        $this->assign('mysql', $service->getMysqlMetrics());
        $this->assign('cache', $service->getCacheMetrics());
        $this->assign('logs', $service->getRuntimeLogStats());

        return $this->view('/monitor_dashboard');
    }

    /**
     * API刷新（AJAX轮询）
     */
    public function refresh()
    {
        $service = new MonitorService();
        $type = $this->request->get('type', 'all');

        $data = [];
        switch ($type) {
            case 'system':
                $data = $service->getSystemMetrics();
                break;
            case 'php':
                $data = $service->getPhpMetrics();
                break;
            case 'mysql':
                $data = $service->getMysqlMetrics();
                break;
            case 'cache':
                $data = $service->getCacheMetrics();
                break;
            case 'logs':
                $data = $service->getRuntimeLogStats();
                break;
            default:
                $data = [
                    'system' => $service->getSystemMetrics(),
                    'php'    => $service->getPhpMetrics(),
                    'mysql'  => $service->getMysqlMetrics(),
                    'cache'  => $service->getCacheMetrics(),
                    'logs'   => $service->getRuntimeLogStats(),
                ];
        }

        return json(['code' => 0, 'msg' => 'success', 'data' => $data]);
    }
}
