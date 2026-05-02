<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 积分变动记录模型
 */
class PointsLog extends Model
{
    protected $name = 'points_log';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'member_id' => 'integer',
        'points'    => 'integer',
        'source_id' => 'integer',
    ];
}
