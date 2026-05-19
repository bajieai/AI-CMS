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

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\InviteLog;
use app\common\model\Member;
use think\facade\Db;

/**
 * 邀请返积分管理 - V2.8新增
 */
class InviteController extends AdminBaseController
{
    /**
     * 邀请统计排行
     */
    public function index()
    {
        $params = $this->request->param();
        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 20);

        // 邀请排行统计
        $rankQuery = Db::table('i8j_invite_relation')
            ->field('inviter_id, COUNT(*) as invite_count, SUM(reward_points) as total_points')
            ->group('inviter_id')
            ->order('invite_count', 'desc');

        $total = Db::table('i8j_invite_relation')
            ->field('COUNT(DISTINCT inviter_id) as total')
            ->find()['total'] ?? 0;

        $rankList = $rankQuery->page($page, $limit)->select()->toArray();

        // 补充邀请人信息
        foreach ($rankList as &$item) {
            $member = Member::find($item['inviter_id']);
            $item['inviter_name'] = $member ? ($member->nickname ?: $member->username) : '未知用户';
            $item['inviter_avatar'] = $member ? $member->avatar : '';
        }

        // 总体统计
        $stats = [
            'total_inviters' => Db::table('i8j_invite_relation')->field('COUNT(DISTINCT inviter_id) as c')->find()['c'] ?? 0,
            'total_invitees' => Db::table('i8j_invite_relation')->count(),
            'total_points'   => Db::table('i8j_invite_relation')->sum('reward_points') ?? 0,
        ];

        $this->assign([
            'list'  => $rankList,
            'page'  => $page,
            'limit' => $limit,
            'total' => $total,
            'stats' => $stats,
        ]);
        return $this->view('/invite_index');
    }

    /**
     * 邀请明细
     */
    public function detail(int $inviterId)
    {
        $member = Member::find($inviterId);
        $list = InviteLog::where('inviter_id', $inviterId)
            ->order('create_time', 'desc')
            ->paginate(20);

        $this->assign([
            'member' => $member,
            'list'   => $list,
        ]);
        return $this->view('/invite_detail');
    }
}
