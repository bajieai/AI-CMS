<?php
declare(strict_types=1);
namespace app\common\service\template;

use app\common\model\TemplateOrder;
use app\common\model\TemplateLicense;
use app\common\model\TemplateCart;
use app\common\model\TemplateStore;
use app\common\service\admin\TemplatePricingService;
use app\common\service\PaymentService;
use think\facade\Log;

class TemplateOrderService
{
    public static function createOrder(int $templateId, int $memberId, string $payMethod = 'wechat', ?string $couponCode = null): array
    {
        $template = TemplateStore::find($templateId);
        if (!$template) return ['success' => false, 'msg' => '模板不存在'];
        $priceInfo = TemplatePricingService::calculateFinalPrice($templateId, $couponCode, $memberId);
        $originalAmount = $priceInfo['current'] ?? 0;
        $discountAmount = $priceInfo['discount'] ?? 0;
        $payAmount = $priceInfo['final'] ?? 0;
        if ($payAmount <= 0) return ['success' => false, 'msg' => '免费模板无需购买，请直接安装'];

        $orderNo = TemplateOrder::generateOrderNo();
        $order = TemplateOrder::create([
            'order_no' => $orderNo, 'store_id' => $templateId, 'member_id' => $memberId,
            'amount' => $originalAmount, 'original_amount' => $originalAmount,
            'discount_amount' => $discountAmount, 'pay_amount' => $payAmount,
            'coupon_code' => $couponCode ?? '', 'promotion_id' => $priceInfo['promotion']['id'] ?? 0,
            'pay_method' => $payMethod, 'status' => 1,
        ]);

        try {
            $payResult = PaymentService::createPayment($memberId, 'template', (string)$order->id, $payAmount, $payMethod);
            if (!$payResult['success']) {
                $order->status = 4; $order->save();
                return ['success' => false, 'msg' => '支付创建失败: ' . ($payResult['msg'] ?? '')];
            }
            return ['success' => true, 'order_no' => $orderNo, 'order_id' => $order->id, 'pay_data' => $payResult['pay_data'] ?? []];
        } catch (\Throwable $e) {
            Log::error('创建支付失败: ' . $e->getMessage());
            $order->status = 4; $order->save();
            return ['success' => false, 'msg' => '支付创建异常: ' . $e->getMessage()];
        }
    }

    public static function getMemberOrders(int $memberId, int $page = 1, int $pageSize = 10): array
    {
        return TemplateOrder::where('member_id', $memberId)->order('id', 'desc')
            ->paginate($pageSize, false, ['page' => $page])->toArray();
    }

    public static function getOrderDetail(int $orderId): ?array
    {
        $order = TemplateOrder::find($orderId);
        return $order ? $order->toArray() : null;
    }

    public static function refund(int $orderId, string $reason): array
    {
        $order = TemplateOrder::find($orderId);
        if (!$order) return ['success' => false, 'msg' => '订单不存在'];
        if ($order->status != 2) return ['success' => false, 'msg' => '订单状态不支持退款'];
        $order->status = 3; $order->refund_time = time(); $order->refund_reason = $reason; $order->save();
        if ($order->license_id > 0) {
            TemplateLicense::where('id', $order->license_id)->update(['status' => TemplateLicense::STATUS_REVOKED]);
        }
        return ['success' => true, 'msg' => '退款成功'];
    }

    public static function getRevenueStats(): array
    {
        $today = strtotime(date('Y-m-d')); $monthStart = strtotime(date('Y-m-01'));
        return [
            'today_revenue' => (float)TemplateOrder::where('status', 2)->where('pay_time', '>=', $today)->sum('pay_amount'),
            'month_revenue' => (float)TemplateOrder::where('status', 2)->where('pay_time', '>=', $monthStart)->sum('pay_amount'),
            'total_revenue' => (float)TemplateOrder::where('status', 2)->sum('pay_amount'),
            'today_orders' => TemplateOrder::where('status', 2)->where('pay_time', '>=', $today)->count(),
            'total_orders' => TemplateOrder::where('status', 2)->count(),
        ];
    }
}
