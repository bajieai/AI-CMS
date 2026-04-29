<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 访问日志模型
 */
class VisitLog extends Model
{
    protected $name = 'visit_log';

    protected $autoWriteTimestamp = false;

    protected $type = [
        'content_id' => 'integer',
        'visit_time' => 'integer',
    ];
}