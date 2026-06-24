<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\TemplateCategoryV2;
use app\common\service\template\TemplateCategoryV2Service;

/**
 * 模板分类V2管理控制器 - V2.9.29 Sprint T-4
 */
class TemplateCategoryV2Controller extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $service = new TemplateCategoryV2Service();
        $list = $service->getAll();
        $this->assign('list', $list);
        return $this->view('/template_category_v2_list');
    }

    public function edit(int $id = 0)
    {
        $info = $id > 0 ? TemplateCategoryV2::find($id) : null;
        if ($this->request->isPost()) {
            $data = [
                'name' => $this->request->post('name', ''),
                'dimension' => $this->request->post('dimension', 'industry'),
                'parent_id' => (int) $this->request->post('parent_id', 0),
                'sort' => (int) $this->request->post('sort', 99),
                'status' => 1,
            ];
            if ($id > 0) {
                $info->save($data);
            } else {
                TemplateCategoryV2::create($data);
            }
            return $this->success('保存成功', ['redirect' => '/admin/template_category_v2/index']);
        }
        $this->assign('info', $info);
        return $this->view('/template_category_v2_edit');
    }

    public function delete(int $id = 0)
    {
        TemplateCategoryV2::destroy($id);
        return $this->success('已删除');
    }
}
