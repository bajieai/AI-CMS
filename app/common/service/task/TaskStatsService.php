<?php

declare(strict_types=1);

namespace app\common\service\task;

use think\facade\Db;
use think\facade\Cache;

/**
 * 任务统计服务 — V2.9.36 Sprint TASK-4
 *
 * 概览、效率分析、瓶颈分析、趋势分析、报告生成
 */
class TaskStatsService
{
    private const CACHE_TAG  = 'task_stats';
    private const TABLE_TASK = 'task';

    /**
     * 概览统计
     *
     * @return array
     */
    public function getOverview(): array
    {
        $cacheKey = 'task_stats_overview';
        return Cache::remember($cacheKey, function () {
            $total = Db::name(self::TABLE_TASK)->count();
            $completed = Db::name(self::TABLE_TASK)->where('status', 'completed')->count();
            $overdue   = Db::name(self::TABLE_TASK)->where('status', 'overdue')->count();
            $inProgress = Db::name(self::TABLE_TASK)->where('status', 'in_progress')->count();
            $pending    = Db::name(self::TABLE_TASK)->where('status', 'pending')->count();

            // 按时完成率
            $onTime = Db::name(self::TABLE_TASK)
                ->where('status', 'completed')
                ->whereNotNull('deadline')
                ->where('complete_time', '<=', Db::raw('deadline'))
                ->count();

            return [
                'total'          => $total,
                'completed'      => $completed,
                'in_progress'    => $inProgress,
                'pending'        => $pending,
                'overdue'        => $overdue,
                'completion_rate'=> $total > 0 ? round($completed / $total * 100, 1) : 0,
                'on_time_rate'   => $completed > 0 ? round($onTime / $completed * 100, 1) : 0,
            ];
        }, 300);
    }

    /**
     * 效率分析
     *
     * @param int $days
     * @return array
     */
    public function getEfficiencyAnalysis(int $days = 30): array
    {
        $cacheKey = 'task_stats_efficiency_' . $days;
        return Cache::remember($cacheKey, function () use ($days) {
            $startDate = date('Y-m-d 00:00:00', strtotime("-{$days} days"));

            // 按负责人统计
            $byAssignee = Db::name(self::TABLE_TASK)
                ->field('assignee_id, COUNT(*) as total, SUM(CASE WHEN status="completed" THEN 1 ELSE 0 END) as completed')
                ->where('create_time', '>=', $startDate)
                ->group('assignee_id')
                ->select()
                ->toArray();

            // 按类型统计
            $byType = Db::name(self::TABLE_TASK)
                ->field('type, COUNT(*) as total, SUM(CASE WHEN status="completed" THEN 1 ELSE 0 END) as completed')
                ->where('create_time', '>=', $startDate)
                ->group('type')
                ->select()
                ->toArray();

            // 按优先级统计
            $byPriority = Db::name(self::TABLE_TASK)
                ->field('priority, COUNT(*) as total, SUM(CASE WHEN status="completed" THEN 1 ELSE 0 END) as completed')
                ->where('create_time', '>=', $startDate)
                ->group('priority')
                ->select()
                ->toArray();

            // 平均完成时长
            $avgDuration = Db::name(self::TABLE_TASK)
                ->where('status', 'completed')
                ->where('complete_time', '>=', $startDate)
                ->whereNotNull('start_time')
                ->avg('TIMESTAMPDIFF(HOUR, start_time, complete_time)');

            return [
                'period'        => $days . '天',
                'by_assignee'   => $byAssignee,
                'by_type'       => $byType,
                'by_priority'   => $byPriority,
                'avg_duration_hours' => round((float)$avgDuration, 1),
            ];
        }, 300);
    }

