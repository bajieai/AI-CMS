<?php
declare(strict_types=1);

namespace app\common\service\channel;

use think\facade\Db;
use think\facade\Cache;

class DistributionLogService
{
    private const CACHE_TAG = 'distribution_log';

    public function log(array $data): array
    {
        $id = Db::name('push_channel')->insertGetId(array_merge($data, ['create_time' => time()]));
        Cache::clear();
        return ['success' => true, 'id' => $id];
    }

    public function getLogs(array $params = []): array
    {
        $query = Db::name('push_channel')->order('create_time', 'desc');
        if (!empty($params['platform_type'])) $query->where('platform_type', $params['platform_type']);
        if (!empty($params['status']) || $params['status'] === '0') $query->where('status', $params['status']);
        if (!empty($params['content_id'])) $query->where('content_id', $params['content_id']);
        if (!empty($params['start_date'])) $query->where('create_time', '>=', strtotime($params['start_date']));
        if (!empty($params['end_date'])) $query->where('create_time', '<', strtotime($params['end_date']) + 86400);
        $total = $query->count();
        $page = max(1, (int)($params['page'] ?? 1));
        $list = $query->page($page, 20)->select()->toArray();
        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    public function updateEffect(int $logId, array $effectData): void
    {
        Db::name('push_channel')->where('id', $logId)->update($effectData);
        Cache::clear();
    }

    public function getEffectOverview(): array
    {
        return Cache::remember('effect_overview', function() {
            $todayStart = strtotime('today');
            $weekStart = strtotime('monday this week');
            $monthStart = strtotime(date('Y-m-01'));
            return [
                'today' => Db::name('push_channel')->where('create_time', '>=', $todayStart)->count(),
                'week' => Db::name('push_channel')->where('create_time', '>=', $weekStart)->count(),
                'month' => Db::name('push_channel')->where('create_time', '>=', $monthStart)->count(),
                'total_reads' => Db::name('push_channel')->sum('reads'),
                'total_likes' => Db::name('push_channel')->sum('likes'),
            ];
        }, 300);
    }

    public function getEffectRanking(): array
    {
        return Cache::remember('effect_ranking', function() {
            return Db::name('push_channel')->field('platform_type, COUNT(*) as count, SUM(reads) as reads, SUM(likes) as likes')
                ->group('platform_type')->order('reads', 'desc')->select()->toArray();
        }, 300);
    }
}
