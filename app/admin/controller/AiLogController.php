<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\AiLog;

/**
 * AI调用日志后台控制器
 */
class AiLogController extends AdminBaseController
{
    /**
     * 日志列表
     */
    public function index()
    {
        $page = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 20);
        $status = $this->request->get('status', '');
        $taskType = $this->request->get('task_type', '');

        $query = AiLog::order('id', 'desc');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }
        if ($taskType !== '') {
            $query->where('task_type', $taskType);
        }

        $list = $query->page($page, $limit)->select();
        $total = $query->count();

        if ($this->request->isAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list, 'count' => $total]);
        }
        $this->assign('list', $list);
        return $this->view('/ai_log_index');
    }

    /**
     * 统计概览
     */
    public function stats()
    {
        $today = date('Y-m-d');
        $todayStart = strtotime($today);

        $totalCalls = AiLog::count();
        $todayCalls = AiLog::where('create_time', '>=', $todayStart)->count();
        $todaySuccess = AiLog::where('create_time', '>=', $todayStart)->where('status', 1)->count();
        $todayFailed = AiLog::where('create_time', '>=', $todayStart)->where('status', 2)->count();
        $todayFallback = AiLog::where('create_time', '>=', $todayStart)->where('status', 3)->count();

        $avgDuration = AiLog::where('create_time', '>=', $todayStart)
            ->where('status', 1)
            ->avg('duration_ms');

        return json([
            'code' => 0,
            'data' => [
                'total_calls'   => $totalCalls,
                'today_calls'   => $todayCalls,
                'today_success' => $todaySuccess,
                'today_failed'  => $todayFailed,
                'today_fallback' => $todayFallback,
                'avg_duration_ms' => round($avgDuration, 0),
            ],
        ]);
    }

    /**
     * 清理30天前的日志
     */
    public function cleanup()
    {
        $before = strtotime('-30 days');
        $count = AiLog::where('create_time', '<', $before)->delete();
        return json(['code' => 0, 'msg' => "已清理 {$count} 条历史日志"]);
    }
}
