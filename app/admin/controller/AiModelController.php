<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\AiModelService;

/**
 * AI模型管理后台控制器
 */
class AiModelController extends AdminBaseController
{
    /**
     * 模型列表
     */
    public function index()
    {
        $list = AiModelService::getList();
        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
        }
        $this->assign('list', $list);
        return $this->view('/ai_model_index');
    }

    public function add()
    {
        return $this->edit(0);
    }

    public function edit(int $id = 0)
    {
        $info = $id ? \app\common\model\AiModel::find($id) : null;
        $this->assign('info', $info);
        return $this->view('/ai_model_edit');
    }

    /**
     * 保存模型
     */
    public function save()
    {
        $data = [
            'id'           => (int) $this->request->post('id', 0),
            'name'         => $this->request->post('name', ''),
            'provider'     => $this->request->post('provider', ''),
            'model_id'     => $this->request->post('model_id', ''),
            'api_base'     => $this->request->post('api_base', ''),
            'api_key'      => $this->request->post('api_key', ''),
            'capabilities' => $this->request->post('capabilities', ''),
            'max_tokens'   => (int) $this->request->post('max_tokens', 2000),
            'temperature'  => (float) $this->request->post('temperature', 0.7),
            'is_enabled'   => (int) $this->request->post('is_enabled', 1),
            'is_default'   => (int) $this->request->post('is_default', 0),
            'sort'         => (int) $this->request->post('sort', 0),
        ];

        if (empty($data['name']) || empty($data['provider']) || empty($data['model_id'])) {
            return json(['code' => 1, 'msg' => '模型名称、供应商、模型ID不能为空']);
        }

        try {
            $model = AiModelService::save($data);
            return json(['code' => 0, 'msg' => '保存成功', 'data' => ['id' => $model->id]]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 删除模型
     */
    public function delete()
    {
        $id = (int) $this->request->post('id', 0);
        try {
            AiModelService::delete($id);
            return json(['code' => 0, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 设置默认模型
     */
    public function setDefault()
    {
        $id = (int) $this->request->post('id', 0);
        try {
            AiModelService::setDefault($id);
            return json(['code' => 0, 'msg' => '设置成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 切换启用状态
     */
    public function toggleEnabled()
    {
        $id = (int) $this->request->post('id', 0);
        try {
            AiModelService::toggleEnabled($id);
            return json(['code' => 0, 'msg' => '操作成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 测试连接
     */
    public function testConnection()
    {
        $id = (int) $this->request->post('id', 0);
        try {
            $result = AiModelService::testConnection($id);
            return json(['code' => $result['success'] ? 0 : 1, 'msg' => $result['message'], 'data' => $result]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}
