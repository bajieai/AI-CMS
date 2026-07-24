<?php
declare(strict_types=1);

namespace app\common\service\member;

use app\common\model\Member;
use think\facade\Db;
use think\facade\Cache;

class SubscriptionService
{
    private const CACHE_TAG = 'subscription';

    public function subscribeCategory(int $memberId, int $categoryId): array
    {
        $exists = Db::name('subscription')->where('member_id', $memberId)->where('category_id', $categoryId)->find();
        if ($exists) return ['success' => true, 'message' => '已订阅'];
        Db::name('subscription')->insert(['member_id' => $memberId, 'category_id' => $categoryId, 'subscription_type' => 'content', 'create_time' => time()]);
        Cache::clear();
        return ['success' => true];
    }

    public function subscribeVip(int $memberId, string $plan): array
    {
        $plans = ['monthly' => ['price' => 19.9, 'days' => 30], 'quarterly' => ['price' => 49.9, 'days' => 90], 'yearly' => ['price' => 198, 'days' => 365]];
        if (!isset($plans[$plan])) return ['success' => false, 'message' => '无效套餐'];
        $planConfig = $plans[$plan];
        $member = Member::find($memberId);
        $expireTime = max(time(), (int)($member->vip_expire_time ?? 0)) + ($planConfig['days'] * 86400);
        $member->vip_expire_time = $expireTime;
        $member->save();
        Db::name('subscription')->insert(['member_id' => $memberId, 'subscription_type' => 'vip', 'vip_plan' => $plan, 'vip_expire_time' => $expireTime, 'auto_renew' => 0, 'notify_frequency' => 'realtime', 'create_time' => time()]);
        Cache::clear();
        return ['success' => true, 'expire_time' => $expireTime];
    }

    public function unsubscribe(int $subscriptionId): array
    {
        Db::name('subscription')->where('id', $subscriptionId)->delete();
        Cache::clear();
        return ['success' => true];
    }

    public function checkVipStatus(int $memberId): array
    {
        $member = Member::find($memberId);
        $expireTime = (int)($member->vip_expire_time ?? 0);
        return ['is_vip' => $expireTime > time(), 'expire_time' => $expireTime, 'days_left' => max(0, (int)(($expireTime - time()) / 86400))];
    }

    public function getExpiringSoon(int $days = 7): array
    {
        $deadline = time() + ($days * 86400);
        return Db::name('subscription')->where('subscription_type', 'vip')->where('vip_expire_time', '>', time())->where('vip_expire_time', '<=', $deadline)->select()->toArray();
    }

    public function getStats(): array
    {
        return Cache::remember('stats', function() {
            $totalVip = Db::name('subscription')->where('subscription_type', 'vip')->where('vip_expire_time', '>', time())->count();
            $totalContent = Db::name('subscription')->where('subscription_type', 'content')->count();
            $monthStart = strtotime(date('Y-m-01'));
            $newVip = Db::name('subscription')->where('subscription_type', 'vip')->where('create_time', '>=', $monthStart)->count();
            return ['total_vip' => $totalVip, 'total_content_sub' => $totalContent, 'new_vip_this_month' => $newVip];
        }, 300);
    }
}
