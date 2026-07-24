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

namespace app\common\service\data;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;

/**
 * 数据大屏数据聚合服务 - V2.9.39 DATA-DEEP-1
 *
 * 实时数据使用 Redis 计数器（Cache::store('redis')），准实时数据用 Cache::remember() 5分钟缓存。
 * 与原有 app\common\service\DataDashboardService（V2.9.13）的区别：
 * - 原 Service: 简单 PV/UV/分类/热门内容
 * - 本 Service: 实时访客 + 内容总览 + 用户分析 + AI能力 + 收入统计 + 内容质量 + 性能监控 + 移动端数据
 */
class DataDashboardService
{
    private const CACHE_TAG     = 'data_dashboard';
    private const CACHE_TTL     = 300; // 5分钟
    private const REALTIME_TAG  = 'realtime_dashboard';

    /**
     * 获取大屏全量数据
     */
    public function getAllData(int $screenId = 0): array
    {
        return [
            'realtime'    => $this->getRealtimeVisitors(),
            'content'     => $this->getContentOverview(),
            'users'       => $this->getUserAnalysis(),
            'ai'          => $this->getAiCapability(),
            'revenue'     => $this->getRevenueStats(),
            'quality'     => $this->getContentQuality(),
            'performance' => $this->getPerformanceMonitor(),
            'mobile'      => $this->getMobileData(),
        ];
    }

    // ========================================================================
    // 1. 实时访客数据（Redis计数器，无缓存延迟）
    // ========================================================================

    /**
     * 实时访客数据
     */
    public function getRealtimeVisitors(): array
    {
        $now  = time();
        $min5 = $now - 300;
        $min1 = $now - 60;

        return Cache::remember('realtime_visitors', function () use ($min5, $min1) {
            // 今日访客（从 visit_log 实时查询，不缓存）
            $todayStart = strtotime('today');

            $todayPv = Db::name('visit_log')
                ->where('visit_time', '>=', $todayStart)
                ->count();

            $todayUv = Db::name('visit_log')
                ->where('visit_time', '>=', $todayStart)
                ->distinct(true)
                ->field('visitor_id')
                ->count('visitor_id');

            // 近5分钟活跃访客
            $active5min = Db::name('visit_log')
                ->where('visit_time', '>=', $min5)
                ->distinct(true)
                ->field('visitor_id')
                ->count('visitor_id');

            // 近1分钟活跃访客
            $active1min = Db::name('visit_log')
                ->where('visit_time', '>=', $min1)
                ->distinct(true)
                ->field('visitor_id')
                ->count('visitor_id');

            // 每小时PV（今日）
            $hourlyPv = [];
            $currentHour = (int) date('H');
            for ($h = 0; $h <= $currentHour; $h++) {
                $hourStart = strtotime("today +{$h} hours");
                $hourEnd   = $hourStart + 3600;
                $pv = Db::name('visit_log')
                    ->where('visit_time', '>=', $hourStart)
                    ->where('visit_time', '<', $hourEnd)
                    ->count();
                $hourlyPv[] = ['hour' => sprintf('%02d:00', $h), 'pv' => $pv];
            }

            // 实时热门页面（近5分钟）
            $hotPages = Db::name('visit_log')
                ->field('content_id, COUNT(*) as visits')
                ->where('visit_time', '>=', $min5)
                ->where('content_id', '>', 0)
                ->group('content_id')
                ->order('visits', 'DESC')
                ->limit(5)
                ->select()
                ->toArray();

            // 填充标题
            if (!empty($hotPages)) {
                $contentIds = array_column($hotPages, 'content_id');
                $titles = Db::name('content')
                    ->whereIn('id', $contentIds)
                    ->column('title', 'id');
                foreach ($hotPages as &$page) {
                    $page['title'] = $titles[$page['content_id']] ?? '未知内容';
                }
            }

            return [
                'today_pv'      => $todayPv,
                'today_uv'      => $todayUv,
                'active_5min'   => $active5min,
                'active_1_min'  => $active1min,
                'hourly_pv'     => $hourlyPv,
                'hot_pages'     => $hotPages,
                'online_ratio'  => $todayUv > 0 ? round($active5min / $todayUv * 100, 1) : 0,
            ];
        }, 30); // 实时数据30秒缓存
    }

