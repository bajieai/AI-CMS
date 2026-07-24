<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.39 COMPLIANCE-4: 安全中心后台控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\compliance\SecurityCenterService;
use think\App;

/**
 * 安全中心后台控制器 - V2.9.39 COMPLIANCE-4
 */
class SecurityCenterController extends AdminBaseController
{
    protected SecurityCenterService $service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new SecurityCenterService();
    }

    /**
     * 安全仪表盘
     */
    public function index()
    {
        $dashboard = $this->service->getDashboard();

        return $this->view('/security_center/index', ['dashboard' => $dashboard]);
    }

    /**
     * 告警列表
     */
    public function alerts()
    {
        $params = $this->request->get();
        $result = $this->service->getAlertList($params);

        return $this->view('/security_center/alerts', $result);
    }

    /**
     * 处理告警
     */
    public function handleAlert()
    {
        $id = (int) $this->request->post('id', 0);
        $action = $this->request->post('action', '');
        $note = $this->request->post('note', '');
        $handlerId = (int) session('user_id');

        $result = $this->service->handleAlert($id, $handlerId, $action, $note);

        if ($result['success']) {
            $this->recordLog('security_alert_handle', "处理安全告警 #{$id} ({$action})");
            return $this->success('处理成功');
        }
        return $this->error($result['msg'] ?? '处理失败');
    }

    /**
     * 合规检查
     */
    public function compliance()
    {
        $result = $this->service->runComplianceCheck();

        return $this->view('/security_center/compliance', ['compliance' => $result]);
    }

    /**
     * 重新运行合规检查
     */
    public function runComplianceCheck()
    {
        $result = $this->service->runComplianceCheck();
        $this->recordLog('security_compliance_check', '运行合规检查');

        return $this->success('检查完成', $result);
    }

    /**
     * 安全报告
     */
    public function report()
    {
        $type = $this->request->get('type', 'daily');
        $report = $this->service->generateReport($type);

        return $this->view('/security_center/report', ['report' => $report]);
    }

    /**
     * 安全配置
     */
    public function config()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->service->updateConfig($data);

            if ($result) {
                $this->recordLog('security_config_update', '更新安全配置');
                return $this->success('配置已更新');
            }
            return $this->error('配置更新失败');
        }

        $config = $this->service->getConfig();

        return $this->view('/security_center/config', ['config' => $config]);
    }
}
