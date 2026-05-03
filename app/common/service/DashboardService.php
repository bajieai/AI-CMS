<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;
use think\facade\Cache;

/**
 * 数据看板服务 - V2.5增强
 * 新增：来源分析、自定义时间范围、收入统计、会员活跃度分析
 */
class DashboardService
{
    /**
     * 总览统计（支持自定义时间范围）
     */
    public static function getOverview(?int $startTime = null, ?int $endTime = null): array
    {
        $todayStart = strtotime('today');
        $yesterdayStart = strtotime('yesterday');
        $startTime = $startTime ?? $todayStart;
        $endTime = $endTime ?? time();

        $todayPV = Db::name('visit_log')->whereBetween('create_time', [$startTime, $endTime])->count();
        $todayUV = Db::name('visit_log')->whereBetween('create_time', [$startTime, $endTime])->group('ip')->count();

        $yesterdayPV = Db::name('visit_log')
            ->whereBetween('create_time', [$yesterdayStart, $todayStart])
            ->count();
        $yesterdayUV = Db::name('visit_log')
            ->whereBetween('create_time', [$yesterdayStart, $todayStart])
            ->group('ip')
            ->count();

        $totalContent = Db::name('content')->where('status', 2)->count();
        $totalMembers = Db::name('member')->count();
        $totalComments = Db::name('comment')->where('status', 1)->count();

        // V2.5：收入统计
        $revenue = PaymentService::getRevenueStats();

        return [
            'today_pv'       => $todayPV,
            'today_uv'       => $todayUV,
            'yesterday_pv'   => $yesterdayPV,
            'yesterday_uv'   => $yesterdayUV,
            'total_content'  => $totalContent,
            'total_members'  => $totalMembers,
            'total_comments' => $totalComments,
            'revenue'        => $revenue,
        ];
    }

    /**
     * PV/UV趋势（支持自定义天数）
     */
    public static function getTrend(int $days = 7, ?int $startTime = null, ?int $endTime = null): array
    {
        if ($startTime && $endTime) {
            // 自定义时间范围
            $result = [];
            $current = $startTime;
            while ($current <= $endTime) {
                $dayEnd = $current + 86400;
                $pv = Db::name('visit_log')->whereBetween('create_time', [$current, $dayEnd])->count();
                $uv = Db::name('visit_log')->whereBetween('create_time', [$current, $dayEnd])->group('ip')->count();
                $result[] = ['date' => date('Y-m-d', $current), 'pv' => $pv, 'uv' => $uv];
                $current = $dayEnd;
            }
            return $result;
        }

        $days = min($days, 90);
        $startDate = strtotime("-{$days} days");

        $pvQuery = Db::name('visit_log')
            ->field('FROM_UNIXTIME(create_time, "%Y-%m-%d") as date, COUNT(*) as pv')
            ->where('create_time', '>=', $startDate)
            ->group('date')
            ->order('date')
            ->select()
            ->toArray();

        $uvQuery = Db::name('visit_log')
            ->field('FROM_UNIXTIME(create_time, "%Y-%m-%d") as date, COUNT(DISTINCT ip) as uv')
            ->where('create_time', '>=', $startDate)
            ->group('date')
            ->order('date')
            ->select()
            ->toArray();

        $pvMap = array_column($pvQuery, 'pv', 'date');
        $uvMap = array_column($uvQuery, 'uv', 'date');

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $result[] = [
                'date' => $date,
                'pv'   => (int) ($pvMap[$date] ?? 0),
                'uv'   => (int) ($uvMap[$date] ?? 0),
            ];
        }

