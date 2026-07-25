<?php
declare(strict_types=1);

namespace app\common\service\operation;

use app\common\model\Content;
use think\facade\Db;
use think\facade\Cache;

class OperationWorkbenchService
{
    private const CACHE_TAG = 'operation_workbench';

    public function getOverview(): array
    {
        return Cache::remember('overview', function() {
            $todayStart = strtotime('today');
            return [
                'today_published' => Content::where('create_time', '>=', $todayStart)->where('status', 1)->count(),
                'pending_audit' => Content::where('status', 0)->count(),
                'low_quality' => Content::where('quality_score', '<', 60)->count(),
                'pending_distribute' => Db::name('distribution_schedule')->where('status', 'pending')->count(),
                'pending_translate' => Content::where('is_auto_translated', 0)->where('translation_of', 0)->count(),
            ];
        }, 60);
    }

    public function getTodoList(): array
    {
        return Cache::remember('todo', function() {
            $todos = [];
            $pendingAudit = Content::where('status', 0)->count();
            if ($pendingAudit > 0) $todos[] = ['type' => 'content_audit', 'priority' => 'high', 'count' => $pendingAudit, 'title' => "{$pendingAudit}篇内容待审核"];
            $lowQuality = Content::where('quality_score', '<', 60)->count();
            if ($lowQuality > 0) $todos[] = ['type' => 'quality_repair', 'priority' => 'high', 'count' => $lowQuality, 'title' => "{$lowQuality}篇内容质量报警"];
            $pendingDist = Db::name('distribution_schedule')->where('status', 'pending')->count();
            if ($pendingDist > 0) $todos[] = ['type' => 'distribute', 'priority' => 'medium', 'count' => $pendingDist, 'title' => "{$pendingDist}个分发任务待执行"];
            return $todos;
        }, 60);
    }

    public function getMetricCards(): array
    {
        return Cache::remember('metrics', function() {
            return [
                'content_health' => ['avg_score' => Db::name('content_quality_score')->avg('total_score') ?: 0, 'excellent_rate' => Content::where('quality_level', 'excellent')->count()],
                'user_activity' => ['today_active' => Db::name('member')->where('login_time', '>=', strtotime('today'))->count()],
                'distribution' => ['today_count' => Db::name('push_channel')->where('create_time', '>=', strtotime('today'))->count()],
                'paid_conversion' => ['today_revenue' => Db::name('paid_content_record')->where('status', 1)->where('create_time', '>=', strtotime('today'))->sum('amount')],
            ];
        }, 60);
    }

    public function getWeeklyReport(): array
    {
        return Cache::remember('weekly', function() {
            $weekStart = strtotime('monday this week');
            return ['published' => Content::where('create_time', '>=', $weekStart)->count(), 'views' => Content::where('update_time', '>=', $weekStart)->sum('views'), 'revenue' => Db::name('paid_content_record')->where('status', 1)->where('create_time', '>=', $weekStart)->sum('amount')];
        }, 300);
    }

    public function getAlerts(): array
    {
        return Cache::remember('alerts', function() {
            $alerts = [];
            $lowQuality = Content::where('quality_score', '<', 60)->count();
            if ($lowQuality > 10) $alerts[] = ['type' => 'quality', 'level' => 'warning', 'message' => "低质量内容({$lowQuality}篇)超过阈值"];
            $pendingAudit = Content::where('status', 0)->where('create_time', '<', strtotime('-1 day'))->count();
            if ($pendingAudit > 0) $alerts[] = ['type' => 'audit', 'level' => 'warning', 'message' => "{$pendingAudit}篇内容超过24小时未审核"];
            return $alerts;
        }, 60);
    }

    public function getCalendar(int $month): array
    {
        $monthStart = strtotime(date('Y-m-01', $month));
        $monthEnd = strtotime(date('Y-m-t', $month) . ' 23:59:59');
        return Content::where('create_time', '>=', $monthStart)->where('create_time', '<=', $monthEnd)->field('id,title,create_time')->order('create_time', 'asc')->select()->toArray();
    }
}
