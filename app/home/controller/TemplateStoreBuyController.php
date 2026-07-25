<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\service\template\TemplateBuyService;

/**
 * 模板购买前台控制器 - V2.9.29 Sprint T-1
 */
class TemplateStoreBuyController extends FrontBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function buy(int $id = 0)
    {
        $userId = $this->memberInfo['id'] ?? 0;
        if ($userId <= 0) return redirect('/member/login?redirect=' . urlencode('/template_store/buy?id=' . $id));

        $service = new TemplateBuyService();
        if ($this->request->isPost()) {
            $priceTier = $this->request->post('price_tier', 'personal');
            $result = $service->createOrder($userId, $id, $priceTier);
            return $result ? $this->success('订单创建成功', $result) : $this->error('创建失败');
        }

        $info = $service->getTemplateInfo($id);
        $this->assign('info', $info);
        return $this->view('/template_buy');
    }

    public function cart()
    {
        $userId = $this->memberInfo['id'] ?? 0;
        $service = new TemplateBuyService();
        $cart = $service->getCart($userId);
        $this->assign('cart', $cart);
        return $this->view('/template_cart');
    }

    public function addToCart()
    {
        if (!$this->request->isPost()) return $this->error('请求方式错误');
        $userId = $this->memberInfo['id'] ?? 0;
        $templateId = (int) $this->request->post('template_id', 0);
        $service = new TemplateBuyService();
        return $service->addToCart($userId, $templateId) ? $this->success('已加入购物车') : $this->error('添加失败');
    }

    public function checkout()
    {
        if (!$this->request->isPost()) return $this->error('请求方式错误');
        $userId = $this->memberInfo['id'] ?? 0;
        $service = new TemplateBuyService();
        $result = $service->checkout($userId);
        return $result ? $this->success('结算成功', $result) : $this->error('结算失败');
    }
}
