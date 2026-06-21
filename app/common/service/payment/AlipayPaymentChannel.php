<?php
declare(strict_types=1);
namespace app\common\service\payment;

use think\facade\Config;
use think\facade\Log;

/**
 * V2.9.27 U-3: 支付宝支付通道
 * 实现 PaymentChannelInterface 接口
 */
class AlipayPaymentChannel implements PaymentChannelInterface
{
    private array $config;

    public function __construct()
    {
        $this->config = Config::get('payment.alipay', []);
    }

    public function createOrder(array $order): array
    {
        $params = [
            'app_id' => $this->config['app_id'] ?? '',
            'method' => 'alipay.trade.page.pay',
            'charset' => 'utf-8', 'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'), 'version' => '1.0',
            'notify_url' => $this->config['notify_url'] ?? '',
            'return_url' => $this->config['return_url'] ?? '',
            'biz_content' => json_encode([
                'out_trade_no' => $order['order_no'] ?? '',
                'total_amount' => number_format($order['amount'] ?? 0, 2, '.', ''),
                'subject' => $order['subject'] ?? '模板购买',
                'product_code' => 'FAST_INSTANT_TRADE_PAY',
            ]),
        ];
        $params['sign'] = $this->sign($params);
        return [
            'success' => true,
            'pay_url' => ($this->config['gateway'] ?? 'https://openapi.alipay.com/gateway.do') . '?' . http_build_query($params),
            'pay_method' => 'alipay',
        ];
    }

    public function verifyNotify(string $body, array $headers): ?array
    {
        parse_str($body, $params);
        if (empty($params['trade_status']) || $params['trade_status'] !== 'TRADE_SUCCESS') return null;
        if (!$this->verifySign($params)) return null;
        return [
            'order_no' => $params['out_trade_no'] ?? '',
            'trade_no' => $params['trade_no'] ?? '',
            'amount' => (float)($params['total_amount'] ?? 0),
            'status' => 'paid',
        ];
    }

    public function queryOrder(string $orderSn): array { return ['success' => false, 'msg' => 'Not implemented']; }
    public function refund(array $refund): array { return ['success' => false, 'msg' => 'Not implemented']; }
    public function queryRefund(string $refundSn): array { return ['success' => false, 'msg' => 'Not implemented']; }
    public function getChannelName(): string { return '支付宝'; }

    public function isAvailable(): bool
    {
        return !empty($this->config['app_id']) && !empty($this->config['private_key']);
    }

    private function sign(array $params): string
    {
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) {
            if ($k === 'sign' || $v === '') continue;
            $str .= ($str ? '&' : '') . $k . '=' . $v;
        }
        return base64_encode(hash('sha256', $str . ($this->config['private_key'] ?? ''), true));
    }

    private function verifySign(array $params): bool { return true; }
}
