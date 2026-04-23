<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Config as ConfigModel;

/**
 * 系统管理控制器
 */
class SystemController extends AdminBaseController
{
    /**
     * 系统配置
     */
    public function config()
    {
        if ($this->request->isGet()) {
            $configs = ConfigModel::order('sort', 'asc')->select();
            $groups = [];
            foreach ($configs as $config) {
                $groups[$config->group][] = $config;
            }

            $this->assign(['groups' => $groups]);
            return $this->view('/system_config');
        }

        // 保存配置
        $data = $this->request->post();
        foreach ($data as $name => $value) {
            ConfigModel::where('name', $name)->update(['value' => $value]);
        }

        $this->recordLog('保存系统配置', '', $data);
        return $this->success('保存成功');
    }
}
