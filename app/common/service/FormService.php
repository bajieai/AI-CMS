<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Form as FormModel;
use app\common\model\FormData as FormDataModel;
use app\common\service\CaptchaService;

/**
 * 表单服务
 */
class FormService
{
    const FIELD_TYPES = [
        'text'     => ['label' => '单行文本', 'component' => 'input'],
        'textarea' => ['label' => '多行文本', 'component' => 'textarea'],
        'number'   => ['label' => '数字', 'component' => 'input'],
        'email'    => ['label' => '邮箱', 'component' => 'input'],
        'phone'    => ['label' => '手机', 'component' => 'input'],
        'radio'    => ['label' => '单选', 'component' => 'radio'],
        'checkbox' => ['label' => '多选', 'component' => 'checkbox'],
        'select'   => ['label' => '下拉选择', 'component' => 'select'],
        'date'     => ['label' => '日期', 'component' => 'input'],
        'file'     => ['label' => '文件上传', 'component' => 'file'],
    ];

    /**
     * 渲染表单HTML
     */
    public static function render(int $formId): string
    {
        $form = FormModel::where('id', $formId)->where('is_enabled', 1)->find();
        if (!$form) return '';

        $fields = $form->fields;
        if (empty($fields)) return '';

        $html = '<form class="i8j-form" action="' . url('home/form/submit') . '" method="POST" enctype="multipart/form-data">';
        $html .= '<input type="hidden" name="form_code" value="' . $form->code . '">';
        $html .= '<input type="hidden" name="__token__" value="' . request()->buildToken() . '">';

        foreach ($fields as $field) {
            $html .= self::renderField($field);
        }

        $html .= '<div class="mb-3"><button type="submit" class="btn btn-primary">' . htmlspecialchars($form->submit_text ?: '提交') . '</button></div>';
        $html .= '</form>';

        return $html;
    }

    /**
     * 渲染单个字段
     */
    protected static function renderField(array $field): string
    {
        $name = $field['name'] ?? '';
        $label = $field['label'] ?? '';
        $type = $field['type'] ?? 'text';
        $required = !empty($field['required']) ? 'required' : '';
        $placeholder = $field['placeholder'] ?? '';
        $options = $field['options'] ?? [];

        $html = '<div class="mb-3">';
        $html .= '<label class="form-label">' . htmlspecialchars($label);
        if ($required) $html .= ' <span class="text-danger">*</span>';
        $html .= '</label>';

        switch ($type) {
            case 'textarea':
                $html .= '<textarea class="form-control" name="' . $name . '" placeholder="' . htmlspecialchars($placeholder) . '" ' . $required . ' rows="4"></textarea>';
                break;
            case 'select':
                $html .= '<select class="form-select" name="' . $name . '" ' . $required . '>';
                $html .= '<option value="">请选择</option>';
                foreach ($options as $opt) {
                    $html .= '<option value="' . htmlspecialchars($opt) . '">' . htmlspecialchars($opt) . '</option>';
                }
                $html .= '</select>';
                break;
            case 'radio':
                foreach ($options as $opt) {
                    $html .= '<div class="form-check"><input class="form-check-input" type="radio" name="' . $name . '" value="' . htmlspecialchars($opt) . '" ' . $required . '><label class="form-check-label">' . htmlspecialchars($opt) . '</label></div>';
                }
                break;
            case 'checkbox':
                foreach ($options as $opt) {
                    $html .= '<div class="form-check"><input class="form-check-input" type="checkbox" name="' . $name . '[]" value="' . htmlspecialchars($opt) . '"><label class="form-check-label">' . htmlspecialchars($opt) . '</label></div>';
                }
                break;
            case 'file':
                $html .= '<input class="form-control" type="file" name="' . $name . '" ' . $required . '>';
                break;
            default:
                $inputType = match($type) {
                    'number' => 'number',
                    'email'  => 'email',
                    'phone'  => 'tel',
                    'date'   => 'date',
                    default  => 'text',
                };
                $html .= '<input class="form-control" type="' . $inputType . '" name="' . $name . '" placeholder="' . htmlspecialchars($placeholder) . '" ' . $required . '>';
                break;
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * 提交表单数据
     */
    public static function submit(array $data): bool
    {
        $formCode = $data['form_code'] ?? '';
        $formId = (int) ($data['form_id'] ?? 0);

        if ($formId > 0) {
            $form = FormModel::find($formId);
        } elseif ($formCode) {
            $form = FormModel::where('code', $formCode)->find();
        } else {
            throw new \Exception('表单参数错误');
        }

        if (!$form || !$form->is_enabled) throw new \Exception('表单不存在或已停用');

        // V2.5：验证码检查
        if (CaptchaService::isFormCaptchaRequired($formCode)) {
            $captchaKey = $data['captcha_key'] ?? '';
            $captchaAnswer = $data['captcha_answer'] ?? '';
            if (!CaptchaService::verify($captchaKey, $captchaAnswer)) {
                throw new \Exception('验证码错误');
            }
        }

        $fields = $form->fields;
        $submitData = [];

        foreach ($fields as $field) {
            $name = $field['name'] ?? '';
            $value = $data[$name] ?? '';

            // 必填验证
            if (!empty($field['required']) && empty($value)) {
                throw new \Exception(($field['label'] ?? $name) . '不能为空');
            }

            // 格式验证
            if ($field['type'] === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception(($field['label'] ?? $name) . '格式不正确');
            }
            if ($field['type'] === 'phone' && $value && !preg_match('/^1[3-9]\d{9}$/', $value)) {
                throw new \Exception(($field['label'] ?? $name) . '格式不正确');
            }

            $submitData[$name] = is_array($value) ? implode(',', $value) : $value;
        }

        // 防刷检查
        if ($form->anti_spam >= 2) {
            $ip = request()->ip();
            $recentCount = FormDataModel::where('form_id', $form->id)
                ->where('ip', $ip)
                ->where('create_time', '>=', time() - 3600)
                ->count();
            if ($recentCount > 5) {
                throw new \Exception('提交过于频繁，请稍后再试');
            }
        }

        FormDataModel::create([
            'form_id'     => $form->id,
            'fields_data' => $submitData,
            'ip'          => request()->ip(),
            'user_agent'  => request()->server('HTTP_USER_AGENT', ''),
        ]);

        return true;
    }

    /**
     * 通过code渲染表单
     */
    public static function renderByCode(string $code): string
    {
        $form = FormModel::where('code', $code)->where('is_enabled', 1)->find();
        if (!$form) return '';
        return self::render($form->id);
    }
}
