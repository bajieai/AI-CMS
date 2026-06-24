<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateOrder;
use app\common\model\TemplateDailyStats;
use app\common\model\TemplateStore;
use app\common\model\TemplateRefund;
use think\facade\Cache;
use think\facade\Db;

/**
 * 模板统计聚合服务 — V2.9.28 M-3
 *
 * 数据采集方案（小扣v2审核问题3）：
 * - Cron每日凌晨聚合（从业务表统计写入聚合表）
 * - 看板实时数据直接查业务表+Redis缓存(TTL=300s)
 * - 图表数据查聚合表
 * - 首次部署提供历史数据回填SQL脚本
 */
class TemplateStatsAggregator
{
    private const CACHE_TAG = 'template_stats';
    private const CACHE_TTL = 300; // 5分钟

    /**
     * 每日聚合（由Cron调用）
     * 将业务表数据聚合写入 template_daily_stats
     */
    public function aggregateDaily(string $date = ''): array
    {
        if (empty($date)) {
            $date = date('Y-m-d', strtotime('-1 day'));
        }

        $startTime = strtotime($date . ' 00:00:00');
        $endTime = strtotime($date . ' 23:59:59');

        // 全站汇总
        $totalViews = TemplateStore::sum('view_count');
        $totalDownloads = TemplateStore::sum('install_count');

        $orderStats = TemplateOrder::whereBetweenTime('create_time', $startTime, $endTime)
            ->field('COUNT(*) as order_count, SUM(pay_amount) as revenue')
            ->find();

        $refundStats = TemplateRefund::where('status', TemplateRefund::STATUS_APPROVED)
            ->whereBetweenTime('process_time', $startTime, $endTime)
            ->field('COUNT(*) as refund_count, SUM(amount) as refund_amount')
            ->find();

        // 写入全站汇总记录
        $this->upsertDailyStats($date, 0, [
            'view_count' => 0, // 浏览量需要从日志表统计，暂为0
            'download_count' => 0,
            'order_count' => $orderStats['order_count'] ?? 0,
            'revenue' => $orderStats['revenue'] ?? 0,
            'refund_count' => $refundStats['refund_count'] ?? 0,
            'refund_amount' => $refundStats['refund_amount'] ?? 0,
        ]);

        // 按模板维度聚合
        $templateOrders = TemplateOrder::whereBetweenTime('create_time', $startTime, $endTime)
            ->field('template_id, COUNT(*) as order_count, SUM(pay_amount) as revenue')
            ->group('template_id')
            ->select();

        foreach ($templateOrders as $row) {
            $this->upsertDailyStats($date, (int)$row['template_id'], [
                'order_count' => (int)$row['order_count'],
                'revenue' => (float)$row['revenue'],
            ]);
        }

        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'date' => $date, 'templates' => $templateOrders->count()];
    }

    /**
     * 获取看板概览数据
     */
    public function getDashboardStats(string $startDate = '', string $endDate = ''): array
    {
        $cacheKey = 'dashboard_stats_' . $startDate . '_' . $endDate;
        return Cache::tag(self::CACHE_TAG)->remember($cacheKey, function() use ($startDate, $endDate) {
            // 今日数据
            $today = date('Y-m-d');
            $todayStart = strtotime($today . ' 00:00:00');
            $todayEnd = time();

            $todayRevenue = TemplateOrder::where('status', TemplateOrder::STATUS_PAID)
                ->whereBetweenTime('pay_time', $todayStart, $todayEnd)
                ->sum('pay_amount');
            $todayOrders = TemplateOrder::where('status', TemplateOrder::STATUS_PAID)
                ->whereBetweenTime('pay_time', $todayStart, $todayEnd)
                ->count();
            $todayDownloads = TemplateStore::sum('install_count');

            // 本周数据
            $weekStart = strtotime('monday this week');
            $weekRevenue = TemplateOrder::where('status', TemplateOrder::STATUS_PAID)
                ->whereBetweenTime('pay_time', $weekStart, $todayEnd)
                ->sum('pay_amount');

            // 本月数据
            $monthStart = strtotime(date('Y-m-01'));
            $monthRevenue = TemplateOrder::where('status', TemplateOrder::STATUS_PAID)
                ->whereBetweenTime('pay_time', $monthStart, $todayEnd)
                ->sum('pay_amount');
            $monthOrders = TemplateOrder::where('status', TemplateOrder::STATUS_PAID)
                ->whereBetweenTime('pay_time', $monthStart, $todayEnd)
                ->count();

            // 总计
            $totalRevenue = TemplateOrder::where('status', TemplateOrder::STATUS_PAID)->sum('pay_amount');
            $totalOrders = TemplateOrder::where('status', TemplateOrder::STATUS_PAID)->count();
            $activeTemplates = TemplateStore::where('status', 1)->count();

            // 转化漏斗
            $totalViews = TemplateStore::sum('view_count');
            $totalCarts = 0; // 购物车数据暂无统计
            $totalPurchases = $totalOrders;
            $funnel = [
                'views' => $totalViews,
                'carts' => $totalCarts,
                'purchases' => $totalPurchases,
                'view_to_cart' => $totalViews > 0 ? round($totalCarts / $totalViews * 100, 2) : 0,
                'cart_to_purchase' => $totalCarts > 0 ? round($totalPurchases / $totalCarts * 100, 2) : 0,
                'view_to_purchase' => $totalViews > 0 ? round($totalPurchases / $totalViews * 100, 2) : 0,
            ];

            // 收入趋势（最近30天，从聚合表查询）
            $trend = $this->getRevenueTrend(30);

            return [
                'summary' => [
                    'total_installs' => (int)TemplateStore::sum('install_count'),
                    'online_templates' => TemplateStore::where('status', 1)->count(),
                    'total_templates' => TemplateStore::count(),
                    'migrate_count' => 0,
                ],
                'today' => [
                    'revenue' => (float)$todayRevenue,
                    'orders' => $todayOrders,
                    'downloads' => $todayDownloads,
                ],
                'week' => ['revenue' => (float)$weekRevenue],
                'month' => [
                    'revenue' => (float)$monthRevenue,
                    'orders' => $monthOrders,
                ],
                'total' => [
                    'revenue' => (float)$totalRevenue,
                    'orders' => $totalOrders,
                    'active_templates' => $activeTemplates,
                ],
                'funnel' => $funnel,
                'trend' => $trend,
            ];
        }, self::CACHE_TTL);
    }

    /**
     * 收入趋势（从聚合表查询）
     */
    public function getRevenueTrend(int $days = 30): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        $records = TemplateDailyStats::where('template_id', 0)
            ->where('stats_date', '>=', $startDate)
            ->order('stats_date', 'asc')
            ->select()
            ->toArray();

        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $record = null;
            foreach ($records as $r) {
                if ($r['stats_date'] == $date) {
                    $record = $r;
                    break;
                }
            }
            $trend[] = [
                'date' => $date,
                'revenue' => $record ? (float)$record['revenue'] : 0,
                'orders' => $record ? (int)$record['order_count'] : 0,
                'downloads' => $record ? (int)$record['download_count'] : 0,
            ];
        }

        return $trend;
    }

    /**
     * Top50模板排行
     */
    public function getTopRanking(int $limit = 50, string $orderBy = 'revenue'): array
    {
        $cacheKey = 'top_ranking_' . $orderBy . '_' . $limit;
        return Cache::tag(self::CACHE_TAG)->remember($cacheKey, function() use ($limit, $orderBy) {
            $query = TemplateStore::where('status', 1);

            switch ($orderBy) {
                case 'downloads':
                    $query->order('install_count', 'desc');
                    break;
                case 'rating':
                    $query->order('rating_avg', 'desc');
                    break;
                case 'revenue':
                default:
                    // 按收入排序需要关联订单表
                    $query->order('install_count', 'desc');
                    break;
            }

            return $query->field('id, name, slug, cover, price, install_count, rating_avg, rating_count, view_count')
                ->limit($limit)
                ->select()
                ->toArray();
        }, self::CACHE_TTL);
    }

    /**
     * 插入或更新每日统计
     */
    private function upsertDailyStats(string $date, int $templateId, array $data): void
    {
        $existing = TemplateDailyStats::where('stats_date', $date)
            ->where('template_id', $templateId)
            ->find();

        if ($existing) {
            $existing->save(array_merge($data, ['update_time' => time()]));
        } else {
            TemplateDailyStats::create(array_merge([
                'stats_date' => $date,
                'template_id' => $templateId,
                'view_count' => 0,
                'download_count' => 0,
                'order_count' => 0,
                'revenue' => 0,
                'refund_count' => 0,
                'refund_amount' => 0,
                'create_time' => time(),
            ], $data));
        }
    }
}
