<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;

class SseClient extends Model
{
    protected $name = 'sse_client';
    protected $autoWriteTimestamp = false;
    protected $createTime = 'connect_time';
    protected $updateTime = false;
    protected $type = [
        'user_id' => 'integer', 'last_event_id' => 'integer',
        'last_active' => 'integer', 'connect_time' => 'integer', 'status' => 'integer',
    ];

    const STATUS_ONLINE = 1;
    const STATUS_OFFLINE = 0;

    public function scopeOnline($query) { return $query->where('status', self::STATUS_ONLINE); }
}
