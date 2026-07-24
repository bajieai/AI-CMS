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

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use think\facade\Cache;
use think\facade\Db;
use app\common\service\CacheService;

/**
 * 数据看板控制器
 * 所有查询：表不存在或无数据时返回 code=0 + 空数据/默认值，绝不返回500
 */
class DashboardController extends AdminBaseController
{
    /**
     * 看板页面
     */
    public function index()
    {
        return $this->view('/dashboard_index');
    }

    /**
     * 安全查询：表不存在时返回默认值而非500
     */
    private function safeQuery(string $table, callable $callback, $default = [])
    {
        try {
            $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
            $fullTable = $prefix . $table;
            $exists = Db::query("SHOW TABLES LIKE '{$fullTable}'");
            if (empty($exists)) {
                return $default;
            }
            return $callback();
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * 生成空日期序列
     */
    private function emptyDateSeries(int $days, array $fields = ['pv' => 0, 'uv' => 0]): array
    {
        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $row = ['date' => date('Y-m-d', strtotime("-{$i} days"))];
            $result[] = array_merge($row, $fields);
        }
        return $result;
    }

    // ===================== AJAX API =====================

    /**
     * 总览统计
     */
    public function getOverview()
    {
        $defaults = [
            'today_pv'       => 0,
            'today_uv'       => 0,
            'yesterday_pv'   => 0,
            'yesterday_uv'   => 0,
            'total_content'  => 0,
            'total_members'  => 0,
            'total_comments' => 0,
        ];

        try {
            $todayStart = strtotime('today');
            $yesterdayStart = strtotime('yesterday');

            $todayPV = $this->safeQuery('visit_log', fn() => Db::name('visit_log')->where('visit_time', '>=', $todayStart)->count(), 0);
            $todayUV = $this->safeQuery('visit_log', fn() => Db::name('visit_log')->where('visit_time', '>=', $todayStart)->group('ip')->count(), 0);
            $yesterdayPV = $this->safeQuery('visit_log', fn() => Db::name('visit_log')->whereBetween('visit_time', [$yesterdayStart, $todayStart])->count(), 0);
            $yesterdayUV = $this->safeQuery('visit_log', fn() => Db::name('visit_log')->whereBetween('visit_time', [$yesterdayStart, $todayStart])->group('ip')->count(), 0);
            $totalContent = $this->safeQuery('content', fn() => Db::name('content')->where('status', 2)->count(), 0);
            $totalMembers = $this->safeQuery('member', fn() => Db::name('member')->count(), 0);
            $totalComments = $this->safeQuery('comment', fn() => Db::name('comment')->where('status', 1)->count(), 0);

            return json([
                'code' => 0,
                'data' => [
                    'today_pv'       => $todayPV,
                    'today_uv'       => $todayUV,
                    'yesterday_pv'   => $yesterdayPV,
                    'yesterday_uv'   => $yesterdayUV,
                    'total_content'  => $totalContent,
                    'total_members'  => $totalMembers,
                    'total_comments' => $totalComments,
                ],
            ]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'data' => $defaults]);
        }
    }

