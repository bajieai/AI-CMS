<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;
class Developer extends Model
{
    protected $name = 'developer';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $type = ['level' => 'integer', 'status' => 'integer', 'total_templates' => 'integer', 'total_revenue' => 'float'];
    const STATUS_PENDING = 0, STATUS_APPROVED = 1, STATUS_REJECTED = 2, STATUS_DISABLED = 3;
    const LEVEL_JUNIOR = 1, LEVEL_CERTIFIED = 2, LEVEL_PROFESSIONAL = 3;
}
