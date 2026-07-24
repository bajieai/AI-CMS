<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;

class RecommendLog extends Model
{
    protected $autoWriteTimestamp = 'datetime';
    protected $updateTime = false;
    protected $json = ['event_data'];
    protected $jsonAssoc = true;

    public function scopeMember($query, int $memberId) { return $query->where('member_id', $memberId); }
    public function scopeContent($query, int $contentId) { return $query->where('content_id', $contentId); }
    public function scopeEventType($query, string $type) { return $query->where('event_type', $type); }
}
