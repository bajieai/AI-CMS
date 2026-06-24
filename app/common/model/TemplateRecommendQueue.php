<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;
class TemplateRecommendQueue extends Model
{
    protected $name = 'template_recommend_queue';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $type = ['user_id' => 'integer', 'template_id' => 'integer', 'score' => 'float'];
}
