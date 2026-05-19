<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Content;
use app\common\model\User;
use think\facade\Cache;
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
        // V2.9.4 性能优化：Dashboard统计数据缓存5分钟（变化不频繁，减少首页12+次DB查询）
        $stats = Cache::remember('admin_dashboard_stats', function () {
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

            return [
                'content_count'     => $contentCount,
                'published_count'   => $publishedCount,
                'draft_count'       => $draftCount,
                'today_count'       => $todayCount,
                'user_count'        => $userCount,
                'pending_count'     => $pendingCount,
                'type_distribution' => $typeDistribution,
                'db_size'           => $dbSize,
                'disk_free'         => $diskFree,
                'disk_total'        => $diskTotal,
                'trend'             => $trend,
                'trend_max'         => $maxCount ?: 1,
                'plugin_count'      => \app\common\model\Plugin::count(),
                'member_count'      => \app\common\model\Member::count(),
                'backup_count'      => count(glob(runtime_path() . 'backup/*') ?: []),
            ];
        }, 300);

        extract($stats);

        // 热门内容 TOP5（实时查询，变化较快）
        $hotContents = Content::with('cate')
            ->where('status', 2)
            ->order('views', 'desc')
            ->limit(5)
            ->select();

        // 最新操作日志（实时查询）
        $latestLogs = \app\common\model\Log::order('id', 'desc')->limit(6)->select();

        // V2.3 定时发布队列（实时查询）
        $publishQueue = Content::where('publish_time', '>', 0)
            ->where('status', 0)
            ->where('publish_time', '>', time())
            ->order('publish_time', 'asc')
            ->limit(10)
            ->select();

        $this->assign([
            'content_count'     => $content_count,
            'published_count'   => $published_count,
            'draft_count'       => $draft_count,
            'user_count'        => $user_count,
            'today_count'       => $today_count,
            'pending_count'     => $pending_count,
            'trend'             => $trend,
            'trend_max'         => $trend_max,
            'latest_logs'       => $latestLogs,
            'type_distribution' => $type_distribution,
            'hot_contents'      => $hotContents,
            'db_size'           => $db_size,
            'disk_free'         => $disk_free,
            'disk_total'        => $disk_total,
            'publish_queue'     => $publishQueue,
            'plugin_count'      => $plugin_count,
            'member_count'      => $member_count,
            'backup_count'      => $backup_count,
        ]);

        return $this->view('/dashboard');
    }
}
