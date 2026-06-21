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

use app\common\model\ContentModelField;

/**
 * V2.9.27 S-2: 字段类型注册表
 * 统一管理13种字段类型的渲染、验证、解析逻辑
 */
class FieldTypeRegistry
{
    /**
     * 字段类型定义
     * 每种类型包含：label, renderMethod, validateMethod, parseMethod
     */
    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_RICH_TEXT = 'rich_text';
    public const TYPE_NUMBER = 'number';
    public const TYPE_SELECT = 'select';
    public const TYPE_RADIO = 'radio';
    public const TYPE_CHECKBOX = 'checkbox';
    public const TYPE_DATE = 'date';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_IMAGE = 'image';
    public const TYPE_FILE = 'file';
    public const TYPE_COLOR = 'color';
    public const TYPE_TAGS = 'tags';
    public const TYPE_LOCATION = 'location';

    /**
     * 所有注册的字段类型
     */
    public static array $types = [
        self::TYPE_TEXT       => '单行文本',
        self::TYPE_TEXTAREA   => '多行文本',
        self::TYPE_RICH_TEXT  => '富文本编辑器',
        self::TYPE_NUMBER     => '数字',
        self::TYPE_SELECT     => '下拉选择',
        self::TYPE_RADIO      => '单选按钮',
        self::TYPE_CHECKBOX   => '多选框',
        self::TYPE_DATE       => '日期',
        self::TYPE_DATETIME   => '日期时间',
        self::TYPE_IMAGE      => '图片上传',
        self::TYPE_FILE       => '文件上传',
        self::TYPE_COLOR      => '颜色选择器',
        self::TYPE_TAGS       => '标签输入',
        self::TYPE_LOCATION   => '地理位置',
    ];

    /**
     * 需要选项的字段类型
     */
    public static array $needOptions = [
        self::TYPE_SELECT,
        self::TYPE_RADIO,
        self::TYPE_CHECKBOX,
    ];

    /**
     * 获取所有类型映射
     */
    public static function getTypeMap(): array
    {
        return self::$types;
    }

    /**
     * 渲染字段的HTML表单控件
     * 由DynamicFormRenderer调用
     */
    public static function renderInput(array $field, $value = null): string
    {
        $type = $field['type'] ?? self::TYPE_TEXT;
        $name = 'ext_' . ($field['name'] ?? '');
        $label = $field['label'] ?? '';
        $required = (int)($field['required'] ?? 0) === 1;
        $placeholder = $field['placeholder'] ?? '';
        $options = self::parseOptions($field['options'] ?? '');
        $value = $value ?? ($field['default_value'] ?? '');

        $requiredAttr = $required ? ' required' : '';
        $placeholderAttr = $placeholder ? ' placeholder="' . htmlspecialchars($placeholder) . '"' : '';

        return match ($type) {
            self::TYPE_TEXT => sprintf(
                '<input type="text" class="form-control" name="%s" value="%s" %s%s>',
                $name, htmlspecialchars((string)$value), $placeholderAttr, $requiredAttr
            ),

            self::TYPE_TEXTAREA => sprintf(
                '<textarea class="form-control" name="%s" rows="3" %s%s>%s</textarea>',
                $name, $placeholderAttr, $requiredAttr, htmlspecialchars((string)$value)
            ),

            self::TYPE_RICH_TEXT => sprintf(
                '<textarea class="form-control rich-editor" name="%s" rows="8" %s%s>%s</textarea>',
                $name, $placeholderAttr, $requiredAttr, htmlspecialchars((string)$value)
            ),

            self::TYPE_NUMBER => sprintf(
                '<input type="number" class="form-control" name="%s" value="%s" step="any" %s%s>',
                $name, htmlspecialchars((string)$value), $placeholderAttr, $requiredAttr
            ),

            self::TYPE_SELECT => self::renderSelect($name, $options, $value, $requiredAttr, $placeholder),

            self::TYPE_RADIO => self::renderRadio($name, $options, $value, $requiredAttr),

            self::TYPE_CHECKBOX => self::renderCheckbox($name, $options, $value, $requiredAttr),

            self::TYPE_DATE => sprintf(
                '<input type="date" class="form-control" name="%s" value="%s" %s>',
                $name, htmlspecialchars((string)$value), $requiredAttr
            ),

            self::TYPE_DATETIME => sprintf(
                '<input type="datetime-local" class="form-control" name="%s" value="%s" %s>',
                $name, htmlspecialchars((string)$value), $requiredAttr
            ),

            self::TYPE_IMAGE => self::renderImageUpload($name, $value, $requiredAttr),

            self::TYPE_FILE => self::renderFileUpload($name, $value, $requiredAttr),

            self::TYPE_COLOR => sprintf(
                '<input type="color" class="form-control form-control-color" name="%s" value="%s" %s>',
                $name, htmlspecialchars((string)$value), $requiredAttr
            ),

            self::TYPE_TAGS => sprintf(
                '<input type="text" class="form-control tag-input" name="%s" value="%s" data-role="tagsinput" %s%s>',
                $name, htmlspecialchars((string)$value), $placeholderAttr, $requiredAttr
            ),

            self::TYPE_LOCATION => sprintf(
                '<div class="input-group"><input type="text" class="form-control" name="%s" value="%s" %s%s><button type="button" class="btn btn-outline-secondary location-picker-btn" data-target="%s"><i class="bi bi-geo-alt"></i></button></div>',
                $name, htmlspecialchars((string)$value), $placeholderAttr, $requiredAttr, $name
            ),

            default => sprintf(
                '<input type="text" class="form-control" name="%s" value="%s" %s%s>',
                $name, htmlspecialchars((string)$value), $placeholderAttr, $requiredAttr
            ),
        };
    }

