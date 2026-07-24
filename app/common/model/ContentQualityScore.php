<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 内容质量评分模型 — V2.9.33 AI5
 */
class ContentQualityScore extends Model
{
    protected $name = 'content_quality_score';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $type = [
        'id'                => 'integer',
        'content_id'        => 'integer',
        'completeness_score'=> 'integer',
        'readability_score' => 'integer',
        'seo_score'         => 'integer',
        'image_match_score' => 'integer',
        'tag_accuracy_score'=> 'integer',
        'total_score'       => 'integer',
        'repair_count'      => 'integer',
        'last_repair_time'  => 'integer',
    ];
}
