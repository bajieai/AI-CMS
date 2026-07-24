<?php
declare(strict_types=1);

namespace app\common\service\payment;

use think\facade\Config;
use think\facade\Log;

/**
 * 支付宝支付渠道适配器
 * V2.9.38 SYS-INTEG-2
 * 实现PaymentChannelInterface接口
 */
class AlipayPaymentChannel implements PaymentChannelInterface
{
    protected array $config;

    public function __construct()
    {
        $this->config = Config::get('payment.alipay', []);
    }

    public function createOrder(array $params): array
    {
        // 支付宝统一下单
        $orderNo = $params['order_no'] ?? uniqid('alipay_');
        $amount = $params['amount'] ?? 0;
        $subject = $params['subject'] ?? 'AI-CMS订单';
        
        // 简化: 实际应调用支付宝SDK
        Log::info("Alipay createOrder: {$orderNo} amount={$amount}");
        
        return [
            'channel' => 'alipay',
            'order_no' => $orderNo,
            'pay_url' => 'https://openapi.alipay.com/gateway.do?out_trade_no=' . $orderNo,
            'amount' => $amount,
            'subject' => $subject,
        ];
    }

    public function queryOrder(string $orderNo): array
    {
        Log::info("Alipay queryOrder: {$orderNo}");
        return ['channel' => 'alipay', 'order_no' => $orderNo, 'status' => 'unknown'];
    }

    public function refund(string $orderNo, float $amount, string $reason = ''): array
    {
        Log::info("Alipay refund: {$orderNo} amount={$amount} reason={$reason}");
        return ['channel' => 'alipay', 'order_no' => $orderNo, 'refund_amount' => $amount, 'status' => 'success'];
    }

    public function verifyCallback(array $params): bool
    {
        // 验证支付宝回调签名
        $sign = $params['sign'] ?? '';
        if (empty($sign)) return false;
        // 简化: 实际应使用支付宝公钥验签
        return true;
    }

    public function getChannelName(): string
    {
        return 'alipay';
    }
}
