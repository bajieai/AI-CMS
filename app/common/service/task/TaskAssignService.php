<?php

declare(strict_types=1);

namespace app\common\service\task;

use think\facade\Db;
use think\facade\Cache;

/**
 * 任务分配增强服务 — V2.9.36 Sprint TASK-1
 *
 * 支持多人分配、转派、批量分配、自动分配
 */
class TaskAssignService
{
    private const CACHE_TAG  = 'task_assign';
    private const TABLE_TASK = 'task';
    private const TABLE_LOG  = 'task_assign_log';

    /**
     * 分配任务（多人分配）
     *
     * @param int   $taskId
     * @param array $assignData assignee_id, collaborators[], reviewer_id, notifiers[]
     * @return array
     */
    public function assignTask(int $taskId, array $assignData): array
    {
        $task = Db::name(self::TABLE_TASK)->find($taskId);
        if (!$task) {
            return ['code' => 1, 'msg' => '任务不存在', 'data' => null];
        }

        $update = [
            'assignee_id' => (int)($assignData['assignee_id'] ?? 0),
            'reviewer_id' => (int)($assignData['reviewer_id'] ?? 0),
            'update_time' => date('Y-m-d H:i:s'),
        ];

        if (isset($assignData['collaborators']) && is_array($assignData['collaborators'])) {
            $update['collaborators'] = json_encode(array_map('intval', $assignData['collaborators']));
        }
        if (isset($assignData['notifiers']) && is_array($assignData['notifiers'])) {
            $update['notifiers'] = json_encode(array_map('intval', $assignData['notifiers']));
        }

        // 如果任务状态是 pending，分配后自动变为 in_progress
        if ($task['status'] === 'pending') {
            $update['status'] = 'in_progress';
            $update['start_time'] = date('Y-m-d H:i:s');
        }

        Db::name(self::TABLE_TASK)->where('id', $taskId)->update($update);

        // 记录分配历史
        Db::name(self::TABLE_LOG)->insert([
            'task_id'      => $taskId,
            'from_user_id' => (int)$task['assignee_id'],
            'to_user_id'   => $update['assignee_id'],
            'action'       => 'assign',
            'reason'       => '初始分配',
            'operator_id'  => (int)($assignData['operator_id'] ?? 0),
            'create_time'  => date('Y-m-d H:i:s'),
        ]);

        Cache::clear();

        return ['code' => 0, 'msg' => '分配成功', 'data' => $update];
    }

