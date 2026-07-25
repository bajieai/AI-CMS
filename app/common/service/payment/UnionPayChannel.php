<?php
declare(strict_types=1);

namespace app\common\service\payment;

use think\facade\Config;
use think\facade\Log;

/**
 * 银联支付渠道适配器
 * V2.9.38 SYS-INTEG-2
 */
class UnionPayChannel implements PaymentChannelInterface
{
    protected array $config;

    public function __construct()
    {
        $this->config = Config::get('payment.unionpay', []);
    }

    public function createOrder(array $params): array
    {
        $orderNo = $params['order_no'] ?? uniqid('unionpay_');
        $amount = $params['amount'] ?? 0;
        Log::info("UnionPay createOrder: {$orderNo} amount={$amount}");
        return [
            'channel' => 'unionpay',
            'order_no' => $orderNo,
            'pay_url' => 'https://gateway.95516.com/gateway/api/frontTransReq.do',
            'amount' => $amount,
        ];
    }

    public function queryOrder(string $orderNo): array
    {
        return ['channel' => 'unionpay', 'order_no' => $orderNo, 'status' => 'unknown'];
    }

    public function refund(string $orderNo, float $amount, string $reason = ''): array
    {
        return ['channel' => 'unionpay', 'order_no' => $orderNo, 'refund_amount' => $amount, 'status' => 'success'];
    }

    public function verifyCallback(array $params): bool
    {
        $sign = $params['signature'] ?? '';
        return !empty($sign);
    }

    public function getChannelName(): string
    {
        return 'unionpay';
    }
}
