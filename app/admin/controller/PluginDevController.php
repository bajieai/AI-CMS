<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\PluginScaffoldService;
use app\common\service\PluginDevSdkService;

/**
 * 插件开发工具后台控制器 - V2.9.29 Sprint D-3
 */
class PluginDevController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function docs()
    {
        $sdk = new PluginDevSdkService();
        $this->assign('doc', $sdk->getSdkDoc());
        return $this->view('/plugin_dev_docs');
    }

    public function examples()
    {
        $sdk = new PluginDevSdkService();
        $this->assign('examples', $sdk->getExamples());
        return $this->view('/plugin_dev_examples');
    }

    public function debug()
    {
        $this->assign('plugins', glob(root_path() . 'plugin/*', GLOB_ONLYDIR));
        return $this->view('/plugin_dev_debug');
    }

    public function scaffold()
    {
        if (!$this->request->isPost()) return $this->error('请求方式错误');
        $name = $this->request->post('name', '');
        $type = $this->request->post('type', 'general');
        if (empty($name)) return $this->error('插件名称不能为空');

        $service = new PluginScaffoldService();
        $result = $service->generate($name, $type);
        return $result ? $this->success('脚手架生成成功', $result) : $this->error('生成失败');
    }
}
