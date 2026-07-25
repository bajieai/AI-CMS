<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint T3: 模板推广活动服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplatePromotion;
use app\common\model\TemplateStore;
use think\facade\Cache;
use think\facade\Db;

/**
 * 模板推广活动服务 - V2.9.31 T3-1
 */
class TemplatePromotionService
{
    private const string CACHE_TAG = 'template_promotion';

    /**
     * 获取当前生效的推广活动（按模板）
     */
    public function getActivePromo(int $templateId): ?TemplatePromotion
    {
        $cacheKey = "template_promo_{$templateId}";
        $result = Cache::get($cacheKey);
        if ($result !== null) {
            return $result === false ? null : $result;
        }

        $promo = TemplatePromotion::active()
            ->byTemplate($templateId)
            ->order('id', 'desc')
            ->find();

        Cache::set($cacheKey, $promo ?: false, 300);
        return $promo ?: null;
    }

    /**
     * 计算优惠后价格
     */
    public function calculatePrice(float $originalPrice, ?TemplatePromotion $promo = null): array
    {
        if (empty($promo) || $promo->status !== 1) {
            return [
                'original_price' => $originalPrice,
                'final_price' => $originalPrice,
                'discount_amount' => 0.00,
                'discount_rate' => 1.00,
                'promo_id' => 0,
            ];
        }

        $discountAmount = 0.00;
        $finalPrice = $originalPrice;

        switch ($promo->promo_type) {
            case TemplatePromotion::TYPE_DISCOUNT:
                // 限时折扣
                $discountAmount = round($originalPrice * (1 - $promo->discount_rate), 2);
                if ($promo->max_discount > 0) {
                    $discountAmount = min($discountAmount, $promo->max_discount);
                }
                $finalPrice = max(0, $originalPrice - $discountAmount);
                break;

            case TemplatePromotion::TYPE_NEWUSER:
                // 新用户专享（折扣率）
                $discountAmount = round($originalPrice * (1 - $promo->discount_rate), 2);
                $finalPrice = max(0, $originalPrice - $discountAmount);
                break;

            case TemplatePromotion::TYPE_FULLREDUCE:
                // 满减
                if ($originalPrice >= $promo->min_amount) {
                    $discountAmount = $promo->discount_amount;
                    if ($promo->max_discount > 0) {
                        $discountAmount = min($discountAmount, $promo->max_discount);
                    }
                    $finalPrice = max(0, $originalPrice - $discountAmount);
                }
                break;

            case TemplatePromotion::TYPE_BUNDLE:
                // 组合优惠（直减）
                $discountAmount = $promo->discount_amount;
                $finalPrice = max(0, $originalPrice - $discountAmount);
                break;
        }

        return [
            'original_price' => $originalPrice,
            'final_price' => $finalPrice,
            'discount_amount' => $discountAmount,
            'discount_rate' => $originalPrice > 0 ? round($finalPrice / $originalPrice, 2) : 1.00,
            'promo_id' => $promo->id,
        ];
    }

    /**
     * 批量获取模板优惠价格
     */
    public function batchCalculatePrices(array $templateIds): array
    {
        $result = [];
        foreach ($templateIds as $id) {
            $store = TemplateStore::find($id);
            if (empty($store)) {
                continue;
            }
            $promo = $this->getActivePromo($id);
            $result[$id] = $this->calculatePrice((float) $store->price, $promo);
        }
        return $result;
    }

    /**
     * 创建推广活动
     */
    public function createPromo(array $data): TemplatePromotion
    {
        $promo = new TemplatePromotion();
        $promo->template_id = (int) ($data['template_id'] ?? 0);
        $promo->promo_type = (int) ($data['promo_type'] ?? 0);
        $promo->discount_rate = (float) ($data['discount_rate'] ?? 0);
        $promo->discount_amount = (float) ($data['discount_amount'] ?? 0);
        $promo->start_time = (int) ($data['start_time'] ?? time());
        $promo->end_time = (int) ($data['end_time'] ?? time() + 86400);
        $promo->min_amount = (float) ($data['min_amount'] ?? 0);
        $promo->max_discount = (float) ($data['max_discount'] ?? 0);
        $promo->usage_limit = (int) ($data['usage_limit'] ?? 0);
        $promo->status = (int) ($data['status'] ?? 1);
        $promo->save();

        // 更新模板表的 promo_id
        TemplateStore::where('id', $promo->template_id)->update([
            'original_price' => Db::raw('price'),
            'promo_id' => $promo->id,
        ]);

        Cache::clear();
        return $promo;
    }

    /**
     * 使用推广活动（增加 usage_count）
     */
    public function usePromo(int $promoId): bool
    {
        $promo = TemplatePromotion::find($promoId);
        if (empty($promo) || $promo->status !== 1) {
            return false;
        }

        if ($promo->usage_limit > 0 && $promo->usage_count >= $promo->usage_limit) {
            return false;
        }

        $promo->inc('usage_count')->save();
        Cache::clear();
        return true;
    }

    /**
     * 获取推广活动列表（后台管理）
     */
    public function getList(array $params = []): array
    {
        $query = TemplatePromotion::with('template');

        if (!empty($params['template_id'])) {
            $query->where('template_id', (int) $params['template_id']);
        }
        if (isset($params['promo_type']) && $params['promo_type'] !== '') {
            $query->where('promo_type', (int) $params['promo_type']);
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', (int) $params['status']);
        }

        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 15);
        $paginator = $query->order('id', 'desc')->paginate($limit, false, ['page' => $page]);

        return [
            'list' => $paginator->items(),
            'total' => $paginator->total(),
            'page' => $page,
            'limit' => $limit,
            'pages' => $paginator->lastPage(),
        ];
    }

    /**
     * 清除推广缓存
     */
    public function clearCache(): void
    {
        Cache::clear();
    }
}
