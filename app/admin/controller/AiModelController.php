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
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 模型列表
     */
    public function index()
    {
        $list = AiModelService::getList();
        // capabilities 存储为逗号分隔字符串，转为数组供模板 volist 遍历
        foreach ($list as &$item) {
            $caps = $item['capabilities'] ?? '';
            $item['capabilities'] = is_array($caps) ? $caps : array_filter(explode(',', (string) $caps));
        }
        unset($item);

        // 供 ai_model/index.html 筛选栏使用
        $providers = array_unique(array_column(is_array($list) ? $list : [], 'provider'));
        $this->assign([
            'list'      => $list,
            'providers' => $providers,
            'keyword'   => $this->request->param('keyword', ''),
            'provider'  => $this->request->param('provider', ''),
        ]);
        return $this->view('ai_model/index');
    }

    /**
     * 添加/编辑模型
     */
    public function add()
    {
        return $this->edit();
    }

    public function edit()
    {
        $id = (int) $this->request->param('id', 0);

        if ($this->request->isPost()) {
            $data = $this->request->post();
            try {
                AiModelService::save($data);
                return $this->success('保存成功');
            } catch (\Throwable $e) {
                return $this->error($e->getMessage());
            }
        }

        $model = null;
        if ($id > 0) {
            $model = \app\common\model\AiModel::find($id);
        }

        $this->assign('info', $model);
        return $this->view('ai_model/edit');
    }

    /**
     * 保存模型（AJAX）
     */
    public function save()
    {
        $data = $this->request->post();
        try {
            AiModelService::save($data);
            return json(['code' => 0, 'msg' => '保存成功']);
        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 设为默认模型
     */
    public function setDefault()
    {
        $id = (int) $this->request->post('id', 0);
        try {
            AiModelService::setDefault($id);
            return json(['code' => 0, 'msg' => '设置成功']);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 测试模型连接
     */
    public function testConnection()
    {
        $id = (int) $this->request->post('id', 0);
        if ($id === 0) {
            $id = (int) $this->request->param('id', 0);
        }
        try {
            $result = AiModelService::testConnection($id);
            return json([
                'code' => $result['success'] ? 0 : 1,
                'msg'  => $result['message'],
            ]);
        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 模型配额统计
     */
    public function quota()
    {
        try {
            $service = new \app\common\service\ai\AiModelConfigService();
            $stats = $service->getCostStats(0, 30);
            $this->assign('stats', $stats);
        } catch (\Throwable $e) {
            $this->assign('stats', ['summary' => [], 'daily_stats' => [], 'model_stats' => []]);
        }
        return $this->view('ai_model/quota');
    }
}
