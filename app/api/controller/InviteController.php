<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\model\Member;
use app\common\model\InviteLog;
use app\common\service\ConfigService;
use think\facade\Request;

/**
 * 邀请奖励前台API - V2.9新增
 */
class InviteController extends BaseController
{
    /**
     * 获取我的邀请信息
     * GET /api/invite/info
     */
    public function info()
    {
        $memberId = $this->getMemberId();
        if (!$memberId) {
            return $this->error('请先登录', 401);
        }

        $member = Member::find($memberId);
        $inviteCode = $member ? ($member->invite_code ?? '') : '';
        $inviteCount = InviteLog::where('inviter_id', $memberId)->count();
        $inviteReward = InviteLog::where('inviter_id', $memberId)->sum('reward_amount');

        // V2.9: 三阶段邀请统计
        $stageCounts = [
            'register' => InviteLog::where('inviter_id', $memberId)->where('reward_stage', '>=', 0)->count(),
            'signin'   => InviteLog::where('inviter_id', $memberId)->where('reward_stage', '>=', 1)->count(),
            'pay'      => InviteLog::where('inviter_id', $memberId)->where('reward_stage', '>=', 2)->count(),
        ];
        $stagePoints = [
            'register' => (int) ConfigService::get('invite_reward_register', 10),
            'signin'   => (int) ConfigService::get('invite_reward_signin', 20),
            'pay'      => (int) ConfigService::get('invite_reward_pay', 50),
        ];

        return $this->success([
            'invite_code'    => $inviteCode,
            'invite_count'   => (int) $inviteCount,
            'invite_reward'  => (float) $inviteReward,
            'invite_url'     => '/pages/index/index?invite_by=' . $memberId,
            'stage_counts'   => $stageCounts,
            'stage_points'   => $stagePoints,
            'stage_stats'    => $stageCounts, // 向后兼容
        ]);
    }

    /**
     * 获取邀请记录
     * GET /api/invite/records
     */
    public function records()
    {
        $memberId = $this->getMemberId();
        if (!$memberId) {
            return $this->error('请先登录', 401);
        }

        $page  = (int) Request::get('page', 1);
        $limit = (int) Request::get('limit', 20);

        $list = InviteLog::where('inviter_id', $memberId)
            ->order('id', 'desc')
            ->paginate(['list_rows' => $limit, 'page' => $page]);

        return $this->success([
            'list'  => $list->items(),
            'total' => $list->total(),
            'page'  => $page,
        ]);
    }
}
