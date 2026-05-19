<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\PluginMarketService;
use app\common\service\PluginService;

/**
 * 插件管理后台控制器 - V2.5新增
 */
class PluginController extends AdminBaseController
{
    /**
     * 插件列表
     */
    public function index()
    {
        try {
            $plugins = PluginService::scanPlugins();
        } catch (\Exception) {
            $plugins = [];
        }

        // V2.9.2 M25: 检查可更新插件
        $updateMap = [];
        try {
            $marketService = new PluginMarketService();
            $updates = $marketService->checkUpdates();
            foreach ($updates as $u) {
                $updateMap[$u['code']] = $u['remote_version'];
            }
        } catch (\Throwable) {
            // 更新检测失败不影响主列表
        }

        foreach ($plugins as &$plugin) {
            $plugin['has_update'] = isset($updateMap[$plugin['code'] ?? '']);
            $plugin['remote_version'] = $updateMap[$plugin['code'] ?? ''] ?? '';
        }

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $plugins]);
        }

        $this->assign('plugins', $plugins);
        $this->assign('updateCount', count($updateMap));
        return $this->view('/plugin_index');
    }

    /**
     * 安装插件
     */
    public function install()
    {
        $code = $this->request->post('code', '');
        if (empty($code)) {
            return json(['code' => 1, 'msg' => '插件标识不能为空']);
        }

        try {
            PluginService::install($code);
            return json(['code' => 0, 'msg' => '插件安装成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 卸载插件
     */
    public function uninstall()
    {
        $code = $this->request->post('code', '');
        if (empty($code)) {
            return json(['code' => 1, 'msg' => '插件标识不能为空']);
        }

        try {
            PluginService::uninstall($code);
            return json(['code' => 0, 'msg' => '插件卸载成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 启用插件
     */
    public function enable()
    {
        $code = $this->request->post('code', '');
        try {
            PluginService::enable($code);
            return json(['code' => 0, 'msg' => '插件已启用']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 禁用插件
     */
    public function disable()
    {
        $code = $this->request->post('code', '');
        try {
            PluginService::disable($code);
            return json(['code' => 0, 'msg' => '插件已禁用']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 插件配置
     */
    public function config()
    {
        $code = $this->request->param('code', '');
        if (empty($code)) {
            return json(['code' => 1, 'msg' => '插件标识不能为空']);
        }

        $plugin = \app\common\model\Plugin::where('code', $code)->find();
        if (!$plugin) {
            return json(['code' => 1, 'msg' => '插件未安装']);
        }

        // 读取 plugin.json 的配置定义
        $jsonFile = root_path() . 'plugin/' . $code . '/plugin.json';
        $schema = [];
        if (file_exists($jsonFile)) {
            $info = json_decode(file_get_contents($jsonFile), true);
            $schema = $info['config'] ?? [];
        }

        if ($this->request->isPost()) {
            $config = $this->request->post('config', []);
            try {
                $plugin->config = $config;
                $plugin->save();
                (new \app\common\service\CacheService())->clearByTag(\app\common\service\CacheService::TAG_PLUGIN);
                return json(['code' => 0, 'msg' => '配置保存成功']);
            } catch (\Exception $e) {
                return json(['code' => 1, 'msg' => $e->getMessage()]);
            }
        }

        $this->assign('plugin', $plugin);
        $this->assign('schema', $schema);
        return $this->view('/plugin_config');
    }
}
