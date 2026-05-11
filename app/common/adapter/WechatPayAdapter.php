<?php
declare(strict_types=1);

namespace app\common\adapter;

use app\common\model\Order;
use think\facade\Config;
use think\facade\Log;

/**
 * V2.9.4 微信支付适配器
 * 沙箱模式优先
 */
class WechatPayAdapter
{
    protected array $config;

    public function __construct()
    {
        $this->config = [
            'app_id' => Config::get('pay_wechat_app_id', ''),
            'mch_id' => Config::get('pay_wechat_mch_id', ''),
            'api_key' => Config::get('pay_wechat_api_key', ''),
            'cert_path' => Config::get('pay_wechat_cert_path', ''),
            'key_path' => Config::get('pay_wechat_key_path', ''),
            'notify_url' => Config::get('pay_wechat_notify_url', ''),
            'sandbox' => Config::get('pay_wechat_sandbox', 1),
        ];
    }

    /**
     * 创建支付（返回扫码支付URL或JSAPI参数）
     */
    public function createPayment(Order $order): array
    {
        // 沙箱模式：返回模拟支付参数
        if ($this->config['sandbox']) {
            return [
                'pay_type' => 'sandbox',
                'order_no' => $order->order_no,
                'amount' => $order->amount,
                'qr_url' => 'sandbox://wechat-pay/' . $order->order_no,
                'msg' => '沙箱模式-模拟支付',
            ];
        }

        // 正式环境：构建微信支付请求参数
        if (empty($this->config['app_id']) || empty($this->config['mch_id'])) {
            throw new \Exception('微信支付配置不完整');
        }

        $params = [
            'appid' => $this->config['app_id'],
            'mch_id' => $this->config['mch_id'],
            'nonce_str' => $this->getNonceStr(),
            'body' => 'AI-CMS订单-' . $order->order_no,
            'out_trade_no' => $order->order_no,
            'total_fee' => (int) bcmul((string) $order->amount, '100', 0), // 元转分
            'spbill_create_ip' => request()->ip(),
            'notify_url' => $this->config['notify_url'],
            'trade_type' => 'NATIVE', // 扫码支付
        ];

        $params['sign'] = $this->makeSign($params);

        // TODO: 实际调用微信支付API
        Log::info('[WechatPay] 创建支付: ' . json_encode($params));

        return [
            'pay_type' => 'native',
            'order_no' => $order->order_no,
            'amount' => $order->amount,
            'prepay_id' => 'mock_prepay_id_' . time(),
            'qr_url' => 'weixin://wxpay/bizpayurl?mock=1&order=' . $order->order_no,
        ];
    }

    /**
     * 验证回调签名
     */
    public function verifyNotify(array $params): array
    {
        // 沙箱模式直接通过
        if ($this->config['sandbox']) {
            return [
                'success' => true,
                'order_no' => $params['out_trade_no'] ?? '',
                'trade_no' => $params['transaction_id'] ?? 'sandbox_' . time(),
                'amount' => isset($params['total_fee']) ? $params['total_fee'] / 100 : 0,
            ];
        }

        // 正式环境验证签名
        if (empty($params['sign'])) {
            return ['success' => false, 'msg' => '缺少签名'];
        }

        $sign = $params['sign'];
        unset($params['sign']);
        $calcSign = $this->makeSign($params);

        if ($sign !== $calcSign) {
            return ['success' => false, 'msg' => '签名不匹配'];
        }

        return [
            'success' => true,
            'order_no' => $params['out_trade_no'] ?? '',
            'trade_no' => $params['transaction_id'] ?? '',
            'amount' => isset($params['total_fee']) ? $params['total_fee'] / 100 : 0,
        ];
    }

    /**
     * 生成签名
     */
    protected function makeSign(array $params): string
    {
        ksort($params);
        $string = '';
        foreach ($params as $key => $value) {
            if ($value !== '' && $key !== 'sign') {
                $string .= $key . '=' . $value . '&';
            }
        }
        $string .= 'key=' . $this->config['api_key'];
        return strtoupper(md5($string));
    }

    /**
     * 生成随机字符串
     */
    protected function getNonceStr(int $length = 32): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }
}
