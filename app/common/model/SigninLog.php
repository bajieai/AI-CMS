<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 签到记录模型 - V2.9 M4新增
 */
class SigninLog extends Model
{
    protected $name = 'signin_log';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'id'         => 'integer',
        'member_id'  => 'integer',
        'points'     => 'integer',
        'streak_days' => 'integer',
    ];
}
