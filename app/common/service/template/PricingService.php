<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateStore;
use app\common\model\TemplatePromotion;
use app\common\model\TemplateCoupon;
use app\common\model\TemplatePriceLog;
use think\facade\Cache;

/**
 * 模板定价与促销服务 — V2.9.26 P-4
 *
 * 价格优先级：活动价 > 优惠码 > 原价
 */
class PricingService
{
    /**
     * 计算最终价格（活动价 > 优惠码 > 原价）
     */
    public function calculateFinalPrice(int $templateId, string $couponCode = ''): array
    {
        $template = TemplateStore::find($templateId);
        if (!$template) {
            return ['success' => false, 'message' => '模板不存在'];
        }

        $billingType = $template->billing_type ?? 'free';
        if ($billingType === 'free') {
            return ['success' => true, 'final_price' => 0, 'original_price' => 0, 'discount' => 0, 'billing_type' => 'free'];
        }

        $originalPrice = (float)($template->price_original > 0 ? $template->price_original : $template->price);
        $currentPrice = $originalPrice;

        // 1. 促销活动价
        $promotions = TemplatePromotion::getActivePromotions($templateId, (int)($template->category_id ?? 0));
        $promotionDiscount = 0;
        $appliedPromotion = null;
        foreach ($promotions as $promo) {
            $discounted = TemplatePromotion::calculateDiscountPrice($currentPrice, $promo);
            if ($discounted < $currentPrice) {
                $currentPrice = $discounted;
                $promotionDiscount = $originalPrice - $currentPrice;
                $appliedPromotion = $promo;
            }
            break; // 只取第一个最优促销
        }

        // 2. 优惠码
        $couponDiscount = 0;
        $appliedCoupon = null;
        if ($couponCode) {
            [$valid, $message, $coupon] = TemplateCoupon::validateCode($couponCode, $currentPrice, $templateId);
            if ($valid) {
                $couponDiscount = TemplateCoupon::calculateDiscount($currentPrice, $coupon);
                $currentPrice = max(0, $currentPrice - $couponDiscount);
                $appliedCoupon = $coupon;
            } else {
                return ['success' => false, 'message' => $message];
            }
        }

        return [
            'success'         => true,
            'original_price'  => $originalPrice,
            'final_price'     => round($currentPrice, 2),
            'promotion_discount' => round($promotionDiscount, 2),
            'coupon_discount' => round($couponDiscount, 2),
            'total_discount'  => round($originalPrice - $currentPrice, 2),
            'billing_type'    => $billingType,
            'promotion'       => $appliedPromotion,
            'coupon'          => $appliedCoupon ? ['id' => $appliedCoupon['id'], 'code' => $appliedCoupon['code']] : null,
        ];
    }

    /**
     * 更新模板价格
     */
    public function updatePrice(int $templateId, float $newPrice, int $operatorId, string $operatorName, string $reason = ''): array
    {
        $template = TemplateStore::find($templateId);
        if (!$template) return ['success' => false, 'message' => '模板不存在'];

        $oldPrice = (float)($template->price_original ?? $template->price ?? 0);
        $template->price_original = $newPrice;
        $template->save();

        TemplatePriceLog::logChange($templateId, $operatorId, $operatorName, 'price_change', $oldPrice, $newPrice, $reason);
        Cache::tag(TemplateStore::class)->clear();
        return ['success' => true, 'message' => '价格已更新'];
    }

    /**
     * 创建促销活动
     */
    public function createPromotion(array $data): array
    {
        $promo = TemplatePromotion::create([
            'name'           => $data['name'] ?? '',
            'type'           => $data['type'] ?? 'discount',
            'discount_type'  => $data['discount_type'] ?? 'percent',
            'discount_value' => $data['discount_value'] ?? 0,
            'template_ids'   => $data['template_ids'] ?? [],
            'category_id'    => $data['category_id'] ?? 0,
            'start_time'     => $data['start_time'] ?? null,
            'end_time'       => $data['end_time'] ?? null,
            'status'         => $data['status'] ?? 1,
            'sort'           => $data['sort'] ?? 100,
        ]);
        Cache::tag(TemplatePromotion::CACHE_TAG)->clear();
        return ['success' => true, 'message' => '促销活动已创建', 'data' => $promo->toArray()];
    }

    /**
     * 创建优惠码
     */
    public function createCoupon(array $data): array
    {
        $coupon = TemplateCoupon::create([
            'code'           => $data['code'] ?? '',
            'name'           => $data['name'] ?? '',
            'discount_type'  => $data['discount_type'] ?? 'percent',
            'discount_value' => $data['discount_value'] ?? 0,
            'min_amount'     => $data['min_amount'] ?? 0,
            'max_uses'       => $data['max_uses'] ?? 0,
            'template_ids'   => $data['template_ids'] ?? [],
            'start_time'     => $data['start_time'] ?? null,
            'end_time'       => $data['end_time'] ?? null,
            'status'         => $data['status'] ?? 1,
        ]);
        return ['success' => true, 'message' => '优惠码已创建', 'data' => $coupon->toArray()];
    }

    /**
     * 获取促销列表
     */
    public function getPromotionList(int $page = 1, int $limit = 20): array
    {
        $query = TemplatePromotion::order('sort', 'asc');
        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();
        return ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    /**
     * 获取优惠码列表
     */
    public function getCouponList(int $page = 1, int $limit = 20): array
    {
        $query = TemplateCoupon::order('created_at', 'desc');
        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();
        return ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    /**
     * 获取价格变更历史
     */
    public function getPriceHistory(int $templateId): array
    {
        return TemplatePriceLog::getHistory($templateId);
    }
}
