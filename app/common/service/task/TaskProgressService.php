<?php

declare(strict_types=1);

namespace app\common\service\task;

use think\facade\Db;
use think\facade\Cache;

/**
 * 任务进度跟踪服务 — V2.9.36 Sprint TASK-2
 *
 * 支持进度更新、里程碑管理、甘特图数据、进度历史、报告生成
 */
class TaskProgressService
{
    private const CACHE_TAG  = 'task_progress';
    private const TABLE_TASK = 'task';
    private const TABLE_LOG  = 'task_progress_log';

    /** 进度颜色映射 */
    private const PROGRESS_COLORS = [
        [0,  30,  'danger'],   // 红
        [31, 60,  'warning'],  // 黄
        [61, 90,  'primary'],  // 蓝
        [91, 100, 'success'],  // 绿
    ];

    /**
     * 更新进度
     *
     * @param int    $taskId
     * @param int    $progress 0-100
     * @param string $note
     * @return array
     */
    public function updateProgress(int $taskId, int $progress, string $note = ''): array
    {
        $task = Db::name(self::TABLE_TASK)->find($taskId);
        if (!$task) {
            return ['code' => 1, 'msg' => '任务不存在', 'data' => null];
        }

        $progress = max(0, min(100, $progress));
        $now = date('Y-m-d H:i:s');

        $update = [
            'progress'     => $progress,
            'progress_note'=> $note,
            'update_time'  => $now,
        ];

        // 进度达到100%时自动完成
        if ($progress >= 100 && $task['status'] !== 'completed') {
            $update['status']         = 'completed';
            $update['complete_time']  = $now;
        } elseif ($progress > 0 && $task['status'] === 'pending') {
            $update['status']     = 'in_progress';
            $update['start_time'] = $now;
        }

        Db::name(self::TABLE_TASK)->where('id', $taskId)->update($update);

        // 记录进度历史
        Db::name(self::TABLE_LOG)->insert([
            'task_id'     => $taskId,
            'progress'    => $progress,
            'note'        => $note,
            'operator_id' => 0,
            'create_time' => $now,
        ]);

        Cache::clear();

        return ['code' => 0, 'msg' => '进度更新成功', 'data' => $update];
    }

    /**
     * 获取进度（含颜色）
     *
     * @param int $taskId
     * @return array
     */
    public function getProgress(int $taskId): array
    {
        $task = Db::name(self::TABLE_TASK)->find($taskId);
        if (!$task) {
            return ['code' => 1, 'msg' => '任务不存在', 'data' => null];
        }

        $progress = (int)($task['progress'] ?? 0);
        $color = $this->getProgressColor($progress);

        $data = [
            'task_id'      => $taskId,
            'title'        => $task['title'],
            'progress'     => $progress,
            'color'        => $color,
            'status'       => $task['status'],
            'progress_note'=> $task['progress_note'] ?? '',
            'deadline'     => $task['deadline'] ?? null,
            'start_time'   => $task['start_time'] ?? null,
            'complete_time'=> $task['complete_time'] ?? null,
        ];

        return ['code' => 0, 'msg' => '', 'data' => $data];
    }

