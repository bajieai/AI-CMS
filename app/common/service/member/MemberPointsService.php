<?php
declare(strict_types=1);

namespace app\common\service\member;

use app\common\model\MemberPointsLog;
use app\common\model\Member;
use think\facade\Cache;

class MemberPointsService
{
    private const CACHE_TAG = 'member_points';
    private const DAILY_LIMIT = 100;

    public function addPoints(int $memberId, int $points, string $type, string $source = '', int $refId = 0): array
    {
        $todayPoints = MemberPointsLog::where('member_id', $memberId)->where('type', '<>', 'admin')->whereTime('create_time', 'today')->sum('points');
        if ($todayPoints + $points > self::DAILY_LIMIT) return ['success' => false, 'message' => '今日积分获取已达上限'];
        if ($refId > 0) {
            $exists = MemberPointsLog::where('member_id', $memberId)->where('type', $type)->where('ref_id', $refId)->find();
            if ($exists) return ['success' => false, 'message' => '已获取过积分'];
        }
        $member = Member::find($memberId);
        if (!$member) return ['success' => false];
        $balance = (int)$member->points + $points;
        $totalPoints = (int)$member->total_points + max(0, $points);
        $member->points = $balance;
        $member->total_points = $totalPoints;
        $member->save();
        MemberPointsLog::create(['member_id' => $memberId, 'points' => $points, 'balance' => $balance, 'type' => $type, 'source' => $source, 'ref_id' => $refId, 'create_time' => time()]);
        $levelResult = (new MemberLevelService())->calculateLevel($memberId);
        Cache::clear();
        return ['success' => true, 'balance' => $balance, 'level_changed' => $levelResult];
    }

    public function deductPoints(int $memberId, int $points, string $type, string $source = ''): array
    {
        $member = Member::find($memberId);
        if (!$member || $member->points < $points) return ['success' => false, 'message' => '积分不足'];
        $balance = (int)$member->points - $points;
        $member->points = $balance;
        $member->save();
        MemberPointsLog::create(['member_id' => $memberId, 'points' => -$points, 'balance' => $balance, 'type' => $type, 'source' => $source, 'create_time' => time()]);
        Cache::clear();
        return ['success' => true, 'balance' => $balance];
    }

    public function getLogs(int $memberId, array $params = []): array
    {
        $query = MemberPointsLog::where('member_id', $memberId);
        if (!empty($params['type'])) $query->where('type', $params['type']);
        $total = $query->count();
        $page = max(1, (int)($params['page'] ?? 1));
        $list = $query->order('create_time', 'desc')->page($page, 20)->select()->toArray();
        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    public function getStats(): array
    {
        return Cache::remember('stats', function() {
            return ['total_issued' => MemberPointsLog::where('points', '>', 0)->sum('points'), 'total_consumed' => abs(MemberPointsLog::where('points', '<', 0)->sum('points')), 'active_members' => MemberPointsLog::distinct('member_id')->count()];
        }, 300);
    }

    public function manualAdjust(int $memberId, int $points, string $reason): array
    {
        $member = Member::find($memberId);
        if (!$member) return ['success' => false];
        $balance = (int)$member->points + $points;
        $member->points = $balance;
        if ($points > 0) $member->total_points = (int)$member->total_points + $points;
        $member->save();
        MemberPointsLog::create(['member_id' => $memberId, 'points' => $points, 'balance' => $balance, 'type' => 'admin', 'source' => $reason, 'create_time' => time()]);
        Cache::clear();
        return ['success' => true, 'balance' => $balance];
    }
}
