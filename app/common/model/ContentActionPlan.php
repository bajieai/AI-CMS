<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;
class ContentActionPlan extends Model
{
    protected $name = 'content_action_plan';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $type = ['content_id' => 'integer', 'execute_time' => 'integer', 'status' => 'integer'];
    const STATUS_PENDING = 0, STATUS_EXECUTED = 1, STATUS_CANCELLED = 2, STATUS_FAILED = 3;
}
