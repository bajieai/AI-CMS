<?php

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * V2.9.25 N-3: 结算报表模型
 */
class TemplateSettlement extends Model
{
    protected $name = 'template_settlement';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    const STATUS_PENDING = 0;   // 待审核
    const STATUS_AUDITED = 1;   // 已审核
    const STATUS_PAID = 2;      // 已打款
    const STATUS_CLOSED = 3;    // 已关闭

    /**
     * 生成结算批次号
     */
    public static function generateBatchNo(): string
    {
        return 'STL' . date('YmdHis') . str_pad((string)mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * 关联订单
     */
    public function orders()
    {
        return $this->hasMany(TemplateOrder::class, 'settlement_id', 'id');
    }
}
