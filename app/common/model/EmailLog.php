<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 邮件发送日志模型 - V2.5新增
 */
class EmailLog extends Model
{
    protected $name = 'email_log';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'status' => 'integer',
    ];
}
