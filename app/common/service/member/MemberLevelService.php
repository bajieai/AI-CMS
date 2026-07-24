<?php
declare(strict_types=1);

namespace app\common\service\member;

use app\common\model\MemberLevel;
use app\common\model\Member;
use think\facade\Cache;

class MemberLevelService
{
    private const CACHE_TAG = 'member_level';

    public function calculateLevel(int $memberId): array
    {
        $member = Member::find($memberId);
        if (!$member) return ['success' => false];
        $points = (int)$member->total_points;
        $level = MemberLevel::where('min_points', '<=', $points)->where('max_points', '>=', $points)->where('status', 1)->order('level_order', 'desc')->find();
        if (!$level) $level = MemberLevel::order('level_order', 'asc')->find();
        if (!$level) return ['success' => false];

        $oldLevelId = (int)$member->level_id;
        if ($level->id != $oldLevelId) {
            $member->level_id = $level->id;
            if ($level->validity_days > 0) $member->level_expire_time = time() + ($level->validity_days * 86400);
            $member->save();
            Cache::clear();
        }
        $nextLevel = MemberLevel::where('level_order', '>', $level->level_order)->order('level_order', 'asc')->find();
        return ['current_level' => $level->toArray(), 'next_level' => $nextLevel ? $nextLevel->toArray() : null, 'upgrade_progress' => $this->calcProgress($points, $level, $nextLevel)];
    }

    public function getLevels(): array
    {
        return Cache::remember('all_levels', function() {
            return MemberLevel::where('status', 1)->order('level_order', 'asc')->select()->toArray();
        }, 3600);
    }

    public function saveLevel(array $data, int $id = 0): array
    {
        if ($id > 0) MemberLevel::where('id', $id)->update($data);
        else { $level = new MemberLevel($data); $level->save(); $id = $level->id; }
        Cache::clear();
        return ['success' => true, 'id' => $id];
    }

    public function manualAdjust(int $memberId, int $levelId): array
    {
        $member = Member::find($memberId);
        if (!$member) return ['success' => false];
        $member->level_id = $levelId;
        $level = MemberLevel::find($levelId);
        if ($level && $level->validity_days > 0) $member->level_expire_time = time() + ($level->validity_days * 86400);
        $member->save();
        Cache::clear();
        return ['success' => true];
    }

    public function getBenefitsComparison(): array
    {
        $levels = $this->getLevels();
        foreach ($levels as &$level) {
            $level['benefits'] = is_string($level['benefits']) ? json_decode($level['benefits'], true) : $level['benefits'];
        }
        return $levels;
    }

    private function calcProgress(int $points, $current, $next): int
    {
        if (!$next) return 100;
        $range = $next->min_points - $current->min_points;
        $done = $points - $current->min_points;
        return $range > 0 ? min(100, (int)($done / $range * 100)) : 100;
    }
}
