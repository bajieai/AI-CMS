<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\member\MemberLevelService;

/**
 * 会员等级管理控制器 — V2.9.34 MEM-1
 */
class MemberLevelController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $service = new MemberLevelService();
        $levels = $service->getLevels();
        $comparison = $service->getBenefitsComparison();
        $this->assign('levels', $levels);
        $this->assign('comparison', $comparison);
        $this->assign('menuActive', 'member_level');
        return $this->view('/member_level/index');
    }

    public function add()
    {
        $this->assign('info', []);
        $this->assign('vip_free_read_mode', 0);
        $this->assign('menuActive', 'member_level');
        if ($this->request->param('modal')) {
            return $this->view('/member_level/edit_modal');
        }
        return $this->view('/member_level/edit');
    }

    public function edit($id = 0)
    {
        $id = (int)$id;
        $level = \app\common\model\MemberLevel::find($id);
        if (!$level) {
            return $this->error('等级不存在');
        }
        $this->assign('info', $level->toArray());
        $this->assign('vip_free_read_mode', 0);
        $this->assign('menuActive', 'member_level');
        if ($this->request->param('modal')) {
            return $this->view('/member_level/edit_modal');
        }
        return $this->view('/member_level/edit');
    }

    public function save()
    {
        $data = $this->request->post();
        $id = (int)($data['id'] ?? 0);
        $service = new MemberLevelService();
        $result = $service->saveLevel($data, $id);
        if ($result['success'] ?? false) {
            return $this->success('保存成功', $result);
        }
        return $this->error($result['message'] ?? '保存失败');
    }

    public function manualAdjust()
    {
        $memberId = (int)$this->request->post('member_id', 0);
        $levelId = (int)$this->request->post('level_id', 0);
        $service = new MemberLevelService();
        $result = $service->manualAdjust($memberId, $levelId);
        if ($result['success'] ?? false) {
            return $this->success('调整成功', $result);
        }
        return $this->error($result['message'] ?? '调整失败');
    }

    public function calculate()
    {
        $memberId = (int)$this->request->param('member_id', 0);
        $service = new MemberLevelService();
        $result = $service->calculateLevel($memberId);
        return json($result);
    }

    public function delete()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) {
            return $this->error('参数错误');
        }
        $level = \app\common\model\MemberLevel::find($id);
        if (!$level) {
            return $this->error('等级不存在');
        }
        if ($level->is_default == 1) {
            return $this->error('默认等级不可删除');
        }
        $level->status = 0;
        $level->save();
        \think\facade\Cache::clear();
        return $this->success('删除成功');
    }
}
