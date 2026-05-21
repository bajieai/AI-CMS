<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
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
        $system = $service->getSystemMetrics();
        $this->assign('load', $system['load']);
        $this->assign('memory', $system['memory']);
        $this->assign('disk', $system['disk']);
        $this->assign('cpu', $system['cpu']);
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
