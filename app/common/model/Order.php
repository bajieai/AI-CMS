<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 订单模型 - V2.9.4新增
 */
class Order extends Model
{
    protected $name = 'orders';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'user_id' => 'integer',
        'amount' => 'float',
        'status' => 'integer',
        'paid_time' => 'integer',
    ];

    // 状态常量
    const STATUS_PENDING = 0;  // 待支付
    const STATUS_PAID = 1;     // 已支付
    const STATUS_REFUNDED = 2; // 已退款
    const STATUS_CLOSED = 3;   // 已关闭

    /**
     * 生成唯一订单号
     */
    public static function generateOrderNo(): string
    {
        return 'ORD' . date('YmdHis') . str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * 创建订单
     */
    public static function createOrder(int $userId, string $source, string $sourceId, float $amount): self
    {
        if ($amount <= 0) {
            throw new \Exception('订单金额必须大于0');
        }

        return self::create([
            'order_no' => self::generateOrderNo(),
            'user_id' => $userId,
            'source' => $source,
            'source_id' => $sourceId,
            'amount' => $amount,
            'status' => self::STATUS_PENDING,
        ]);
    }

    /**
     * 标记已支付
     */
    public function markAsPaid(string $payMethod, string $tradeNo = ''): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \Exception('订单状态异常，无法支付');
        }

        $this->status = self::STATUS_PAID;
        $this->pay_method = $payMethod;
        $this->pay_trade_no = $tradeNo;
        $this->paid_time = time();
        return $this->save();
    }

    /**
     * 标记已退款
     */
    public function markAsRefunded(): bool
    {
        if ($this->status !== self::STATUS_PAID) {
            throw new \Exception('订单状态异常，无法退款');
        }
        $this->status = self::STATUS_REFUNDED;
        return $this->save();
    }

    /**
     * 关闭订单
     */
    public function close(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \Exception('仅待支付订单可关闭');
        }
        $this->status = self::STATUS_CLOSED;
        return $this->save();
    }

    /**
     * 状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '待支付',
            self::STATUS_PAID => '已支付',
            self::STATUS_REFUNDED => '已退款',
            self::STATUS_CLOSED => '已关闭',
        ];
        return $map[$data['status']] ?? '未知';
    }

    /**
     * 状态徽章
     */
    public function getStatusBadgeAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => 'bg-warning',
            self::STATUS_PAID => 'bg-success',
            self::STATUS_REFUNDED => 'bg-info',
            self::STATUS_CLOSED => 'bg-secondary',
        ];
        return $map[$data['status']] ?? 'bg-secondary';
    }
}
