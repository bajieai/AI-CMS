<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use think\facade\Cache;
use think\facade\Db;
use app\common\service\CacheService;

/**
 * 数据看板控制器
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
     * 总览统计
     */
    public function getOverview()
    {
        try {
            $todayStart = strtotime('today');
            $yesterdayStart = strtotime('yesterday');

            $todayPV = Db::name('visit_log')->where('visit_time', '>=', $todayStart)->count();
            $todayUV = Db::name('visit_log')->where('visit_time', '>=', $todayStart)->group('ip')->count();

            $yesterdayPV = Db::name('visit_log')
                ->whereBetween('visit_time', [$yesterdayStart, $todayStart])
                ->count();
            $yesterdayUV = Db::name('visit_log')
                ->whereBetween('visit_time', [$yesterdayStart, $todayStart])
                ->group('ip')
                ->count();

            $totalContent = Db::name('content')->where('status', 2)->count();
            $totalMembers = Db::name('member')->count();
            $totalComments = Db::name('comment')->where('status', 1)->count();

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
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * PV/UV趋势（7/30天）
     */
    public function getTrend()
    {
        try {
            $days = (int) $this->request->get('days', 7);
            if ($days > 90) $days = 90;

            $cacheKey = "dashboard_trend_{$days}";
            $result = Cache::get($cacheKey);

            if ($result === null) {
                $startDate = strtotime("-{$days} days");

                $pvQuery = Db::name('visit_log')
                    ->field('FROM_UNIXTIME(visit_time, "%Y-%m-%d") as date, COUNT(*) as pv')
                    ->where('visit_time', '>=', $startDate)
                    ->group('date')
                    ->order('date')
                    ->select()
                    ->toArray();

                $uvQuery = Db::name('visit_log')
                    ->field('FROM_UNIXTIME(visit_time, "%Y-%m-%d") as date, COUNT(DISTINCT ip) as uv')
                    ->where('visit_time', '>=', $startDate)
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

                Cache::tag(CacheService::TAG_CONFIG)->set($cacheKey, $result, 300);
            }

            return json(['code' => 0, 'data' => $result]);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
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
                $data = Db::name('content')
                    ->alias('c')
                    ->join('cate cat', 'c.cate_id = cat.id', 'LEFT')
                    ->field('cat.name as category_name, COALESCE(SUM(c.views), 0) as total_views, COUNT(c.id) as content_count')
                    ->where('c.status', '>=', 0)
                    ->group('cat.name')
                    ->order('total_views', 'desc')
                    ->select()
                    ->toArray();

                Cache::tag(CacheService::TAG_CONFIG)->set($cacheKey, $data, 300);
            }

            return json(['code' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
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

            $data = Db::name('content')
                ->field('id, title, views, comment_count')
                ->where('status', 2)
                ->order($field, 'desc')
                ->limit($limit)
                ->select();

            return json(['code' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
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
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * V2.9.9: 设备分布（从UA实时解析）
     */
    public function getDeviceStats()
    {
        $todayStart = strtotime('today');
        $data = Db::name('visit_log')
            ->field("CASE
                WHEN ua LIKE '%Mobile%' AND ua NOT LIKE '%iPad%' THEN 'mobile'
                WHEN ua LIKE '%iPad%' OR (ua LIKE '%Android%' AND ua NOT LIKE '%Mobile%') THEN 'tablet'
                WHEN ua LIKE '%Windows%' OR ua LIKE '%Macintosh%' OR ua LIKE '%Linux%' THEN 'desktop'
                WHEN ua LIKE '%bot%' OR ua LIKE '%spider%' OR ua LIKE '%crawler%' THEN 'bot'
                ELSE 'unknown'
            END as device, COUNT(*) as count")
            ->where('visit_time', '>=', $todayStart)
            ->group('device')
            ->select();

        return json(['code' => 0, 'data' => $data]);
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
            return json(['code' => 1, 'msg' => $e->getMessage()]);
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

            // UTF-8 BOM for Excel
            echo "\xEF\xBB\xBF";

            $out = fopen('php://output', 'w');

            // 汇总指标
            fputcsv($out, ['维度', '指标', '数值']);
            fputcsv($out, ['访客', 'PV', $report['visitor']['pv']]);
            fputcsv($out, ['访客', 'UV', $report['visitor']['uv']]);
            fputcsv($out, ['访客', '新访客', $report['visitor']['new_visitor_count']]);
            fputcsv($out, ['内容', '新增内容', $report['content']['published']]);
            fputcsv($out, ['内容', '总浏览', $report['content']['total_views']]);
            fputcsv($out, ['订单', '成交订单', $report['order']['count']]);
            fputcsv($out, ['订单', '成交金额', $report['order']['amount']]);
            fputcsv($out, []);

            // DAU趋势
            fputcsv($out, ['日期', 'DAU']);
            foreach ($dauData['daily'] as $item) {
                fputcsv($out, [$item['date'], $item['dau']]);
            }
            fputcsv($out, []);
            fputcsv($out, ['MAU', $dauData['mau']]);

            fclose($out);
            exit;
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
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
            return json(['code' => 1, 'msg' => $e->getMessage()]);
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
            return json(['code' => 1, 'msg' => $e->getMessage()]);
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
            return json(['code' => 1, 'msg' => $e->getMessage()]);
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
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 收入汇总 - V2.8新增
     */
    public function getRevenueStats()
    {
        try {
            $days = (int) $this->request->get('days', 7);
            if ($days > 90) $days = 90;
            
            $startDate = strtotime("-{$days} days");
            
            // 付费订单收入
            $orderData = Db::name('paid_order')
                ->field('FROM_UNIXTIME(pay_time, "%Y-%m-%d") as date, SUM(amount) as revenue, COUNT(*) as count')
                ->where('status', 2) // 已支付
                ->where('pay_time', '>=', $startDate)
                ->group('date')
                ->order('date')
                ->select()
                ->toArray();
            
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
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 会员增长趋势 - V2.8新增
     */
    public function getMemberGrowth()
    {
        try {
            $days = (int) $this->request->get('days', 7);
            if ($days > 90) $days = 90;
            
            $startDate = strtotime("-{$days} days");
            
            $memberData = Db::name('member')
                ->field('FROM_UNIXTIME(create_time, "%Y-%m-%d") as date, COUNT(*) as count')
                ->where('create_time', '>=', $startDate)
                ->group('date')
                ->order('date')
                ->select()
                ->toArray();
            
            $memberMap = array_column($memberData, 'count', 'date');
            
            // 累计会员数
            $totalMembers = Db::name('member')->where('create_time', '<', $startDate)->count();
            
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
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 积分统计 - V2.8新增
     */
    public function getPointsStats()
    {
        try {
            $days = (int) $this->request->get('days', 7);
            if ($days > 90) $days = 90;
            
            $startDate = strtotime("-{$days} days");
            
            $pointsData = Db::name('points_log')
                ->field('FROM_UNIXTIME(create_time, "%Y-%m-%d") as date, SUM(CASE WHEN points > 0 THEN points ELSE 0 END) as income, SUM(CASE WHEN points < 0 THEN ABS(points) ELSE 0 END) as expense, COUNT(*) as count')
                ->where('create_time', '>=', $startDate)
                ->group('date')
                ->order('date')
                ->select()
                ->toArray();
            
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
            
            // 总积分流通量
            $totalPoints = Db::name('member')->sum('points') ?? 0;
            
            return json(['code' => 0, 'data' => ['trend' => $result, 'total' => $totalPoints]]);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 内容排行Top20 - V2.8新增
     */
    public function getContentRank()
    {
        try {
            $days = (int) $this->request->get('days', 30);
            $limit = min((int) $this->request->get('limit', 20), 50);
            
            $startDate = strtotime("-{$days} days");
            
            $data = Db::name('content')
                ->field('id, title, views, comment_count, create_time')
                ->where('status', 2)
                ->where('create_time', '>=', $startDate)
                ->order('views', 'desc')
                ->limit($limit)
                ->select();
            
            return json(['code' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 导出Excel报表 - V2.8新增
     */
    public function exportExcel()
    {
        try {
            $type = $this->request->get('type', 'overview');
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            if ($type === 'trend') {
                $sheet->setTitle('PV/UV趋势');
                $sheet->setCellValue('A1', '日期');
                $sheet->setCellValue('B1', 'PV');
                $sheet->setCellValue('C1', 'UV');
                
                $trend = $this->getTrendData(30);
                $row = 2;
                foreach ($trend as $item) {
                    $sheet->setCellValue('A' . $row, $item['date']);
                    $sheet->setCellValue('B' . $row, $item['pv']);
                    $sheet->setCellValue('C' . $row, $item['uv']);
                    $row++;
                }
            } else if ($type === 'content') {
                $sheet->setTitle('内容排行');
                $sheet->setCellValue('A1', 'ID');
                $sheet->setCellValue('B1', '标题');
                $sheet->setCellValue('C1', '浏览量');
                $sheet->setCellValue('D1', '评论数');
                
                $content = $this->getContentRankData(30);
                $row = 2;
                foreach ($content as $item) {
                    $sheet->setCellValue('A' . $row, $item['id']);
                    $sheet->setCellValue('B' . $row, $item['title']);
                    $sheet->setCellValue('C' . $row, $item['views']);
                    $sheet->setCellValue('D' . $row, $item['comment_count']);
                    $row++;
                }
            }
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="report_' . $type . '_' . date('Ymd') . '.xlsx"');
            header('Cache-Control: max-age=0');
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 获取趋势数据（内部方法）
     */
    protected function getTrendData(int $days): array
    {
        $startDate = strtotime("-{$days} days");
        
        $pvQuery = Db::name('visit_log')
            ->field('FROM_UNIXTIME(visit_time, "%Y-%m-%d") as date, COUNT(*) as pv')
            ->where('visit_time', '>=', $startDate)
            ->group('date')
            ->order('date')
            ->select()
            ->toArray();
        
        $uvQuery = Db::name('visit_log')
            ->field('FROM_UNIXTIME(visit_time, "%Y-%m-%d") as date, COUNT(DISTINCT ip) as uv')
            ->where('visit_time', '>=', $startDate)
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
     * 获取内容排行数据（内部方法）
     */
    protected function getContentRankData(int $days): array
    {
        $startDate = strtotime("-{$days} days");
        
        return Db::name('content')
            ->field('id, title, views, comment_count')
            ->where('status', 2)
            ->where('create_time', '>=', $startDate)
            ->order('views', 'desc')
            ->limit(50)
            ->select()
            ->toArray();
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
            return json(['code' => 1, 'msg' => $e->getMessage()]);
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
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}
