<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Order;
use think\facade\Log;

/**
 * V2.9.4 支付回调通知分发服务
 */
class PaymentNotifyService
{
    /**
     * 根据订单来源分发通知
     */
    public static function dispatch(Order $order): void
    {
        try {
            switch ($order->source) {
                case 'plugin':
                    self::notifyPluginPurchase($order);
                    break;
                case 'template':
                    self::notifyTemplatePurchase($order);
                    break;
                case 'member':
                    self::notifyMemberRecharge($order);
                    break;
                case 'content':
                    self::notifyContentPurchase($order);
                    break;
                default:
                    Log::info("[PaymentNotify] 未知订单来源: {$order->source}");
            }
        } catch (\Throwable $e) {
            Log::error("[PaymentNotify] 通知分发失败: " . $e->getMessage());
        }
    }

    /**
     * 插件购买通知
     */
    protected static function notifyPluginPurchase(Order $order): void
    {
        Log::info("[PaymentNotify] 插件购买成功: user={$order->user_id} plugin={$order->source_id} order={$order->order_no}");
        // TODO: 触发插件安装/许可证发放
    }

    /**
     * 模板购买通知
     */
    protected static function notifyTemplatePurchase(Order $order): void
    {
        Log::info("[PaymentNotify] 模板购买成功: user={$order->user_id} template={$order->source_id} order={$order->order_no}");
    }

    /**
     * 会员充值通知
     */
    protected static function notifyMemberRecharge(Order $order): void
    {
        Log::info("[PaymentNotify] 会员充值成功: user={$order->user_id} level={$order->source_id} order={$order->order_no}");
        // TODO: 更新会员等级
    }

    /**
     * 内容购买通知
     */
    protected static function notifyContentPurchase(Order $order): void
    {
        Log::info("[PaymentNotify] 内容购买成功: user={$order->user_id} content={$order->source_id} order={$order->order_no}");
        // TODO: 记录用户已购买内容
    }
}
