<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Form as FormModel;
use app\common\model\FormData as FormDataModel;

/**
 * 表单管理后台控制器
 */
class FormController extends AdminBaseController
{
    /**
     * 表单列表
     */
    public function index()
    {
        $list = FormModel::order('sort', 'asc')->select();
        if ($this->request->isAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
        }
        $this->assign('list', $list);
        return $this->view('/form_index');
    }

    /**
     * 创建表单页面
     */
    public function add()
    {
        return $this->edit(0);
    }

    /**
     * 编辑表单页面
     */
    public function edit(int $id = 0)
    {
        $form = $id ? FormModel::find($id) : null;
        $this->assign('form', $form);
        return $this->view('/form_edit');
    }

    /**
     * 保存表单
     */
    public function save()
    {
        $data = [
            'id'           => (int) $this->request->post('id', 0),
            'name'         => $this->request->post('name', ''),
            'code'         => $this->request->post('code', ''),
            'fields'       => $this->request->post('fields', []),
            'submit_text'  => $this->request->post('submit_text', '提交'),
            'success_msg'  => $this->request->post('success_msg', '提交成功'),
            'success_action' => $this->request->post('success_action', 'message'),
            'redirect_url' => $this->request->post('redirect_url', ''),
            'anti_spam'    => (int) $this->request->post('anti_spam', 0),
            'is_enabled'   => (int) $this->request->post('is_enabled', 1),
            'sort'         => (int) $this->request->post('sort', 0),
        ];

        if (empty($data['name']) || empty($data['code'])) {
            return json(['code' => 1, 'msg' => '表单名称和标识不能为空']);
        }

        // 检查code唯一
        $exists = FormModel::where('code', $data['code']);
        if ($data['id']) $exists->where('id', '<>', $data['id']);
        if ($exists->find()) {
            return json(['code' => 1, 'msg' => '表单标识已存在']);
        }

        if (!empty($data['id'])) {
            $form = FormModel::find($data['id']);
            if (!$form) return json(['code' => 1, 'msg' => '表单不存在']);
        } else {
            $form = new FormModel();
        }

        $form->save($data);
        return json(['code' => 0, 'msg' => '保存成功', 'data' => ['id' => $form->id]]);
    }

    /**
     * 切换启用状态
     */
    public function toggleEnabled()
    {
        $id = (int) $this->request->post('id', 0);
        $form = FormModel::find($id);
        if (!$form) return json(['code' => 1, 'msg' => '表单不存在']);
        $form->is_enabled = (int) $this->request->post('is_enabled', 1);
        $form->save();
        return json(['code' => 0, 'msg' => '更新成功']);
    }

    /**
     * 删除表单
     */
    public function delete()
    {
        $id = (int) $this->request->post('id', 0);
        $form = FormModel::find($id);
        if (!$form) return json(['code' => 1, 'msg' => '表单不存在']);

        $form->delete();
        FormDataModel::where('form_id', $id)->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    /**
     * 表单提交数据列表
     */
    public function dataIndex()
    {
        $formId = (int) $this->request->get('form_id', 0);
        $page = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 20);

        $list = FormDataModel::where('form_id', $formId)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();
        $total = FormDataModel::where('form_id', $formId)->count();

        if ($this->request->isAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list, 'count' => $total]);
        }

        $form = FormModel::find($formId);
        $this->assign('form_id', $formId);
        $this->assign('form', $form);
        $this->assign('list', $list);
        return $this->view('/form_data_index');
    }
}
