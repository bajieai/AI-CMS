<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
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

            // V2.9.5: 记录升级日志（时间线数据源）
            try {
                \app\common\model\MemberDowngradeLog::create([
                    'user_id' => $memberId,
                    'from_level' => $oldLevelId,
                    'to_level' => $newLevel['id'],
                    'action' => 'auto_upgrade',
                    'trigger_condition' => 'points_reached',
                    'notified' => 1,
                ]);
            } catch (\Throwable $e) {
                \think\facade\Log::warning("[MemberLevel] 升级日志写入失败 member_id={$memberId}: " . $e->getMessage());
            }

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
     * V2.9.3 M20: 获取会员等级进度详情
     */
    public static function getLevelProgress(int $memberId): array
    {
        $member = Member::find($memberId);
        if (!$member) {
            return ['success' => false, 'msg' => '会员不存在'];
        }

        $currentLevel = MemberLevel::find($member->level_id);
        if (!$currentLevel) {
            $currentLevel = self::getDefaultLevel();
        }

        $nextLevel = MemberLevel::where('min_points', '>', $currentLevel->min_points)
            ->order('min_points', 'asc')
            ->find();

        $progress = 0;
        $needPoints = 0;
        $currentPoints = $member->total_points;

        if ($nextLevel) {
            $range = $nextLevel->min_points - $currentLevel->min_points;
            $current = $currentPoints - $currentLevel->min_points;
            $progress = $range > 0 ? min(100, max(0, intval($current / $range * 100))) : 100;
            $needPoints = max(0, $nextLevel->min_points - $currentPoints);
        } else {
            $progress = 100;
        }

        // 缓冲期信息（使用独立方法）
        $graceInfo = null;
        $grace = self::isInGracePeriod($memberId);
        if ($grace['in_grace']) {
            $graceInfo = [
                'end_time' => $grace['end_time'],
                'end_date' => date('Y-m-d H:i:s', $grace['end_time']),
                'days_left' => $grace['days_left'],
            ];
        }

        $icon = $currentLevel->icon ?? '';
        // 纯类名（如badge-lv1、bi bi-award）不作为img src，而是留给模板用onerror降级
        if ($icon !== '' && !str_starts_with($icon, '/') && !str_starts_with($icon, 'http://') && !str_starts_with($icon, 'https://')) {
            $icon = ''; // 非URL（如badge-lv1或bi bi-award），模板走{else}显示默认图标
        }

        return [
            'success' => true,
            'current' => [
                'id' => $currentLevel->id,
                'name' => $currentLevel->name,
                'icon' => $icon,
                'min_points' => $currentLevel->min_points,
            ],
            'next' => $nextLevel ? [
                'id' => $nextLevel->id,
                'name' => $nextLevel->name,
                'min_points' => $nextLevel->min_points,
            ] : null,
            'progress' => $progress,
            'current_points' => $currentPoints,
            'need_points' => $needPoints,
            'grace' => $graceInfo,
        ];
    }

    /**
     * V2.9.3 M20: 判断会员是否在缓冲期内
     * @return array ['in_grace' => bool, 'end_time' => int, 'days_left' => int]
     */
    public static function isInGracePeriod(int $memberId): array
    {
        $now = time();
        $result = ['in_grace' => false, 'end_time' => 0, 'days_left' => 0];

        try {
            $graceEndTime = Db::name('member')->where('id', $memberId)->value('grace_end_time') ?: 0;
            if ($graceEndTime > $now) {
                $result['in_grace'] = true;
                $result['end_time'] = $graceEndTime;
                $result['days_left'] = ceil(($graceEndTime - $now) / 86400);
            }
        } catch (\Throwable) {
            // grace_end_time字段不存在则不在缓冲期
        }

        return $result;
    }

    /**
     * V2.9.3 M20: 自动降级扫描（含7天缓冲期）
     * 逻辑：
     * 1. 积分不满足当前等级要求 → 进入7天缓冲期（发送预警通知）
     * 2. 已在缓冲期且积分恢复 → 取消缓冲期
     * 3. 已在缓冲期且过期 → 执行降级（发送降级通知）
     */
    public static function autoDowngrade(): array
    {
        $now = time();
        $graceDays = (int) config('member.auto_downgrade_grace_days', 7);
        $defaultLevel = self::getDefaultLevel();
        $defaultLevelId = $defaultLevel ? $defaultLevel->id : 0;

        $warned = 0;
        $downgraded = 0;
        $cancelled = 0;

        // 获取所有等级（按sort升序）
        $levels = MemberLevel::order('sort', 'asc')->column('min_points', 'id');
        if (empty($levels)) {
            return ['warned' => 0, 'downgraded' => 0, 'cancelled' => 0];
        }

        $members = Member::where('level_id', '>', $defaultLevelId)->select();
        foreach ($members as $member) {
            $currentMinPoints = $levels[$member->level_id] ?? PHP_INT_MAX;

            // 使用独立方法判断缓冲期状态
            $grace = self::isInGracePeriod($member->id);
            $hasGrace = $grace['in_grace'];
            $graceEndTime = $grace['end_time'];

            // 积分满足等级要求
            if ($member->total_points >= $currentMinPoints) {
                // 如果在缓冲期中，取消缓冲期
                if ($hasGrace) {
                    try {
                        Db::name('member')->where('id', $member->id)->update(['grace_end_time' => 0]);
                        $cancelled++;
                    } catch (\Throwable) {}
                }
                continue;
            }

            // 积分不满足
            if (!$hasGrace) {
                // 未在缓冲期：设置缓冲期并发送预警
                try {
                    Db::name('member')->where('id', $member->id)->update([
                        'grace_end_time' => $now + $graceDays * 86400,
                    ]);
                    self::notifyGraceWarning($member->id, $graceDays);
                    $warned++;
                } catch (\Throwable) {
                    // 字段不存在则直接降级（无缓冲期）
                    self::performDowngrade($member->id, $levels, $defaultLevelId);
                    $downgraded++;
                }
            } elseif ($graceEndTime <= $now) {
                // 缓冲期已过期：执行降级
                self::performDowngrade($member->id, $levels, $defaultLevelId);
                $downgraded++;
            }
        }

        return [
            'warned' => $warned,
            'downgraded' => $downgraded,
            'cancelled' => $cancelled,
        ];
    }

    /**
     * 执行降级到满足条件的最高等级
     */
    protected static function performDowngrade(int $memberId, array $levels, int $defaultLevelId, string $triggerCondition = 'points_insufficient'): void
    {
        $member = Member::find($memberId);
        if (!$member) return;

        $oldLevelId = $member->level_id;
        $newLevelId = $defaultLevelId;

        // 找到满足条件的最高等级
        foreach ($levels as $id => $minPoints) {
            if ($member->total_points >= $minPoints) {
                $newLevelId = $id;
            }
        }

        if ($newLevelId != $oldLevelId) {
            $update = ['level_id' => $newLevelId];
            try {
                $update['grace_end_time'] = 0;
            } catch (\Throwable) {}
            Db::name('member')->where('id', $memberId)->update($update);
            self::notifyLevelChange($memberId, $oldLevelId, $newLevelId);

            // V2.9.4: 记录降级日志
            try {
                \app\common\model\MemberDowngradeLog::create([
                    'user_id' => $memberId,
                    'from_level' => $oldLevelId,
                    'to_level' => $newLevelId,
                    'action' => 'auto_downgrade',
                    'trigger_condition' => $triggerCondition,
                    'notified' => 1,
                ]);
            } catch (\Throwable) {}
        }
    }

    /**
     * V2.9.3 M20: 缓冲期预警通知
     */
    protected static function notifyGraceWarning(int $memberId, int $days): void
    {
        try {
            $service = new NotificationService();
            $service->send(
                'member',
                $memberId,
                'level_grace_warning',
                '等级降级预警',
                "您的会员积分已不满足当前等级要求，系统将在 {$days} 天后自动降级。请及时赚取积分以保持等级。",
                '/member/level'
            );
        } catch (\Throwable $e) {
            \think\facade\Log::warning("[MemberLevel] 缓冲期预警通知失败 member_id={$memberId}: " . $e->getMessage());
        }
    }

    /**
     * 获取默认等级
     */
    public static function getDefaultLevel(): ?MemberLevel
    {
        return MemberLevel::where('is_default', 1)->find() ?? MemberLevel::order('sort', 'asc')->find();
    }
}
