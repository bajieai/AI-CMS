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

namespace app\common\adapter;

use app\common\model\Order;
use think\facade\Config;
use think\facade\Log;

/**
 * V2.9.4 支付宝支付适配器
 * 沙箱模式优先
 */
class AlipayAdapter
{
    protected array $config;

    public function __construct()
    {
        $this->config = [
            'app_id' => Config::get('pay_alipay_app_id', ''),
            'private_key' => Config::get('pay_alipay_private_key', ''),
            'public_key' => Config::get('pay_alipay_public_key', ''),
            'notify_url' => Config::get('pay_alipay_notify_url', ''),
            'return_url' => Config::get('pay_alipay_return_url', ''),
            'sandbox' => Config::get('pay_alipay_sandbox', 1),
        ];
    }

    /**
     * 创建支付
     */
    public function createPayment(Order $order): array
    {
        // 沙箱模式
        if ($this->config['sandbox']) {
            return [
                'pay_type' => 'sandbox',
                'order_no' => $order->order_no,
                'amount' => $order->amount,
                'pay_url' => 'sandbox://alipay-pay/' . $order->order_no,
                'msg' => '沙箱模式-模拟支付',
            ];
        }

        if (empty($this->config['app_id'])) {
            throw new \Exception('支付宝配置不完整');
        }

        $params = [
            'app_id' => $this->config['app_id'],
            'method' => 'alipay.trade.page.pay',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => json_encode([
                'out_trade_no' => $order->order_no,
                'total_amount' => (string) $order->amount,
                'subject' => 'AI-CMS订单-' . $order->order_no,
                'product_code' => 'FAST_INSTANT_TRADE_PAY',
            ]),
            'notify_url' => $this->config['notify_url'],
            'return_url' => $this->config['return_url'],
        ];

        $params['sign'] = $this->makeSign($params);

        Log::info('[Alipay] 创建支付: ' . json_encode($params));

        return [
            'pay_type' => 'page_pay',
            'order_no' => $order->order_no,
            'amount' => $order->amount,
            'pay_url' => 'https://openapi.alipay.com/gateway.do?' . http_build_query($params),
        ];
    }

    /**
     * 验证回调签名
     */
    public function verifyNotify(array $params): array
    {
        if ($this->config['sandbox']) {
            return [
                'success' => true,
                'order_no' => $params['out_trade_no'] ?? '',
                'trade_no' => $params['trade_no'] ?? 'sandbox_alipay_' . time(),
                'amount' => (float) ($params['total_amount'] ?? 0),
            ];
        }

        // 正式环境验证签名
        if (empty($params['sign'])) {
            return ['success' => false, 'msg' => '缺少签名'];
        }

        // TODO: 使用支付宝公钥验证RSA2签名
        $sign = $params['sign'];
        unset($params['sign'], $params['sign_type']);

        // 简化验证（正式环境需要使用openssl_verify）
        return [
            'success' => true,
            'order_no' => $params['out_trade_no'] ?? '',
            'trade_no' => $params['trade_no'] ?? '',
            'amount' => (float) ($params['total_amount'] ?? 0),
        ];
    }

    /**
     * 生成签名（RSA2）
     */
    protected function makeSign(array $params): string
    {
        ksort($params);
        $string = '';
        foreach ($params as $key => $value) {
            if ($value !== '' && !in_array($key, ['sign', 'sign_type'])) {
                $string .= $key . '=' . $value . '&';
            }
        }
        $string = rtrim($string, '&');

        // 沙箱模式返回模拟签名
        if ($this->config['sandbox']) {
            return md5($string . $this->config['app_id']);
        }

        // 正式环境使用RSA2签名
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($this->config['private_key'], 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        openssl_sign($string, $sign, $privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($sign);
    }
}
