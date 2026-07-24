<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class MemberPointsLog extends Model
{
    protected $name = 'member_points_log';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $type = ['id' => 'integer', 'member_id' => 'integer', 'points' => 'integer', 'balance' => 'integer', 'ref_id' => 'integer'];
}
