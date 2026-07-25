<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\dev\CicdIntegrationService;

/**
 * CI/CD集成后台控制器
 */
class CicdController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $configs = CicdIntegrationService::getConfigs();
        $this->assign('configs', $configs);
        return $this->view('cicd/index');
    }

    public function save()
    {
        $data = $this->request->post();
        CicdIntegrationService::saveConfig($data);
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    public function webhooks()
    {
        $page = (int)$this->request->param('page', 1);
        $result = CicdIntegrationService::getWebhooks($page, 20);
        $this->assign('list', $result['list'] ?? []);
        $this->assign('total', $result['total'] ?? 0);
        $this->assign('page', $page);
        return $this->view('cicd/webhooks');
    }
}
