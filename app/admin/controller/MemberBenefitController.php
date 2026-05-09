<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Member;
use app\common\model\MemberLevel;
use app\common\service\MemberLevelService;

/**
 * 会员权益配置后台控制器 - V2.9.2 M20
 */
class MemberBenefitController extends AdminBaseController
{
    /**
     * 权益配置页
     */
    public function edit()
    {
        $id = (int) $this->request->get('id', 0);
        if ($id <= 0) {
            return $this->error('参数错误');
        }

        $level = MemberLevel::find($id);
        if (!$level) {
            return $this->error('等级不存在');
        }

        $level->exclusive_content_ids = json_decode($level->exclusive_content_ids ?? '[]', true);

        $this->assign('level', $level);
        return $this->view('/member_benefit_edit');
    }

    /**
     * 保存权益配置
     */
    public function save()
    {
        $id = (int) $this->request->post('id', 0);
        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $level = MemberLevel::find($id);
        if (!$level) {
            return json(['code' => 1, 'msg' => '等级不存在']);
        }

        $data = [
            'discount'              => (float) $this->request->post('discount', 1.0),
            'points_rate'           => (float) $this->request->post('points_rate', 1.0),
            'daily_ai_quota'        => (int) $this->request->post('daily_ai_quota', 0),
            'vip_badge_icon'        => $this->request->post('vip_badge_icon', ''),
            'exclusive_content_ids' => json_encode($this->request->post('exclusive_content_ids', [])),
            'auto_downgrade_days'   => (int) $this->request->post('auto_downgrade_days', 0),
        ];

        try {
            $level->save($data);
            return json(['code' => 0, 'msg' => '权益配置保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '保存失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 会员列表（用于手动降级）
     */
    public function members()
    {
        $levelId = (int) $this->request->get('level_id', 0);
        $keyword = $this->request->get('keyword', '');
        $pageSize = (int) $this->request->get('limit', 20);

        $query = Member::with('level')->order('id', 'desc');

        if ($levelId > 0) {
            $query->where('level_id', $levelId);
        }
        if ($keyword) {
            $query->where('nickname|username|phone', 'like', '%' . $keyword . '%');
        }

        $list = $query->paginate($pageSize);
        $levels = MemberLevel::order('sort', 'asc')->select();

        $this->assign('list', $list);
        $this->assign('levels', $levels);
        $this->assign('level_id', $levelId);
        $this->assign('keyword', $keyword);

        return $this->view('/member_benefit_members');
    }

    /**
     * 手动降级
     */
    public function downgrade()
    {
        $memberId = (int) $this->request->post('member_id', 0);
        $targetLevelId = (int) $this->request->post('target_level_id', 0);

        if ($memberId <= 0 || $targetLevelId <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $result = MemberLevelService::manualDowngrade($memberId, $targetLevelId);

        if ($result['success']) {
            return json(['code' => 0, 'msg' => $result['msg']]);
        }

        return json(['code' => 1, 'msg' => $result['msg']]);
    }
}
