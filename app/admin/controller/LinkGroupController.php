<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\LinkGroup as LinkGroupModel;
use think\Request;

class LinkGroupController extends AdminBaseController
{
    public function index(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $list = LinkGroupModel::order('sort', 'asc')->paginate(15, false, ['page' => $page]);
        return $this->view('/link_group_list', ['list' => $list]);
    }

    /**
     * 添加分组
     */
    public function add(Request $request)
    {
        if ($request->isGet()) {
            return $this->view('/link_group_edit', ['info' => null]);
        }

        $data = $request->post();
        $validate = $this->validateData($data);
        if ($validate !== true) {
            return $this->error($validate);
        }

        $model = new LinkGroupModel();
        if ($model->save($data)) {
            $this->recordLog('添加友链分组', $data['name'] ?? '', $data);
            return $this->success('添加成功', ['redirect' => '/admin/link_group/index']);
        }
        return $this->error('添加失败');
    }

    /**
     * 编辑分组
     */
    public function edit(int $id, Request $request)
    {
        $info = LinkGroupModel::find($id);
        if (empty($info)) {
            return $this->error('分组不存在');
        }

        if ($request->isGet()) {
            return $this->view('/link_group_edit', ['info' => $info]);
        }

        $data = $request->post();
        $validate = $this->validateData($data);
        if ($validate !== true) {
            return $this->error($validate);
        }

        if ($info->save($data)) {
            $this->recordLog('编辑友链分组', $data['name'] ?? '', $data);
            return $this->success('更新成功', ['redirect' => '/admin/link_group/index']);
        }
        return $this->error('更新失败');
    }

    /**
     * 删除分组
     */
    public function delete(int $id)
    {
        $info = LinkGroupModel::find($id);
        if (empty($info)) {
            return $this->error('分组不存在');
        }

        // 检查分组下是否有友链
        if ($info->links()->count() > 0) {
            return $this->error('该分组下存在友链，请先移出或删除友链');
        }

        if ($info->delete()) {
            $this->recordLog('删除友链分组', $info['name'] ?? '', []);
            return $this->success('删除成功');
        }
        return $this->error('删除失败');
    }

    /**
     * 切换状态
     */
    public function toggleStatus(int $id)
    {
        $info = LinkGroupModel::find($id);
        if (empty($info)) {
            return $this->error('分组不存在');
        }

        $info->status = $info->status === 1 ? 0 : 1;
        if ($info->save()) {
            return $this->success('状态更新成功');
        }
        return $this->error('状态更新失败');
    }

    /**
     * 验证数据
     */
    protected function validateData(array $data): bool|string
    {
        if (empty($data['name'])) {
            return '分组名称不能为空';
        }
        if (mb_strlen($data['name']) > 100) {
            return '分组名称不能超过100个字符';
        }
        if (!isset($data['sort']) || !is_numeric($data['sort'])) {
            return '排序值必须为数字';
        }
        return true;
    }
}