    /**
     * 验证字段值
     * @return array [valid => bool, value => mixed, error => string]
     */
    public static function validate(array $field, $value): array
    {
        $type = $field['type'] ?? self::TYPE_TEXT;
        $required = (int)($field['required'] ?? 0) === 1;
        $label = $field['label'] ?? ($field['name'] ?? '字段');

        // 必填检查
        if ($required && ($value === '' || $value === null || $value === [])) {
            return ['valid' => false, 'value' => $value, 'error' => $label . '不能为空'];
        }

        // 空值跳过后续验证
        if ($value === '' || $value === null) {
            return ['valid' => true, 'value' => $value, 'error' => ''];
        }

        // 类型特定验证
        $result = match ($type) {
            self::TYPE_NUMBER => self::validateNumber($value),
            self::TYPE_DATE, self::TYPE_DATETIME => self::validateDate($value, $type),
            self::TYPE_COLOR => self::validateColor($value),
            self::TYPE_CHECKBOX => self::validateCheckbox($value, $field),
            default => ['valid' => true, 'value' => $value, 'error' => ''],
        };

        // 验证规则（自定义validation JSON）
        $validation = $field['validation'] ?? '';
        if (!empty($validation) && $result['valid']) {
            $rules = json_decode($validation, true);
            if (is_array($rules)) {
                $result = self::applyValidationRules($result['value'], $rules, $label);
            }
        }

        return $result;
    }

    /**
     * 解析字段值（入库前处理）
     */
    public static function parseValue(array $field, $value)
    {
        $type = $field['type'] ?? self::TYPE_TEXT;

        return match ($type) {
            self::TYPE_NUMBER => is_numeric($value) ? (float)$value : 0,
            self::TYPE_CHECKBOX => is_array($value) ? implode(',', $value) : (string)$value,
            self::TYPE_TAGS => is_array($value) ? implode(',', $value) : (string)$value,
            self::TYPE_DATE, self::TYPE_DATETIME => self::parseDate($value, $type),
            self::TYPE_RICH_TEXT => self::sanitizeRichText($value),
            default => is_string($value) ? trim($value) : $value,
        };
    }

    /**
     * 格式化字段值（前台展示用）
     */
    public static function formatValue(array $field, $value): string
    {
        $type = $field['type'] ?? self::TYPE_TEXT;

        if ($value === '' || $value === null) {
            return '';
        }

        return match ($type) {
            self::TYPE_NUMBER => number_format((float)$value, (strpos((string)$value, '.') !== false) ? 2 : 0),
            self::TYPE_DATE => date('Y-m-d', (int)$value),
            self::TYPE_DATETIME => date('Y-m-d H:i', (int)$value),
            self::TYPE_COLOR => sprintf('<span class="color-swatch" style="background:%s;"></span> %s', htmlspecialchars($value), htmlspecialchars($value)),
            self::TYPE_CHECKBOX => self::formatCheckbox($value, $field),
            self::TYPE_IMAGE => sprintf('<img src="%s" class="img-thumbnail" style="max-width:100px;">', htmlspecialchars($value)),
            self::TYPE_FILE => sprintf('<a href="%s" target="_blank"><i class="bi bi-file-earmark"></i> 下载文件</a>', htmlspecialchars($value)),
            self::TYPE_TAGS => self::formatTags($value),
            default => (string)$value,
        };
    }

