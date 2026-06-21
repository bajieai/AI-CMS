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
use app\common\model\ContentModel;
use app\common\model\ContentModelField;
use app\common\service\content\FieldTypeRegistry;
use think\facade\Cache;

/**
 * V2.9.20 A-2: 内容模型管理控制器
 */
class ContentModelController extends AdminBaseController
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
        $list = ContentModel::order('sort', 'asc')->order('id', 'asc')->select();

        $this->assign([
                'list' => $list,
                'typeMap' => ContentModel::$typeMap,
            ]);
            return $this->view('/content_model_index');
    }

    /**
     * 添加/编辑模型
     */
    public function edit(int $id = 0)
    {
        $info = $id > 0 ? ContentModel::find($id) : null;

        if ($this->request->isGet()) {
            $fields = [];
            if ($info) {
                $fields = ContentModelField::where('model_id', $info->id)
                    ->order('sort', 'asc')
                    ->select();
            }

            $this->assign([
                'info' => $info,
                'fields' => $fields,
                'typeMap' => ContentModel::$typeMap,
                'fieldTypeMap' => FieldTypeRegistry::getTypeMap(),
            ]);
            return $this->view('/content_model_edit');
        }

        // POST保存
        $data = $this->request->post();
        $saveData = [
            'name' => $data['name'] ?? '',
            'type' => (int) ($data['type'] ?? 1),
            'icon' => $data['icon'] ?? '',
            'description' => $data['description'] ?? '',
            'sort' => (int) ($data['sort'] ?? 0),
            'status' => (int) ($data['status'] ?? 1),
            // V2.9.27 S-1/S-6: SEO字段和模板文件
            'seo_title' => $data['seo_title'] ?? '',
            'seo_keywords' => $data['seo_keywords'] ?? '',
            'seo_description' => $data['seo_description'] ?? '',
            'template_file' => $data['template_file'] ?? '',
        ];

        if (empty($saveData['name'])) {
            return $this->error('模型名称不能为空');
        }

        if ($info) {
            $info->save($saveData);
            $modelId = $info->id;
        } else {
            $model = ContentModel::create($saveData);
            $modelId = $model->id;
        }

        // 清除缓存
        Cache::tag('content_model')->clear();

        $this->recordLog($id > 0 ? '编辑内容模型' : '添加内容模型', $saveData['name']);
        return $this->success('保存成功', ['redirect' => '/admin/content_model/index']);
    }

    /**
     * 删除模型
     */
    public function delete(int $id)
    {
        $info = ContentModel::find($id);
        if (empty($info)) {
            return $this->error('模型不存在');
        }

        // 检查是否有关联的内容
        $count = \app\common\model\Content::where('model_id', $id)->count();
        if ($count > 0) {
            return $this->error('该模型下存在内容，无法删除');
        }

        // 删除关联字段
        ContentModelField::where('model_id', $id)->delete();
        $info->delete();

        Cache::tag('content_model')->clear();
        $this->recordLog('删除内容模型', $info->name);
        return $this->success('删除成功');
    }

    /**
     * AJAX: 保存字段
     */
    public function saveField()
    {
        $data = $this->request->post();
        $modelId = (int) ($data['model_id'] ?? 0);
        $fieldId = (int) ($data['field_id'] ?? 0);

        if ($modelId <= 0) {
            return $this->error('模型ID错误');
        }

        $model = ContentModel::find($modelId);
        if (empty($model)) {
            return $this->error('模型不存在');
        }

        $saveData = [
            'model_id' => $modelId,
            'field_name' => $data['field_name'] ?? '',
            'field_label' => $data['field_label'] ?? '',
            'field_type' => $data['field_type'] ?? 'text',
            'is_required' => (int) ($data['is_required'] ?? 0),
            'sort' => (int) ($data['sort'] ?? 0),
            'default_value' => $data['default_value'] ?? '',
            'options' => $data['options'] ?? '',
            'status' => (int) ($data['status'] ?? 1),
        ];

        // V2.9.27 S-2: 新增字段属性
        if (isset($data['placeholder'])) {
            $saveData['placeholder'] = $data['placeholder'];
        }
        if (isset($data['validation'])) {
            $saveData['validation'] = $data['validation'];
        }
        if (isset($data['is_searchable'])) {
            $saveData['is_searchable'] = (int) $data['is_searchable'];
        }
        if (isset($data['is_list_show'])) {
            $saveData['is_list_show'] = (int) $data['is_list_show'];
        }

        if (empty($saveData['field_name']) || empty($saveData['field_label'])) {
            return $this->error('字段名和标签不能为空');
        }

        // 字段名格式校验
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $saveData['field_name'])) {
            return $this->error('字段名只能包含小写字母、数字和下划线，且必须以字母开头');
        }

        // 唯一性校验（同一模型下字段名不能重复）
        $exists = ContentModelField::where('model_id', $modelId)
            ->where('field_name', $saveData['field_name']);
        if ($fieldId > 0) {
            $exists->where('id', '<>', $fieldId);
        }
        if ($exists->find()) {
            return $this->error('字段名已存在');
        }

        if ($fieldId > 0) {
            $field = ContentModelField::find($fieldId);
            if (empty($field)) {
                return $this->error('字段不存在');
            }
            $field->save($saveData);
        } else {
            ContentModelField::create($saveData);
        }

        Cache::tag('content_model')->clear();
        return $this->success('保存成功');
    }

    /**
     * AJAX: 删除字段
     */
    public function deleteField(int $id)
    {
        $field = ContentModelField::find($id);
        if (empty($field)) {
            return $this->error('字段不存在');
        }

        $field->delete();
        Cache::tag('content_model')->clear();
        return $this->success('删除成功');
    }

    /**
     * AJAX: 获取字段列表
     */
    public function getFields(int $modelId)
    {
        $list = ContentModelField::where('model_id', $modelId)
            ->order('sort', 'asc')
            ->select();
        return $this->success('获取成功', ['list' => $list]);
    }

    /**
     * AJAX: 切换状态
     */
    public function toggleStatus(int $id)
    {
        $info = ContentModel::find($id);
        if (empty($info)) {
            return $this->error('模型不存在');
        }

        $info->status = $info->status ? 0 : 1;
        $info->save();
        Cache::tag('content_model')->clear();
        return $this->success('操作成功', ['status' => $info->status]);
    }
}
