<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class TemplatePromotionActivity extends Model
{
    protected $name = 'template_promotion_activity';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $type = ['id' => 'integer', 'status' => 'integer', 'start_time' => 'integer', 'end_time' => 'integer'];
}
