<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class ReportDefinition extends Model
{
    protected $name = 'report_definition';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $dateFormat = 'U';  // 保持时间戳为int，不转为datetime字符串
    protected $type = [
        'id' => 'integer',
        'is_system' => 'integer',
        'creator_id' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];
}
