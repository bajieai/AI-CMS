<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Member as MemberModel;
use think\Request;

class MemberController extends AdminBaseController
{
    /**
     * 会员列表
     */
    public function index(Request $request)
    {
        $query = MemberModel::order('create_time', 'desc');

        if ($request->get('keyword')) {
            $query->where('username|email|nickname', 'like', '%' . $request->get('keyword') . '%');
        }
        if ($request->get('status') !== null && $request->get('status') !== '') {
            $query->where('status', (int) $request->get('status'));
        }

        $list = $query->paginate(15, false, ['query' => $request->get()]);
        return $this->view('/member_list', ['list' => $list]);
    }

    /**
     * 查看会员详情
     */
    public function detail(int $id)
    {
        $member = MemberModel::find($id);
        if (!$member) {
            return $this->error('会员不存在');
        }
        return $this->success('获取成功', $member->toArray());
    }

    /**
     * 禁用/启用会员
     */
    public function toggleStatus(int $id)
    {
        $member = MemberModel::find($id);
        if (!$member) {
            return $this->error('会员不存在');
        }
        $member->status = $member->status ? 0 : 1;
        $member->save();
        $this->recordLog('切换会员状态', '会员ID:' . $id . ' 状态:' . $member->status);
        return $this->success('操作成功');
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

        if ($this->request->isGet()) {
            return $this->success('获取成功', $member->toArray());
        }

        $data = $this->request->post();
        if (!empty($data['nickname'])) {
            $member->nickname = $data['nickname'];
        }
        if (!empty($data['avatar'])) {
            $member->avatar = $data['avatar'];
        }
        if (isset($data['status'])) {
            $member->status = (int) $data['status'];
        }
        $member->save();
        $this->recordLog('编辑会员', '会员ID:' . $id);
        return $this->success('更新成功');
    }
}