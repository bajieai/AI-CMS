<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;
class ContentRecommendLog extends Model
{
    protected $name = 'content_recommend_log';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $type = ['content_id' => 'integer', 'recommended_content_id' => 'integer', 'user_id' => 'integer', 'impressed' => 'integer', 'clicked' => 'integer'];
}
