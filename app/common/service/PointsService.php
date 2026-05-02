<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Member;
use app\common\model\PointsLog;
use think\facade\Db;

/**
 * 积分服务
 */
class PointsService
{
    /**
     * 增加积分
     */
    public static function add(int $memberId, int $points, string $type, int $sourceId = 0, string $note = ''): bool
    {
        if ($points <= 0) return false;

        Db::startTrans();
        try {
            Db::name('member')
                ->where('id', $memberId)
                ->inc('points', $points)
                ->inc('total_points', $points)
                ->update();

            PointsLog::create([
                'member_id' => $memberId,
                'points'    => $points,
                'type'      => $type,
                'source_id' => $sourceId,
                'note'      => $note,
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
     * 消费积分
     */
    public static function consume(int $memberId, int $points, string $type, int $sourceId = 0, string $note = ''): bool
    {
        if ($points <= 0) return false;

        $member = Member::find($memberId);
        if (!$member || $member->points < $points) {
            throw new \Exception('积分不足');
        }

        Db::startTrans();
        try {
            Db::name('member')
                ->where('id', $memberId)
                ->dec('points', $points)
                ->update();

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
}
