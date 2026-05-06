<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Member;
use app\common\model\MemberLevel;
use think\facade\Db;

/**
 * 会员等级服务
 */
class MemberLevelService
{
    /**
     * 检查会员等级是否需要升降级
     */
    public static function checkUpgrade(int $memberId): bool
    {
        $member = Member::find($memberId);
        if (!$member) return false;

        $newLevel = MemberLevel::where('min_points', '<=', $member->total_points)
            ->order('min_points', 'desc')
            ->find();

        if ($newLevel && $newLevel->id != $member->level_id) {
            Db::name('member')->where('id', $memberId)->update(['level_id' => $newLevel->id]);
            return true;
        }

        return false;
    }

    /**
     * 获取等级信息（含下一等级进度）
     */
    public static function getLevelInfo(int $levelId, int $totalPoints = 0): array
    {
        $level = MemberLevel::find($levelId);
        if (!$level) return [];

        $nextLevel = MemberLevel::where('min_points', '>', $level->min_points)
            ->order('min_points', 'asc')
            ->find();

        $progress = 0;
        if ($nextLevel && $totalPoints > 0) {
            $range = $nextLevel->min_points - $level->min_points;
            $current = $totalPoints - $level->min_points;
            $progress = $range > 0 ? min(100, intval($current / $range * 100)) : 100;
        } elseif (!$nextLevel) {
            $progress = 100;
        }

        return [
            'id'              => $level->id,
            'name'            => $level->name,
            'icon'            => $level->icon,
            'price'           => $level->price,
            'discount'        => $level->discount,
            'points_rate'     => $level->points_rate,
            'daily_ai_quota'  => $level->daily_ai_quota,
            'min_points'      => $level->min_points,
            'next_level'      => $nextLevel ? [
                'name'       => $nextLevel->name,
                'min_points' => $nextLevel->min_points,
            ] : null,
            'progress'        => $progress,
        ];
    }

    /**
     * 获取所有等级列表
     */
    public static function getList(): array
    {
        return MemberLevel::order('sort', 'asc')->select()->toArray();
    }

    /**
     * 保存等级
     */
    public static function save(array $data): MemberLevel
    {
        if (!empty($data['id'])) {
            $level = MemberLevel::find($data['id']);
            if (!$level) throw new \Exception('等级不存在');
        } else {
            $level = new MemberLevel();
        }

        if (!empty($data['is_default'])) {
            MemberLevel::where('is_default', 1)->update(['is_default' => 0]);
        }

        $level->save($data);
        return $level;
    }

    /**
     * 删除等级
     */
    public static function delete(int $id): bool
    {
        $level = MemberLevel::find($id);
        if (!$level) throw new \Exception('等级不存在');
        if ($level->is_default) throw new \Exception('不能删除默认等级');

        $memberCount = Member::where('level_id', $id)->count();
        if ($memberCount > 0) throw new \Exception("该等级下有{$memberCount}个会员，不能删除");

        return $level->delete();
    }

    /**
     * 获取默认等级
     */
    public static function getDefaultLevel(): ?MemberLevel
    {
        return MemberLevel::where('is_default', 1)->find() ?? MemberLevel::order('sort', 'asc')->find();
    }
}
