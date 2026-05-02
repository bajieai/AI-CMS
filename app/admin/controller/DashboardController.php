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

            $todayPV = Db::name('visit_log')->where('create_time', '>=', $todayStart)->count();
            $todayUV = Db::name('visit_log')->where('create_time', '>=', $todayStart)->group('ip')->count();

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
     * 设备分布
     */
    public function getDeviceStats()
    {
        $todayStart = strtotime('today');
        $data = Db::name('visit_log')
            ->field('device, COUNT(*) as count')
            ->where('create_time', '>=', $todayStart)
            ->group('device')
            ->select();

        return json(['code' => 0, 'data' => $data]);
    }
}
