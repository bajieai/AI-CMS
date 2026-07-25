<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Member;
use app\common\model\PointsLog;
use app\common\model\MemberLevel;
use think\facade\Db;

/**
 * 积分服务 - V2.5增强
 * 修复：consume()改为原子操作，消除竞态条件
 */
class PointsService
{
    /**
     * 增加积分（原子操作）
     * V2.6: 应用会员等级积分倍率
     */
    public static function add(int $memberId, int $points, string $type, int $sourceId = 0, string $note = ''): bool
    {
        if ($points <= 0) return false;

        // V2.6: 应用积分倍率
        $member = Member::find($memberId);
        $rate = 1.0;
        if ($member && $member->level_id) {
            $level = MemberLevel::find($member->level_id);
            if ($level && $level->points_rate > 0) {
                $rate = (float) $level->points_rate;
            }
        }
        $finalPoints = (int) round($points * $rate);

        Db::startTrans();
        try {
            // 原子增加
            Db::name('member')
                ->where('id', $memberId)
                ->inc('points', $finalPoints)
                ->inc('total_points', $finalPoints)
                ->update();

            PointsLog::create([
                'member_id' => $memberId,
                'points'    => $finalPoints,
                'type'      => $type,
                'source_id' => $sourceId,
                'note'      => $note . ($rate != 1.0 ? " (倍率x{$rate})" : ''),
            ]);

            MemberLevelService::checkUpgrade($memberId);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 消费积分 - V2.5安全修复
     * 原子操作：WHERE points >= N + DEC(points, N)
     * 消除"先查后减"竞态条件
     */
    public static function consume(int $memberId, int $points, string $type, int $sourceId = 0, string $note = ''): bool
    {
        if ($points <= 0) return false;

        Db::startTrans();
        try {
            // 原子扣减：WHERE条件保证余额充足，affected_rows=1表示成功
            $affected = Db::name('member')
                ->where('id', $memberId)
                ->where('points', '>=', $points)
                ->dec('points', $points)
                ->update();

            if ($affected === 0) {
                throw new \Exception('积分不足');
            }

            PointsLog::create([
                'member_id' => $memberId,
                'points'    => -$points,
                'type'      => $type,
                'source_id' => $sourceId,
                'note'      => $note,
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 检查每日积分获取上限
     */
    public static function checkDailyLimit(string $type, int $memberId): bool
    {
        $todayStart = strtotime('today');
        $count = PointsLog::where('member_id', $memberId)
            ->where('type', $type)
            ->where('points', '>', 0)
            ->where('create_time', '>=', $todayStart)
            ->count();

        $limitKey = "points_{$type}_daily_limit";
        $limit = (int) ConfigService::get($limitKey, 0);
        if ($limit > 0 && $count >= $limit) {
            return false;
        }
        return true;
    }

    /**
     * 获取积分配置值
     */
    public static function getConfig(string $key, int $default = 0): int
    {
        return (int) ConfigService::get("points_{$key}", $default);
    }

    /**
     * 获取会员当前积分
     */
    public static function getBalance(int $memberId): int
    {
        $member = Member::find($memberId);
        return $member ? (int) $member->points : 0;
    }
}