    /**
     * 转派任务
     *
     * @param int    $taskId
     * @param int    $newAssigneeId
     * @param string $reason
     * @return array
     */
    public function reassignTask(int $taskId, int $newAssigneeId, string $reason = ''): array
    {
        $task = Db::name(self::TABLE_TASK)->find($taskId);
        if (!$task) {
            return ['code' => 1, 'msg' => '任务不存在', 'data' => null];
        }
        if ($newAssigneeId <= 0) {
            return ['code' => 1, 'msg' => '新负责人ID无效', 'data' => null];
        }

        $oldAssigneeId = (int)$task['assignee_id'];

        Db::name(self::TABLE_TASK)->where('id', $taskId)->update([
            'assignee_id' => $newAssigneeId,
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        Db::name(self::TABLE_LOG)->insert([
            'task_id'      => $taskId,
            'from_user_id' => $oldAssigneeId,
            'to_user_id'   => $newAssigneeId,
            'action'       => 'reassign',
            'reason'       => $reason,
            'operator_id'  => 0,
            'create_time'  => date('Y-m-d H:i:s'),
        ]);

        Cache::clear();

        return ['code' => 0, 'msg' => '转派成功', 'data' => ['from' => $oldAssigneeId, 'to' => $newAssigneeId]];
    }

    /**
     * 批量分配
     *
     * @param array $taskIds
     * @param int   $assigneeId
     * @return array
     */
    public function batchAssign(array $taskIds, int $assigneeId): array
    {
        if (empty($taskIds) || $assigneeId <= 0) {
            return ['code' => 1, 'msg' => '参数无效', 'data' => null];
        }

        $success = 0;
        $failed  = 0;
        $now     = date('Y-m-d H:i:s');

        foreach ($taskIds as $tid) {
            $tid = (int)$tid;
            $task = Db::name(self::TABLE_TASK)->find($tid);
            if (!$task) {
                $failed++;
                continue;
            }

            $update = ['assignee_id' => $assigneeId, 'update_time' => $now];
            if ($task['status'] === 'pending') {
                $update['status'] = 'in_progress';
                $update['start_time'] = $now;
            }
            Db::name(self::TABLE_TASK)->where('id', $tid)->update($update);

            Db::name(self::TABLE_LOG)->insert([
                'task_id'      => $tid,
                'from_user_id' => (int)$task['assignee_id'],
                'to_user_id'   => $assigneeId,
                'action'       => 'batch_assign',
                'reason'       => '批量分配',
                'operator_id'  => 0,
                'create_time'  => $now,
            ]);
            $success++;
        }

        Cache::clear();

        return ['code' => 0, 'msg' => "批量分配完成: 成功{$success}个, 失败{$failed}个", 'data' => ['success' => $success, 'failed' => $failed]];
    }

    /**
     * 自动分配
     *
     * @param int    $taskId
     * @param string $strategy balanced|round_robin|skill_match
     * @return array
     */
    public function autoAssign(int $taskId, string $strategy = 'balanced'): array
    {
        $task = Db::name(self::TABLE_TASK)->find($taskId);
        if (!$task) {
            return ['code' => 1, 'msg' => '任务不存在', 'data' => null];
        }

        // 获取候选用户列表（有任务管理权限的用户）
        $candidates = $this->getCandidateUsers();
        if (empty($candidates)) {
            return ['code' => 1, 'msg' => '没有可用的候选用户', 'data' => null];
        }

        $assigneeId = 0;

        switch ($strategy) {
            case 'round_robin':
                // 轮询: 找到上次分配的用户，分配给下一个
                $lastAssign = Db::name(self::TABLE_LOG)
                    ->where('action', 'in', ['assign', 'auto_assign'])
                    ->order('id', 'desc')
                    ->find();
                $lastUserId = $lastAssign ? (int)$lastAssign['to_user_id'] : 0;
                $assigneeId = $this->getNextRoundRobin($candidates, $lastUserId);
                break;

            case 'skill_match':
                // 技能匹配: 根据 task type 匹配有相关技能的用户
                $assigneeId = $this->matchBySkill($candidates, $task['type'] ?? 'general');
                break;

            case 'balanced':
            default:
                // 均衡: 分配给当前任务数最少的用户
                $assigneeId = $this->getLeastBusyUser($candidates);
                break;
        }

        if ($assigneeId <= 0) {
            return ['code' => 1, 'msg' => '自动分配失败，无匹配用户', 'data' => null];
        }

        $update = [
            'assignee_id' => $assigneeId,
            'status'      => $task['status'] === 'pending' ? 'in_progress' : $task['status'],
            'update_time' => date('Y-m-d H:i:s'),
        ];
        if ($task['status'] === 'pending') {
            $update['start_time'] = date('Y-m-d H:i:s');
        }

        Db::name(self::TABLE_TASK)->where('id', $taskId)->update($update);

        Db::name(self::TABLE_LOG)->insert([
            'task_id'      => $taskId,
            'from_user_id' => (int)$task['assignee_id'],
            'to_user_id'   => $assigneeId,
            'action'       => 'auto_assign',
            'reason'       => '自动分配(' . $strategy . ')',
            'operator_id'   => 0,
            'create_time'   => date('Y-m-d H:i:s'),
        ]);

        Cache::clear();

        return ['code' => 0, 'msg' => '自动分配成功', 'data' => ['assignee_id' => $assigneeId, 'strategy' => $strategy]];
    }

    /**
     * 获取用户当前任务数
     *
     * @param int $userId
     * @return int
     */
    public function getAssigneeWorkload(int $userId): int
    {
        $cacheKey = 'task_workload_' . $userId;
        return (int)Cache::remember($cacheKey, function () use ($userId) {
            return Db::name(self::TABLE_TASK)
                ->where('assignee_id', $userId)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count();
        }, 60);
    }

    /**
     * 获取分配历史
     *
     * @param int $taskId
     * @return array
     */
    public function getAssignHistory(int $taskId): array
    {
        $list = Db::name(self::TABLE_LOG)
            ->where('task_id', $taskId)
            ->order('id', 'desc')
            ->select()
            ->toArray();
        return ['code' => 0, 'msg' => '', 'data' => $list];
    }

    // ======== 内部方法 ========

    /**
     * 获取候选用户列表
     */
    private function getCandidateUsers(): array
    {
        // 从 admin 用户表中获取有管理权限的用户
        $users = Db::name('admin_user')
            ->where('status', 1)
            ->column('id,username');
        return $users ?: [];
    }

    /**
     * 轮询分配：返回下一个用户ID
     */
    private function getNextRoundRobin(array $candidates, int $lastUserId): int
    {
        if (count($candidates) === 1) {
            return (int)$candidates[0];
        }
        $found = false;
        foreach ($candidates as $uid) {
            if ($found) {
                return (int)$uid;
            }
            if ((int)$uid === $lastUserId) {
                $found = true;
            }
        }
        return (int)$candidates[0];
    }

    /**
     * 技能匹配：根据任务类型匹配用户
     */
    private function matchBySkill(array $candidates, string $taskType): int
    {
        // 简单实现: 返回第一个候选用户
        // 实际项目中可查 user_skill 表匹配
        return !empty($candidates) ? (int)$candidates[0] : 0;
    }

    /**
     * 均衡分配：返回任务最少的用户
     */
    private function getLeastBusyUser(array $candidates): int
    {
        if (empty($candidates)) {
            return 0;
        }
        $minWorkload = PHP_INT_MAX;
        $bestUser    = (int)$candidates[0];

        foreach ($candidates as $uid) {
            $workload = $this->getAssigneeWorkload((int)$uid);
            if ($workload < $minWorkload) {
                $minWorkload = $workload;
                $bestUser    = (int)$uid;
            }
        }
        return $bestUser;
    }
}
