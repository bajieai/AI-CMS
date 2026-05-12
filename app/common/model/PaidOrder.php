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
     * V2.9.5 type 枚举：content_purchase(内容购买) / reward(打赏) / download(下载付费)
     */
    public const TYPE_CONTENT_PURCHASE = 'content_purchase';
    public const TYPE_REWARD           = 'reward';
    public const TYPE_DOWNLOAD         = 'download';

    /**
     * V2.9.5 获取类型友好文案
     */
    public function getTypeTextAttr($value, $data): string
    {
        return match ($data['type'] ?? '') {
            self::TYPE_CONTENT_PURCHASE => '内容购买',
            self::TYPE_REWARD           => '打赏',
            self::TYPE_DOWNLOAD         => '下载付费',
            default                     => '其他',
        };
    }

    /**
     * V2.9.5 获取关联的PaymentService订单号
     */
    public function getPaymentOrderNo(): string
    {
        return $this->payment_order_no ?: '';
    }

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
