<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 采集日志模型 - V2.5新增
 */
class CollectLog extends Model
{
    protected $name = 'collect_log';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'source_id' => 'integer',
        'content_id' => 'integer',
        'status' => 'integer',
        'pub_time' => 'integer',
    ];
}
