<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Order;
use think\facade\Config;
use think\facade\Log;

/**
 * V2.9.4 统一支付服务
 * 支付抽象层：统一微信支付+支付宝支付
 */
class PaymentService
{
    /**
     * 创建支付订单
     */
    public static function createPayment(int $userId, string $source, string $sourceId, float $amount, string $payMethod = 'wechat'): array
    {
        $enabled = Config::get('pay_enabled', 0);
        if (!$enabled) {
            return ['success' => false, 'msg' => '支付功能未启用'];
        }

        try {
            // 创建订单
            $order = Order::createOrder($userId, $source, $sourceId, $amount);

            // 调用支付适配器获取支付参数
            $adapter = self::getAdapter($payMethod);
            if (!$adapter) {
                return ['success' => false, 'msg' => '不支持的支付方式'];
            }

            $payResult = $adapter->createPayment($order);

            return [
                'success' => true,
                'order_no' => $order->order_no,
                'order_id' => $order->id,
                'pay_data' => $payResult,
            ];
        } catch (\Throwable $e) {
            Log::error('[Payment] 创建支付失败: ' . $e->getMessage());
            return ['success' => false, 'msg' => '创建支付失败: ' . $e->getMessage()];
        }
    }

    /**
     * 处理支付回调通知
     */
    public static function handleNotify(string $payMethod, array $params): array
    {
        $adapter = self::getAdapter($payMethod);
        if (!$adapter) {
            return ['success' => false, 'msg' => '不支持的支付方式'];
        }

        try {
            // 验证签名
            $verifyResult = $adapter->verifyNotify($params);
            if (!$verifyResult['success']) {
                Log::warning('[Payment] 回调签名验证失败: ' . json_encode($params));
                return ['success' => false, 'msg' => '签名验证失败'];
            }

            $orderNo = $verifyResult['order_no'] ?? '';
            $tradeNo = $verifyResult['trade_no'] ?? '';
            $amount = $verifyResult['amount'] ?? 0;

            // 查找订单
            $order = Order::where('order_no', $orderNo)->find();
            if (!$order) {
                return ['success' => false, 'msg' => '订单不存在'];
            }

            // 幂等检查：已支付订单不重复处理
            if ($order->status === Order::STATUS_PAID) {
                return ['success' => true, 'msg' => '已处理'];
            }

            // 金额校验
            if (bccomp((string) $amount, (string) $order->amount, 2) !== 0) {
                Log::error("[Payment] 金额不匹配: 回调{$amount} vs 订单{$order->amount} order_no={$orderNo}");
                return ['success' => false, 'msg' => '金额不匹配'];
            }

            // 更新订单状态
            $order->markAsPaid($payMethod, $tradeNo);

            // 分发业务通知
            PaymentNotifyService::dispatch($order);

            return ['success' => true, 'msg' => '支付成功'];
        } catch (\Throwable $e) {
            Log::error('[Payment] 回调处理失败: ' . $e->getMessage());
            return ['success' => false, 'msg' => '回调处理失败'];
        }
    }

    /**
     * 收入统计（供后台收入看板使用）
     */
    public static function getRevenueStats(): array
    {
        $todayStart = strtotime('today');
        $monthStart = strtotime(date('Y-m-01'));

        // 今日收入
        $todayRevenue = Order::where('status', Order::STATUS_PAID)
            ->where('paid_time', '>=', $todayStart)
            ->sum('amount') ?: 0;

        // 今日订单数
        $todayOrders = Order::where('status', Order::STATUS_PAID)
            ->where('paid_time', '>=', $todayStart)
            ->count();

        // 本月收入
        $monthRevenue = Order::where('status', Order::STATUS_PAID)
            ->where('paid_time', '>=', $monthStart)
            ->sum('amount') ?: 0;

        // 本月订单数
        $monthOrders = Order::where('status', Order::STATUS_PAID)
            ->where('paid_time', '>=', $monthStart)
            ->count();

        // 总收入
        $totalRevenue = Order::where('status', Order::STATUS_PAID)
            ->sum('amount') ?: 0;

        // 总订单数
        $totalOrders = Order::where('status', Order::STATUS_PAID)
            ->count();

        return [
            'today_revenue'  => round((float) $todayRevenue, 2),
            'month_revenue'  => round((float) $monthRevenue, 2),
            'total_revenue'  => round((float) $totalRevenue, 2),
            'today_orders'   => (int) $todayOrders,
            'month_orders'   => (int) $monthOrders,
            'total_orders'   => (int) $totalOrders,
        ];
    }

    /**
     * 查询订单支付状态
     */
    public static function queryOrder(string $orderNo): array
    {
        $order = Order::where('order_no', $orderNo)->find();
        if (!$order) {
            return ['success' => false, 'msg' => '订单不存在'];
        }

        return [
            'success' => true,
            'order_no' => $order->order_no,
            'status' => $order->status,
            'status_text' => $order->status_text,
            'amount' => $order->amount,
            'pay_method' => $order->pay_method,
            'paid_time' => $order->paid_time,
        ];
    }

    /**
     * 获取支付适配器
     */
    protected static function getAdapter(string $payMethod): ?object
    {
        switch ($payMethod) {
            case 'wechat':
                $enabled = Config::get('pay_wechat_enabled', 0);
                return $enabled ? new \app\common\adapter\WechatPayAdapter() : null;
            case 'alipay':
                $enabled = Config::get('pay_alipay_enabled', 0);
                if ($enabled) {
                    return new \app\common\adapter\AlipayAdapter();
                }
                // V2.9.27 U-3: 优先使用新的AlipayPaymentChannel
                try {
                    $channel = new \app\common\service\payment\AlipayPaymentChannel();
                    if ($channel->isAvailable()) {
                        return $channel;
                    }
                } catch (\Throwable) {}
                return null;
            default:
                return null;
        }
    }
}
