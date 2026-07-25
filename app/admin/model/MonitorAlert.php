<?php

declare(strict_types=1);

namespace app\admin\model;

use think\Model;

class MonitorAlert extends Model
{
    protected $name = 'monitor_alert';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'alert_rule'        => 'json',
        'alert_channels'    => 'json',
        'alert_recipients'  => 'json',
        'escalation_config' => 'json',
    ];
}
