<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\TemplateStore;
use app\common\model\TemplateOrder;
use app\common\model\Favorite;
use think\facade\Cache;

class MemberTemplateService
{
    public function getPurchasedTemplates(int $memberId): array
    {
        return Cache::remember(
            'member_template:purchased:' . $memberId,
            function () use ($memberId) {
                $orders = TemplateOrder::where('member_id', $memberId)
                    ->where('status', 1)
                    ->order('id', 'desc')
                    ->select()
                    ->toArray();
                $storeIds = array_column($orders, 'store_id');
                if (empty($storeIds)) return [];
                $templates = TemplateStore::whereIn('id', $storeIds)
                    ->select()->toArray();
                $templateMap = array_column($templates, null, 'id');
                $result = [];
                foreach ($orders as $order) {
                    $tpl = $templateMap[$order['store_id']] ?? null;
                    if ($tpl) {
                        $tpl['order_info'] = $order;
                        $result[] = $tpl;
                    }
                }
                return $result;
            },
            300
        );
    }

    public function getFavoritedTemplates(int $memberId): array
    {
        return Cache::remember(
            'member_template:favorited:' . $memberId,
            function () use ($memberId) {
                $favorites = Favorite::where('user_id', $memberId)
                    ->where('type', 'template')
                    ->order('id', 'desc')
                    ->select()->toArray();
                $storeIds = array_column($favorites, 'target_id');
                if (empty($storeIds)) return [];
                $templates = TemplateStore::whereIn('id', $storeIds)
                    ->select()->toArray();
                return $templates;
            },
            300
        );
    }

    public function getInstallStatus(int $memberId, int $storeId): string
    {
        $installed = TemplateStore::where('id', $storeId)->where('status', 1)->find();
        if (!$installed) return 'not_installed';
        return 'installed';
    }

    public function installPurchasedTemplate(int $memberId, int $storeId): bool
    {
        Cache::clear();
        return true;
    }
}