    /**
     * 瓶颈分析
     *
     * @return array
     */
    public function getBottleneckAnalysis(): array
    {
        $cacheKey = 'task_stats_bottleneck';
        return Cache::remember($cacheKey, function () {
            // 按状态统计停留时间
            $byStatus = Db::name(self::TABLE_TASK)
                ->field('status, COUNT(*) as count, AVG(TIMESTAMPDIFF(HOUR, update_time, NOW())) as avg_stay_hours')
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->group('status')
                ->select()
                ->toArray();

            // 超期任务列表
            $overdueTasks = Db::name(self::TABLE_TASK)
                ->where('status', 'overdue')
                ->order('deadline', 'asc')
                ->limit(20)
                ->select()
                ->toArray();

            // 长期未更新任务
            $stalledThreshold = date('Y-m-d H:i:s', strtotime('-7 days'));
            $stalledTasks = Db::name(self::TABLE_TASK)
                ->where('status', 'in', ['in_progress', 'pending'])
                ->where('update_time', '<', $stalledThreshold)
                ->count();

            return [
                'by_status'      => $byStatus,
                'overdue_tasks'  => $overdueTasks,
                'overdue_count'  => count($overdueTasks),
                'stalled_count'  => $stalledTasks,
                'bottleneck_status' => $this->identifyBottleneck($byStatus),
            ];
        }, 300);
    }

    /**
     * 趋势分析
     *
     * @param int $days
     * @return array
     */
    public function getTrend(int $days = 30): array
    {
        $cacheKey = 'task_stats_trend_' . $days;
        return Cache::remember($cacheKey, function () use ($days) {
            $trend = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $dayStart = $date . ' 00:00:00';
                $dayEnd   = $date . ' 23:59:59';

                $created   = Db::name(self::TABLE_TASK)->whereBetweenTime('create_time', $dayStart, $dayEnd)->count();
                $completed = Db::name(self::TABLE_TASK)->where('status', 'completed')->whereBetweenTime('complete_time', $dayStart, $dayEnd)->count();
                $overdue   = Db::name(self::TABLE_TASK)->where('status', 'overdue')->whereBetweenTime('update_time', $dayStart, $dayEnd)->count();

                $trend[] = [
                    'date'      => $date,
                    'created'   => $created,
                    'completed' => $completed,
                    'overdue'   => $overdue,
                ];
            }
            return $trend;
        }, 300);
    }

    /**
     * 获取报告（日报/周报/月报）
     *
     * @param string $type daily|weekly|monthly
     * @return array
     */
    public function getReport(string $type = 'daily'): array
    {
        $overview = $this->getOverview();

        $days = match ($type) {
            'weekly'  => 7,
            'monthly' => 30,
            default   => 1,
        };

        $efficiency  = $this->getEfficiencyAnalysis($days);
        $bottleneck  = $this->getBottleneckAnalysis();
        $trend       = $this->getTrend(min($days, 30));

        return [
            'code' => 0,
            'msg'  => '',
            'data' => [
                'report_type'  => $type,
                'generated_at' => date('Y-m-d H:i:s'),
                'overview'     => $overview,
                'efficiency'   => $efficiency,
                'bottleneck'   => $bottleneck,
                'trend'        => $trend,
            ],
        ];
    }

    /**
     * 导出报告
     *
     * @param string $type
     * @param string $format csv|json
     * @return array
     */
    public function exportReport(string $type, string $format = 'csv'): array
    {
        $report = $this->getReport($type);

        if ($format === 'json') {
            return ['code' => 0, 'msg' => '', 'data' => $report['data']];
        }

        // CSV格式: 展平概览数据
        $overview = $report['data']['overview'];
        $csvData  = "指标,数值\n";
        foreach ($overview as $key => $val) {
            $csvData .= "{$key},{$val}\n";
        }

        return ['code' => 0, 'msg' => '', 'data' => ['csv' => $csvData, 'filename' => "task_report_{$type}_" . date('Ymd') . '.csv']];
    }

    // ======== 内部方法 ========

    /**
     * 识别瓶颈环节
     */
    private function identifyBottleneck(array $byStatus): string
    {
        $maxStay = 0;
        $bottleneck = '';
        foreach ($byStatus as $s) {
            $stay = (float)($s['avg_stay_hours'] ?? 0);
            if ($stay > $maxStay) {
                $maxStay = $stay;
                $bottleneck = $s['status'];
            }
        }
        return $bottleneck ?: 'none';
    }
}
