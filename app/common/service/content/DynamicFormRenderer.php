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

namespace app\common\service\content;

use app\common\model\ContentModel;
use app\common\model\ContentModelField;
use think\facade\Cache;

/**
 * V2.9.27 S-3: 动态表单渲染器
 * 根据内容模型定义动态生成表单HTML
 */
class DynamicFormRenderer
{
    /**
     * 渲染模型扩展字段表单
     * @param int $modelId 内容模型ID
     * @param array $extData 已有的扩展数据
     * @return string HTML表单
     */
    public static function render(int $modelId, array $extData = []): string
    {
        if ($modelId <= 0) {
            return '';
        }

        $fields = self::getFields($modelId);
        if (empty($fields)) {
            return '';
        }

        $html = '<div class="model-ext-fields" data-model-id="' . $modelId . '">';
        $html .= '<h5 class="mb-3"><i class="bi bi-list-ul"></i> 模型扩展字段</h5>';

        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $value = $extData[$fieldName] ?? ($field['default_value'] ?? '');
            $html .= self::renderFieldRow($field, $value);
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * 渲染单个字段行（带label和容器）
     */
    public static function renderFieldRow(array $field, $value = null): string
    {
        $label = $field['label'] ?? ($field['name'] ?? '');
        $required = (int)($field['required'] ?? 0) === 1;
        $type = $field['type'] ?? 'text';

        $html = '<div class="mb-3">';
        $html .= sprintf(
            '<label class="form-label">%s%s</label>',
            htmlspecialchars($label),
            $required ? ' <span class="text-danger">*</span>' : ''
        );

        // 使用FieldTypeRegistry渲染输入控件
        $html .= FieldTypeRegistry::renderInput($field, $value);

        // 提示文字
        if (!empty($field['placeholder']) && $type !== 'select') {
            // placeholder已在input中显示
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * 获取模型的字段定义（带缓存）
     */
    public static function getFields(int $modelId): array
    {
        if ($modelId <= 0) {
            return [];
        }

        $cacheKey = 'content_model_fields_' . $modelId;
        $fields = Cache::get($cacheKey);
        if ($fields !== null) {
            return $fields;
        }

        $fields = ContentModelField::where('model_id', $modelId)
            ->where('status', ContentModelField::STATUS_ENABLED)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        Cache::set($cacheKey, $fields, 3600);
        return $fields;
    }

    /**
     * 清除模型字段缓存
     */
    public static function clearCache(int $modelId): void
    {
        Cache::clear();
    }

    /**
     * 提取提交的扩展字段数据
     * 从POST数据中提取 ext_ 前缀的字段
     */
    public static function extractData(array $postData, int $modelId): array
    {
        $fields = self::getFields($modelId);
        if (empty($fields)) {
            return [];
        }

        $extData = [];
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $postKey = 'ext_' . $fieldName;
            $value = $postData[$postKey] ?? null;

            if ($value === null) {
                continue;
            }

            // 使用FieldTypeRegistry验证和解析
            $validation = FieldTypeRegistry::validate($field, $value);
            if (!$validation['valid']) {
                throw new \InvalidArgumentException($validation['error']);
            }

            $extData[$fieldName] = FieldTypeRegistry::parseValue($field, $validation['value']);
        }

        return $extData;
    }

    /**
     * 渲染模型选择器下拉框
     */
    public static function renderModelSelector(int $type, int $selectedModelId = 0): string
    {
        $models = ContentModel::where('status', ContentModel::STATUS_ENABLED)
            ->where('type', $type)
            ->order('sort', 'asc')
            ->select();

        if ($models->isEmpty()) {
            return '';
        }

        $html = sprintf('<select class="form-select" name="model_id" id="model_selector">');
        $html .= '<option value="0">-- 默认模型 --</option>';
        foreach ($models as $model) {
            $selected = $model->id === $selectedModelId ? ' selected' : '';
            $html .= sprintf(
                '<option value="%d"%s>%s</option>',
                $model->id, $selected, htmlspecialchars($model->name)
            );
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * 渲染模型专属分类选择器（S-3d）
     * 按model_id筛选分类
     */
    public static function renderModelCategories(int $modelId, int $type, int $selectedCateId = 0): string
    {
        $cates = \app\common\model\Cate::where('status', 1)
            ->where('type', $type)
            ->where(function ($query) use ($modelId) {
                $query->where('model_id', $modelId)->whereOr('model_id', 0);
            })
            ->order('sort', 'asc')
            ->select();

        $html = '<select class="form-select" name="cate_id" id="cate_selector">';
        $html .= '<option value="0">-- 请选择分类 --</option>';
        foreach ($cates as $cate) {
            $selected = $cate->id === $selectedCateId ? ' selected' : '';
            $html .= sprintf(
                '<option value="%d"%s>%s</option>',
                $cate->id, $selected, htmlspecialchars($cate->name)
            );
        }
        $html .= '</select>';
        return $html;
    }
}
