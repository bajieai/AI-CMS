<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\template\TemplateResponsiveEditService;

/**
 * 模板响应式编辑 — V2.9.33 CUS3-2
 */
class TemplateResponsiveController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    /**
     * 响应式编辑页
     */
    public function edit(int $templateId)
    {
        $service = new TemplateResponsiveEditService();
        $data = $service->getResponsiveCss($templateId);

        $this->assign([
            'template_id' => $templateId,
            'base_css' => $data['base_css'],
            'breakpoints' => $data['breakpoints'],
            'menuActive' => 'template_responsive',
        ]);

        return $this->view('/template_store/responsive_edit');
    }

    /**
     * 保存响应式CSS
     */
    public function save(int $templateId)
    {
        $data = $this->request->post();
        $service = new TemplateResponsiveEditService();
        $result = $service->saveResponsiveCss($templateId, $data);

        return $result['success'] ? $this->success('保存成功') : $this->error('保存失败');
    }
}
