<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class ChannelPlatform extends Model
{
    protected $name = 'channel_platform';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $type = ['id' => 'integer', 'is_default' => 'integer', 'status' => 'integer', 'token_expire_time' => 'integer'];
}
