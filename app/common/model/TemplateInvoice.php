<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 模板发票申请模型 — V2.9.28 M-1
 */
class TemplateInvoice extends Model
{
    protected $name = 'template_invoice';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    const STATUS_PENDING = 0;  // 待开具
    const STATUS_ISSUED = 1;   // 已开具
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
            self::STATUS_PENDING => '待开具',
            self::STATUS_ISSUED => '已开具',
            self::STATUS_REJECTED => '已拒绝',
        ];
        return $map[$data['status']] ?? '未知';
    }
}
