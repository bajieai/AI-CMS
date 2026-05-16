<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 访问日志模型 - V2.7 P0-6
 */
class VisitLog extends Model
{
    protected $name = 'visit_log';

    protected $autoWriteTimestamp = false;

    protected $type = [
        'content_id'   => 'integer',
        'visitor_id'   => 'integer',
        'session_id'   => 'string',
        'visit_time'   => 'integer',
        'event_type'   => 'string',
        'share_channel'=> 'string',
    ];
}
