<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;
class ApiKey extends Model
{
    protected $name = 'api_key';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $type = ['rate_limit' => 'integer', 'is_active' => 'integer'];
}
