<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 模板优惠码模型 — V2.9.26 P-4
 */
class TemplateCoupon extends Model
{
    protected $name = 'template_coupon';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'template_ids' => 'json',
    ];

    /**
     * 三重校验优惠码
     */
    public static function validateCode(string $code, float $orderAmount, int $templateId = 0): array
    {
        if (empty($code)) {
            return [false, '请输入优惠码', null];
        }
        $coupon = self::where('code', $code)->where('status', 1)->find();
        if (!$coupon) {
            return [false, '优惠码不存在或已禁用', null];
        }
        $now = date('Y-m-d H:i:s');
        if ($coupon->start_time && $coupon->start_time > $now) {
            return [false, '优惠码尚未生效', null];
        }
        if ($coupon->end_time && $coupon->end_time < $now) {
            return [false, '优惠码已过期', null];
        }
        if ($coupon->max_uses > 0 && $coupon->used_count >= $coupon->max_uses) {
            return [false, '优惠码已用完', null];
        }
        if ($coupon->min_amount > 0 && $orderAmount < $coupon->min_amount) {
            return [false, '订单金额未达到最低消费', null];
        }
        $templateIds = $coupon->template_ids ?? [];
        if (!empty($templateIds) && $templateId > 0 && !in_array($templateId, $templateIds)) {
            return [false, '此优惠码不适用于该模板', null];
        }
        return [true, '优惠码有效', $coupon];
    }

    public static function calculateDiscount(float $originalPrice, $coupon): float
    {
        if (!$coupon) return 0;
        if ($coupon['discount_type'] === 'percent') {
            return round($originalPrice * $coupon['discount_value'] / 100, 2);
        }
        return min($originalPrice, round($coupon['discount_value'], 2));
    }

    public static function markUsed(int $couponId): void
    {
        self::where('id', $couponId)->inc('used_count')->update();
    }
}
