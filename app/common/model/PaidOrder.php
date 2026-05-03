<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 付费订单模型
 */
class PaidOrder extends Model
{
    protected $name = 'paid_order';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'member_id' => 'integer',
        'content_id' => 'integer',
        'status'    => 'integer',
        'price'     => 'float',
        'paid_at'   => 'integer',
    ];

    /**
     * 关联会员
     */
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * 关联内容
     */
    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }
}
