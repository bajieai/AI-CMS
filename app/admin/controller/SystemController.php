<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Config as ConfigModel;
use app\common\model\CustomVar;
use app\common\model\Module;

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

    /**
     * 自定义变量列表页
     */
    public function customVar()
    {
        if ($this->request->isGet()) {
            $list = CustomVar::order('sort', 'asc')->select();
            $this->assign(['list' => $list]);
            return $this->view('/system_custom_var');
        }

        // POST: 批量保存排序
        $data = $this->request->post('sort/a', []);
        foreach ($data as $id => $sort) {
            CustomVar::where('id', (int) $id)->update(['sort' => (int) $sort]);
        }
        CustomVar::clearCache();
        $this->recordLog('保存自定义变量排序');
        return $this->success('保存成功');
    }

    /**
     * 新增/编辑自定义变量（AJAX）
     */
    public function customVarSave()
    {
        $id = $this->request->post('id', 0);
        $name = trim($this->request->post('name', ''));
        $value = $this->request->post('value', '');
        $remark = trim($this->request->post('remark', ''));
        $sort = (int) $this->request->post('sort', 0);

        if (empty($name)) {
            return $this->error('变量名不能为空');
        }
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            return $this->error('变量名只能包含字母、数字和下划线，且不能以数字开头');
        }

        $data = [
            'name'   => $name,
            'value'  => $value,
            'remark' => $remark,
            'sort'   => $sort,
        ];

        if ($id) {
            $exists = CustomVar::where('name', $name)->where('id', '<>', $id)->find();
            if ($exists) {
                return $this->error('变量名已存在');
            }
            CustomVar::where('id', (int) $id)->update($data);
            $this->recordLog('编辑自定义变量', $name, $data);
        } else {
            $exists = CustomVar::where('name', $name)->find();
            if ($exists) {
                return $this->error('变量名已存在');
            }
            CustomVar::create($data);
            $this->recordLog('新增自定义变量', $name, $data);
        }

        CustomVar::clearCache();
        return $this->success('保存成功');
    }

    /**
     * 删除自定义变量
     */
    public function customVarDelete()
    {
        $id = (int) $this->request->post('id', 0);
        if (!$id) {
            return $this->error('参数错误');
        }

        $var = CustomVar::find($id);
        if (!$var) {
            return $this->error('变量不存在');
        }

        $var->delete();
        CustomVar::clearCache();
        $this->recordLog('删除自定义变量', $var->name);
        return $this->success('删除成功');
    }

    /**
     * 功能开关页面
     */
    public function moduleControl()
    {
        if ($this->request->isGet()) {
            $modules = Module::order('sort', 'asc')->select();
            $categories = [
                'core'       => '核心模块',
                'operation'  => '内容运营',
                'interaction'=> '互动管理',
                'seo_data'   => 'SEO与数据',
                'extension'  => '高级扩展',
            ];
            $grouped = [];
            foreach ($modules as $module) {
                $cat = $module->category ?: 'other';
                $grouped[$cat][] = $module;
            }

            $this->assign([
                'grouped'    => $grouped,
                'categories' => $categories,
            ]);
            return $this->view('/system_module');
        }

        return $this->error('非法请求');
    }

    /**
     * 切换模块启用状态（AJAX）
     */
    public function moduleToggle()
    {
        $roleId = (int) session('role_id');
        if ($roleId !== 1) {
            return $this->error('仅超级管理员可操作');
        }

        $id = (int) $this->request->post('id', 0);
        $isEnabled = (int) $this->request->post('is_enabled', 0);

        if (!$id) {
            return $this->error('参数错误');
        }

        $module = Module::find($id);
        if (!$module) {
            return $this->error('模块不存在');
        }

        if ($module->is_system) {
            return $this->error('系统模块不可关闭');
        }

        $module->is_enabled = $isEnabled;
        $module->save();
        Module::clearCache();

        $this->recordLog($isEnabled ? '启用模块' : '禁用模块', $module->name);
        return $this->success('操作成功');
    }
}
