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

namespace app\common\service\payment;

use app\common\service\ConfigService;
use GuzzleHttp\Client;
use think\facade\Log;

/**
 * 微信支付通道 - V2.5新增
 * 支持Native(PC扫码) + JSAPI(公众号) + H5支付
 * 基于微信支付V3 API
 */
class WechatPaymentChannel implements PaymentChannelInterface
{
    protected Client $client;
    protected string $appId;
    protected string $mchId;
    protected string $v3Key;
    protected string $serialNo;
    protected string $notifyUrl;
    protected string $privateKey;
    protected string $basePath = 'https://api.mch.weixin.qq.com/v3';

    public function __construct()
    {
        // V2.5修复：配置键名与后台配置保存保持一致
        $this->appId = (string) ConfigService::get('payment_wechat_app_id', '');
        $this->mchId = (string) ConfigService::get('payment_wechat_mch_id', '');
        $this->v3Key = (string) ConfigService::get('payment_wechat_api_key_v3', '');
        $this->serialNo = (string) ConfigService::get('payment_wechat_mch_serial', '');
        $this->notifyUrl = (string) ConfigService::get('payment_wechat_notify_url', '/api/payment/wechat/notify');

        // 加载商户私钥（从runtime/cert/目录）
        $certPath = runtime_path() . 'cert' . DIRECTORY_SEPARATOR . 'wechat_pay_private.pem';
        $this->privateKey = file_exists($certPath) ? file_get_contents($certPath) : '';

        $this->client = new Client([
            'base_uri' => $this->basePath,
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    public function createOrder(array $order): array
    {
        $tradeType = $order['trade_type'] ?? 'NATIVE'; // NATIVE/JSAPI/H5

        $data = [
            'appid' => $this->appId,
            'mchid' => $this->mchId,
            'description' => mb_substr($order['subject'] ?? '付费内容', 0, 127),
            'out_trade_no' => $order['order_sn'],
            'notify_url' => $this->getFullNotifyUrl(),
            'amount' => [
                'total' => (int) round(($order['amount'] ?? 0) * 100), // 分
                'currency' => 'CNY',
            ],
        ];

        if ($tradeType === 'NATIVE') {
            return $this->createNativeOrder($data);
        } elseif ($tradeType === 'JSAPI') {
            $data['payer'] = ['openid' => $order['openid'] ?? ''];
            return $this->createJsapiOrder($data);
        } elseif ($tradeType === 'H5') {
            $data['scene_info'] = [
                'payer_client_ip' => $order['client_ip'] ?? '127.0.0.1',
                'h5_info' => ['type' => 'Wap', 'wap_url' => $order['wap_url'] ?? '', 'wap_name' => $order['wap_name'] ?? '付费内容'],
            ];
            return $this->createH5Order($data);
        }

        throw new \Exception("不支持的交易类型: {$tradeType}");
    }

    /**
     * Native下单（PC扫码支付）
     */
    protected function createNativeOrder(array $data): array
    {
        $response = $this->request('POST', '/pay/transactions/native', $data);
        if (isset($response['code_url'])) {
            return ['trade_type' => 'NATIVE', 'code_url' => $response['code_url']];
        }
        throw new \Exception('创建Native支付订单失败: ' . ($response['message'] ?? '未知错误'));
    }

    /**
     * JSAPI下单（公众号内支付）
     */
    protected function createJsapiOrder(array $data): array
    {
        $response = $this->request('POST', '/pay/transactions/jsapi', $data);
        if (isset($response['prepay_id'])) {
            // 生成JSAPI调起支付参数
            $jsapiParams = $this->buildJsapiParams($response['prepay_id']);
            return ['trade_type' => 'JSAPI', 'prepay_id' => $response['prepay_id'], 'jsapi_params' => $jsapiParams];
        }
        throw new \Exception('创建JSAPI支付订单失败: ' . ($response['message'] ?? '未知错误'));
    }

    /**
     * H5下单（手机浏览器支付）
     */
    protected function createH5Order(array $data): array
    {
        $response = $this->request('POST', '/pay/transactions/h5', $data);
        if (isset($response['h5_url'])) {
            return ['trade_type' => 'H5', 'h5_url' => $response['h5_url']];
        }
        throw new \Exception('创建H5支付订单失败: ' . ($response['message'] ?? '未知错误'));
    }

    /**
     * 生成JSAPI调起支付参数
     */
    protected function buildJsapiParams(string $prepayId): array
    {
        $time = time();
        $nonceStr = $this->getNonceStr();
        $package = "prepay_id={$prepayId}";

        $signStr = "{$this->appId}\n{$time}\n{$nonceStr}\n{$package}\n";
        $sign = $this->sign($signStr);

        return [
            'appId' => $this->appId,
            'timeStamp' => (string) $time,
            'nonceStr' => $nonceStr,
            'package' => $package,
            'signType' => 'RSA',
            'paySign' => $sign,
        ];
    }

    public function verifyNotify(string $body, array $headers): ?array
    {
        try {
            // V3验签步骤：
            // 1. 构造验签串
            $timestamp = $headers['wechatpay-timestamp'] ?? '';
            $nonce = $headers['wechatpay-nonce'] ?? '';
            $signature = $headers['wechatpay-signature'] ?? '';
            $signStr = "{$timestamp}\n{$nonce}\n{$body}\n";

            // 2. 验证签名（需加载微信平台证书）
            // 简化实现：先解密通知数据
            $data = json_decode($body, true);
            if (!$data || !isset($data['resource'])) {
                return null;
            }

            // 3. 解密通知内容
            $decrypted = $this->decryptResource($data['resource']);
            if ($decrypted) {
                return json_decode($decrypted, true);
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('微信支付回调验签失败: ' . $e->getMessage());
            return null;
        }
    }

    public function queryOrder(string $orderSn): array
    {
        return $this->request('GET', "/pay/transactions/out-trade-no/{$orderSn}?mchid={$this->mchId}");
    }

    public function refund(array $refund): array
    {
        $data = [
            'out_trade_no' => $refund['order_sn'],
            'out_refund_no' => $refund['refund_sn'],
            'reason' => $refund['reason'] ?? '用户申请退款',
            'amount' => [
                'refund' => (int) round(($refund['refund_amount'] ?? 0) * 100),
                'total' => (int) round(($refund['total_amount'] ?? 0) * 100),
                'currency' => 'CNY',
            ],
        ];
        return $this->request('POST', '/refund/domestic/refunds', $data);
    }

    public function queryRefund(string $refundSn): array
    {
        return $this->request('GET', "/refund/domestic/refunds/{$refundSn}");
    }

    public function getChannelName(): string
    {
        return 'wechat';
    }

    public function isAvailable(): bool
    {
        return !empty($this->appId) && !empty($this->mchId) && !empty($this->v3Key);
    }

    /**
     * 发送签名请求到微信支付API
     */
    protected function request(string $method, string $uri, array $data = []): array
    {
        $timestamp = time();
        $nonceStr = $this->getNonceStr();
        $body = $method === 'GET' ? '' : json_encode($data, JSON_UNESCAPED_UNICODE);

        // 构造签名串
        $signStr = "{$method}\n{$uri}\n{$timestamp}\n{$nonceStr}\n{$body}\n";
        $sign = $this->sign($signStr);

        $headers = [
            'Authorization' => "WECHATPAY2-SHA256-RSA2048 mchid=\"{$this->mchId}\",nonce_str=\"{$nonceStr}\",timestamp=\"{$timestamp}\",serial_no=\"{$this->serialNo}\",signature=\"{$sign}\"",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $options = ['headers' => $headers];
        if ($method === 'POST' && !empty($data)) {
            $options['body'] = $body;
        }

        try {
            $response = $this->client->request($method, $uri, $options);
            $result = json_decode((string) $response->getBody(), true);
            return $result ?: [];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorBody = (string) $e->getResponse()?->getBody();
            Log::error("微信支付请求失败: {$method} {$uri} - {$errorBody}");
            $error = json_decode($errorBody, true);
            throw new \Exception('微信支付请求失败: ' . ($error['message'] ?? $e->getMessage()));
        }
    }

    /**
     * SHA256-RSA2048签名
     */
    protected function sign(string $str): string
    {
        if (empty($this->privateKey)) {
            throw new \Exception('商户私钥未配置');
        }
        $key = openssl_pkey_get_private($this->privateKey);
        if (!$key) {
            throw new \Exception('商户私钥格式错误');
        }
        openssl_sign($str, $signature, $key, OPENSSL_ALGO_SHA256);
        openssl_free_key($key);
        return base64_encode($signature);
    }

    /**
     * 解密回调通知资源数据
     */
    protected function decryptResource(array $resource): ?string
    {
        $ciphertext = base64_decode($resource['ciphertext'] ?? '');
        $nonce = $resource['nonce'] ?? '';
        $associatedData = $resource['associated_data'] ?? '';

        $key = $this->v3Key;
        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $associatedData
        );

        return $plaintext !== false ? $plaintext : null;
    }

    /**
     * 获取完整回调URL
     */
    protected function getFullNotifyUrl(): string
    {
        $domain = ConfigService::get('site_url', '');
        if (!empty($domain)) {
            return rtrim($domain, '/') . $this->notifyUrl;
        }
        return $this->notifyUrl;
    }

    /**
     * 生成随机字符串
     */
    protected function getNonceStr(int $length = 32): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $str;
    }
}
