<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;
class Like extends Model
{
    protected $name = 'like';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $type = ['user_id' => 'integer', 'content_id' => 'integer'];
}
