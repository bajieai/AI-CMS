<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 审核记录模型 - V2.6
 */
class ReviewRecord extends Model
{
    protected $name = 'review_record';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'workflow_id' => 'integer',
        'target_id' => 'integer',
        'current_step' => 'integer',
        'total_steps' => 'integer',
        'status' => 'integer',
        'submitter_id' => 'integer',
    ];
}
