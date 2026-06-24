<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;
class ContentSubscription extends Model
{
    protected $name = 'content_subscription';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $type = ['user_id' => 'integer', 'subscribe_id' => 'integer', 'notify_email' => 'integer', 'notify_site' => 'integer'];
}
