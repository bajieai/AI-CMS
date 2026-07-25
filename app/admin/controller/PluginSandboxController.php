<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\PluginSandboxService;
use think\App;
use think\facade\View;

/**
 * V2.9.35 PLUG-4: 插件沙箱控制器
 */
class PluginSandboxController extends AdminBaseController
{
    protected PluginSandboxService $sandboxService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->sandboxService = new PluginSandboxService();
    }

    public function status()
    {
        $status = $this->sandboxService->getStatus();
        View::assign($status);
        return $this->view('/plugin_sandbox/index');
    }

    public function scan()
    {
        $identifier = $this->request->post('identifier', '');
        $result = $this->sandboxService->scan($identifier);
        return json(['code' => 0, 'data' => $result]);
    }
}
