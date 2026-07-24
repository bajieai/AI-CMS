<?php

declare(strict_types=1);

namespace app\common\service\task;

use think\facade\Db;
use think\facade\Cache;

/**
 * 任务催办通知服务 — V2.9.36 Sprint TASK-3
 *
 * 支持到期前提醒、超期催办、停滞检查、通知模板管理
 */
class TaskNotifyService
{
    private const CACHE_TAG       = 'task_notify';
    private const TABLE_TASK      = 'task';
    private const TABLE_TEMPLATE  = 'task_notify_template';
    private const TABLE_LOG       = 'task_notify_log';

    /** 通知类型 */
    const TYPE_REMINDER_1D = 'reminder_1d';
    const TYPE_REMINDER_3D = 'reminder_3d';
    const TYPE_OVERDUE     = 'overdue';
    const TYPE_STALLED     = 'stalled';
    const TYPE_CUSTOM      = 'custom';

    /**
     * 检查并发送通知（到期前1天/3天提醒）
     *
     * @return array
     */
    public function checkAndNotify(): array
    {
        $now    = date('Y-m-d H:i:s');
        $date3d = date('Y-m-d H:i:s', strtotime('+3 days'));
        $date1d = date('Y-m-d H:i:s', strtotime('+1 day'));

        $notified = 0;

        // 3天提醒
        $tasks3d = Db::name(self::TABLE_TASK)
            ->where('status', 'in', ['pending', 'in_progress'])
            ->where('deadline', '<=', $date3d)
            ->where('deadline', '>', $date1d)
            ->select()
            ->toArray();

        foreach ($tasks3d as $task) {
            if (!$this->hasNotifiedRecently((int)$task['id'], self::TYPE_REMINDER_3D)) {
                $this->sendNotification((int)$task['id'], (int)$task['assignee_id'], self::TYPE_REMINDER_3D,
                    "任务「{$task['title']}」将在3天内到期，请及时处理。");
                $notified++;
            }
        }

        // 1天提醒
        $tasks1d = Db::name(self::TABLE_TASK)
            ->where('status', 'in', ['pending', 'in_progress'])
            ->where('deadline', '<=', $date1d)
            ->where('deadline', '>', $now)
            ->select()
            ->toArray();

        foreach ($tasks1d as $task) {
            if (!$this->hasNotifiedRecently((int)$task['id'], self::TYPE_REMINDER_1D)) {
                $this->sendNotification((int)$task['id'], (int)$task['assignee_id'], self::TYPE_REMINDER_1D,
                    "任务「{$task['title']}」将在1天内到期，请尽快完成！");
                $notified++;
            }
        }

        return ['code' => 0, 'msg' => "提醒检查完成，发送{$notified}条通知", 'data' => ['notified' => $notified]];
    }

    /**
     * 检查超期任务并发催办
     *
     * @return array
     */
    public function checkOverdue(): array
    {
        $now = date('Y-m-d H:i:s');

        // 查找已超期但未完成的任务
        $overdueTasks = Db::name(self::TABLE_TASK)
            ->where('status', 'in', ['pending', 'in_progress'])
            ->where('deadline', '<', $now)
            ->where('deadline', 'not null')
            ->select()
            ->toArray();

        $notified = 0;
        foreach ($overdueTasks as $task) {
            // 标记为超期
            Db::name(self::TABLE_TASK)->where('id', $task['id'])->update([
                'status' => 'overdue',
                'update_time' => $now,
            ]);

            if (!$this->hasNotifiedRecently((int)$task['id'], self::TYPE_OVERDUE, 3600)) {
                $this->sendNotification((int)$task['id'], (int)$task['assignee_id'], self::TYPE_OVERDUE,
                    "任务「{$task['title']}」已超期，请立即处理！");
                $notified++;
            }
        }

        Cache::clear();

        return ['code' => 0, 'msg' => "超期检查完成，{$notified}条催办已发送", 'data' => ['overdue' => count($overdueTasks), 'notified' => $notified]];
    }

    /**
     * 检查进度停滞（3天未更新）
     *
     * @return array
     */
    public function checkStalled(): array
    {
        $stalledThreshold = date('Y-m-d H:i:s', strtotime('-3 days'));

        $stalledTasks = Db::name(self::TABLE_TASK)
            ->where('status', 'in', ['in_progress', 'pending'])
            ->where('update_time', '<', $stalledThreshold)
            ->select()
            ->toArray();

        $notified = 0;
        foreach ($stalledTasks as $task) {
            if (!$this->hasNotifiedRecently((int)$task['id'], self::TYPE_STALLED, 86400)) {
                $this->sendNotification((int)$task['id'], (int)$task['assignee_id'], self::TYPE_STALLED,
                    "任务「{$task['title']}」已3天未更新进度，请及时跟进。");
                $notified++;
            }
        }

        return ['code' => 0, 'msg' => "停滞检查完成，{$notified}条提醒已发送", 'data' => ['stalled' => count($stalledTasks), 'notified' => $notified]];
    }

    /**
     * 发送通知
     *
     * @param int    $taskId
     * @param int    $userId
     * @param string $type
     * @param string $content
     * @return array
     */
    public function sendNotification(int $taskId, int $userId, string $type, string $content): array
    {
        if ($userId <= 0) {
            return ['code' => 1, 'msg' => '用户ID无效', 'data' => null];
        }

        // 写入通知日志
        Db::name(self::TABLE_LOG)->insert([
            'task_id'     => $taskId,
            'user_id'     => $userId,
            'type'        => $type,
            'content'     => $content,
            'create_time' => date('Y-m-d H:i:s'),
        ]);

        // 写入站内消息通知
        try {
            Db::name('notification')->insert([
                'user_id'     => $userId,
                'title'       => '任务通知',
                'content'     => $content,
                'type'        => 'task',
                'biz_id'      => $taskId,
                'is_read'     => 0,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // notification 表可能不存在，忽略
        }

        return ['code' => 0, 'msg' => '通知发送成功', 'data' => null];
    }

    /**
     * 获取通知模板列表
     *
     * @return array
     */
    public function getNotifyTemplates(): array
    {
        $list = Db::name(self::TABLE_TEMPLATE)
            ->order('id', 'desc')
            ->select()
            ->toArray();
        return ['code' => 0, 'msg' => '', 'data' => $list];
    }

    /**
     * 保存通知模板
     *
     * @param string $name
     * @param string $content
     * @param string $type
     * @return array
     */
    public function saveNotifyTemplate(string $name, string $content, string $type = 'custom'): array
    {
        if (empty($name) || empty($content)) {
            return ['code' => 1, 'msg' => '名称和内容不能为空', 'data' => null];
        }

        $id = Db::name(self::TABLE_TEMPLATE)->insertGetId([
            'name'        => $name,
            'content'     => $content,
            'type'        => $type,
            'status'      => 1,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        return ['code' => 0, 'msg' => '模板保存成功', 'data' => ['id' => $id]];
    }

    // ======== 内部方法 ========

    /**
     * 检查最近是否已发送过通知（防重复）
     *
     * @param int    $taskId
     * @param string $type
     * @param int    $minIntervalSeconds 最小间隔（秒），默认12小时
     * @return bool
     */
    private function hasNotifiedRecently(int $taskId, string $type, int $minIntervalSeconds = 43200): bool
    {
        $threshold = date('Y-m-d H:i:s', time() - $minIntervalSeconds);
        $count = Db::name(self::TABLE_LOG)
            ->where('task_id', $taskId)
            ->where('type', $type)
            ->where('create_time', '>=', $threshold)
            ->count();
        return $count > 0;
    }
}
