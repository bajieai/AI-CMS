<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
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
