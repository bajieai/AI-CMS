<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 审核日志模型 - V2.6
 */
class ReviewLog extends Model
{
    protected $name = 'review_log';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'record_id' => 'integer',
        'step' => 'integer',
        'reviewer_id' => 'integer',
    ];
}
