<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\template\TemplateCategoryUpgradeService;

/**
 * 模板分类管理 — V2.9.33 T5-4
 */
class TemplateCategoryController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    /**
     * 分类列表
     */
    public function index()
    {
        try {
            $service = new TemplateCategoryUpgradeService();
            $tree = $service->getCategoryTree();
            $stats = $service->getCategoryStats();
        } catch (\Throwable $e) {
            $tree = [];
            $stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
        }

        $this->assign([
            'tree' => $tree,
            'stats' => $stats,
            'menuActive' => 'template_category',
        ]);

        return $this->view('/template_store/category');
    }

    /**
     * 保存分类
     */
    public function save()
    {
        $data = $this->request->post();
        $id = (int) ($data['id'] ?? 0);

        $service = new TemplateCategoryUpgradeService();
        $result = $service->saveCategory($data, $id);

        return $result['success'] ? $this->success('保存成功') : $this->error('保存失败');
    }
}
