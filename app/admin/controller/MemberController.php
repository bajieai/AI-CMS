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
use app\common\model\Member as MemberModel;
use app\common\model\MemberLevel;
use app\common\service\MemberService;
use think\Request;

class MemberController extends AdminBaseController
{
    protected MemberService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new MemberService;
    }

    /**
     * 会员列表
     */
    public function index(Request $request)
    {
        $query = MemberModel::with('level')->order('create_time', 'desc');

        if ($request->get('keyword')) {
            $query->where('username|email|nickname', 'like', '%' . $request->get('keyword') . '%');
        }
        if ($request->get('status') !== null && $request->get('status') !== '') {
            $query->where('status', (int) $request->get('status'));
        }

        $list = $query->paginate(15, false, ['query' => $request->get()]);
        $levels = MemberLevel::column('name', 'id');
        return $this->view('/member_list', ['list' => $list, 'levels' => $levels]);
    }

    /**
     * 添加会员
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $result = $this->service->adminSave($request->post());
            if ($result['success']) {
                $this->recordLog('添加会员', '用户名:' . $request->post('username'));
                return $this->success($result['msg'], $result['data'] ?? []);
            }
            return $this->error($result['msg']);
        }

        $levels = MemberLevel::order('sort', 'asc')->select();
        return $this->view('/member_edit', ['levels' => $levels, 'member' => null]);
    }

    /**
     * 编辑会员
     */
    public function edit(int $id)
    {
        $member = MemberModel::find($id);
        if (!$member) {
            return $this->error('会员不存在');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->service->adminSave($data, $id);
            if ($result['success']) {
                $this->recordLog('编辑会员', '会员ID:' . $id);
                return $this->success($result['msg'], $result['data'] ?? []);
            }
            return $this->error($result['msg']);
        }

        $levels = MemberLevel::order('sort', 'asc')->select();

        // V2.9.5 等级历史时间线
        $timeline = \app\common\model\MemberDowngradeLog::getTimeline($id);

        return $this->view('/member_edit', ['member' => $member, 'levels' => $levels, 'timeline' => $timeline]);
    }

    /**
     * 删除会员
     */
    public function delete(int $id)
    {
        $member = MemberModel::find($id);
        if (!$member) {
            return $this->error('会员不存在');
        }

        $member->delete();
        $this->recordLog('删除会员', '会员ID:' . $id);
        return $this->success('删除成功');
    }

    /**
     * 禁用/启用/切换会员状态
     */
    public function toggleStatus(int $id)
    {
        $member = MemberModel::find($id);
        if (!$member) {
            return $this->error('会员不存在');
        }
        $member->status = $member->status == 1 ? 0 : 1;
        $member->save();
        $this->recordLog('切换会员状态', '会员ID:' . $id . ' 状态:' . $member->status);
        return $this->success('操作成功');
    }

    /**
     * 审核通过会员
     */
    public function audit(int $id)
    {
        $member = MemberModel::find($id);
        if (!$member) {
            return $this->error('会员不存在');
        }
        if ($member->status != 2) {
            return $this->error('该会员不处于待审核状态');
        }
        $member->status = 1;
        $member->save();
        $this->recordLog('审核通过会员', '会员ID:' . $id);
        return $this->success('审核通过');
    }

    /**
     * 查看会员详情（AJAX）
     */
    public function detail(int $id)
    {
        $member = MemberModel::find($id);
        if (!$member) {
            return $this->error('会员不存在');
        }
        return $this->success('获取成功', $member->toArray());
    }
}
