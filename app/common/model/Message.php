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

    /**
     * V2.9.5 私信内容存储转义，防止XSS
     */
    public function setContentAttr($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
