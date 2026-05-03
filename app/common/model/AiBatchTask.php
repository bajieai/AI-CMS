<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * AI批量生成任务模型 - V2.5新增
 */
class AiBatchTask extends Model
{
    protected $name = 'ai_batch_task';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'total' => 'integer',
        'completed' => 'integer',
        'status' => 'integer',
        'cate_id' => 'integer',
        'model_id' => 'integer',
    ];
}
