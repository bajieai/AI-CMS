<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;

class SseMessageQueue extends Model
{
    protected $name = 'sse_message_queue';
    protected $autoWriteTimestamp = false;
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $type = [
        'user_id' => 'integer', 'is_delivered' => 'integer',
        'delivered_at' => 'integer', 'expires_at' => 'integer', 'create_time' => 'integer',
    ];
    protected $json = ['payload'];

    const CHANNEL_AUDIT = 'audit';
    const CHANNEL_COMMENT = 'comment';
    const CHANNEL_SYSTEM = 'system';
    const CHANNEL_NOTIFICATION = 'notification';

    public function scopeUndelivered($query) { return $query->where('is_delivered', 0); }
    public function scopeNotExpired($query) {
        return $query->where(function ($q) { $q->where('expires_at', 0)->whereOr('expires_at', '>', time()); });
    }
}
