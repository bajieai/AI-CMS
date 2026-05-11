<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 会员降级日志模型 - V2.9.4新增
 */
class MemberDowngradeLog extends Model
{
    protected $name = 'member_downgrade_log';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'user_id' => 'integer',
        'from_level' => 'integer',
        'to_level' => 'integer',
        'notified' => 'integer',
    ];
}
