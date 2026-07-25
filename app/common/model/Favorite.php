<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;
class Favorite extends Model
{
    protected $name = 'favorite';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $type = ['user_id' => 'integer', 'content_id' => 'integer'];
}
