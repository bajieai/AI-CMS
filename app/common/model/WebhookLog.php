<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;
class WebhookLog extends Model
{
    protected $name = 'webhook_log';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $type = ['endpoint_id' => 'integer', 'response_code' => 'integer', 'status' => 'integer', 'attempt' => 'integer', 'duration_ms' => 'integer'];
}
