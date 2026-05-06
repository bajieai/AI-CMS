<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 私信消息模型 - V2.6
 */
class Message extends Model
{
    protected $name = 'message';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'conversation_id' => 'integer',
        'from_user_id' => 'integer',
        'to_user_id' => 'integer',
        'is_read' => 'integer',
    ];
}
