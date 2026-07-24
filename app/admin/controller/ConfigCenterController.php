<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.39 SYS-ROBUST-3: 配置中心后台控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\system\ConfigCenterService;
use think\App;

/**
 * 配置中心后台控制器 - V2.9.39 SYS-ROBUST-3
 */
class ConfigCenterController extends AdminBaseController
{
    protected ConfigCenterService $service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new ConfigCenterService();
    }

    /**
     * 配置中心首页
     */
    public function index()
    {
        $groups = $this->service->getGroups();
        $envs = $this->service->getEnvironments();

        return $this->view('/config_center/index', ['groups' => $groups, 'envs' => $envs]);
    }

    /**
     * 查看分组配置
     */
    public function group()
    {
        $group = $this->request->get('group', 'system');
        $env = $this->request->get('env');
        $env = $env === '' ? null : $env;

        $configs = $this->service->getConfigsByGroup($group, $env);

        return $this->view('/config_center/group', [
            'configs' => $configs,
            'group'   => $group,
            'env'     => $env,
        ]);
    }

    /**
     * 编辑配置
     */
    public function edit()
    {
        if ($this->request->isPost()) {
            $name = $this->request->post('name', '');
            $value = $this->request->post('value', '');
            $group = $this->request->post('group', 'system');
            $env = $this->request->post('env');
            $env = $env === '' ? null : $env;
            $operatorId = (int) session('user_id');

            $result = $this->service->set($name, $value, $group, $env, $operatorId);

            if ($result['success']) {
                $this->recordLog('config_center_edit', "修改配置 {$name}");
                return $this->success('配置已更新');
            }
            return $this->error($result['msg'] ?? '更新失败');
        }

        $name = $this->request->get('name', '');
        $group = $this->request->get('group', 'system');
        $env = $this->request->get('env');

        $value = $this->service->get($name, '', $env ?: null);

        return $this->view('/config_center/edit', [
            'name'  => $name,
            'value' => $value,
            'group' => $group,
            'env'   => $env,
        ]);
    }

    /**
     * 版本历史
     */
    public function versions()
    {
        $name = $this->request->get('name', '');
        $page = (int) $this->request->get('page', 1);

        $result = $this->service->getVersionHistory($name, $page);

        return $this->view('/config_center/versions', $result);
    }

    /**
     * 回滚配置
     */
    public function rollback()
    {
        $versionId = (int) $this->request->post('version_id', 0);
        $operatorId = (int) session('user_id');

        $result = $this->service->rollbackToVersion($versionId, $operatorId);

        if ($result['success']) {
            $this->recordLog('config_center_rollback', "回滚配置版本 #{$versionId}");
            return $this->success('已回滚');
        }
        return $this->error($result['msg'] ?? '回滚失败');
    }

    /**
     * 导出配置
     */
    public function export()
    {
        $group = $this->request->get('group');
        $env = $this->request->get('env');
        $env = $env === '' ? null : $env;

        $data = $this->service->export($group ?: null, $env);

        return json($data, 200, [
            'Content-Disposition' => 'attachment; filename="config_export_' . date('Ymd_His') . '.json"',
        ]);
    }

    /**
     * 清除缓存
     */
    public function clearCache()
    {
        $this->service->clearCache();
        $this->recordLog('config_center_clear_cache', '清除配置缓存');

        return $this->success('缓存已清除');
    }
}
