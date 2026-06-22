<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\template\TemplateRecommendPositionService;

/**
 * 推荐位管理控制器 — V2.9.28 M-6
 */
class TemplateRecommendPositionController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    /**
     * 推荐位列表
     */
    public function index()
    {
        $params = $this->request->get();
        $service = new TemplateRecommendPositionService();
        $data = $service->getList($params, 20);

        $this->assign([
            'list' => $data['list'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'params' => $params,
            'menuActive' => 'template_recommend_position',
        ]);

        return $this->view('/template_store/recommend_positions');
    }

    /**
     * 添加/编辑推荐位
     */
    public function edit(int $id = 0)
    {
        $service = new TemplateRecommendPositionService();

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['template_ids'] = json_decode($data['template_ids'] ?? '[]', true) ?: [];
            $result = $service->save($data, $id);
            if ($result['success']) {
                $this->recordLog($id > 0 ? '编辑推荐位' : '添加推荐位', $data['name'] ?? '');
                return $this->success($result['message'], ['redirect' => '/admin/template_recommend_position/index']);
            }
            return $this->error($result['message']);
        }

        $position = $id > 0 ? $service->getDetail($id) : null;
        $templates = \app\common\model\TemplateStore::where('status', 1)
            ->field('id, name, cover, price')
            ->order('install_count', 'desc')
            ->limit(100)
            ->select();

        $this->assign([
            'position' => $position,
            'id' => $id,
            'templates' => $templates,
            'menuActive' => 'template_recommend_position',
        ]);

        return $this->view('/template_store/recommend_position_edit');
    }

    /**
     * 删除推荐位
     */
    public function delete(int $id)
    {
        $service = new TemplateRecommendPositionService();
        $result = $service->delete($id);
        if ($result['success']) {
            $this->recordLog('删除推荐位', "ID:{$id}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }
}
