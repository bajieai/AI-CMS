<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class TemplateRecommendLog extends Model
{
    protected $name = 'template_recommend_log';
    protected $autoWriteTimestamp = false;
    protected $type = [
        'id' => 'integer', 'user_id' => 'integer', 'template_id' => 'integer',
        'impression' => 'integer', 'click' => 'integer', 'install' => 'integer',
        'click_time' => 'integer', 'install_time' => 'integer', 'create_time' => 'integer',
    ];
}