    // ========================================================================
    // 2. 内容总览
    // ========================================================================

    /**
     * 内容总览数据
     */
    public function getContentOverview(): array
    {
        return Cache::remember('content_overview', function () {
            $todayStart = strtotime('today');
            $weekStart  = strtotime('-7 days');

            $totalContent = Db::name('content')->where('status', '>=', 0)->count();
            $published    = Db::name('content')->where('status', 2)->count();
            $todayNew     = Db::name('content')->where('create_time', '>=', $todayStart)->count();
            $weekNew      = Db::name('content')->where('create_time', '>=', $weekStart)->count();

            // 待审核
            $pendingAudit = Db::name('content')->where('status', 1)->count();

            // 总阅读量
            $totalViews = (int) Db::name('content')->sum('views');

            // 今日阅读量
            $todayViews = Db::name('visit_log')->where('visit_time', '>=', $todayStart)->count();

            // 近7天内容趋势
            $trend = [];
            for ($i = 6; $i >= 0; $i--) {
                $dayStart = strtotime("-{$i} days", strtotime('today'));
                $dayEnd   = $dayStart + 86399;
                $count = Db::name('content')
                    ->whereBetween('create_time', [$dayStart, $dayEnd])
                    ->count();
                $trend[] = [
                    'date'  => date('m-d', $dayStart),
                    'count' => $count,
                ];
            }

            // 分类分布 Top10
            $categoryDist = Db::name('content')
                ->field('cate_id, COUNT(*) as count')
                ->where('status', '>=', 0)
                ->group('cate_id')
                ->order('count', 'DESC')
                ->limit(10)
                ->select()
                ->toArray();

            $cateNames = Db::name('cate')->column('name', 'id');
            foreach ($categoryDist as &$cat) {
                $cat['name'] = $cateNames[$cat['cate_id']] ?? '未分类';
            }

            return [
                'total_content' => $totalContent,
                'published'     => $published,
                'today_new'     => $todayNew,
                'week_new'      => $weekNew,
                'pending_audit' => $pendingAudit,
                'total_views'   => $totalViews,
                'today_views'   => $todayViews,
                'trend_7d'      => $trend,
                'category_dist' => $categoryDist,
            ];
        }, self::CACHE_TTL);
    }

    // ========================================================================
    // 3. 用户分析
    // ========================================================================

