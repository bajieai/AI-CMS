<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 系统通知模型 - V2.6
 */
class MessageSystem extends Model
{
    protected $name = 'message_system';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'send_time' => 'integer',
        'expire_time' => 'integer',
    ];
}
