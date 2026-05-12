<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Order;
use app\common\model\PaidOrder;
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
     * V2.9.5: VIP续费桥接
     */
    protected static function notifyMemberRecharge(Order $order): void
    {
        Log::info("[PaymentNotify] 会员充值成功: user={$order->user_id} level={$order->source_id} order={$order->order_no}");

        try {
            $memberId = $order->user_id;
            $levelId = (int) $order->source_id;

            if ($levelId > 0) {
                $member = \app\common\model\Member::find($memberId);
                $level = \app\common\model\MemberLevel::find($levelId);
                if ($member && $level) {
                    $oldLevelId = $member->level_id;
                    $member->level_id = $levelId;
                    // VIP有效期延长30天（可根据等级配置调整）
                    $extendDays = (int) ($level->vip_expire_days ?? 30);
                    $member->vip_expire_time = max($member->vip_expire_time, time()) + $extendDays * 86400;
                    $member->save();

                    Log::info("[PaymentNotify] 会员等级已更新: member_id={$memberId} level_id={$levelId} vip_expire=" . date('Y-m-d H:i:s', $member->vip_expire_time));

                    // 记录等级变更日志
                    if ($oldLevelId != $levelId) {
                        try {
                            \app\common\model\MemberDowngradeLog::create([
                                'user_id' => $memberId,
                                'from_level' => $oldLevelId,
                                'to_level' => $levelId,
                                'action' => 'auto_upgrade',
                                'trigger_condition' => 'vip_renew_payment',
                                'notified' => 1,
                            ]);
                        } catch (\Throwable $e) {
                            Log::warning("[PaymentNotify] VIP续费日志写入失败: " . $e->getMessage());
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error("[PaymentNotify] 会员充值业务处理失败: " . $e->getMessage());
        }
    }

    /**
     * 内容购买通知
     * V2.9.5: 桥接PaidService完成付费内容业务闭环
     */
    protected static function notifyContentPurchase(Order $order): void
    {
        Log::info("[PaymentNotify] 内容购买成功: user={$order->user_id} content={$order->source_id} order={$order->order_no}");

        try {
            // 查找关联的PaidOrder并完成业务
            $paidOrder = PaidOrder::where('payment_order_no', $order->order_no)
                ->where('status', 0)
                ->find();
            if ($paidOrder) {
                PaidService::completePaymentByOrderNo($order->order_no, $order->user_id);
                Log::info("[PaymentNotify] PaidOrder已同步完成: paid_order_sn={$paidOrder->order_sn}");
            } else {
                // 兜底：直接按content_id+user_id创建购买记录（兼容纯PaymentService订单）
                $contentId = (int) $order->source_id;
                if ($contentId > 0) {
                    PaidOrder::create([
                        'order_sn'   => 'P' . date('YmdHis') . str_pad((string) $order->user_id, 6, '0', STR_PAD_LEFT) . rand(100, 999),
                        'member_id'  => $order->user_id,
                        'content_id' => $contentId,
                        'type'       => 'content',
                        'price'      => $order->amount,
                        'pay_type'   => 'money',
                        'status'     => 1,
                        'paid_at'    => time(),
                        'payment_order_no' => $order->order_no,
                    ]);
                    Log::info("[PaymentNotify] 已兜底创建PaidOrder记录: content_id={$contentId}");
                }
            }
        } catch (\Throwable $e) {
            Log::error("[PaymentNotify] 内容购买业务同步失败: " . $e->getMessage());
        }
    }
}
