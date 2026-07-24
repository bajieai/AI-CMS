<?php
declare(strict_types=1);

namespace app\api\controller\h5;

use think\facade\Db;
use think\response\Json;

/**
 * H5支付控制器
 */
class PaymentController extends H5BaseController
{
    /**
     * 创建支付订单
     */
    public function create(): Json
    {
        if (!$this->memberId) {
            return $this->error('请先登录', 401);
        }
        $type = $this->request->param('type', 'member'); // member/content/template/points/donation
        $targetId = (int)$this->request->param('target_id', 0);
        $amount = (float)$this->request->param('amount', 0);
        $paymentMethod = $this->request->param('payment_method', 'wechat'); // wechat/alipay

        if ($amount <= 0) {
            return $this->error('支付金额必须大于0');
        }

        $orderNo = 'H5' . date('YmdHis') . mt_rand(1000, 9999);
        $orderId = Db::name('order')->insertGetId([
            'order_no' => $orderNo,
            'member_id' => $this->memberId,
            'type' => $type,
            'target_id' => $targetId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'status' => 'pending',
            'create_time' => date('Y-m-d H:i:s'),
        ]);

        // 调用支付渠道（复用V2.9.38支付扩展）
        try {
            $paymentChannel = $paymentMethod === 'alipay'
                ? new \app\common\service\payment\AlipayPaymentChannel()
                : null; // 微信H5支付

            if ($paymentChannel) {
                $result = $paymentChannel->createOrder([
                    'order_no' => $orderNo,
                    'amount' => $amount,
                    'subject' => 'AI-CMS订单-' . $orderNo,
                    'return_url' => $this->request->domain() . '/h5/payment/callback',
                ]);
                return $this->success(['order_id' => $orderId, 'order_no' => $orderNo, 'pay_url' => $result['pay_url'] ?? '']);
            }

            // 微信H5支付（简化实现）
            return $this->success(['order_id' => $orderId, 'order_no' => $orderNo, 'pay_type' => 'wechat_h5']);
        } catch (\Exception $e) {
            return $this->error('支付创建失败: ' . $e->getMessage());
        }
    }

    /**
     * 支付回调
     */
    public function callback(): Json
    {
        $orderNo = $this->request->param('order_no', '');
        $status = $this->request->param('status', '');
        if (!$orderNo) {
            return $this->error('参数错误');
        }
        $order = Db::name('order')->where('order_no', $orderNo)->find();
        if (!$order) {
            return $this->error('订单不存在');
        }
        if ($order['status'] === 'pending') {
            Db::name('order')->where('order_no', $orderNo)->update([
                'status' => 'paid',
                'pay_time' => date('Y-m-d H:i:s'),
            ]);
            // 记录支付日志
            Db::name('payment_log')->insert([
                'order_no' => $orderNo,
                'member_id' => $order['member_id'],
                'amount' => $order['amount'],
                'pay_channel' => $order['payment_method'],
                'status' => 1,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
        }
        return $this->success(['order_no' => $orderNo, 'status' => 'paid']);
    }
}
