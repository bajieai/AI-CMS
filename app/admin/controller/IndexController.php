<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Content;
use app\common\model\User;

/**
 * 后台首页控制器
 */
class IndexController extends AdminBaseController
{
    protected array $noNeedPermission = ['index'];

    /**
     * 后台仪表盘
     */
    public function index()
    {
        // 使用聚合查询一次性获取内容统计，减少数据库往返
        $contentStats = Content::field([
            'COUNT(*) as total',
            'SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as published',
            'SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as draft',
            'SUM(CASE WHEN status = 2 AND create_time >= CURDATE() THEN 1 ELSE 0 END) as today',
        ])->where('status', '>=', 0)->find();

        $contentCount   = (int) ($contentStats['total'] ?? 0);
        $publishedCount = (int) ($contentStats['published'] ?? 0);
        $draftCount     = (int) ($contentStats['draft'] ?? 0);
        $todayCount     = (int) ($contentStats['today'] ?? 0);

        $userCount = User::where('status', 1)->count();

        // 最近7天发布趋势 —— 使用单次查询获取全部日期数据
        $trend = [];
        $maxCount = 0;
        $startDate = date('Y-m-d', strtotime('-6 days'));

        $trendRaw = Content::fieldRaw('DATE(create_time) as date, COUNT(*) as day_count')
            ->where('status', 2)
            ->whereBetween('create_time', [$startDate . ' 00:00:00', date('Y-m-d') . ' 23:59:59'])
            ->group('DATE(create_time)')
            ->select()
            ->toArray();

        $trendRows = [];
        foreach ($trendRaw as $row) {
            $trendRows[$row['date']] = (int) $row['day_count'];
        }

        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $count = $trendRows[$date] ?? 0;
            $trend[] = ['date' => substr($date, 5), 'count' => $count];
            if ($count > $maxCount) {
                $maxCount = $count;
            }
        }

        // 最新操作日志
        $latestLogs = \app\common\model\Log::order('id', 'desc')->limit(6)->select();

        $this->assign([
            'content_count' => $contentCount,
            'published_count' => $publishedCount,
            'draft_count' => $draftCount,
            'user_count' => $userCount,
            'today_count' => $todayCount,
            'trend' => $trend,
            'trend_max' => $maxCount ?: 1,
            'latest_logs' => $latestLogs,
        ]);

        return $this->view('/dashboard');
    }
}
