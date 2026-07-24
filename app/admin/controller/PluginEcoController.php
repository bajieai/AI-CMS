<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\plugin\PluginDevService;
use app\common\service\plugin\PluginVersionService;
use app\common\service\plugin\PluginStatsService;
use app\common\service\plugin\ApiOpenPlatformService;

/**
 * 插件生态管理
 * V2.9.37 PLUG-ECO
 */
class PluginEcoController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function developer()
    {
        $service = new PluginDevService();
        return $this->view('/plugin_eco_developer', []);
    }

    public function audit()
    {
        $auditStatus = $this->request->get('status', 'pending');
        $plugins = \app\common\model\Plugin::where('audit_status', $auditStatus)->paginate(20);
        return $this->view('/plugin_eco_audit', ['plugins' => $plugins->toArray(), 'status' => $auditStatus]);
    }

    public function doAudit()
    {
        $pluginId = (int) $this->request->post('plugin_id', 0);
        $result = $this->request->post('result', '');
        $comment = $this->request->post('comment', '');
        $service = new PluginDevService();
        $ok = $service->manualAudit($pluginId, $this->currentUser['id'] ?? 1, $result, $comment);
        return json(['success' => $ok, 'msg' => $ok ? '审核完成' : '审核失败']);
    }

    public function autoAudit()
    {
        $pluginId = (int) $this->request->post('plugin_id', 0);
        $service = new PluginDevService();
        $result = $service->autoAudit($pluginId);
        return json(['success' => true, 'data' => $result]);
    }

    public function publishPlugin()
    {
        $pluginId = (int) $this->request->post('plugin_id', 0);
        $service = new PluginDevService();
        $ok = $service->publish($pluginId);
        return json(['success' => $ok, 'msg' => $ok ? '已上线' : '上线失败']);
    }

    public function offlinePlugin()
    {
        $pluginId = (int) $this->request->post('plugin_id', 0);
        $reason = $this->request->post('reason', '');
        $service = new PluginDevService();
        $ok = $service->offline($pluginId, $reason);
        return json(['success' => $ok, 'msg' => $ok ? '已下架' : '下架失败']);
    }

    public function versions()
    {
        $pluginId = (int) $this->request->get('plugin_id', 0);
        $service = new PluginVersionService();
        $versions = $service->getVersionList($pluginId);
        return $this->view('/plugin_eco_versions', ['versions' => $versions, 'plugin_id' => $pluginId]);
    }

    public function createVersion()
    {
        $data = $this->request->post();
        $pluginId = (int) ($data['plugin_id'] ?? 0);
        $service = new PluginVersionService();
        $id = $service->createVersion($pluginId, $data);
        return json(['success' => $id > 0, 'id' => $id]);
    }

    public function grayscalePublish()
    {
        $versionId = (int) $this->request->post('version_id', 0);
        $ratio = (float) $this->request->post('ratio', 0.1);
        $service = new PluginVersionService();
        $ok = $service->grayscalePublish($versionId, $ratio);
        return json(['success' => $ok]);
    }

    public function fullPublish()
    {
        $versionId = (int) $this->request->post('version_id', 0);
        $service = new PluginVersionService();
        $ok = $service->fullPublish($versionId);
        return json(['success' => $ok]);
    }

    public function rollbackVersion()
    {
        $versionId = (int) $this->request->post('version_id', 0);
        $service = new PluginVersionService();
        $ok = $service->rollbackVersion($versionId);
        return json(['success' => $ok]);
    }

    public function stats()
    {
        $pluginId = (int) $this->request->get('plugin_id', 0);
        $service = new PluginStatsService();
        $dashboard = $service->getDashboard($pluginId);
        return $this->view('/plugin_eco_stats', ['dashboard' => $dashboard, 'plugin_id' => $pluginId]);
    }

    public function apiOpen()
    {
        $service = new ApiOpenPlatformService();
        $apiList = $service->getApiList();
        $config = $service->getConfig();
        $stats = $service->getCallStats();
        return $this->view('/plugin_eco_api_open', ['api_list' => $apiList, 'config' => $config, 'stats' => $stats]);
    }

    public function createApiKey()
    {
        $name = $this->request->post('name', '');
        $service = new ApiOpenPlatformService();
        $result = $service->createApiKey($this->currentUser['id'] ?? 1, $name);
        return json(['success' => true, 'data' => $result]);
    }
}
