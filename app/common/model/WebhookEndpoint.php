<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;
class WebhookEndpoint extends Model
{
    protected $name = 'webhook_endpoint';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $type = ['is_active' => 'integer', 'retry_count' => 'integer', 'timeout_seconds' => 'integer', 'fail_count' => 'integer'];
}
