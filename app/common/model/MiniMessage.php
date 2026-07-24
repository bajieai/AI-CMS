<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 移动端消息模型
 * V2.9.37 MINI-FULL-6
 */
class MiniMessage extends Model
{
    protected $autoWriteTimestamp = 'datetime';
    protected $updateTime = false;

    // JSON字段
    protected $json = ['msg_data'];
    protected $jsonAssoc = true;

    protected $type = [
        'member_id'   => 'integer',
        'is_read'     => 'integer',
    ];

    // 未读消息
    public function scopeUnread($query, int $memberId)
    {
        return $query->where('member_id', $memberId)->where('is_read', 0);
    }

    // 按用户
    public function scopeMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    // 按类型
    public function scopeType($query, string $type)
    {
        return $query->where('msg_type', $type);
    }
}
