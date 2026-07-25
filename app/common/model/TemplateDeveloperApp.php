<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class TemplateDeveloperApp extends Model
{
    protected $name = 'template_developer_app';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $type = ['id' => 'integer', 'member_id' => 'integer', 'status' => 'integer', 'last_used_time' => 'integer'];
}
