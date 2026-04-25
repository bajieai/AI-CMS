<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Content;
use app\common\model\User;
use think\facade\Db;

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
        $pendingCount = Content::where('status', 1)->count();

        // 内容类型分布
        $typeDistRaw = Content::field('type, COUNT(*) as count')
            ->where('status', 2)
            ->group('type')
            ->select()
            ->toArray();
        $typeDistribution = [];
        $typeMap = [1 => '产品', 2 => '案例', 3 => '新闻', 4 => '下载', 5 => '招聘', 6 => '单页'];
        $typeColors = [1 => '#2563eb', 2 => '#06b6d4', 3 => '#22c55e', 4 => '#f59e0b', 5 => '#64748b', 6 => '#1e293b'];
        $totalPublished = 0;
        foreach ($typeDistRaw as $item) {
            $totalPublished += (int) $item['count'];
        }
        foreach ($typeDistRaw as $item) {
            $typeDistribution[] = [
                'type' => (int) $item['type'],
                'name' => $typeMap[$item['type']] ?? '未知',
                'count' => (int) $item['count'],
                'color' => $typeColors[$item['type']] ?? '#94a3b8',
                'percent' => $totalPublished > 0 ? round($item['count'] / $totalPublished * 100, 1) : 0,
            ];
        }

        // 热门内容 TOP5
        $hotContents = Content::with('cate')
            ->where('status', 2)
            ->order('views', 'desc')
            ->limit(5)
            ->select();

        // 系统信息增强
        $dbConfig = \think\facade\Config::get('database.default');
        $dbName = \think\facade\Config::get('database.connections.' . $dbConfig . '.database', '');
        $dbSize = 0;
        if ($dbName) {
            $dbSizeResult = Db::query("SELECT SUM(data_length + index_length) as size FROM information_schema.tables WHERE table_schema = ?", [$dbName]);
            $dbSize = (int) ($dbSizeResult[0]['size'] ?? 0);
        }

        $diskFree = disk_free_space(root_path());
        $diskTotal = disk_total_space(root_path());

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
            'pending_count' => $pendingCount,
            'trend' => $trend,
            'trend_max' => $maxCount ?: 1,
            'latest_logs' => $latestLogs,
            'type_distribution' => $typeDistribution,
            'hot_contents' => $hotContents,
            'db_size' => $dbSize,
            'disk_free' => $diskFree,
            'disk_total' => $diskTotal,
        ]);

        return $this->view('/dashboard');
    }
}
