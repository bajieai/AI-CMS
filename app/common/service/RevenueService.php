<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\model\TemplateOrder;
use app\common\model\TemplateSettlement;
use think\facade\Cache;

/**
 * V2.9.25 N-3: 营收统计服务
 */
class RevenueService
{
    private string $cacheTag = 'template_revenue';

    /**
     * 营收概览
     */
    public function getOverview(string $startDate = '', string $endDate = ''): array
    {
        $start = $startDate ?: date('Y-m-d', strtotime('-30 days'));
        $end = $endDate ?: date('Y-m-d');
        $startTs = strtotime($start . ' 00:00:00');
        $endTs = strtotime($end . ' 23:59:59');

        $cacheKey = 'revenue_overview_' . md5($start . $end);
        return Cache::tag($this->cacheTag)->remember($cacheKey, function () use ($startTs, $endTs, $start, $end) {
            // 总收入
            $totalRevenue = TemplateOrder::where('pay_status', TemplateOrder::STATUS_PAID)
                ->whereBetweenTime('pay_time', $startTs, $endTs)
                ->sum('pay_amount');

            // 订单数
            $orderCount = TemplateOrder::where('pay_status', TemplateOrder::STATUS_PAID)
                ->whereBetweenTime('pay_time', $startTs, $endTs)
                ->count();

            // 退款金额
            $refundAmount = TemplateOrder::where('pay_status', TemplateOrder::STATUS_REFUNDED)
                ->whereBetweenTime('refund_time', $startTs, $endTs)
                ->sum('pay_amount');

            // 退款订单数
            $refundCount = TemplateOrder::where('pay_status', TemplateOrder::STATUS_REFUNDED)
                ->whereBetweenTime('refund_time', $startTs, $endTs)
                ->count();

            // 待支付订单
            $pendingCount = TemplateOrder::where('pay_status', TemplateOrder::STATUS_PENDING)
                ->whereBetweenTime('create_time', $startTs, $endTs)
                ->count();

            // 客单价
            $avgOrderValue = $orderCount > 0 ? round((float)$totalRevenue / $orderCount, 2) : 0;

            // 收入趋势（按日）
            $trend = TemplateOrder::where('pay_status', TemplateOrder::STATUS_PAID)
                ->whereBetweenTime('pay_time', $startTs, $endTs)
                ->field("DATE_FORMAT(FROM_UNIXTIME(pay_time), '%Y-%m-%d') as date, SUM(pay_amount) as revenue, COUNT(*) as orders")
                ->group('date')
                ->order('date', 'asc')
                ->select()
                ->toArray();

            // 模板收入排行
            $topTemplates = TemplateOrder::where('pay_status', TemplateOrder::STATUS_PAID)
                ->whereBetweenTime('pay_time', $startTs, $endTs)
                ->field('template_id, template_name, SUM(pay_amount) as total_revenue, COUNT(*) as order_count')
                ->group('template_id')
                ->order('total_revenue', 'desc')
                ->limit(10)
                ->select()
                ->toArray();

            // 支付方式分布
            $payMethodDist = TemplateOrder::where('pay_status', TemplateOrder::STATUS_PAID)
                ->whereBetweenTime('pay_time', $startTs, $endTs)
                ->field('pay_method, COUNT(*) as count, SUM(pay_amount) as amount')
                ->group('pay_method')
                ->select()
                ->toArray();

            return [
                'date_range' => ['start' => $start, 'end' => $end],
                'total_revenue' => round((float)$totalRevenue, 2),
                'order_count' => $orderCount,
                'refund_amount' => round((float)$refundAmount, 2),
                'refund_count' => $refundCount,
                'pending_count' => $pendingCount,
                'avg_order_value' => $avgOrderValue,
                'revenue_trend' => $trend,
                'top_templates' => $topTemplates,
                'pay_method_distribution' => $payMethodDist,
            ];
        }, 600);
    }

    /**
     * 生成结算批次
     */
    public function createSettlement(string $periodStart, string $periodEnd, float $commissionRate = 0.1): array
    {
        $startTs = strtotime($periodStart . ' 00:00:00');
        $endTs = strtotime($periodEnd . ' 23:59:59');

        // 查找未结算的已支付订单
        $orders = TemplateOrder::where('pay_status', TemplateOrder::STATUS_PAID)
            ->where('settlement_status', TemplateOrder::SETTLEMENT_UNSETTLED)
            ->whereBetweenTime('pay_time', $startTs, $endTs)
            ->select();

        if ($orders->isEmpty()) {
            return ['code' => 1, 'msg' => '没有可结算的订单'];
        }

        $totalAmount = 0;
        foreach ($orders as $order) {
            $totalAmount += (float)$order->pay_amount;
        }

        $commission = round($totalAmount * $commissionRate, 2);
        $settlementAmount = round($totalAmount - $commission, 2);

        $settlement = TemplateSettlement::create([
            'batch_no' => TemplateSettlement::generateBatchNo(),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_orders' => $orders->count(),
            'total_amount' => $totalAmount,
            'commission_amount' => $commission,
            'settlement_amount' => $settlementAmount,
            'status' => TemplateSettlement::STATUS_PENDING,
            'create_time' => time(),
        ]);

        // 标记订单为已结算
        foreach ($orders as $order) {
            $order->settlement_status = TemplateOrder::SETTLEMENT_SETTLED;
            $order->settlement_id = $settlement->id;
            $order->save();
        }

        Cache::tag($this->cacheTag)->clear();

        return [
            'code' => 0,
            'msg' => '结算批次创建成功',
            'data' => [
                'batch_no' => $settlement->batch_no,
                'total_orders' => $settlement->total_orders,
                'total_amount' => $settlement->total_amount,
                'commission' => $commission,
                'settlement_amount' => $settlementAmount,
            ],
        ];
    }

    /**
     * 获取结算列表
     */
    public function getSettlementList(int $page = 1, int $limit = 20): array
    {
        $list = TemplateSettlement::order('id', 'desc')
            ->paginate(['list_rows' => $limit, 'page' => $page]);

        return [
            'list' => $list->items(),
            'total' => $list->total(),
        ];
    }

    /**
     * 审核结算
     */
    public function auditSettlement(int $id, string $auditor, string $remark = ''): array
    {
        $settlement = TemplateSettlement::find($id);
        if (!$settlement) {
            return ['code' => 1, 'msg' => '结算记录不存在'];
        }
        if ($settlement->status !== TemplateSettlement::STATUS_PENDING) {
            return ['code' => 1, 'msg' => '该结算批次已审核'];
        }

        $settlement->status = TemplateSettlement::STATUS_AUDITED;
        $settlement->auditor = $auditor;
        $settlement->audit_time = time();
        $settlement->remark = $remark;
        $settlement->save();

        Cache::tag($this->cacheTag)->clear();
        return ['code' => 0, 'msg' => '审核成功'];
    }
}
