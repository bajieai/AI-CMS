<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 邮件模板模型 - V2.5新增
 */
class EmailTemplate extends Model
{
    protected $name = 'email_template';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'is_enabled' => 'integer',
    ];
}
