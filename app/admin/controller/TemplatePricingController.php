<?php
declare(strict_types=1);
namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\TemplateStore;
use app\common\service\admin\TemplatePricingService;

class TemplatePricingController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    public function index()
    {
        $templates = TemplateStore::where('status', 1)->order('id', 'desc')->paginate(20);
        $this->assign('list', $templates);
        return $this->view('/template_pricing_index');
    }

    public function edit(int $templateId = 0)
    {
        if ($this->request->isGet()) {
            $template = TemplateStore::find($templateId);
            $pricing = TemplatePricingService::getPricing($templateId);
            $this->assign(['template' => $template, 'pricing' => $pricing]);
            return $this->view('/template_pricing_edit');
        }
        $data = $this->request->post();
        TemplatePricingService::setPricing((int)$data['template_id'], $data);
        $this->recordLog('设置模板定价', '模板ID:' . $data['template_id']);
        return $this->success('保存成功', ['redirect' => '/admin/template_pricing/index']);
    }

    public function calculatePrice()
    {
        $templateId = (int)$this->request->post('template_id', 0);
        $couponCode = $this->request->post('coupon_code', '');
        $result = TemplatePricingService::calculateFinalPrice($templateId, $couponCode);
        return $this->success('计算成功', $result);
    }
}
