<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class CustomWhitelist extends Model
{
    protected $name = 'custom_whitelist';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $type = ['id' => 'integer', 'creator_id' => 'integer'];
}
