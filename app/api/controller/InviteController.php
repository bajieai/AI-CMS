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

namespace app\api\controller;

use app\common\model\Member;
use app\common\model\InviteLog;
use app\common\service\ConfigService;
use think\facade\Request;

/**
 * 邀请奖励前台API
 * @api_group 邀请奖励
 * @api_desc 邀请码、邀请统计、奖励查询等接口
 */
class InviteController extends BaseController
{
    /**
     * 获取我的邀请信息
     * @api 我的邀请信息
     * @api_desc 获取当前会员的邀请码、邀请人数、奖励金额和三阶段统计数据
     * @return json 返回邀请码/邀请统计/各阶段奖励
     * @api_auth yes
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
     * @api 邀请记录列表
     * @api_desc 分页获取当前会员的邀请记录
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return json 返回邀请记录列表
     * @api_auth yes
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