    /**
     * PV/UV趋势（7/30天）
     */
    public function getTrend()
    {
        $days = (int) $this->request->get('days', 7);
        if ($days > 90) $days = 90;

        try {
            $cacheKey = "dashboard_trend_{$days}";
            $result = Cache::get($cacheKey);

            if ($result === null) {
                $startDate = strtotime("-{$days} days");

                $pvQuery = $this->safeQuery('visit_log', fn() => Db::name('visit_log')
                    ->field('FROM_UNIXTIME(visit_time, "%Y-%m-%d") as date, COUNT(*) as pv')
                    ->where('visit_time', '>=', $startDate)
                    ->group('date')
                    ->order('date')
                    ->select()
                    ->toArray(), []);

                $uvQuery = $this->safeQuery('visit_log', fn() => Db::name('visit_log')
                    ->field('FROM_UNIXTIME(visit_time, "%Y-%m-%d") as date, COUNT(DISTINCT ip) as uv')
                    ->where('visit_time', '>=', $startDate)
                    ->group('date')
                    ->order('date')
                    ->select()
                    ->toArray(), []);

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

                Cache::set($cacheKey, $result, 300);
            }

            return json(['code' => 0, 'data' => $result]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'data' => $this->emptyDateSeries($days)]);
        }
    }

    /**
     * 内容贡献分析（各分类浏览量饼图）
     */
    public function getCategoryStats()
    {
        try {
            $cacheKey = 'dashboard_category_stats';
            $data = Cache::get($cacheKey);

            if ($data === null) {
                $data = $this->safeQuery('content', fn() => Db::name('content')
                    ->alias('c')
                    ->join('cate cat', 'c.cate_id = cat.id', 'LEFT')
                    ->field('cat.name as category_name, COALESCE(SUM(c.views), 0) as total_views, COUNT(c.id) as content_count')
                    ->where('c.status', '>=', 0)
                    ->group('cat.name')
                    ->order('total_views', 'desc')
                    ->select()
                    ->toArray(), []);

                Cache::set($cacheKey, $data, 300);
            }

            return json(['code' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'data' => []]);
        }
    }

    /**
     * 热门内容TOP10
     */
    public function getTopContent()
    {
        try {
            $limit = (int) $this->request->get('limit', 10);
            $orderBy = $this->request->get('order', 'views');

            $field = match($orderBy) {
                'comments' => 'comment_count',
                default    => 'views',
            };

            $data = $this->safeQuery('content', fn() => Db::name('content')
                ->field('id, title, views, comment_count')
                ->where('status', 2)
                ->order($field, 'desc')
                ->limit($limit)
                ->select()
                ->toArray(), []);

            return json(['code' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'data' => []]);
        }
    }

    /**
     * 别名：总览统计
     */
    public function overview()
    {
        return $this->getOverview();
    }

    /**
     * 别名：PV/UV趋势
     */
    public function trend()
    {
        return $this->getTrend();
    }

    /**
     * 别名：内容贡献分析
     */
    public function categoryStats()
    {
        return $this->getCategoryStats();
    }

    /**
     * 别名：热门内容TOP10
     */
    public function topContent()
    {
        return $this->getTopContent();
    }

    /**
     * V3.1: 来源分析接口
     */
    public function getSourceAnalysis()
    {
        try {
            $days = (int) $this->request->get('days', 30);
            if ($days > 90) $days = 90;
            $startTime = strtotime("-{$days} days");
            $endTime = time();

            $data = \app\common\service\DashboardService::getSourceAnalysis($startTime, $endTime);
            return json(['code' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'data' => []]);
        }
    }

    /**
     * V2.9.9: 设备分布（从UA实时解析）
     */
    public function getDeviceStats()
    {
        try {
            $todayStart = strtotime('today');
            $data = $this->safeQuery('visit_log', fn() => Db::name('visit_log')
                ->field("CASE
                    WHEN ua LIKE '%Mobile%' AND ua NOT LIKE '%iPad%' THEN 'mobile'
                    WHEN ua LIKE '%iPad%' OR (ua LIKE '%Android%' AND ua NOT LIKE '%Mobile%') THEN 'tablet'
                    WHEN ua LIKE '%Windows%' OR ua LIKE '%Macintosh%' OR ua LIKE '%Linux%' THEN 'desktop'
                    WHEN ua LIKE '%bot%' OR ua LIKE '%spider%' OR ua LIKE '%crawler%' THEN 'bot'
                    ELSE 'unknown'
                END as device, COUNT(*) as count")
                ->where('visit_time', '>=', $todayStart)
                ->group('device')
                ->select()
                ->toArray(), []);

            return json(['code' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'data' => []]);
        }
    }

    /**
     * V2.9.9 B-1: 运营报表页面
     */
    public function dataOperations()
    {
        return $this->view('/data_operations');
    }

    /**
     * V2.9.9 B-1: 运营报表API（访客/内容/订单维度）
     */
    public function getOperationsReport()
    {
        try {
            $start = $this->request->get('start');
            $end = $this->request->get('end');
            $startTime = $start ? strtotime($start) : strtotime('-7 days');
            $endTime = $end ? strtotime($end . ' 23:59:59') : time();

            $data = \app\common\service\DashboardService::getOperationsReport($startTime, $endTime);
            return json(['code' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'data' => []]);
        }
    }

    /**
     * V2.9.9 E-2: 运营数据CSV导出
     */
    public function exportOperationsCsv()
    {
        try {
            $start = $this->request->get('start');
            $end = $this->request->get('end');
            $startTime = $start ? strtotime($start) : strtotime('-7 days');
            $endTime = $end ? strtotime($end . ' 23:59:59') : time();
            $days = (int) $this->request->get('days', 30);

            $report = \app\common\service\DashboardService::getOperationsReport($startTime, $endTime);
            $dauData = \app\common\service\DashboardService::getDauMau($days);

            $filename = 'operations_' . date('Ymd_His') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            echo "\xEF\xBB\xBF";

            $out = fopen('php://output', 'w');

            fputcsv($out, ['维度', '指标', '数值']);
            fputcsv($out, ['访客', 'PV', $report['visitor']['pv'] ?? 0]);
            fputcsv($out, ['访客', 'UV', $report['visitor']['uv'] ?? 0]);
            fputcsv($out, ['访客', '新访客', $report['visitor']['new_visitor_count'] ?? 0]);
            fputcsv($out, ['内容', '新增内容', $report['content']['published'] ?? 0]);
            fputcsv($out, ['内容', '总浏览', $report['content']['total_views'] ?? 0]);
            fputcsv($out, ['订单', '成交订单', $report['order']['count'] ?? 0]);
            fputcsv($out, ['订单', '成交金额', $report['order']['amount'] ?? 0]);
            fputcsv($out, []);

            fputcsv($out, ['日期', 'DAU']);
            foreach ($dauData['daily'] ?? [] as $item) {
                fputcsv($out, [$item['date'], $item['dau']]);
            }
            fputcsv($out, []);
            fputcsv($out, ['MAU', $dauData['mau'] ?? 0]);

            fclose($out);
            exit;
        } catch (\Throwable $e) {
            return json(['code' => 0, 'data' => ['error' => '导出失败']]);
        }
    }

    /**
     * V2.9.9 B-2: DAU/MAU统计
     */
    public function getDauMau()
    {
        try {
            $days = (int) $this->request->get('days', 30);
            if ($days > 90) $days = 90;
            $data = \app\common\service\DashboardService::getDauMau($days);
            return json(['code' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'data' => ['daily' => [], 'mau' => 0]]);
        }
    }

    /**
     * V2.9.9 B-2: 跳出率
     */
    public function getBounceRate()
    {
        try {
            $days = (int) $this->request->get('days', 7);
            $data = \app\common\service\DashboardService::getBounceRate($days);
            return json(['code' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'data' => []]);
        }
    }

    /**
     * V2.9.9 B-2: 浏览器分布
     */
    public function getBrowserStats()
    {
        try {
            $days = (int) $this->request->get('days', 7);
            $data = \app\common\service\DashboardService::getBrowserStats($days);
            return json(['code' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'data' => []]);
        }
    }

    /**
     * V2.9.9 B-2: 热门内容+停留时长
     */
    public function getTopContentWithDuration()
    {
        try {
            $limit = (int) $this->request->get('limit', 10);
            $days = (int) $this->request->get('days', 7);
            $data = \app\common\service\DashboardService::getTopContentWithDuration($limit, $days);
            return json(['code' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'data' => []]);
        }
    }

    /**
     * 收入汇总 — paid_order 表不存在时优雅降级
     */
    public function getRevenueStats()
    {
        $days = (int) $this->request->get('days', 7);
        if ($days > 90) $days = 90;

        try {
            $startDate = strtotime("-{$days} days");

            $orderData = $this->safeQuery('paid_order', fn() => Db::name('paid_order')
                ->field('FROM_UNIXTIME(paid_at, "%Y-%m-%d") as date, SUM(price) as revenue, COUNT(*) as count')
                ->where('status', 2)
                ->where('paid_at', '>=', $startDate)
                ->group('date')
                ->order('date')
                ->select()
                ->toArray(), []);

            $orderMap = array_column($orderData, 'revenue', 'date');
            $countMap = array_column($orderData, 'count', 'date');

            $result = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $result[] = [
                    'date' => $date,
                    'revenue' => round((float)($orderMap[$date] ?? 0), 2),
                    'count' => (int)($countMap[$date] ?? 0),
                ];
            }

            return json(['code' => 0, 'data' => $result]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'data' => $this->emptyDateSeries($days, ['revenue' => 0, 'count' => 0])]);
        }
    }

    /**
     * 会员增长趋势
     */
    public function getMemberGrowth()
    {
        $days = (int) $this->request->get('days', 7);
        if ($days > 90) $days = 90;

        try {
            $startDate = strtotime("-{$days} days");

            $memberData = $this->safeQuery('member', fn() => Db::name('member')
                ->field('FROM_UNIXTIME(create_time, "%Y-%m-%d") as date, COUNT(*) as count')
                ->where('create_time', '>=', $startDate)
                ->group('date')
                ->order('date')
                ->select()
                ->toArray(), []);

            $memberMap = array_column($memberData, 'count', 'date');

            $totalMembers = $this->safeQuery('member', fn() => Db::name('member')->where('create_time', '<', $startDate)->count(), 0);

            $result = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $daily = (int)($memberMap[$date] ?? 0);
                $totalMembers += $daily;
                $result[] = [
                    'date' => $date,
                    'daily' => $daily,
                    'total' => $totalMembers,
                ];
            }

            return json(['code' => 0, 'data' => $result]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'data' => $this->emptyDateSeries($days, ['daily' => 0, 'total' => 0])]);
        }
    }

    /**
     * 积分统计 — points_log 表不存在时优雅降级
     */
    public function getPointsStats()
    {
        $days = (int) $this->request->get('days', 7);
        if ($days > 90) $days = 90;

        try {
            $startDate = strtotime("-{$days} days");

            $pointsData = $this->safeQuery('points_log', fn() => Db::name('points_log')
                ->field('FROM_UNIXTIME(create_time, "%Y-%m-%d") as date, SUM(CASE WHEN points > 0 THEN points ELSE 0 END) as income, SUM(CASE WHEN points < 0 THEN ABS(points) ELSE 0 END) as expense, COUNT(*) as count')
                ->where('create_time', '>=', $startDate)
                ->group('date')
                ->order('date')
                ->select()
                ->toArray(), []);

            $incomeMap = array_column($pointsData, 'income', 'date');
            $expenseMap = array_column($pointsData, 'expense', 'date');

            $result = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $result[] = [
                    'date' => $date,
                    'income' => (int)($incomeMap[$date] ?? 0),
                    'expense' => (int)($expenseMap[$date] ?? 0),
                ];
            }

            $totalPoints = $this->safeQuery('member', fn() => Db::name('member')->sum('points') ?? 0, 0);

            return json(['code' => 0, 'data' => ['trend' => $result, 'total' => $totalPoints]]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'data' => ['trend' => $this->emptyDateSeries($days, ['income' => 0, 'expense' => 0]), 'total' => 0]]);
        }
    }

    /**
     * 内容排行Top20
     */
    public function getContentRank()
    {
        try {
            $days = (int) $this->request->get('days', 30);
            $limit = min((int) $this->request->get('limit', 20), 50);
            $startDate = strtotime("-{$days} days");

            $data = $this->safeQuery('content', fn() => Db::name('content')
                ->field('id, title, views, comment_count, create_time')
                ->where('status', 2)
                ->where('create_time', '>=', $startDate)
                ->order('views', 'desc')
                ->limit($limit)
                ->select()
                ->toArray(), []);

            return json(['code' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'data' => []]);
        }
    }

    /**
     * 导出Excel报表 (暂不实现 — 缺少PhpSpreadsheet依赖)
     */
    public function exportExcel()
    {
        return json(['code' => 0, 'data' => null, 'msg' => '导出功能暂未实现']);
    }

    /**
     * V2.9.9 J-1: 指标趋势环比
     */
    public function getMetricTrend()
    {
        try {
            $metric = trim($this->request->get('metric', 'pv'));
            $days = (int) $this->request->get('days', 7);
            if ($days > 90) $days = 90;

            $data = \app\common\service\DashboardService::getMetricTrend($metric, $days);
            return json(['code' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'data' => []]);
        }
    }

    /**
     * V2.9.9-R5: 死链统计
     */
    public function getDeadLinkStats()
    {
        try {
            $data = \app\common\service\DashboardService::getDeadLinkStats();
            return json(['code' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'data' => ['total' => 0, 'fixed' => 0, 'unfixed' => 0]]);
        }
    }
}
