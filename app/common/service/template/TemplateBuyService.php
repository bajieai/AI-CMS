<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateStore;
use think\facade\Cache;
use think\facade\Db;

/**
 * 模板购买服务 - V2.9.29 Sprint T-1
 * V2.9.30: 从 app\home\service 迁移到 app\common\service\template
 */
class TemplateBuyService
{
    private const PRICE_TIERS = [
        'free' => 0,
        'personal' => 99,
        'commercial' => 299,
        'enterprise' => 999,
    ];

    public function getTemplateInfo(int $id): ?TemplateStore
    {
        return Cache::remember('tpl_info_' . $id, function () use ($id) {
            return TemplateStore::find($id);
        }, 3600);
    }

    public function createOrder(int $userId, int $templateId, string $tier = 'personal'): array
    {
        $tpl = $this->getTemplateInfo($templateId);
        if (!$tpl) return [];

        $price = self::PRICE_TIERS[$tier] ?? 0;
        $orderNo = 'TPL' . date('YmdHis') . mt_rand(1000, 9999);

        $orderId = Db::table('i8j_template_order')->insertGetId([
            'order_no' => $orderNo,
            'user_id' => $userId,
            'template_id' => $templateId,
            'price_tier' => $tier,
            'amount' => $price,
            'status' => 0,
            'create_time' => date('Y-m-d H:i:s'),
        ]);

        return ['order_id' => $orderId, 'order_no' => $orderNo, 'amount' => $price];
    }

    public function getCart(int $userId): array
    {
        return Cache::remember('tpl_cart_' . $userId, function () use ($userId) {
            return Db::table('i8j_template_cart')
                ->where('user_id', $userId)
                ->select()->toArray();
        }, 60);
    }

    public function addToCart(int $userId, int $templateId): bool
    {
        $exists = Db::table('i8j_template_cart')
            ->where('user_id', $userId)
            ->where('template_id', $templateId)
            ->find();
        if ($exists) return true;
        Db::table('i8j_template_cart')->insert([
            'user_id' => $userId,
            'template_id' => $templateId,
            'create_time' => date('Y-m-d H:i:s'),
        ]);
        Cache::delete('tpl_cart_' . $userId);
        return true;
    }

    public function checkout(int $userId): array
    {
        $items = Db::table('i8j_template_cart')->where('user_id', $userId)->select();
        $orders = [];
        foreach ($items as $item) {
            $orders[] = $this->createOrder($userId, (int) $item['template_id'], 'personal');
        }
        Db::table('i8j_template_cart')->where('user_id', $userId)->delete();
        Cache::delete('tpl_cart_' . $userId);
        return $orders;
    }
}
