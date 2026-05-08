<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Member;
use app\common\model\PointsLog;
use app\common\model\SigninLog;
use think\facade\Db;

/**
 * 签到服务
 */
class SigninService
{
    /**
     * 执行签到
     */
    public static function signin(int $memberId): array
    {
        $today = date('Y-m-d');
        $member = Member::find($memberId);
        if (!$member) throw new \Exception('会员不存在');

        // 检查今天是否已签到
        $exists = SigninLog::where('member_id', $memberId)
            ->where('signin_date', $today)
            ->find();
        if ($exists) throw new \Exception('今日已签到');

        // 计算连续签到天数
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $lastSignin = SigninLog::where('member_id', $memberId)
            ->order('signin_date', 'desc')
            ->find();

        $consecutiveDays = 1;
        if ($lastSignin && $lastSignin->signin_date == $yesterday) {
            $consecutiveDays = ($member->signin_count ?? 0) + 1;
        }

        // 计算积分
        $basePoints = PointsService::getConfig('signin', 5);
        $bonusPoints = 0;

        if ($consecutiveDays >= 7) {
            $bonusPoints = PointsService::getConfig('signin_7days', 30);
        } elseif ($consecutiveDays >= 3) {
            $bonusPoints = PointsService::getConfig('signin_3days', 10);
        }

        $totalPoints = $basePoints + $bonusPoints;

        Db::startTrans();
        try {
            // 签到记录（绕过模型严格字段检查）
            Db::name('signin_log')->insert([
                'member_id'        => $memberId,
                'signin_date'      => $today,
                'points'           => $totalPoints,
                'consecutive_days' => $consecutiveDays,
                'create_time'      => time(),
            ]);

            // 更新会员积分和签到信息
            Db::name('member')->where('id', $memberId)->update([
                'points'           => Db::raw('points + ' . $totalPoints),
                'total_points'     => Db::raw('total_points + ' . $totalPoints),
                'signin_count'     => $consecutiveDays,
                'last_signin_date' => $today,
            ]);

            // 积分日志（绕过模型严格字段检查）
            Db::name('points_log')->insert([
                'member_id'  => $memberId,
                'points'     => $totalPoints,
                'type'       => 'signin',
                'source_id'  => 0,
                'note'       => "签到第{$consecutiveDays}天",
                'create_time'=> time(),
            ]);

            // 检查等级升降
            MemberLevelService::checkUpgrade($memberId);

            Db::commit();

            // V2.9 邀请奖励：首次签到触发邀请人奖励
            InviteRewardService::onMemberEvent($memberId, 'signin');

            return [
                'points'           => $totalPoints,
                'base_points'      => $basePoints,
                'bonus_points'     => $bonusPoints,
                'consecutive_days' => $consecutiveDays,
            ];
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 获取签到日历数据（当月）
     */
    public static function getCalendar(int $memberId, string $month = ''): array
    {
        $month = $month ?: date('Y-m');
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $logs = SigninLog::where('member_id', $memberId)
            ->whereBetween('signin_date', [$startDate, $endDate])
            ->column('signin_date, consecutive_days', 'signin_date');

        $days = [];
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        while ($current <= $end) {
            $date = date('Y-m-d', $current);
            $days[] = [
                'date'             => $date,
                'is_signed'        => isset($logs[$date]),
                'consecutive_days' => $logs[$date]['consecutive_days'] ?? 0,
            ];
            $current = strtotime('+1 day', $current);
        }

        return $days;
    }
}
