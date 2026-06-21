<?php
declare(strict_types=1);
namespace app\common\service\admin;

use app\common\model\TemplatePricing;
use app\common\model\TemplateStore;
use app\common\model\TemplatePromotion;
use app\common\model\TemplateCoupon;
use think\facade\Cache;

/**
 * V2.9.27 U-1: 模板定价服务
 */
class TemplatePricingService
{
    public static function setPricing(int $templateId, array $data): TemplatePricing
    {
        $billingType = $data['billing_type'] ?? TemplatePricing::BILLING_ONE_TIME;
        $pricing = TemplatePricing::where('template_id', $templateId)->where('billing_type', $billingType)->find();
        $saveData = [
            'template_id' => $templateId, 'billing_type' => $billingType,
            'price' => (float)($data['price'] ?? 0), 'original_price' => (float)($data['original_price'] ?? 0),
            'recurring_period' => $data['recurring_period'] ?? '', 'trial_days' => (int)($data['trial_days'] ?? 0),
            'is_active' => (int)($data['is_active'] ?? 1), 'sort' => (int)($data['sort'] ?? 0),
        ];
        if ($pricing) { $pricing->save($saveData); } else { $pricing = TemplatePricing::create($saveData); }
        Cache::tag('template_pricing')->clear();
        return $pricing;
    }

    public static function getPricing(int $templateId): ?TemplatePricing
    {
        return Cache::tag('template_pricing')->remember('tpl_pricing_' . $templateId, function () use ($templateId) {
            return TemplatePricing::where('template_id', $templateId)->where('is_active', 1)->order('sort', 'asc')->find();
        }, 3600);
    }

    public static function calculateFinalPrice(int $templateId, ?string $couponCode = null, int $memberId = 0): array
    {
        $pricing = self::getPricing($templateId);
        if (!$pricing) return ['original' => 0, 'discount' => 0, 'final' => 0, 'promotion' => null, 'coupon' => null, 'billing_type' => 'free'];

        $original = $pricing->original_price > 0 ? $pricing->original_price : $pricing->price;
        $currentPrice = $pricing->price;
        $discount = 0; $promotionInfo = null; $couponInfo = null;

        // 促销活动
        $promotion = TemplatePromotion::where('status', 1)
            ->where('start_time', '<', time())->where('end_time', '>', time())
            ->where(function ($q) use ($templateId) { $q->where('template_id', $templateId)->whereOr('template_id', 0); })
            ->order('id', 'desc')->find();
        if ($promotion) {
            if ($promotion->discount_type === 'percent') $discount = $currentPrice * (1 - $promotion->discount_value / 100);
            elseif ($promotion->discount_type === 'amount') $discount = min($promotion->discount_value, $currentPrice);
            $promotionInfo = $promotion->toArray();
        }

        // 优惠码
        if ($couponCode) {
            $coupon = TemplateCoupon::where('code', $couponCode)->where('status', 1)
                ->where(function ($q) { $q->where('expires_at', 0)->whereOr('expires_at', '>', time()); })->find();
            if ($coupon && ($coupon->used_count < $coupon->max_count || $coupon->max_count == 0)) {
                $priceAfterPromo = $currentPrice - $discount;
                if ($coupon->discount_type === 'percent') $couponDiscount = $priceAfterPromo * (1 - $coupon->discount_value / 100);
                else $couponDiscount = min($coupon->discount_value, $priceAfterPromo);
                $discount += $couponDiscount;
                $couponInfo = $coupon->toArray();
            }
        }

        $final = max(0, $currentPrice - $discount);
        return ['original' => $original, 'current' => $currentPrice, 'discount' => round($discount, 2), 'final' => round($final, 2), 'promotion' => $promotionInfo, 'coupon' => $couponInfo, 'billing_type' => $pricing->billing_type];
    }

    public static function deletePricing(int $templateId, string $billingType): bool
    {
        $result = TemplatePricing::where('template_id', $templateId)->where('billing_type', $billingType)->delete();
        Cache::tag('template_pricing')->clear();
        return $result > 0;
    }
}
