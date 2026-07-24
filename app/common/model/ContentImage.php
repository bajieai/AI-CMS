<?php
declare(strict_types=1);
namespace app\common\model;

use think\Model;

/**
 * 内容配图模型 - V2.9.32 AI4-1
 */
class ContentImage extends Model
{
    protected $name = 'content_image';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'content_id' => 'integer',
        'quality_score' => 'integer',
        'ai_generated' => 'integer',
        'auto_triggered' => 'integer',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    public function scopeByContent($query, int $contentId)
    {
        return $query->where('content_id', $contentId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('image_type', $type);
    }
}
