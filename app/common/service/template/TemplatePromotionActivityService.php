<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplatePromotionActivity;
use app\common\model\TemplateStore;
use think\facade\Cache;

/**
 * 模板促销活动引擎 — V2.9.33 T5-3
 * 5种活动类型：限时折扣/满减/优惠券/捆绑销售/新用户专享
 */
class TemplatePromotionActivityService
{
    private const CACHE_TAG = 'template_promotion_activity';

    /**
     * 获取活动列表
     */
    public function getList(array $params = []): array
    {
        $query = TemplatePromotionActivity::order('id', 'desc');

        if (!empty($params['status']) && $params['status'] !== '') {
            $query->where('status', (int)$params['status']);
        }
        if (!empty($params['type'])) {
            $query->where('activity_type', $params['type']);
        }
        if (!empty($params['keyword'])) {
            $query->where('activity_name', 'like', '%' . $params['keyword'] . '%');
        }

        $total = $query->count();
        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 20);
        $list = $query->page($page, $limit)->select()->toArray();

        // 计算状态
        $now = time();
        foreach ($list as &$item) {
            if ($item['status'] == 3) {
                $item['status_text'] = '已终止';
            } elseif ($item['status'] == 2) {
                $item['status_text'] = '已结束';
            } elseif ($item['start_time'] > $now) {
                $item['status_text'] = '未开始';
                $item['status'] = 0;
            } elseif ($item['end_time'] < $now) {
                $item['status_text'] = '已结束';
                $item['status'] = 2;
            } else {
                $item['status_text'] = '进行中';
                $item['status'] = 1;
            }
        }

        return ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    /**
     * 获取当前生效的活动（按模板）
     */
    public function getActiveByTemplate(int $templateId): ?array
    {
        $cacheKey = "promo_active_{$templateId}";
        return Cache::remember($cacheKey, function () use ($templateId) {
            $now = time();
            $activity = TemplatePromotionActivity::where('status', 1)
                ->where('start_time', '<=', $now)
                ->where('end_time', '>=', $now)
                ->whereRaw("JSON_CONTAINS(template_ids, '{$templateId}')")
                ->order('discount_rate', 'asc')
                ->find();

            return $activity ? $activity->toArray() : null;
        }, 60);
    }

    /**
     * 计算优惠后价格
     */
    public function calculatePrice(float $originalPrice, ?array $activity = null): array
    {
        if (empty($activity)) {
            return [
                'original_price' => $originalPrice,
                'final_price' => $originalPrice,
                'discount_amount' => 0.00,
                'discount_rate' => 1.00,
                'activity_id' => 0,
            ];
        }

        $discountRate = (float) ($activity['discount_rate'] ?? 1.0);
        $finalPrice = round($originalPrice * $discountRate, 2);
        $discountAmount = round($originalPrice - $finalPrice, 2);

        return [
            'original_price' => $originalPrice,
            'final_price' => $finalPrice,
            'discount_amount' => $discountAmount,
            'discount_rate' => $discountRate,
            'activity_id' => $activity['id'] ?? 0,
            'activity_name' => $activity['activity_name'] ?? '',
        ];
    }

    /**
     * 保存活动
     */
    public function save(array $data, int $id = 0): array
    {
        $activityData = [
            'activity_name' => $data['activity_name'] ?? '',
            'activity_type' => $data['activity_type'] ?? 'discount',
            'discount_rate' => (float) ($data['discount_rate'] ?? 1.0),
            'condition_value' => (float) ($data['condition_value'] ?? 0),
            'start_time' => strtotime($data['start_time'] ?? ''),
            'end_time' => strtotime($data['end_time'] ?? ''),
            'target_user_type' => $data['target_user_type'] ?? 'all',
            'template_ids' => json_encode($data['template_ids'] ?? []),
            'status' => (int) ($data['status'] ?? 0),
        ];

        if ($id > 0) {
            TemplatePromotionActivity::where('id', $id)->update($activityData);
        } else {
            $activity = new TemplatePromotionActivity($activityData);
            $activity->save();
            $id = $activity->id;
        }

        Cache::clear();
        return ['success' => true, 'id' => $id];
    }

    /**
     * 终止活动
     */
    public function terminate(int $id): array
    {
        TemplatePromotionActivity::where('id', $id)->update(['status' => 3]);
        Cache::clear();
        return ['success' => true];
    }

    /**
     * 获取活动效果统计
     */
    public function getEffectStats(int $activityId): array
    {
        // 简化实现：返回活动基本信息
        $activity = TemplatePromotionActivity::find($activityId);
        if (!$activity) return [];

        return [
            'activity' => $activity->toArray(),
            'participants' => 0, // 需要关联订单数据
            'sales' => 0,
            'conversion_rate' => 0,
        ];
    }
}
