<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\plugin\PluginDevToolService;

/**
 * 开发者文档控制器 - V2.9.40 DEV-ECO2-3
 *
 * 开发者文档管理：API文档、开发指南、FAQ、代码示例
 */
class DevDocController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 文档首页
     */
    public function index()
    {
        $service = new PluginDevToolService();

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => [
                'template_types' => $service->getTemplateTypes(),
            ]]);
        }

        $this->assign('template_types', $service->getTemplateTypes());
        return $this->view('/dev_doc/index');
    }

    /**
     * 生成代码模板
     */
    public function generateTemplate()
    {
        $type = $this->request->post('type', 'controller');
        $pluginName = $this->request->post('plugin_name', '');
        $className = $this->request->post('class_name', 'Test');

        if (empty($pluginName)) {
            return json(['code' => 1, 'msg' => '请指定插件名称']);
        }

        $service = new PluginDevToolService();
        $code = $service->generateTemplate($type, $pluginName, $className);

        return json(['code' => 0, 'msg' => '模板生成成功', 'data' => ['code' => $code]]);
    }

    /**
     * 开发状态检查
     */
    public function checklist()
    {
        $pluginName = $this->request->get('plugin_name', '');
        if (empty($pluginName)) {
            return json(['code' => 1, 'msg' => '请指定插件名称']);
        }

        $service = new PluginDevToolService();
        $result = $service->getDevChecklist($pluginName);

        return json(['code' => 0, 'msg' => 'success', 'data' => $result]);
    }

    /**
     * 创建调试沙箱
     */
    public function createSandbox()
    {
        $pluginName = $this->request->post('plugin_name', '');
        if (empty($pluginName)) {
            return json(['code' => 1, 'msg' => '请指定插件名称']);
        }

        $service = new PluginDevToolService();
        $sandboxDir = $service->createSandbox($pluginName);

        return json(['code' => 0, 'msg' => '沙箱创建成功', 'data' => ['path' => $sandboxDir]]);
    }

    /**
     * 运行测试
     */
    public function runTests()
    {
        $pluginName = $this->request->post('plugin_name', '');
        if (empty($pluginName)) {
            return json(['code' => 1, 'msg' => '请指定插件名称']);
        }

        $service = new PluginDevToolService();
        $result = $service->runTests($pluginName);

        return json(['code' => 0, 'msg' => 'success', 'data' => $result]);
    }
}