    /**
     * 解析选项JSON
     */
    public static function parseOptions(?string $options): array
    {
        if (empty($options)) {
            return [];
        }
        $decoded = json_decode($options, true);
        if (is_array($decoded)) {
            // 支持简单数组 ['选项1','选项2'] 或键值对 ['key'=>'label']
            $result = [];
            foreach ($decoded as $k => $v) {
                if (is_int($k)) {
                    $result[$v] = $v;
                } else {
                    $result[$k] = $v;
                }
            }
            return $result;
        }
        // 尝试逗号分隔
        $parts = explode(',', $options);
        $result = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $result[$part] = $part;
            }
        }
        return $result;
    }

    // === 私有方法 ===

    private static function renderSelect(string $name, array $options, $value, string $requiredAttr, string $placeholder): string
    {
        $html = sprintf('<select class="form-select" name="%s"%s>', $name, $requiredAttr);
        if ($placeholder) {
            $html .= sprintf('<option value="">%s</option>', htmlspecialchars($placeholder));
        }
        foreach ($options as $k => $v) {
            $selected = ((string)$k === (string)$value) ? ' selected' : '';
            $html .= sprintf('<option value="%s"%s>%s</option>', htmlspecialchars($k), $selected, htmlspecialchars($v));
        }
        $html .= '</select>';
        return $html;
    }

    private static function renderRadio(string $name, array $options, $value, string $requiredAttr): string
    {
        $html = '<div class="radio-group">';
        foreach ($options as $k => $v) {
            $checked = ((string)$k === (string)$value) ? ' checked' : '';
            $html .= sprintf(
                '<div class="form-check"><input class="form-check-input" type="radio" name="%s" value="%s" id="%s_%s"%s%s><label class="form-check-label" for="%s_%s">%s</label></div>',
                $name, htmlspecialchars($k), $name, md5($k), $checked, $requiredAttr, $name, md5($k), htmlspecialchars($v)
            );
        }
        $html .= '</div>';
        return $html;
    }

    private static function renderCheckbox(string $name, array $options, $value, string $requiredAttr): string
    {
        $selectedValues = is_array($value) ? $value : (empty($value) ? [] : explode(',', (string)$value));
        $html = '<div class="checkbox-group">';
        foreach ($options as $k => $v) {
            $checked = in_array((string)$k, array_map('strval', $selectedValues)) ? ' checked' : '';
            $html .= sprintf(
                '<div class="form-check"><input class="form-check-input" type="checkbox" name="%s[]" value="%s" id="%s_%s"%s%s><label class="form-check-label" for="%s_%s">%s</label></div>',
                $name, htmlspecialchars($k), $name, md5($k), $checked, $requiredAttr, $name, md5($k), htmlspecialchars($v)
            );
        }
        $html .= '</div>';
        return $html;
    }

    private static function renderImageUpload(string $name, $value, string $requiredAttr): string
    {
        $html = '<div class="image-upload-wrapper">';
        if ($value) {
            $html .= sprintf('<div class="mb-2"><img src="%s" class="img-thumbnail" style="max-width:150px;" id="preview_%s"></div>', htmlspecialchars($value), $name);
        }
        $html .= sprintf('<div class="input-group"><input type="text" class="form-control" name="%s" value="%s" id="%s"%s><button type="button" class="btn btn-outline-primary upload-btn" data-target="%s" data-type="image"><i class="bi bi-upload"></i> 上传</button></div>', $name, htmlspecialchars((string)$value), $name, $requiredAttr, $name);
        $html .= '</div>';
        return $html;
    }

    private static function renderFileUpload(string $name, $value, string $requiredAttr): string
    {
        $html = '<div class="file-upload-wrapper">';
        if ($value) {
            $html .= sprintf('<div class="mb-2"><a href="%s" target="_blank"><i class="bi bi-file-earmark"></i> %s</a></div>', htmlspecialchars($value), basename($value));
        }
        $html .= sprintf('<div class="input-group"><input type="text" class="form-control" name="%s" value="%s" id="%s"%s><button type="button" class="btn btn-outline-primary upload-btn" data-target="%s" data-type="file"><i class="bi bi-upload"></i> 上传</button></div>', $name, htmlspecialchars((string)$value), $name, $requiredAttr, $name);
        $html .= '</div>';
        return $html;
    }

    private static function validateNumber($value): array
    {
        if (!is_numeric($value)) {
            return ['valid' => false, 'value' => $value, 'error' => '必须是数字'];
        }
        return ['valid' => true, 'value' => $value, 'error' => ''];
    }

    private static function validateDate($value, string $type): array
    {
        $format = $type === self::TYPE_DATE ? 'Y-m-d' : 'Y-m-d H:i:s';
        $d = \DateTime::createFromFormat($format, $value);
        if (!$d || $d->format($format) !== $value) {
            return ['valid' => false, 'value' => $value, 'error' => '日期格式不正确'];
        }
        return ['valid' => true, 'value' => $value, 'error' => ''];
    }

    private static function validateColor($value): array
    {
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            return ['valid' => false, 'value' => $value, 'error' => '颜色格式不正确(如#FF0000)'];
        }
        return ['valid' => true, 'value' => $value, 'error' => ''];
    }

    private static function validateCheckbox($value, array $field): array
    {
        $options = self::parseOptions($field['options'] ?? '');
        $values = is_array($value) ? $value : explode(',', (string)$value);
        foreach ($values as $v) {
            if (!isset($options[$v])) {
                return ['valid' => false, 'value' => $value, 'error' => '包含无效选项: ' . $v];
            }
        }
        return ['valid' => true, 'value' => $value, 'error' => ''];
    }

    private static function applyValidationRules($value, array $rules, string $label): array
    {
        // min_length, max_length, min, max, pattern
        if (isset($rules['min_length']) && mb_strlen((string)$value) < $rules['min_length']) {
            return ['valid' => false, 'value' => $value, 'error' => $label . '最少' . $rules['min_length'] . '个字符'];
        }
        if (isset($rules['max_length']) && mb_strlen((string)$value) > $rules['max_length']) {
            return ['valid' => false, 'value' => $value, 'error' => $label . '最多' . $rules['max_length'] . '个字符'];
        }
        if (isset($rules['min']) && is_numeric($value) && (float)$value < (float)$rules['min']) {
            return ['valid' => false, 'value' => $value, 'error' => $label . '不能小于' . $rules['min']];
        }
        if (isset($rules['max']) && is_numeric($value) && (float)$value > (float)$rules['max']) {
            return ['valid' => false, 'value' => $value, 'error' => $label . '不能大于' . $rules['max']];
        }
        if (isset($rules['pattern']) && !preg_match('/' . $rules['pattern'] . '/', (string)$value)) {
            return ['valid' => false, 'value' => $value, 'error' => $label . '格式不正确'];
        }
        return ['valid' => true, 'value' => $value, 'error' => ''];
    }

    private static function parseDate($value, string $type): int
    {
        if (empty($value)) {
            return 0;
        }
        if (is_numeric($value)) {
            return (int)$value;
        }
        $ts = strtotime($value);
        return $ts !== false ? $ts : 0;
    }

    private static function sanitizeRichText($value): string
    {
        if (!is_string($value)) {
            return '';
        }
        // 基础XSS防护（保留安全标签）
        $value = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $value);
        $value = preg_replace('/<iframe[^>]*>.*?<\/iframe>/is', '', $value);
        $value = preg_replace('/on\w+\s*=\s*"[^"]*"/i', '', $value);
        $value = preg_replace('/on\w+\s*=\s*\'[^\']*\'/i', '', $value);
        return $value;
    }

    private static function formatCheckbox($value, array $field): string
    {
        $options = self::parseOptions($field['options'] ?? '');
        $values = is_array($value) ? $value : explode(',', (string)$value);
        $labels = [];
        foreach ($values as $v) {
            $labels[] = $options[$v] ?? $v;
        }
        return implode(', ', $labels);
    }

    private static function formatTags($value): string
    {
        $tags = is_array($value) ? $value : explode(',', (string)$value);
        $html = '';
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if ($tag) {
                $html .= sprintf('<span class="badge bg-info me-1">%s</span>', htmlspecialchars($tag));
            }
        }
        return $html;
    }
}
