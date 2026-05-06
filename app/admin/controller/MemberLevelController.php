<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\MemberLevelService;

/**
 * 会员等级管理后台控制器
 */
class MemberLevelController extends AdminBaseController
{
    /**
     * 等级列表
     */
    public function index()
    {
        $list = MemberLevelService::getList();
        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
        }
        $this->assign('list', $list);
        return $this->view('/member_level_index');
    }

    /**
     * 添加等级页面
     */
    public function add()
    {
        return $this->edit(0);
    }

    /**
     * 编辑等级页面
     */
    public function edit(int $id = 0)
    {
        $level = $id ? \app\common\model\MemberLevel::find($id) : null;
        $this->assign('info', $level);
        return $this->view('/member_level_edit');
    }

    /**
     * 保存等级
     */
    public function save()
    {
        $data = [
            'id'                      => (int) $this->request->post('id', 0),
            'name'                    => $this->request->post('name', ''),
            'min_points'              => (int) $this->request->post('min_points', 0),
            'price'                   => (float) $this->request->post('price', 0),
            'discount'                => (float) $this->request->post('discount', 1.00),
            'points_rate'             => (float) $this->request->post('points_rate', 1.00),
            'daily_ai_quota'          => (int) $this->request->post('daily_ai_quota', 0),
            'allow_download'          => (int) $this->request->post('allow_download', 0),
            'allow_comment_no_review' => (int) $this->request->post('allow_comment_no_review', 0),
            'icon'                    => $this->request->post('icon', ''),
            'sort'                    => (int) $this->request->post('sort', 0),
            'is_default'              => (int) $this->request->post('is_default', 0),
        ];

        if (empty($data['name'])) {
            return json(['code' => 1, 'msg' => '等级名称不能为空']);
        }

        try {
            $level = MemberLevelService::save($data);
            return json(['code' => 0, 'msg' => '保存成功', 'data' => ['id' => $level->id]]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 删除等级
     */
    public function delete()
    {
        $id = (int) $this->request->post('id', 0);
        try {
            MemberLevelService::delete($id);
            return json(['code' => 0, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}
