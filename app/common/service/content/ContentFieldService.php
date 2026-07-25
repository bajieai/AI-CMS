<?php
declare(strict_types=1);

namespace app\common\service\content;

use think\facade\Db;
use think\facade\Cache;
use app\common\model\ContentField;

/**
 * 自定义字段服务 (V2.9.36 CM-1)
 *
 * 提供字段CRUD、批量排序、表单渲染、模板标签生成等
 * 缓存标签 content_field，TTL 5分钟
 */
class ContentFieldService
{
    private const CACHE_TAG = 'content_field';
    private const CACHE_TTL = 300; // 5分钟
    private const TABLE     = 'content_field';

    /**
     * 获取模型字段列表（缓存5分钟）
     */
    public function getFields(int $modelId): array
    {
        if ($modelId <= 0) {
            return [];
        }

        return Cache::remember(
            'fields_model_' . $modelId,
            function () use ($modelId) {
                return Db::name(self::TABLE)
                    ->where('model_id', $modelId)
                    ->order('sort_order', 'asc')
                    ->order('id', 'asc')
                    ->select()
                    ->toArray();
            },
            self::CACHE_TTL
        );
    }

    /**
     * 获取字段详情
     */
    public function getFieldById(int $id): ?array
    {
        try {
            $row = Db::name(self::TABLE)->where('id', $id)->find();
            if (!$row) {
                return null;
            }
            $row['field_options']    = $this->decodeJson($row['field_options'] ?? null);
            $row['field_validation'] = $this->decodeJson($row['field_validation'] ?? null);
            $row['field_layout']     = $this->decodeJson($row['field_layout'] ?? null);
            return $row;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 创建字段
     */
    public function createField(array $data): array
    {
        try {
            $modelId = (int)($data['model_id'] ?? 0);
            if ($modelId <= 0) {
                return ['code' => 1, 'msg' => '模型ID不能为空', 'data' => null];
            }
            if (empty($data['field_name']) || empty($data['field_label'])) {
                return ['code' => 1, 'msg' => '字段标识和标签不能为空', 'data' => null];
            }

            // 同模型下字段名唯一检查
            $exists = Db::name(self::TABLE)
                ->where('model_id', $modelId)
                ->where('field_name', $data['field_name'])
                ->find();
            if ($exists) {
                return ['code' => 1, 'msg' => '字段标识已存在', 'data' => null];
            }

            $maxSort = Db::name(self::TABLE)
                ->where('model_id', $modelId)
                ->max('sort_order');

            $insert = [
                'model_id'         => $modelId,
                'field_name'       => trim($data['field_name']),
                'field_label'      => trim($data['field_label']),
                'field_type'       => $data['field_type'] ?? 'text',
                'field_options'    => json_encode($data['field_options'] ?? [], JSON_UNESCAPED_UNICODE),
                'field_validation' => json_encode($data['field_validation'] ?? [], JSON_UNESCAPED_UNICODE),
                'field_layout'     => json_encode($data['field_layout'] ?? [], JSON_UNESCAPED_UNICODE),
                'default_value'    => $data['default_value'] ?? '',
                'placeholder'      => $data['placeholder'] ?? '',
                'help_text'        => $data['help_text'] ?? '',
                'sort_order'       => (int)($data['sort_order'] ?? ($maxSort + 1)),
                'is_required'      => (int)($data['is_required'] ?? 0),
                'is_unique'        => (int)($data['is_unique'] ?? 0),
                'is_searchable'    => (int)($data['is_searchable'] ?? 0),
                'is_list_show'     => (int)($data['is_list_show'] ?? 0),
                'is_system'        => 0,
                'status'           => 1,
                'create_time'      => date('Y-m-d H:i:s'),
                'update_time'      => date('Y-m-d H:i:s'),
            ];

            $id = Db::name(self::TABLE)->insertGetId($insert);
            Cache::clear();

            return ['code' => 0, 'msg' => '创建成功', 'data' => ['id' => $id]];
        } catch (\Throwable $e) {
            return ['code' => 1, 'msg' => '创建字段失败: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * 更新字段
     */
    public function updateField(int $id, array $data): array
    {
        try {
            $field = Db::name(self::TABLE)->where('id', $id)->find();
            if (!$field) {
                return ['code' => 1, 'msg' => '字段不存在', 'data' => null];
            }

            $update = [];
            $strFields = ['field_label', 'field_type', 'default_value', 'placeholder', 'help_text'];
            foreach ($strFields as $f) {
                if (isset($data[$f])) {
                    $update[$f] = $data[$f];
                }
            }
            // 系统字段不允许修改标识
            if (isset($data['field_name']) && (int)$field['is_system'] === 0) {
                $update['field_name'] = $data['field_name'];
            }
            $jsonFields = ['field_options', 'field_validation', 'field_layout'];
            foreach ($jsonFields as $f) {
                if (isset($data[$f])) {
                    $update[$f] = json_encode($data[$f], JSON_UNESCAPED_UNICODE);
                }
            }
            $intFields = ['sort_order', 'is_required', 'is_unique', 'is_searchable', 'is_list_show', 'status'];
            foreach ($intFields as $f) {
                if (isset($data[$f])) {
                    $update[$f] = (int)$data[$f];
                }
            }
            $update['update_time'] = date('Y-m-d H:i:s');

            if (empty($update)) {
                return ['code' => 0, 'msg' => '无更新数据', 'data' => null];
            }

            Db::name(self::TABLE)->where('id', $id)->update($update);
            Cache::clear();

            return ['code' => 0, 'msg' => '更新成功', 'data' => ['id' => $id]];
        } catch (\Throwable $e) {
            return ['code' => 1, 'msg' => '更新字段失败: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * 删除字段（系统字段不可删）
     */
    public function deleteField(int $id): array
    {
        try {
            $field = Db::name(self::TABLE)->where('id', $id)->find();
            if (!$field) {
                return ['code' => 1, 'msg' => '字段不存在', 'data' => null];
            }
            if ((int)$field['is_system'] === 1) {
                return ['code' => 1, 'msg' => '系统字段不可删除', 'data' => null];
            }

            Db::name(self::TABLE)->where('id', $id)->delete();
            Cache::clear();

            return ['code' => 0, 'msg' => '删除成功', 'data' => null];
        } catch (\Throwable $e) {
            return ['code' => 1, 'msg' => '删除字段失败: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * 批量排序
     * @param array $sortData [['id'=>1,'sort_order'=>1], ...]
     */
    public function sortFields(array $sortData): array
    {
        try {
            Db::startTrans();
            foreach ($sortData as $item) {
                if (!isset($item['id'])) {
                    continue;
                }
                Db::name(self::TABLE)->where('id', (int)$item['id'])->update([
                    'sort_order'  => (int)($item['sort_order'] ?? 0),
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
            }
            Db::commit();
            Cache::clear();

            return ['code' => 0, 'msg' => '排序成功', 'data' => null];
        } catch (\Throwable $e) {
            Db::rollback();
            return ['code' => 1, 'msg' => '排序失败: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * 复制字段
     */
    public function copyField(int $id): array
    {
        try {
            $field = Db::name(self::TABLE)->where('id', $id)->find();
            if (!$field) {
                return ['code' => 1, 'msg' => '字段不存在', 'data' => null];
            }

            unset($field['id']);
            $field['field_name']  = $field['field_name'] . '_copy';
            $field['field_label'] = $field['field_label'] . '_副本';
            $field['sort_order']  = (int)$field['sort_order'] + 1;
            $field['is_system']   = 0;
            $field['create_time'] = date('Y-m-d H:i:s');
            $field['update_time'] = date('Y-m-d H:i:s');

            $newId = Db::name(self::TABLE)->insertGetId($field);
            Cache::clear();

            return ['code' => 0, 'msg' => '复制成功', 'data' => ['id' => $newId]];
        } catch (\Throwable $e) {
            return ['code' => 1, 'msg' => '复制字段失败: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * 返回28种字段类型列表
     */
    public function getFieldTypes(): array
    {
        return ContentField::getFieldTypeList();
    }

    /**
     * 根据字段类型生成HTML表单控件
     */
    public function renderFormField(array $field, $value = null): string
    {
        $type       = $field['field_type'] ?? 'text';
        $name       = 'cf_' . ($field['field_name'] ?? '');
        $label      = $field['field_label'] ?? '';
        $required   = (int)($field['is_required'] ?? 0) === 1;
        $placeholder = $field['placeholder'] ?? '';
        $options    = $this->parseOptions($field['field_options'] ?? []);
        $helpText   = $field['help_text'] ?? '';
        $value      = $value ?? ($field['default_value'] ?? '');

        $reqAttr   = $required ? ' required' : '';
        $phAttr    = $placeholder ? ' placeholder="' . htmlspecialchars($placeholder) . '"' : '';
        $valAttr   = htmlspecialchars((string)$value);

        $html = '<div class="mb-3">';
        $html .= sprintf('<label class="form-label">%s%s</label>', htmlspecialchars($label), $required ? ' <span class="text-danger">*</span>' : '');

        $html .= match ($type) {
            'text' => sprintf(
                '<input type="text" class="form-control" name="%s" value="%s" %s%s>',
                $name, $valAttr, $phAttr, $reqAttr
            ),

            'textarea' => sprintf(
                '<textarea class="form-control" name="%s" rows="3" %s%s>%s</textarea>',
                $name, $phAttr, $reqAttr, $valAttr
            ),

            'editor' => sprintf(
                '<textarea class="form-control rich-text" name="%s" rows="8" %s%s>%s</textarea>',
                $name, $phAttr, $reqAttr, $valAttr
            ),

            'number' => sprintf(
                '<input type="number" class="form-control" name="%s" value="%s" step="any" %s%s>',
                $name, $valAttr, $phAttr, $reqAttr
            ),

            'select' => $this->renderSelect($name, $options, $value, $reqAttr),

            'multi-select' => $this->renderMultiSelect($name, $options, $value, $reqAttr),

            'radio' => $this->renderRadio($name, $options, $value, $reqAttr),

            'checkbox' => $this->renderCheckbox($name, $options, $value, $reqAttr),

            'date' => sprintf(
                '<input type="date" class="form-control" name="%s" value="%s" %s>',
                $name, $valAttr, $reqAttr
            ),

            'datetime' => sprintf(
                '<input type="datetime-local" class="form-control" name="%s" value="%s" %s>',
                $name, $valAttr, $reqAttr
            ),

            'switch' => sprintf(
                '<div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="%s" value="1" id="sw_%s"%s%s%s><label class="form-check-label" for="sw_%s">%s</label></div>',
                $name, $name, (int)$value === 1 ? ' checked' : '', $reqAttr, $placeholder ? ' data-label="' . htmlspecialchars($placeholder) . '"' : '', $name, htmlspecialchars($label)
            ),

            'image' => sprintf(
                '<div class="image-upload-wrapper"><input type="file" class="form-control image-upload" name="%s" accept="image/*" data-value="%s" %s></div>',
                $name, $valAttr, $reqAttr
            ),

            'images' => sprintf(
                '<div class="multi-image-upload-wrapper"><input type="file" class="form-control image-upload" name="%s[]" accept="image/*" multiple data-value="%s" %s></div>',
                $name, $valAttr, $reqAttr
            ),

            'file' => sprintf(
                '<div class="file-upload-wrapper"><input type="file" class="form-control" name="%s" data-value="%s" %s></div>',
                $name, $valAttr, $reqAttr
            ),

            'files' => sprintf(
                '<div class="multi-file-upload-wrapper"><input type="file" class="form-control" name="%s[]" multiple data-value="%s" %s></div>',
                $name, $valAttr, $reqAttr
            ),

            'color' => sprintf(
                '<input type="color" class="form-control form-control-color" name="%s" value="%s" %s>',
                $name, $valAttr, $reqAttr
            ),

            'rating' => sprintf(
                '<div class="star-rating" data-name="%s" data-value="%d" data-max="5"><div class="stars">%s</div></div>',
                $name, (int)$value, $reqAttr
            ),

            'icon' => sprintf(
                '<div class="icon-picker-wrapper"><input type="text" class="form-control icon-picker" name="%s" value="%s" %s%s></div>',
                $name, $valAttr, $phAttr, $reqAttr
            ),

            'url' => sprintf(
                '<input type="url" class="form-control" name="%s" value="%s" %s%s>',
                $name, $valAttr, $phAttr, $reqAttr
            ),

            'email' => sprintf(
                '<input type="email" class="form-control" name="%s" value="%s" %s%s>',
                $name, $valAttr, $phAttr, $reqAttr
            ),

            'phone' => sprintf(
                '<input type="tel" class="form-control" name="%s" value="%s" %s%s>',
                $name, $valAttr, $phAttr, $reqAttr
            ),

            'password' => sprintf(
                '<input type="password" class="form-control" name="%s" value="%s" %s%s>',
                $name, $valAttr, $phAttr, $reqAttr
            ),

            'hidden' => sprintf(
                '<input type="hidden" name="%s" value="%s">',
                $name, $valAttr
            ),

            'location' => sprintf(
                '<div class="input-group"><input type="text" class="form-control map-location" name="%s" value="%s" %s%s><button type="button" class="btn btn-outline-secondary location-picker" data-target="%s"><i class="bi bi-geo-alt"></i></button></div>',
                $name, $valAttr, $phAttr, $reqAttr, $name
            ),

            'link' => sprintf(
                '<input type="url" class="form-control content-link" name="%s" value="%s" %s%s>',
                $name, $valAttr, $phAttr, $reqAttr
            ),

            'notice' => sprintf(
                '<div class="alert alert-info">%s</div>',
                htmlspecialchars($field['default_value'] ?? $label)
            ),

            'group' => '<div class="field-group-container"></div>',

            'repeater' => '<div class="repeater-container" data-name="' . $name . '"><button type="button" class="btn btn-sm btn-outline-primary add-repeater-item">+ 添加</button></div>',

            default => sprintf(
                '<input type="text" class="form-control" name="%s" value="%s" %s%s>',
                $name, $valAttr, $phAttr, $reqAttr
            ),
        };

        if ($helpText) {
            $html .= sprintf('<div class="form-text">%s</div>', htmlspecialchars($helpText));
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * 从表单数据中提取自定义字段值，返回JSON可存储的数组
     */
    public function parseCustomFields(int $modelId, array $data): array
    {
        $fields = $this->getFields($modelId);
        if (empty($fields)) {
            return [];
        }

        $result = [];
        foreach ($fields as $field) {
            $fieldName = $field['field_name'];
            $key       = 'cf_' . $fieldName;
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $value = $data[$key];

            // 根据类型处理
            $type = $field['field_type'];
            if (in_array($type, ['checkbox', 'multi-select', 'images', 'files'], true)) {
                $value = is_array($value) ? $value : (empty($value) ? [] : [$value]);
            } elseif ($type === 'number') {
                $value = is_numeric($value) ? (float)$value : 0;
            } elseif ($type === 'switch') {
                $value = (int)$value;
            } elseif ($type === 'rating') {
                $value = (int)$value;
            }

            $result[$fieldName] = $value;
        }

        return $result;
    }

    /**
     * 获取模板标签说明
     */
    public function getTemplateTags(int $modelId): array
    {
        $fields = $this->getFields($modelId);
        $tags   = [];

        foreach ($fields as $field) {
            $tags[] = [
                'tag'         => '{$' . $field['field_name'] . '}',
                'name'        => $field['field_label'],
                'type'        => $field['field_type'],
                'description' => $field['help_text'] ?? '',
                'example'     => '{$' . $field['field_name'] . '}',
            ];
        }

        return $tags;
    }

    /**
     * 清除缓存
     */
    public function clearCache(): void
    {
        Cache::clear();
    }

    // === 私有方法 ===

    private function renderSelect(string $name, array $options, $value, string $reqAttr): string
    {
        $html = sprintf('<select class="form-select" name="%s"%s>', $name, $reqAttr);
        $html .= '<option value="">-- 请选择 --</option>';
        foreach ($options as $k => $v) {
            $selected = ((string)$k === (string)$value) ? ' selected' : '';
            $html .= sprintf('<option value="%s"%s>%s</option>', htmlspecialchars($k), $selected, htmlspecialchars($v));
        }
        $html .= '</select>';
        return $html;
    }

    private function renderMultiSelect(string $name, array $options, $value, string $reqAttr): string
    {
        $selectedValues = is_array($value) ? $value : (empty($value) ? [] : explode(',', (string)$value));
        $html = sprintf('<select class="form-select" name="%s[]" multiple%s>', $name, $reqAttr);
        foreach ($options as $k => $v) {
            $selected = in_array((string)$k, array_map('strval', $selectedValues)) ? ' selected' : '';
            $html .= sprintf('<option value="%s"%s>%s</option>', htmlspecialchars($k), $selected, htmlspecialchars($v));
        }
        $html .= '</select>';
        return $html;
    }

    private function renderRadio(string $name, array $options, $value, string $reqAttr): string
    {
        $html = '<div class="radio-group">';
        foreach ($options as $k => $v) {
            $checked = ((string)$k === (string)$value) ? ' checked' : '';
            $id = $name . '_' . md5((string)$k);
            $html .= sprintf(
                '<div class="form-check"><input class="form-check-input" type="radio" name="%s" value="%s" id="%s"%s%s><label class="form-check-label" for="%s">%s</label></div>',
                $name, htmlspecialchars($k), $id, $checked, $reqAttr, $id, htmlspecialchars($v)
            );
        }
        $html .= '</div>';
        return $html;
    }

    private function renderCheckbox(string $name, array $options, $value, string $reqAttr): string
    {
        $selectedValues = is_array($value) ? $value : (empty($value) ? [] : explode(',', (string)$value));
        $html = '<div class="checkbox-group">';
        foreach ($options as $k => $v) {
            $checked = in_array((string)$k, array_map('strval', $selectedValues)) ? ' checked' : '';
            $id = $name . '_' . md5((string)$k);
            $html .= sprintf(
                '<div class="form-check"><input class="form-check-input" type="checkbox" name="%s[]" value="%s" id="%s"%s%s><label class="form-check-label" for="%s">%s</label></div>',
                $name, htmlspecialchars($k), $id, $checked, $reqAttr, $id, htmlspecialchars($v)
            );
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * 解析字段选项（支持数组、JSON字符串、逗号分隔）
     */
    private function parseOptions($options): array
    {
        if (empty($options)) {
            return [];
        }
        if (is_array($options)) {
            $result = [];
            foreach ($options as $k => $v) {
                if (is_int($k)) {
                    $result[(string)$v] = (string)$v;
                } else {
                    $result[$k] = $v;
                }
            }
            return $result;
        }
        $decoded = json_decode((string)$options, true);
        if (is_array($decoded)) {
            $result = [];
            foreach ($decoded as $k => $v) {
                if (is_int($k)) {
                    $result[(string)$v] = (string)$v;
                } else {
                    $result[$k] = $v;
                }
            }
            return $result;
        }
        // 逗号分隔
        $result = [];
        foreach (explode(',', (string)$options) as $part) {
            $part = trim($part);
            if ($part !== '') {
                $result[$part] = $part;
            }
        }
        return $result;
    }

    /**
     * 解码JSON
     */
    private function decodeJson($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (empty($value)) {
            return [];
        }
        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
