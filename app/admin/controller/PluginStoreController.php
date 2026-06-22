<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\PluginOnlineInstallService;
use app\common\service\PluginSandboxService;
use think\facade\Db;

/**
 * 插件商店在线安装控制器 — V2.9.28 P-1
 */
class PluginStoreController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    /**
     * 插件商店首页
     */
    public function index()
    {
        // 获取已安装插件列表
        $installedPlugins = Db::name('plugin')->order('id', 'desc')->select()->toArray();

        // 获取更新检查结果
        $updateChecks = Db::name('plugin_update_check')->order('check_time', 'desc')->select()->toArray();
        $updateMap = [];
        foreach ($updateChecks as $uc) {
            $updateMap[$uc['plugin_name']] = $uc;
        }

        $this->assign([
            'installedPlugins' => $installedPlugins,
            'updateMap' => $updateMap,
            'menuActive' => 'plugin_store',
        ]);

        return $this->view('/plugin/store_index');
    }

    /**
     * 安装插件（AJAX，前端通过SSE监听进度）
     */
    public function install()
    {
        $pluginName = $this->request->post('plugin_name', '');
        $downloadUrl = $this->request->post('download_url', '');
        $version = $this->request->post('version', '');

        if (empty($pluginName) || empty($downloadUrl)) {
            return json(['code' => -1, 'msg' => '参数不完整']);
        }

        $service = new PluginOnlineInstallService();
        $result = $service->install($pluginName, $downloadUrl, $version, $this->adminInfo['id'] ?? 0);

        if ($result['success']) {
            $this->recordLog('在线安装插件', $pluginName);
        }

        return json(['code' => $result['success'] ? 0 : -1, 'msg' => $result['message'], 'log_id' => $result['log_id'] ?? 0]);
    }

    /**
     * 更新插件
     */
    public function update()
    {
        $pluginName = $this->request->post('plugin_name', '');
        $downloadUrl = $this->request->post('download_url', '');
        $version = $this->request->post('version', '');

        if (empty($pluginName) || empty($downloadUrl)) {
            return json(['code' => -1, 'msg' => '参数不完整']);
        }

        $service = new PluginOnlineInstallService();
        $result = $service->update($pluginName, $downloadUrl, $version, $this->adminInfo['id'] ?? 0);

        if ($result['success']) {
            $this->recordLog('在线更新插件', $pluginName . ' → ' . $version);
        }

        return json(['code' => $result['success'] ? 0 : -1, 'msg' => $result['message'], 'log_id' => $result['log_id'] ?? 0]);
    }

    /**
     * 安装日志列表
     */
    public function logs()
    {
        $service = new PluginOnlineInstallService();
        $data = $service->getInstallLogs('', 20);

        $this->assign([
            'list' => $data['list'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'menuActive' => 'plugin_store',
        ]);

        return $this->view('/plugin/install_logs');
    }

    /**
     * 安全扫描报告
     */
    public function securityScan()
    {
        $zipPath = $this->request->post('zip_path', '');
        if (empty($zipPath) || !file_exists($zipPath)) {
            return json(['code' => -1, 'msg' => '文件不存在']);
        }

        $service = new PluginSandboxService();
        $result = $service->scanZip($zipPath);

        return json(['code' => 0, 'data' => $result]);
    }
}
