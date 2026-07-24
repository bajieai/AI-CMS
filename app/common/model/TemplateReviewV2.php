<?php
declare(strict_types=1);
namespace app\common\model;

use think\Model;

/**
 * 模板评分评论V2模型 - V2.9.32 T4-1
 */
class TemplateReviewV2 extends Model
{
    protected $name = 'template_review_v2';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'user_id' => 'integer', 'template_id' => 'integer',
        'rating_overall' => 'integer', 'rating_ease' => 'integer',
        'rating_design' => 'integer', 'rating_feature' => 'integer',
        'rating_performance' => 'integer', 'likes' => 'integer',
        'status' => 'integer', 'images' => 'json',
    ];

    public function template()
    {
        return $this->belongsTo(TemplateStore::class, 'template_id');
    }

    public function scopeByTemplate($query, int $templateId)
    {
        return $query->where('template_id', $templateId);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 1);
    }
}
