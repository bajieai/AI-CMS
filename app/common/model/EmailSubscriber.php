<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 邮件订阅模型
 */
class EmailSubscriber extends Model
{
    protected $name = 'email_subscriber';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'status' => 'integer',
    ];

    public function getStatusTextAttr($value, $data): string
    {
        return $data['status'] ? '订阅中' : '已退订';
    }
}