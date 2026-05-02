<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 签到记录模型
 */
class SigninLog extends Model
{
    protected $name = 'signin_log';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'member_id'        => 'integer',
        'points'           => 'integer',
        'consecutive_days' => 'integer',
    ];
}
