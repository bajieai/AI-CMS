<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class TemplateComponent extends Model
{
    protected $name = 'template_component';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $type = ['id' => 'integer', 'author_id' => 'integer', 'status' => 'integer', 'is_system' => 'integer'];
}
