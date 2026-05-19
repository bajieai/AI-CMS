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

/**
 * 支付通道接口 - V2.5新增
 * 策略模式：不同支付方式实现此接口
 */
interface PaymentChannelInterface
{
    /**
     * 创建支付订单（统一下单）
     * @param array $order 订单信息 [order_sn, amount, subject, description, notify_url, ...]
     * @return array 支付参数 [code_url/jsapi_params/h5_url, ...]
     */
    public function createOrder(array $order): array;

    /**
     * 验证支付回调签名
     * @param string $body 原始请求体
     * @param array $headers 请求头
     * @return array|null 验签通过返回解密后的通知数据，失败返回null
     */
    public function verifyNotify(string $body, array $headers): ?array;

    /**
     * 查询订单状态
     * @param string $orderSn 商户订单号
     * @return array [trade_state, transaction_id, ...]
     */
    public function queryOrder(string $orderSn): array;

    /**
     * 申请退款
     * @param array $refund [order_sn, refund_sn, total_amount, refund_amount, reason]
     * @return array 退款结果
     */
    public function refund(array $refund): array;

    /**
     * 查询退款状态
     * @param string $refundSn 退款单号
     * @return array 退款状态
     */
    public function queryRefund(string $refundSn): array;

    /**
     * 获取通道名称
     */
    public function getChannelName(): string;

    /**
     * 检查通道是否已配置可用
     */
    public function isAvailable(): bool;
}
