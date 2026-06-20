<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Cache;

/**
 * 模板促销活动模型 — V2.9.26 P-4
 */
class TemplatePromotion extends Model
{
    protected $name = 'template_promotion';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'template_ids' => 'json',
    ];

    public const CACHE_TAG = 'template_promotion';

    /**
     * 获取生效中的促销活动
     */
    public static function getActivePromotions(int $templateId = 0, int $categoryId = 0): array
    {
        $cacheKey = 'active_promo_' . $templateId . '_' . $categoryId;
        return Cache::tag(self::CACHE_TAG)->remember($cacheKey, function () use ($templateId, $categoryId) {
            $now = date('Y-m-d H:i:s');
            $query = self::where('status', 1)
                ->where(function ($q) use ($now) {
                    $q->whereNull('start_time')->whereOr('start_time', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_time')->whereOr('end_time', '>=', $now);
                })
                ->order('sort', 'asc');

            $promotions = $query->select()->toArray();

            // 筛选适用当前模板的促销
            if ($templateId > 0) {
                $promotions = array_filter($promotions, function ($p) use ($templateId, $categoryId) {
                    $ids = $p['template_ids'] ?? [];
                    if (!empty($ids) && !in_array($templateId, $ids)) {
                        return false;
                    }
                    if ($categoryId > 0 && $p['category_id'] > 0 && $p['category_id'] != $categoryId) {
                        return false;
                    }
                    return true;
                });
            }
            return array_values($promotions);
        }, 300);
    }

    /**
     * 计算促销价
     */
    public static function calculateDiscountPrice(float $originalPrice, array $promotion): float
    {
        if ($promotion['discount_type'] === 'percent') {
            return round($originalPrice * (1 - $promotion['discount_value'] / 100), 2);
        }
        return max(0, round($originalPrice - $promotion['discount_value'], 2));
    }
}
