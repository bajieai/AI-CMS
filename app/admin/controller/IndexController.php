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
        // 统计数据
        $contentCount = Content::where('status', '>=', 0)->count();
        $publishedCount = Content::where('status', 2)->count();
        $draftCount = Content::where('status', 0)->count();
        $userCount = User::where('status', 1)->count();
        $todayCount = Content::where('status', 2)
            ->whereBetween('create_time', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])
            ->count();

        // 最近7天发布趋势
        $trend = [];
        $maxCount = 0;
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $count = Content::where('status', 2)
                ->whereBetween('create_time', [$date . ' 00:00:00', $date . ' 23:59:59'])
                ->count();
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
