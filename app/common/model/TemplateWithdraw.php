<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 提现申请模型 — V2.9.28 M-7
 */
class TemplateWithdraw extends Model
{
    protected $name = 'template_withdraw';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    const STATUS_PENDING = 0;    // 待审核
    const STATUS_PROCESSING = 1; // 打款中
    const STATUS_COMPLETED = 2;  // 已完成
    const STATUS_REJECTED = 3;   // 已驳回

    protected $type = [
        'developer_id' => 'integer',
        'amount' => 'float',
        'fee' => 'float',
        'actual_amount' => 'float',
        'status' => 'integer',
    ];

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '待审核',
            self::STATUS_PROCESSING => '打款中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_REJECTED => '已驳回',
        ];
        return $map[$data['status']] ?? '未知';
    }
}
