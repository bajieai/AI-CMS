<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 模板退款记录模型 — V2.9.28 M-1
 */
class TemplateRefund extends Model
{
    protected $name = 'template_refund';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    const STATUS_PENDING = 0;  // 待审核
    const STATUS_APPROVED = 1; // 已通过
    const STATUS_REJECTED = 2; // 已拒绝

    /**
     * 关联订单
     */
    public function order()
    {
        return $this->belongsTo(TemplateOrder::class, 'order_id');
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '待审核',
            self::STATUS_APPROVED => '已通过',
            self::STATUS_REJECTED => '已拒绝',
        ];
        return $map[$data['status']] ?? '未知';
    }
}
