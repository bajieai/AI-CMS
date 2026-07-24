<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\plugin\PluginPaymentService;

/**
 * 插件支付控制器 — V2.9.36 Sprint PLUG-SHOP
 */
class PluginPaymentController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 发起支付
     */
    public function pay(int $id)
    {
        $payType = $this->request->post('pay_type', 'alipay');

        $service = new PluginPaymentService();
        $result = $service->pay($id, $payType);

        return $result['code'] === 0
            ? $this->success($result['msg'], $result['data'] ?? [])
            : $this->error($result['msg']);
    }

    /**
     * 支付宝回调
     */
    public function alipayCallback()
    {
        $data = $this->request->post();
        $service = new PluginPaymentService();
        $result = $service->handleCallback('alipay', $data);

        return $result['code'] === 0 ? json(['code' => 0, 'msg' => 'success']) : json(['code' => 1, 'msg' => 'fail']);
    }

    /**
     * 微信回调
     */
    public function wechatCallback()
    {
        $data = $this->request->post();
        $service = new PluginPaymentService();
        $result = $service->handleCallback('wechat', $data);

        return $result['code'] === 0 ? json(['code' => 0, 'msg' => 'success']) : json(['code' => 1, 'msg' => 'fail']);
    }

    /**
     * 查询支付状态
     */
    public function status(int $id)
    {
        $tradeService = new \app\common\service\plugin\PluginTradeService();
        $order = $tradeService->getOrderById($id);

        if (!$order) {
            return $this->error('订单不存在');
        }

        return $this->success('ok', [
            'order_id'   => $order['id'],
            'order_no'   => $order['order_no'],
            'pay_status' => $order['pay_status'],
            'pay_type'   => $order['pay_type'],
            'price'      => $order['price'],
        ]);
    }
}
