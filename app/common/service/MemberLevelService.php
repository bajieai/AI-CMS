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
        $member = Db::name('member')->where('id', $memberId)->find();
        if (!$member) return false;

        $oldLevelId = $member['level_id'];
        $newLevel = Db::name('member_level')->where('min_points', '<=', $member['total_points'])
            ->order('min_points', 'desc')
            ->find();

        if ($newLevel && $newLevel['id'] != $oldLevelId) {
            Db::name('member')->where('id', $memberId)->update(['level_id' => $newLevel['id']]);

            // V2.9.2 M20: 等级变更通知
            self::notifyLevelChange($memberId, $oldLevelId, $newLevel['id']);

            return true;
        }

        return false;
    }

    /**
     * V2.9.2 M20: 手动降级会员
     */
    public static function manualDowngrade(int $memberId, int $targetLevelId): array
    {
        if (!config('member.level_manual_downgrade', 1)) {
            return ['success' => false, 'msg' => '系统未启用手动降级功能'];
        }

        $member = Member::find($memberId);
        if (!$member) {
            return ['success' => false, 'msg' => '会员不存在'];
        }

        $targetLevel = MemberLevel::find($targetLevelId);
        if (!$targetLevel) {
            return ['success' => false, 'msg' => '目标等级不存在'];
        }

        $oldLevelId = $member->level_id;
        if ($oldLevelId == $targetLevelId) {
            return ['success' => false, 'msg' => '目标等级与当前等级相同'];
        }

        $member->level_id = $targetLevelId;
        $member->save();

        // 发送降级通知
        self::notifyLevelChange($memberId, $oldLevelId, $targetLevelId);

        return ['success' => true, 'msg' => '降级成功'];
    }

    /**
     * V2.9.2 M20: 等级变更通知
     */
    protected static function notifyLevelChange(int $memberId, int $oldLevelId, int $newLevelId): void
    {
        if (!config('member.level_change_notify', 1)) {
            return;
        }

        try {
            $oldLevel = MemberLevel::find($oldLevelId);
            $newLevel = MemberLevel::find($newLevelId);
            if (!$oldLevel || !$newLevel) return;

            $isUpgrade = $newLevel->sort > $oldLevel->sort;
            $service = new NotificationService();

            if ($isUpgrade) {
                $service->send(
                    'member',
                    $memberId,
                    'level_upgrade',
                    '等级升级通知',
                    "恭喜！您的会员等级已升级至「{$newLevel->name}」，尊享更多权益。",
                    '/member/level'
                );
            } else {
                $service->send(
                    'member',
                    $memberId,
                    'level_downgrade',
                    '等级调整通知',
                    "您的会员等级已调整为「{$newLevel->name}」，当前等级权益已生效。",
                    '/member/level'
                );
            }
        } catch (\Throwable $e) {
            \think\facade\Log::warning("[MemberLevel] 等级变更通知失败 member_id={$memberId}: " . $e->getMessage());
        }
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
            'id'                    => $level->id,
            'name'                  => $level->name,
            'icon'                  => $level->icon,
            'price'                 => $level->price,
            'discount'              => $level->discount,
            'points_rate'           => $level->points_rate,
            'daily_ai_quota'        => $level->daily_ai_quota,
            'min_points'            => $level->min_points,
            'is_vip'                => $level->is_vip,
            // V2.9.2 M20 新增权益字段
            'vip_badge_icon'        => $level->vip_badge_icon ?? '',
            'exclusive_content_ids' => json_decode($level->exclusive_content_ids ?? '[]', true),
            'auto_downgrade_days'   => $level->auto_downgrade_days ?? 0,
            'next_level'            => $nextLevel ? [
                'name'       => $nextLevel->name,
                'min_points' => $nextLevel->min_points,
            ] : null,
            'progress'              => $progress,
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