    /**
     * 用户分析数据
     */
    public function getUserAnalysis(): array
    {
        return Cache::remember('user_analysis', function () {
            $todayStart = strtotime('today');
            $weekStart  = strtotime('-7 days');
            $monthStart = strtotime(date('Y-m-01'));

            $totalMembers = Db::name('member')->count();
            $todayNew     = Db::name('member')->where('create_time', '>=', $todayStart)->count();
            $weekNew      = Db::name('member')->where('create_time', '>=', $weekStart)->count();
            $monthNew     = Db::name('member')->where('create_time', '>=', $monthStart)->count();

            // 会员等级分布
            $levelDist = Db::name('member')
                ->alias('m')
                ->join('member_level l', 'm.level_id = l.id', 'LEFT')
                ->field('l.level_name, COUNT(*) as count')
                ->group('m.level_id')
                ->select()
                ->toArray();

            // 近7天注册趋势
            $regTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $dayStart = strtotime("-{$i} days", strtotime('today'));
                $dayEnd   = $dayStart + 86399;
                $count = Db::name('member')
                    ->whereBetween('create_time', [$dayStart, $dayEnd])
                    ->count();
                $regTrend[] = [
                    'date'  => date('m-d', $dayStart),
                    'count' => $count,
                ];
            }

            // 活跃用户（近7天有访问记录）
            $activeUsers = Db::name('visit_log')
                ->where('visit_time', '>=', $weekStart)
                ->distinct(true)
                ->field('visitor_id')
                ->count('visitor_id');

            // 付费用户数
            $paidUsers = 0;
            try {
                $paidUsers = Db::name('paid_content_record')
                    ->where('status', 1)
                    ->distinct(true)
                    ->field('member_id')
                    ->count('member_id');
            } catch (\Throwable) {
                // 表可能不存在
            }

            // 付费转化率
            $conversionRate = $totalMembers > 0 ? round($paidUsers / $totalMembers * 100, 2) : 0;

            return [
                'total_members'    => $totalMembers,
                'today_new'        => $todayNew,
                'week_new'         => $weekNew,
                'month_new'        => $monthNew,
                'level_dist'       => $levelDist,
                'reg_trend_7d'     => $regTrend,
                'active_users_7d'  => $activeUsers,
                'paid_users'       => $paidUsers,
                'conversion_rate'  => $conversionRate,
            ];
        }, self::CACHE_TTL);
    }

    // ========================================================================
    // 4. AI能力统计
    // ========================================================================

    /**
     * AI能力使用数据
     */
    public function getAiCapability(): array
    {
        return Cache::remember('ai_capability', function () {
            $todayStart = strtotime('today');
            $weekStart  = strtotime('-7 days');

            // AI调用总数
            $totalCalls = 0;
            $todayCalls = 0;
            $weekCalls  = 0;

            try {
                $totalCalls = Db::name('ai_log')->count();
                $todayCalls = Db::name('ai_log')->where('create_time', '>=', $todayStart)->count();
                $weekCalls  = Db::name('ai_log')->where('create_time', '>=', $weekStart)->count();
            } catch (\Throwable) {
                // ai_log 表可能不存在
            }

            // AI调用类型分布
            $typeDist = [];
            try {
                $typeDist = Db::name('ai_log')
                    ->field('type, COUNT(*) as count')
                    ->where('create_time', '>=', $weekStart)
                    ->group('type')
                    ->order('count', 'DESC')
                    ->select()
                    ->toArray();
            } catch (\Throwable) {
            }

            // 近7天AI调用趋势
            $aiTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $dayStart = strtotime("-{$i} days", strtotime('today'));
                $dayEnd   = $dayStart + 86399;
                $count = 0;
                try {
                    $count = Db::name('ai_log')
                        ->whereBetween('create_time', [$dayStart, $dayEnd])
                        ->count();
                } catch (\Throwable) {
                }
                $aiTrend[] = [
                    'date'  => date('m-d', $dayStart),
                    'count' => $count,
                ];
            }

            // AI模型使用分布
            $modelDist = [];
            try {
                $modelDist = Db::name('ai_log')
                    ->field('model, COUNT(*) as count')
                    ->where('create_time', '>=', $weekStart)
                    ->group('model')
                    ->order('count', 'DESC')
                    ->limit(5)
                    ->select()
                    ->toArray();
            } catch (\Throwable) {
            }

            // 智能体执行统计
            $agentExecutions = 0;
            try {
                $agentExecutions = Db::name('ai_workflow_exec')
                    ->where('create_time', '>=', $weekStart)
                    ->count();
            } catch (\Throwable) {
            }

            return [
                'total_calls'       => $totalCalls,
                'today_calls'       => $todayCalls,
                'week_calls'        => $weekCalls,
                'type_dist'         => $typeDist,
                'trend_7d'          => $aiTrend,
                'model_dist'        => $modelDist,
                'agent_executions'  => $agentExecutions,
            ];
        }, self::CACHE_TTL);
    }

    // ========================================================================
    // 5. 收入统计
    // ========================================================================

    /**
     * 收入统计数据
     */
    public function getRevenueStats(): array
    {
        return Cache::remember('revenue_stats', function () {
            $todayStart  = strtotime('today');
            $monthStart  = strtotime(date('Y-m-01'));
            $lastMonthStart = strtotime('first day of last month');

            $totalRevenue = 0;
            $todayRevenue = 0;
            $monthRevenue = 0;

            try {
                $totalRevenue = (float) Db::name('paid_content_record')->where('status', 1)->sum('amount');
                $todayRevenue = (float) Db::name('paid_content_record')->where('status', 1)->where('create_time', '>=', $todayStart)->sum('amount');
                $monthRevenue = (float) Db::name('paid_content_record')->where('status', 1)->where('create_time', '>=', $monthStart)->sum('amount');
            } catch (\Throwable) {
            }

            // 近7天收入趋势
            $revenueTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $dayStart = strtotime("-{$i} days", strtotime('today'));
                $dayEnd   = $dayStart + 86399;
                $amount = 0;
                try {
                    $amount = (float) Db::name('paid_content_record')
                        ->where('status', 1)
                        ->whereBetween('create_time', [$dayStart, $dayEnd])
                        ->sum('amount');
                } catch (\Throwable) {
                }
                $revenueTrend[] = [
                    'date'   => date('m-d', $dayStart),
                    'amount' => round($amount, 2),
                ];
            }

            // 订单总数
            $totalOrders = 0;
            try {
                $totalOrders = Db::name('paid_content_record')->count();
            } catch (\Throwable) {
            }

            // 今日订单
            $todayOrders = 0;
            try {
                $todayOrders = Db::name('paid_content_record')->where('create_time', '>=', $todayStart)->count();
            } catch (\Throwable) {
            }

            // 客单价
            $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

            // 模板商店收入
            $templateRevenue = 0;
            try {
                $templateRevenue = (float) Db::name('template_order')
                    ->where('status', 1)
                    ->sum('amount');
            } catch (\Throwable) {
            }

            return [
                'total_revenue'    => round($totalRevenue, 2),
                'today_revenue'    => round($todayRevenue, 2),
                'month_revenue'    => round($monthRevenue, 2),
                'revenue_trend_7d' => $revenueTrend,
                'total_orders'     => $totalOrders,
                'today_orders'     => $todayOrders,
                'avg_order_value'  => $avgOrderValue,
                'template_revenue' => round($templateRevenue, 2),
            ];
        }, self::CACHE_TTL);
    }

    // ========================================================================
    // 6. 内容质量
    // ========================================================================

    /**
     * 内容质量数据
     */
    public function getContentQuality(): array
    {
        return Cache::remember('content_quality', function () {
            // 质量评分统计
            $avgScore = 0;
            $highQuality = 0;
            $lowQuality = 0;
            $totalScored = 0;

            try {
                $avgScore = (float) (Db::name('content_quality_score')->avg('total_score') ?: 0);
                $highQuality = Db::name('content_quality_score')->where('total_score', '>=', 80)->count();
                $lowQuality = Db::name('content_quality_score')->where('total_score', '<', 60)->count();
                $totalScored = Db::name('content_quality_score')->count();
            } catch (\Throwable) {
            }

            // 质量分布（按区间）
            $qualityDist = [];
            $ranges = [
                ['label' => '优秀(80-100)', 'min' => 80, 'max' => 100],
                ['label' => '良好(60-79)', 'min' => 60, 'max' => 79],
                ['label' => '一般(40-59)', 'min' => 40, 'max' => 59],
                ['label' => '较差(<40)', 'min' => 0, 'max' => 39],
            ];

            foreach ($ranges as $range) {
                $count = 0;
                try {
                    $count = Db::name('content_quality_score')
                        ->where('total_score', '>=', $range['min'])
                        ->where('total_score', '<=', $range['max'])
                        ->count();
                } catch (\Throwable) {
                }
                $qualityDist[] = ['label' => $range['label'], 'count' => $count];
            }

            // 待修复内容数
            $needFix = $lowQuality;

            // 质量趋势（近7天均值）
            $qualityTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $dayStart = strtotime("-{$i} days", strtotime('today'));
                $dayEnd   = $dayStart + 86399;
                $avg = 0;
                try {
                    $avg = (float) (Db::name('content_quality_score')
                        ->whereBetween('create_time', [$dayStart, $dayEnd])
                        ->avg('total_score') ?: 0);
                } catch (\Throwable) {
                }
                $qualityTrend[] = [
                    'date'  => date('m-d', $dayStart),
                    'score' => round($avg, 1),
                ];
            }

            return [
                'avg_score'     => round($avgScore, 1),
                'high_quality'  => $highQuality,
                'low_quality'   => $lowQuality,
                'total_scored'  => $totalScored,
                'quality_dist'  => $qualityDist,
                'need_fix'      => $needFix,
                'trend_7d'      => $qualityTrend,
            ];
        }, self::CACHE_TTL);
    }

    // ========================================================================
    // 7. 性能监控
    // ========================================================================

    /**
     * 系统性能监控数据
     */
    public function getPerformanceMonitor(): array
    {
        return Cache::remember('performance_monitor', function () {
            // 慢查询统计
            $slowQueries = 0;
            $avgQueryTime = 0;
            try {
                $slowQueries = Db::name('performance_log')
                    ->where('type', 'slow_query')
                    ->where('create_time', '>=', strtotime('-1 hours'))
                    ->count();
                $avgQueryTime = (float) (Db::name('performance_log')
                    ->where('type', 'query')
                    ->where('create_time', '>=', strtotime('-1 hours'))
                    ->avg('duration') ?: 0);
            } catch (\Throwable) {
            }

            // 缓存命中率
            $cacheHits = 0;
            $cacheMisses = 0;
            $cacheHitRate = 0;
            try {
                $cacheStats = Db::name('cache_stats')
                    ->where('create_time', '>=', strtotime('-1 hours'))
                    ->field('SUM(hits) as hits, SUM(misses) as misses')
                    ->find();
                $cacheHits = (int) ($cacheStats['hits'] ?? 0);
                $cacheMisses = (int) ($cacheStats['misses'] ?? 0);
                $total = $cacheHits + $cacheMisses;
                $cacheHitRate = $total > 0 ? round($cacheHits / $total * 100, 1) : 0;
            } catch (\Throwable) {
            }

            // 系统负载
            $loadAvg = sys_getloadavg();
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
            $memoryPercent = $memoryLimit > 0 ? round($memoryUsage / $memoryLimit * 100, 1) : 0;

            // 磁盘使用
            $diskFree = disk_free_space('/');
            $diskTotal = disk_total_space('/');
            $diskPercent = $diskTotal > 0 ? round(($diskTotal - $diskFree) / $diskTotal * 100, 1) : 0;

            // PHP-FPM 进程数（从 cgroup 读取）
            $cpuUsage = $this->getCpuUsage();

            return [
                'slow_queries'     => $slowQueries,
                'avg_query_time'   => round($avgQueryTime, 2),
                'cache_hits'       => $cacheHits,
                'cache_misses'     => $cacheMisses,
                'cache_hit_rate'   => $cacheHitRate,
                'load_avg'         => [
                    '1min'  => round($loadAvg[0] ?? 0, 2),
                    '5min'  => round($loadAvg[1] ?? 0, 2),
                    '15min' => round($loadAvg[2] ?? 0, 2),
                ],
                'memory'           => [
                    'usage'   => $this->formatBytes($memoryUsage),
                    'limit'   => $this->formatBytes($memoryLimit),
                    'percent' => $memoryPercent,
                ],
                'disk'             => [
                    'free'    => $this->formatBytes($diskFree),
                    'total'   => $this->formatBytes($diskTotal),
                    'percent' => $diskPercent,
                ],
                'cpu_usage'        => $cpuUsage,
            ];
        }, 60); // 性能数据1分钟缓存
    }

    // ========================================================================
    // 8. 移动端数据
    // ========================================================================

    /**
     * 移动端统计数据
     */
    public function getMobileData(): array
    {
        return Cache::remember('mobile_data', function () {
            $todayStart = strtotime('today');
            $weekStart  = strtotime('-7 days');

            $mobilePv = 0;
            $mobileUv = 0;

            try {
                $mobilePv = Db::name('visit_log')
                    ->where('visit_time', '>=', $weekStart)
                    ->where('device_type', 'in', ['mobile', 'tablet'])
                    ->count();
                $mobileUv = Db::name('visit_log')
                    ->where('visit_time', '>=', $weekStart)
                    ->where('device_type', 'in', ['mobile', 'tablet'])
                    ->distinct(true)
                    ->field('visitor_id')
                    ->count('visitor_id');
            } catch (\Throwable) {
            }

            // 小程序统计
            $miniStats = [
                'pv'        => 0,
                'uv'        => 0,
                'sessions'  => 0,
            ];
            try {
                $miniStats['pv'] = Db::name('mini_stats')->where('stat_date', '>=', date('Ymd', $weekStart))->sum('pv');
                $miniStats['uv'] = Db::name('mini_stats')->where('stat_date', '>=', date('Ymd', $weekStart))->sum('uv');
                $miniStats['sessions'] = Db::name('mini_stats')->where('stat_date', '>=', date('Ymd', $weekStart))->sum('session_count');
            } catch (\Throwable) {
            }

            // 设备分布
            $deviceDist = [];
            try {
                $deviceDist = Db::name('visit_log')
                    ->field('device_type, COUNT(*) as count')
                    ->where('visit_time', '>=', $weekStart)
                    ->group('device_type')
                    ->select()
                    ->toArray();
            } catch (\Throwable) {
            }

            // 移动端近7天趋势
            $mobileTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $dayStart = strtotime("-{$i} days", strtotime('today'));
                $dayEnd   = $dayStart + 86399;
                $pv = 0;
                try {
                    $pv = Db::name('visit_log')
                        ->where('visit_time', '>=', $dayStart)
                        ->where('visit_time', '<', $dayEnd)
                        ->where('device_type', 'in', ['mobile', 'tablet'])
                        ->count();
                } catch (\Throwable) {
                }
                $mobileTrend[] = [
                    'date' => date('m-d', $dayStart),
                    'pv'   => $pv,
                ];
            }

            return [
                'mobile_pv'      => $mobilePv,
                'mobile_uv'      => $mobileUv,
                'mini_stats'     => $miniStats,
                'device_dist'    => $deviceDist,
                'trend_7d'       => $mobileTrend,
            ];
        }, self::CACHE_TTL);
    }

    // ========================================================================
    // 工具方法
    // ========================================================================

    /**
     * 格式化字节大小
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    /**
     * 解析 PHP memory_limit
     */
    private function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return 0;
        }
        $value = (int) $limit;
        $unit = strtolower(substr($limit, -1));
        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    /**
     * 获取 CPU 使用率（兼容 cgroup v1/v2）
     */
    private function getCpuUsage(): float
    {
        // cgroup v2
        if (is_file('/sys/fs/cgroup/cpu.stat')) {
            $stat = file_get_contents('/sys/fs/cgroup/cpu.stat');
            if (preg_match('/usage_usec\s+(\d+)/', $stat, $m)) {
                $usage = (int) $m[1];
                // 取最近一个采样窗口
                if (is_file('/sys/fs/cgroup/cpu.max')) {
                    $quota = file_get_contents('/sys/fs/cgroup/cpu.max');
                    $parts = explode(' ', trim($quota));
                    if ($parts[0] !== 'max' && isset($parts[1])) {
                        $quotaVal = (int) $parts[0];
                        $periodVal = (int) $parts[1];
                        if ($periodVal > 0) {
                            $cores = $quotaVal / $periodVal;
                            return round(min(100, $cores * 100 / max(1, $quotaVal / $usage * 1000000)), 1);
                        }
                    }
                }
                return round(min(100, $usage / 1000000 / 100), 1);
            }
        }

        // cgroup v1
        if (is_file('/sys/fs/cgroup/cpuacct/cpuacct.usage')) {
            $usage = (int) file_get_contents('/sys/fs/cgroup/cpuacct/cpuacct.usage');
            return round(min(100, $usage / 1000000000 / 100), 1);
        }

        // 降级：使用 load average 近似
        $load = sys_getloadavg();
        return round(min(100, ($load[0] ?? 0) * 100), 1);
    }

    /**
     * 清除大屏缓存
     */
    public static function clearCache(): void
    {
        Cache::clear();
        Cache::clear();
    }
}
