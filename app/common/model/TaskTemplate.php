<?php

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 任务模板模型 — V2.9.36 Sprint TASK-5
 *
 * 对应 i8j_task_template 表
 */
class TaskTemplate extends Model
{
    protected $name = 'task_template';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    /** JSON 字段自动转换 */
    protected $json = ['task_data', 'subtasks', 'milestones', 'assign_rules', 'audit_flow', 'variables', 'attachments'];
    protected $jsonAssoc = true;

    const STATUS_ENABLED  = 1;
    const STATUS_DISABLED = 0;
}
