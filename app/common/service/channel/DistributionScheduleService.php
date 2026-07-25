<?php
declare(strict_types=1);

namespace app\common\service\channel;

use think\facade\Db;
use think\facade\Cache;

class DistributionScheduleService
{
    private const CACHE_TAG = 'dist_schedule';

    public function create(int $contentId, array $platforms, int $scheduleTime): array
    {
        $id = Db::name('distribution_schedule')->insertGetId([
            'content_id' => $contentId, 'platforms' => json_encode($platforms),
            'schedule_time' => $scheduleTime, 'status' => 'pending', 'create_time' => time(),
        ]);
        Cache::clear();
        return ['success' => true, 'id' => $id];
    }

    public function cancel(int $scheduleId): array
    {
        Db::name('distribution_schedule')->where('id', $scheduleId)->where('status', 'pending')->update(['status' => 'cancelled']);
        Cache::clear();
        return ['success' => true];
    }

    public function getPendingList(): array
    {
        return Db::name('distribution_schedule')->where('status', 'pending')->where('schedule_time', '<=', time() + 60)->select()->toArray();
    }

    public function execute(int $scheduleId): array
    {
        $schedule = Db::name('distribution_schedule')->find($scheduleId);
        if (!$schedule || $schedule['status'] !== 'pending') return ['success' => false];
        $platforms = json_decode($schedule['platforms'], true) ?: [];
        $results = [];
        foreach ($platforms as $platform) {
            if ($platform['type'] === 'wechat') {
                $results[] = (new WeChatChannelService())->publish($schedule['content_id'], $platform['account_id']);
            } else {
                $results[] = (new PlatformChannelService())->publish($schedule['content_id'], $platform['account_id']);
            }
        }
        Db::name('distribution_schedule')->where('id', $scheduleId)->update(['status' => 'completed', 'execute_time' => time()]);
        Cache::clear();
        return ['success' => true, 'results' => $results];
    }

    public function getAutoRules(): array
    {
        return Cache::remember('auto_rules', function() {
            return Db::name('distribution_auto_rule')->where('status', 1)->select()->toArray();
        }, 300);
    }

    public function saveAutoRule(array $rule): array
    {
        $id = Db::name('distribution_auto_rule')->insertGetId(array_merge($rule, ['create_time' => time()]));
        Cache::clear();
        return ['success' => true, 'id' => $id];
    }
}
