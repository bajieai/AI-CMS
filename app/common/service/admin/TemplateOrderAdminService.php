<?php
declare(strict_types=1);
namespace app\common\service\admin;

use app\common\model\TemplateOrder;
use app\common\service\template\TemplateOrderService;

class TemplateOrderAdminService
{
    public static function getOrderList(array $params = [], int $pageSize = 20): array
    {
        $query = TemplateOrder::with('template');
        if (!empty($params['status'])) $query->where('status', (int)$params['status']);
        if (!empty($params['keyword'])) $query->where('order_no', 'like', '%' . $params['keyword'] . '%');
        if (!empty($params['start_date'])) $query->where('create_time', '>=', strtotime($params['start_date']));
        if (!empty($params['end_date'])) $query->where('create_time', '<', strtotime($params['end_date']) + 86400);
        return $query->order('id', 'desc')->paginate($pageSize, false, ['page' => $params['page'] ?? 1])->toArray();
    }

    public static function refundOrder(int $orderId, string $reason): array
    {
        return TemplateOrderService::refund($orderId, $reason);
    }

    public static function getStats(): array
    {
        return TemplateOrderService::getRevenueStats();
    }
}
