<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class MemberLevel extends Model
{
    protected $name = 'member_level';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $type = ['id' => 'integer', 'level_order' => 'integer', 'min_points' => 'integer', 'max_points' => 'integer', 'auto_upgrade' => 'integer', 'auto_downgrade' => 'integer', 'validity_days' => 'integer', 'status' => 'integer'];
}
