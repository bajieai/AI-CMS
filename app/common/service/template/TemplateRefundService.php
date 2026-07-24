<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateRefund;
use app\common\model\TemplateOrder;
use app\common\model\TemplateLicense;
use think\facade\Db;
use think\facade\Log;

/**
 * 模板退款服务 — V2.9.28 M-1
 */
class TemplateRefundService
{
    /**
     * 创建退款申请
     */
    public function createRefund(int $orderId, int $userId, string $reason): array
    {
        $order = TemplateOrder::find($orderId);
        if (!$order) {
            return ['success' => false, 'message' => '订单不存在'];
        }
        if ($order->member_id != $userId) {
            return ['success' => false, 'message' => '无权操作此订单'];
        }
        if ($order->status != TemplateOrder::STATUS_PAID) {
            return ['success' => false, 'message' => '订单状态不支持退款'];
        }

        // 检查是否已有退款申请
        $existing = TemplateRefund::where('order_id', $orderId)
            ->where('status', TemplateRefund::STATUS_PENDING)
            ->find();
        if ($existing) {
            return ['success' => false, 'message' => '该订单已有待审核的退款申请'];
        }

        $refund = TemplateRefund::create([
            'order_id' => $orderId,
            'user_id' => $userId,
            'reason' => $reason,
            'amount' => $order->pay_amount > 0 ? $order->pay_amount : $order->amount,
            'status' => TemplateRefund::STATUS_PENDING,
        ]);

        return ['success' => true, 'message' => '退款申请已提交', 'refund_id' => $refund->id];
    }

    /**
     * 审核退款申请（管理员）
     */
    public function approveRefund(int $refundId, int $adminId, string $adminRemark = ''): array
    {
        $refund = TemplateRefund::find($refundId);
        if (!$refund) {
            return ['success' => false, 'message' => '退款记录不存在'];
        }
        if ($refund->status != TemplateRefund::STATUS_PENDING) {
            return ['success' => false, 'message' => '退款申请已处理'];
        }

        Db::startTrans();
        try {
            // 更新退款记录
            $refund->status = TemplateRefund::STATUS_APPROVED;
            $refund->admin_remark = $adminRemark;
            $refund->process_time = time();
            $refund->save();

            // 更新订单状态为已退款
            $order = TemplateOrder::find($refund->order_id);
            if ($order) {
                $order->status = TemplateOrder::STATUS_REFUNDED;
                $order->refund_time = time();
                $order->refund_reason = $refund->reason;
                $order->save();

                // 释放授权
                TemplateLicense::where('order_id', $order->id)
                    ->where('status', 1)
                    ->update(['status' => 0]);
            }

            Db::commit();
            Log::info('[TemplateRefund] 退款审核通过: refund_id=' . $refundId . ', admin=' . $adminId);
            return ['success' => true, 'message' => '退款已通过，授权已释放'];
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('[TemplateRefund] 退款审核失败: ' . $e->getMessage());
            return ['success' => false, 'message' => '操作失败: ' . $e->getMessage()];
        }
    }

    /**
     * 拒绝退款申请
     */
    public function rejectRefund(int $refundId, int $adminId, string $adminRemark): array
    {
        $refund = TemplateRefund::find($refundId);
        if (!$refund) {
            return ['success' => false, 'message' => '退款记录不存在'];
        }
        if ($refund->status != TemplateRefund::STATUS_PENDING) {
            return ['success' => false, 'message' => '退款申请已处理'];
        }

        $refund->status = TemplateRefund::STATUS_REJECTED;
        $refund->admin_remark = $adminRemark;
        $refund->process_time = time();
        $refund->save();

        return ['success' => true, 'message' => '退款已拒绝'];
    }

    /**
     * 获取退款列表
     */
    public function getList(array $params = [], int $page = 1, int $limit = 20): array
    {
        $query = TemplateRefund::with('order');
        if (!empty($params['status'])) {
            $query->where('status', (int)$params['status']);
        }
        if (!empty($params['order_id'])) {
            $query->where('order_id', (int)$params['order_id']);
        }
        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }
}
