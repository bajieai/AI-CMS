<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;

class QaLog extends Model
{
    protected $autoWriteTimestamp = 'datetime';
    protected $updateTime = false;
    protected $json = ['answer_source'];
    protected $jsonAssoc = true;

    protected $type = [
        'member_id'     => 'integer',
        'is_helpful'    => 'integer',
        'is_sensitive'  => 'integer',
        'is_answered'   => 'integer',
        'response_time' => 'integer',
    ];

    public function scopeSession($query, string $sessionId) { return $query->where('session_id', $sessionId); }
    public function scopeAnswered($query) { return $query->where('is_answered', 1); }
}