        return $result;
    }

    /**
     * V2.5新增：来源分析
     */
    public static function getSourceAnalysis(?int $startTime = null, ?int $endTime = null): array
    {
        $startTime = $startTime ?? strtotime('-30 days');
        $endTime = $endTime ?? time();

        // 按source_type分组统计
        $data = Db::name('visit_log')
            ->field('source_type, COUNT(*) as pv, COUNT(DISTINCT ip) as uv')
            ->whereBetween('create_time', [$startTime, $endTime])
            ->group('source_type')
            ->order('pv', 'desc')
            ->select()
            ->toArray();

        // 来源类型标签
        $labels = [
            'direct'  => '直接访问',
            'search'  => '搜索引擎',
            'social'  => '社交媒体',
            'referral' => '外部链接',
            'other'   => '其他',
        ];

        $result = [];
        $totalPV = 0;
        foreach ($data as $item) {
            $type = $item['source_type'] ?: 'direct';
            $totalPV += $item['pv'];
            $result[] = [
                'type'  => $type,
                'label' => $labels[$type] ?? $type,
                'pv'    => (int) $item['pv'],
                'uv'    => (int) $item['uv'],
            ];
        }

        // 计算占比
        foreach ($result as &$item) {
            $item['percent'] = $totalPV > 0 ? round($item['pv'] / $totalPV * 100, 1) : 0;
        }

        return $result;
    }

    /**
     * V2.5新增：引荐来源TOP10
     */
    public static function getTopReferrers(int $limit = 10, ?int $startTime = null, ?int $endTime = null): array
    {
        $startTime = $startTime ?? strtotime('-30 days');
        $endTime = $endTime ?? time();

        return Db::name('visit_log')
            ->field('referrer, COUNT(*) as count')
            ->whereBetween('create_time', [$startTime, $endTime])
            ->where('referrer', '<>', '')
            ->where('source_type', 'referral')
            ->group('referrer')
            ->order('count', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * V2.5新增：会员活跃度分析
     */
    public static function getMemberActivity(): array
    {
        $todayStart = strtotime('today');
        $weekStart = strtotime('-7 days');
        $monthStart = strtotime('-30 days');

        // 今日活跃
        $todayActive = Db::name('member')
            ->where('last_login_time', '>=', $todayStart)
            ->count();

        // 7天活跃
        $weekActive = Db::name('member')
            ->where('last_login_time', '>=', $weekStart)
            ->count();

        // 30天活跃
        $monthActive = Db::name('member')
            ->where('last_login_time', '>=', $monthStart)
            ->count();

        // 总会员数
        $totalMembers = Db::name('member')->count();

        // 会员等级分布
        $levelDistribution = Db::name('member')
            ->alias('m')
            ->leftJoin('member_level ml', 'm.level_id = ml.id')
            ->field('ml.name as level_name, COUNT(m.id) as count')
            ->group('m.level_id')
            ->order('count', 'desc')
            ->select()
            ->toArray();

        // 积分分布
        $pointsDistribution = [
            ['range' => '0', 'count' => Db::name('member')->where('points', 0)->count()],
            ['range' => '1-100', 'count' => Db::name('member')->whereBetween('points', [1, 100])->count()],
            ['range' => '101-500', 'count' => Db::name('member')->whereBetween('points', [101, 500])->count()],
            ['range' => '501-2000', 'count' => Db::name('member')->whereBetween('points', [501, 2000])->count()],
            ['range' => '2000+', 'count' => Db::name('member')->where('points', '>', 2000)->count()],
        ];

        return [
            'today_active'     => $todayActive,
            'week_active'      => $weekActive,
            'month_active'     => $monthActive,
            'total_members'    => $totalMembers,
            'level_distribution' => $levelDistribution,
            'points_distribution' => $pointsDistribution,
        ];
    }

    /**
     * 分类统计
     */
    public static function getCategoryStats(): array
    {
        return Db::name('content')
            ->alias('c')
            ->leftJoin('cate cat', 'c.cate_id = cat.id', 'LEFT')
            ->field('cat.name as category_name, COALESCE(SUM(c.views), 0) as total_views, COUNT(c.id) as content_count')
            ->where('c.status', '>=', 0)
            ->group('cat.name')
            ->order('total_views', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 热门内容TOP10
     */
    public static function getTopContent(int $limit = 10, string $orderBy = 'views'): array
    {
        $field = match($orderBy) {
            'comments' => 'comment_count',
            default    => 'views',
        };

        return Db::name('content')
            ->field('id, title, views, comment_count')
            ->where('status', 2)
            ->order($field, 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 设备分布
     */
    public static function getDeviceStats(): array
    {
        $todayStart = strtotime('today');
        return Db::name('visit_log')
            ->field('device, COUNT(*) as count')
            ->where('create_time', '>=', $todayStart)
            ->group('device')
            ->select()
            ->toArray();
    }
}
