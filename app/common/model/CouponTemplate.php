<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 优惠券模板模型 - V2.9新增
 * 对应 i8j_coupon_template 表
 */
class CouponTemplate extends Model
{
    protected $name = 'coupon_template';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'id'             => 'integer',
        'total_stock'    => 'integer',
        'remain_stock'   => 'integer',
        'per_user_limit' => 'integer',
        'start_time'     => 'integer',
        'end_time'       => 'integer',
        'status'         => 'integer',
    ];

    /**
     * 获取适用范围名称
     */
    public function getScopeNameAttr(): string
    {
        $map = ['all' => '全部商品', 'category' => '指定分类', 'content' => '指定商品'];
        return $map[$this->scope_type] ?? $this->scope_type;
    }

    /**
     * 获取状态名称
     */
    public function getStatusNameAttr(): string
    {
        $map = [0 => '草稿', 1 => '启用', 2 => '停用', 3 => '已过期'];
        return $map[$this->status] ?? '未知';
    }

    /**
     * 检查优惠券是否可用
     */
    public function isValid(): bool
    {
        if ($this->status !== 1) return false;
        $now = time();
        if ($this->start_time > 0 && $this->start_time > $now) return false;
        if ($this->end_time > 0 && $this->end_time < $now) return false;
        if ($this->remain_stock > 0 && $this->remain_stock <= 0) return false;
        return true;
    }

    /**
     * 计算折扣金额
     *
     * @param float $amount 订单金额
     * @return float 折扣金额
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->condition_amount > 0 && $amount < $this->condition_amount) {
            return 0.0;
        }

        switch ($this->coupon_type) {
            case 'reduce':
                return (float) $this->reduce_amount;
            case 'discount':
                return round($amount * (1 - (float) $this->reduce_amount), 2);
            case 'free_shipping':
                return 0.0; // 免邮券不抵扣金额
            default:
                return 0.0;
        }
    }
}
