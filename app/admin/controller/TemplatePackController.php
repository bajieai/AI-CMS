<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\template\TemplatePackService;

/**
 * 模板包管理控制器 — V2.9.28 M-4
 */
class TemplatePackController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    /**
     * 模板包列表
     */
    public function index()
    {
        $params = $this->request->get();
        $service = new TemplatePackService();
        $data = $service->getList($params, 20);

        $this->assign([
            'list' => $data['list'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'params' => $params,
            'menuActive' => 'template_pack',
        ]);

        return $this->view('/template_store/pack_list');
    }

    /**
     * 添加/编辑模板包
     */
    public function edit(int $id = 0)
    {
        $service = new TemplatePackService();

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['template_ids'] = json_decode($data['template_ids'] ?? '[]', true) ?: [];
            $result = $service->save($data, $id);
            if ($result['success']) {
                $this->recordLog($id > 0 ? '编辑模板包' : '添加模板包', $data['name'] ?? '');
                return $this->success($result['message'], ['redirect' => '/admin/template_pack/index']);
            }
            return $this->error($result['message']);
        }

        $pack = $id > 0 ? $service->getDetail($id) : null;
        $templates = \app\common\model\TemplateStore::where('status', 1)
            ->field('id, name, price, screenshots')
            ->order('id', 'desc')
            ->select();

        $this->assign([
            'pack' => $pack,
            'id' => $id,
            'templates' => $templates,
            'menuActive' => 'template_pack',
        ]);

        return $this->view('/template_store/pack_edit');
    }

    /**
     * 删除模板包
     */
    public function delete(int $id)
    {
        $service = new TemplatePackService();
        $result = $service->delete($id);
        if ($result['success']) {
            $this->recordLog('删除模板包', "ID:{$id}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }
}
