<?php

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * V2.9.25 N-3: 模板订单模型
 */
class TemplateOrder extends Model
{
    protected $name = 'template_order';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    const STATUS_PENDING = 0;   // 待支付
    const STATUS_PAID = 1;      // 已支付
    const STATUS_REFUNDED = 2;  // 已退款
    const STATUS_CANCELLED = 3; // 已取消

    const SETTLEMENT_UNSETTLED = 0; // 未结算
    const SETTLEMENT_SETTLED = 1;   // 已结算

    /**
     * 生成订单号
     */
    public static function generateOrderNo(): string
    {
        return 'TPL' . date('YmdHis') . str_pad((string)mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * 关联模板
     */
    public function template()
    {
        return $this->belongsTo(TemplateStore::class, 'template_id', 'id');
    }
}
