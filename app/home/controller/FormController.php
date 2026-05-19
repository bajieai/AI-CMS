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

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\service\FormService;

/**
 * 前台表单提交控制器
 */
class FormController extends FrontBaseController
{
    protected bool $enablePageCache = false;

    /**
     * V2.7: 表单展示页（支持可视化编辑器渲染）
     */
    public function show(string $code = '')
    {
        $form = \app\common\model\Form::where('code', $code)
            ->where('is_enabled', 1)
            ->find();
        if (!$form) {
            return $this->error('表单不存在或已停用');
        }

        // 优先使用可视化编辑器配置
        $fieldsConfig = [];
        if (!empty($form->fields_config)) {
            $fieldsConfig = is_string($form->fields_config)
                ? json_decode($form->fields_config, true)
                : $form->fields_config;
        } elseif (!empty($form->fields)) {
            // 兼容旧版字段配置
            $fieldsConfig = $form->fields;
        }

        $this->assign([
            'form' => $form,
            'fields_config' => $fieldsConfig,
        ]);
        return $this->view('/form_render');
    }

    /**
     * 提交表单
     */
    public function submit()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }

        try {
            FormService::submit($this->request->post());
            return json(['code' => 0, 'msg' => '提交成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}
