<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateInvoice;
use app\common\model\TemplateOrder;

/**
 * 模板发票服务 — V2.9.28 M-1
 */
class TemplateInvoiceService
{
    /**
     * 申请发票
     */
    public function apply(int $orderId, int $userId, array $data): array
    {
        $order = TemplateOrder::find($orderId);
        if (!$order) {
            return ['success' => false, 'message' => '订单不存在'];
        }
        if ($order->member_id != $userId) {
            return ['success' => false, 'message' => '无权操作此订单'];
        }
        if ($order->status != TemplateOrder::STATUS_PAID) {
            return ['success' => false, 'message' => '订单未支付，无法开具发票'];
        }

        // 检查是否已申请
        $existing = TemplateInvoice::where('order_id', $orderId)
            ->where('status', '<>', TemplateInvoice::STATUS_REJECTED)
            ->find();
        if ($existing) {
            return ['success' => false, 'message' => '该订单已申请发票'];
        }

        $invoice = TemplateInvoice::create([
            'order_id' => $orderId,
            'user_id' => $userId,
            'title' => $data['title'] ?? '',
            'tax_no' => $data['tax_no'] ?? '',
            'amount' => $order->pay_amount > 0 ? $order->pay_amount : $order->amount,
            'email' => $data['email'] ?? '',
            'status' => TemplateInvoice::STATUS_PENDING,
        ]);

        return ['success' => true, 'message' => '发票申请已提交', 'invoice_id' => $invoice->id];
    }

    /**
     * 开具发票（管理员）
     */
    public function issue(int $invoiceId, string $invoiceNo, string $invoiceFile): array
    {
        $invoice = TemplateInvoice::find($invoiceId);
        if (!$invoice) {
            return ['success' => false, 'message' => '发票记录不存在'];
        }
        if ($invoice->status != TemplateInvoice::STATUS_PENDING) {
            return ['success' => false, 'message' => '发票状态不支持开具'];
        }

        $invoice->status = TemplateInvoice::STATUS_ISSUED;
        $invoice->invoice_no = $invoiceNo;
        $invoice->invoice_file = $invoiceFile;
        $invoice->save();

        return ['success' => true, 'message' => '发票已开具'];
    }

    /**
     * 拒绝开票
     */
    public function reject(int $invoiceId, string $reason = ''): array
    {
        $invoice = TemplateInvoice::find($invoiceId);
        if (!$invoice) {
            return ['success' => false, 'message' => '发票记录不存在'];
        }

        $invoice->status = TemplateInvoice::STATUS_REJECTED;
        $invoice->invoice_no = $reason;
        $invoice->save();

        return ['success' => true, 'message' => '已拒绝开票'];
    }

    /**
     * 获取发票列表
     */
    public function getList(array $params = [], int $page = 1, int $limit = 20): array
    {
        $query = TemplateInvoice::with('order');
        if (!empty($params['status'])) {
            $query->where('status', (int)$params['status']);
        }
        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }
}