    /**
     * 添加里程碑
     *
     * @param int   $taskId
     * @param array $milestone name, due_date, description
     * @return array
     */
    public function addMilestone(int $taskId, array $milestone): array
    {
        $task = Db::name(self::TABLE_TASK)->find($taskId);
        if (!$task) {
            return ['code' => 1, 'msg' => '任务不存在', 'data' => null];
        }

        $milestones = $this->parseJson($task['milestones'] ?? null);
        $milestone['id'] = count($milestones) > 0 ? max(array_column($milestones, 'id')) + 1 : 1;
        $milestone['completed'] = false;
        $milestone['created_at'] = date('Y-m-d H:i:s');
        $milestones[] = $milestone;

        Db::name(self::TABLE_TASK)->where('id', $taskId)->update([
            'milestones'  => json_encode($milestones, JSON_UNESCAPED_UNICODE),
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        Cache::clear();

        return ['code' => 0, 'msg' => '里程碑添加成功', 'data' => $milestone];
    }

    /**
     * 获取里程碑列表
     *
     * @param int $taskId
     * @return array
     */
    public function getMilestones(int $taskId): array
    {
        $task = Db::name(self::TABLE_TASK)->find($taskId);
        if (!$task) {
            return ['code' => 1, 'msg' => '任务不存在', 'data' => null];
        }

        $milestones = $this->parseJson($task['milestones'] ?? null);
        return ['code' => 0, 'msg' => '', 'data' => $milestones];
    }

    /**
     * 获取甘特图数据
     *
     * @param int $taskId 0表示所有任务
     * @return array
     */
    public function getGanttData(int $taskId = 0): array
    {
        $query = Db::name(self::TABLE_TASK)
            ->field('id,title,start_time,deadline,progress,status,assignee_id')
            ->order('start_time', 'asc');

        if ($taskId > 0) {
            $query->where('id', $taskId);
        }

        $tasks = $query->select()->toArray();

        $ganttData = [];
        foreach ($tasks as $t) {
            $ganttData[] = [
                'id'         => (int)$t['id'],
                'title'      => $t['title'],
                'start'      => $t['start_time'] ?: date('Y-m-d'),
                'end'        => $t['deadline'] ?: date('Y-m-d', strtotime('+7 days')),
                'progress'   => (int)($t['progress'] ?? 0),
                'color'      => $this->getProgressColor((int)($t['progress'] ?? 0)),
                'status'     => $t['status'],
                'assignee'   => (int)$t['assignee_id'],
            ];
        }

        return ['code' => 0, 'msg' => '', 'data' => $ganttData];
    }

    /**
     * 获取进度历史
     *
     * @param int $taskId
     * @return array
     */
    public function getProgressHistory(int $taskId): array
    {
        $list = Db::name(self::TABLE_LOG)
            ->where('task_id', $taskId)
            ->order('id', 'desc')
            ->select()
            ->toArray();
        return ['code' => 0, 'msg' => '', 'data' => $list];
    }

    /**
     * 生成报告
     *
     * @param int    $taskId
     * @param string $type daily|weekly|monthly
     * @return array
     */
    public function generateReport(int $taskId, string $type = 'daily'): array
    {
        $task = Db::name(self::TABLE_TASK)->find($taskId);
        if (!$task) {
            return ['code' => 1, 'msg' => '任务不存在', 'data' => null];
        }

        $milestones = $this->parseJson($task['milestones'] ?? null);
        $collaborators = $this->parseJson($task['collaborators'] ?? null);

        $completedMilestones = array_filter($milestones, fn($m) => !empty($m['completed']));
        $pendingMilestones   = array_filter($milestones, fn($m) => empty($m['completed']));

        $report = [
            'report_type'  => $type,
            'task_id'      => $taskId,
            'title'        => $task['title'],
            'status'       => $task['status'],
            'progress'     => (int)$task['progress'],
            'color'        => $this->getProgressColor((int)$task['progress']),
            'assignee_id'  => (int)$task['assignee_id'],
            'collaborators'=> $collaborators,
            'deadline'     => $task['deadline'] ?? null,
            'start_time'   => $task['start_time'] ?? null,
            'complete_time'=> $task['complete_time'] ?? null,
            'milestones'   => [
                'total'     => count($milestones),
                'completed' => count($completedMilestones),
                'pending'   => count($pendingMilestones),
            ],
            'generated_at' => date('Y-m-d H:i:s'),
        ];

        return ['code' => 0, 'msg' => '报告生成成功', 'data' => $report];
    }

    // ======== 内部方法 ========

    /**
     * 根据进度获取颜色
     */
    private function getProgressColor(int $progress): string
    {
        foreach (self::PROGRESS_COLORS as [$min, $max, $color]) {
            if ($progress >= $min && $progress <= $max) {
                return $color;
            }
        }
        return 'secondary';
    }

    /**
     * 安全解析 JSON
     */
    private function parseJson($data): array
    {
        if (empty($data)) {
            return [];
        }
        if (is_array($data)) {
            return $data;
        }
        $decoded = json_decode($data, true);
        return is_array($decoded) ? $decoded : [];
    }
}
