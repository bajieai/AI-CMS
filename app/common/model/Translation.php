<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 翻译模型 - V2.5新增
 */
class Translation extends Model
{
    protected $name = 'translation';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
}
