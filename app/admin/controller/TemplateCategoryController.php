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
use app\common\model\TemplateCategory;
use app\common\service\TemplateCategoryService;

/**
 * 模板分类管理后台控制器 (V2.9.21)
 */
class TemplateCategoryController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 分类列表
     */
    public function index()
    {
        $type = $this->request->get('type', '');
        if ($type && !isset(TemplateCategory::$typeMap[$type])) {
            $type = '';
        }

        $list = TemplateCategory::order('type', 'asc')
            ->order('parent_id', 'asc')
            ->order('sort', 'asc')
            ->select();

        // 构建树形结构（按 type 分组）
        $grouped = [];
        foreach (TemplateCategory::$typeMap as $t => $label) {
            $grouped[$t] = ['label' => $label, 'items' => []];
        }
        foreach ($list as $item) {
            $t = $item->type;
            if (isset($grouped[$t])) {
                $grouped[$t]['items'][] = $item;
            }
        }

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
        }

        $this->assign('grouped', $grouped);
        $this->assign('typeMap', TemplateCategory::$typeMap);
        $this->assign('typeFilter', $type);
        return $this->view('/template_category_index');
    }

    /**
     * 添加分类页面
     */
    public function add()
    {
        return $this->edit(0);
    }

    /**
     * 编辑分类页面
     */
    public function edit(int $id = 0)
    {
        $category = $id ? TemplateCategory::find($id) : [];
        // 父级分类列表（按维度分组）
        $parentOptions = [];
        foreach (TemplateCategory::$typeMap as $t => $label) {
            $parents = TemplateCategory::where('type', $t)
                ->where('parent_id', 0)
                ->where('status', TemplateCategory::STATUS_ENABLED)
                ->order('sort', 'asc')
                ->select();
            $parentOptions[$t] = $parents;
        }

        $this->assign('category', $category ?: []);
        $this->assign('typeMap', TemplateCategory::$typeMap);
        $this->assign('parentOptions', $parentOptions);
        return $this->view('/template_category_form');
    }

    /**
     * 保存分类
     */
    public function save()
    {
        $data = [
            'id'        => (int) $this->request->post('id', 0),
            'name'      => trim($this->request->post('name', '')),
            'code'      => trim($this->request->post('code', '')),
            'type'      => trim($this->request->post('type', '')),
            'parent_id' => (int) $this->request->post('parent_id', 0),
            'sort'      => (int) $this->request->post('sort', 0),
            'status'    => (int) $this->request->post('status', TemplateCategory::STATUS_ENABLED),
        ];

        if (empty($data['name'])) {
            return json(['code' => 1, 'msg' => '分类名称不能为空']);
        }
        if (!isset(TemplateCategory::$typeMap[$data['type']])) {
            return json(['code' => 1, 'msg' => '无效的维度类型']);
        }

        try {
            if ($data['id'] > 0) {
                TemplateCategoryService::updateCategory($data['id'], $data);
            } else {
                TemplateCategoryService::createCategory($data);
            }
            return json(['code' => 0, 'msg' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 删除分类
     */
    public function delete()
    {
        $id = (int) $this->request->post('id', 0);
        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        try {
            // 检查是否有子分类
            $children = TemplateCategory::where('parent_id', $id)->count();
            if ($children > 0) {
                return json(['code' => 1, 'msg' => '该分类下存在子分类，请先删除子分类']);
            }
            TemplateCategoryService::deleteCategory($id);
            return json(['code' => 0, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 切换启用状态
     */
    public function toggleStatus()
    {
        $id = (int) $this->request->post('id', 0);
        $category = TemplateCategory::find($id);
        if (!$category) {
            return json(['code' => 1, 'msg' => '分类不存在']);
        }
        $category->status = $category->status == TemplateCategory::STATUS_ENABLED
            ? TemplateCategory::STATUS_DISABLED
            : TemplateCategory::STATUS_ENABLED;
        $category->save();
        TemplateCategoryService::clearCache();
        return json(['code' => 0, 'msg' => '状态已更新']);
    }
}
