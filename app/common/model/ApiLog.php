<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;
class ApiLog extends Model
{
    protected $name = 'api_log';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $type = ['api_key_id' => 'integer', 'status_code' => 'integer', 'duration_ms' => 'integer'];
}
