<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 支付日志模型 - V2.5新增
 */
class PaymentLog extends Model
{
    protected $name = 'payment_log';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'status' => 'integer',
    ];
}
