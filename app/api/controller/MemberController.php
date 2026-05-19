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

namespace app\api\controller;

use app\common\controller\ApiController;
use app\common\model\Member;
use app\common\service\CacheService;
use think\facade\Config;
use think\facade\Log;

/**
 * 小程序会员接口 - V2.9 M4新增
 *
 * 路由前缀：/api/v1/member
 */
class MemberController extends ApiController
{
    /**
     * 获取会员信息
     * GET /member/info
     */
    public function info(): \think\Response
    {
        $memberId = $this->getMemberId();
        if ($memberId <= 0) {
            return json(['code' => 1, 'msg' => '请先登录']);
        }

        $member = Member::find($memberId);
        if (!$member) {
            return json(['code' => 1, 'msg' => '会员不存在']);
        }

        return json(['code' => 0, 'data' => [
            'id'            => $member->id,
            'username'      => $member->username,
            'nickname'      => $member->nickname,
            'avatar'        => $member->avatar,
            'points'        => $member->points,
            'signin_count'  => $member->signin_count ?? 0,
            'vip_status'    => $member->vip_status,
            'vip_expire'    => $member->vip_expire,
        ]]);
    }

    /**
     * 签到
     * POST /member/signin
     */
    public function signin(): \think\Response
    {
        $memberId = $this->getMemberId();
        if ($memberId <= 0) {
            return json(['code' => 1, 'msg' => '请先登录']);
        }

        $member = Member::find($memberId);
        if (!$member) {
            return json(['code' => 1, 'msg' => '会员不存在']);
        }

        // 检查今日是否已签到
        $today = date('Y-m-d');
        $exists = \app\common\model\SigninLog::where('member_id', $memberId)
            ->where('signin_date', $today)
            ->find();

        if ($exists) {
            return json(['code' => 1, 'msg' => '今日已签到']);
        }

        // 签到规则
        $pointsBase  = (int) Config::get('signin.points_base', 5);
        $continuousBonus = (int) Config::get('signin.continuous_bonus', 2);
        $streakThreshold  = (int) Config::get('signin.streak_threshold', 7);

        // 计算连续签到天数
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $yesterdaySignin = \app\common\model\SigninLog::where('member_id', $memberId)
            ->where('signin_date', $yesterday)
            ->find();

        $streakDays = 0;
        if ($yesterdaySignin) {
            $streakDays = ($member->signin_streak ?? 0) + 1;
        } else {
            $streakDays = 1;
        }

        // 连续奖励
        $points = $pointsBase;
        $bonusMsg = '';
        if ($streakDays >= $streakThreshold) {
            $points += $continuousBonus;
            $bonusMsg = "，连续签到{$streakDays}天额外奖励{$continuousBonus}积分";
        }

        // 7日连续签到额外奖励（M6联动：邀请奖励事件）
        $inviteRewardTriggered = false;
        if ($streakDays >= 7 && ($member->signin_streak ?? 0) < 7) {
            $inviteRewardTriggered = true;
        }

        // 记录签到
        \app\common\model\SigninLog::create([
            'member_id'   => $memberId,
            'signin_date'  => $today,
            'points'      => $points,
            'streak_days' => $streakDays,
        ]);

        // 更新会员积分和连续天数
        $member->points       = ($member->points ?? 0) + $points;
        $member->signin_count = ($member->signin_count ?? 0) + 1;
        $member->signin_streak = $streakDays;
        $member->last_signin  = $today;
        $member->save();

        // 触发邀请奖励事件（如果满足7日连续）
        if ($inviteRewardTriggered) {
            try {
                \app\common\service\InviteRewardService::trigger(
                    $memberId,
                    'signin_complete',
                    ['streak_days' => $streakDays]
                );
            } catch (\Throwable $e) {
                Log::warning('[signin] 邀请奖励触发失败: ' . $e->getMessage());
            }
        }

        CacheService::clearByTag(CacheService::TAG_MEMBER);

        return json(['code' => 0, 'msg' => '签到成功', 'data' => [
            'points'        => $member->points,
            'today_points'  => $points,
            'streak_days'   => $streakDays,
            'bonus_msg'     => $bonusMsg,
        ]]);
    }

    /**
     * 检查今日是否已签到
     * GET /member/hasSignedToday
     */
    public function hasSignedToday(): \think\Response
    {
        $memberId = $this->getMemberId();
        if ($memberId <= 0) {
            return json(['code' => 0, 'data' => ['signed' => false]]);
        }

        $today = date('Y-m-d');
        $exists = \app\common\model\SigninLog::where('member_id', $memberId)
            ->where('signin_date', $today)
            ->find();

        return json(['code' => 0, 'data' => [
            'signed'       => (bool) $exists,
            'streak_days'  => $member->signin_streak ?? 0,
        ]]);
    }

    /**
     * 获取签到记录
     * GET /member/signinLog
     */
    public function signinLog(): \think\Response
    {
        $memberId = $this->getMemberId();
        if ($memberId <= 0) {
            return json(['code' => 1, 'msg' => '请先登录']);
        }

        $logs = \app\common\model\SigninLog::where('member_id', $memberId)
            ->order('signin_date', 'desc')
            ->limit(30)
            ->select();

        return json(['code' => 0, 'data' => $logs]);
    }

    /**
     * 从Token中获取会员ID（复用ApiController机制）
     */
    protected function getMemberId(): int
    {
        $token = $this->request->header('Authorization', '');
        if (preg_match('/Bearer\s+(.*)$/i', $token, $matches)) {
            $token = $matches[1];
        }

        if (empty($token)) {
            return 0;
        }

        $cacheKey = 'api_token:' . $token;
        $cached   = cache($cacheKey);
        if (empty($cached['member_id'])) {
            return 0;
        }

        return (int) $cached['member_id'];
    }
}
