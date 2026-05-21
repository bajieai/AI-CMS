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

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\CouponTemplate;
use app\common\service\CouponService;
use think\Request;

/**
 * 优惠券管理控制器 - V2.9新增
 */
class CouponController extends AdminBaseController
{
    /**
     * 优惠券模板列表
     */
    public function index()
    {
        $list = CouponTemplate::order('id', 'desc')
            ->paginate(20);

        // 统计数据
        $stats = [
            'enabled' => CouponTemplate::where('status', 1)->count(),
            'issued'  => \app\common\model\UserCoupon::count(),
            'used'    => \app\common\model\UserCoupon::where('status', 1)->count(),
        ];

        return $this->view('/coupon_index', ['list' => $list, 'stats' => $stats]);
    }

    /**
     * 添加优惠券模板页面
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $data   = $request->post();
            $result = CouponService::createTemplate($data);
            if ($result) {
                return json(['success' => true, 'msg' => '添加成功', 'data' => ['id' => $result->id]]);
            }
            return json(['success' => false, 'msg' => '添加失败']);
        }

        return $this->view('/coupon_edit', ['action' => 'add', 'template' => [
            'coupon_name'     => '',
            'coupon_type'     => 'reduce',
            'condition_amount'=> 0,
            'reduce_amount'   => 0,
            'total_stock'     => 0,
            'per_user_limit'  => 1,
            'start_time'      => 0,
            'end_time'        => 0,
            'scope_type'      => 'all',
            'status'          => 0,
        ]]);
    }

    /**
     * 编辑优惠券模板页面
     */
    public function edit(Request $request, int $id)
    {
        $template = CouponTemplate::find($id);
        if (!$template) {
            return redirect('/admin/coupon/index');
        }

        if ($request->isPost()) {
            $data   = $request->post();
            $result = CouponService::updateTemplate($id, $data);
            if ($result) {
                return json(['success' => true, 'msg' => '更新成功']);
            }
            return json(['success' => false, 'msg' => '更新失败']);
        }

        $templateArr = $template->toArray();
        $defaults = [
            'coupon_name'     => '',
            'coupon_type'     => 'reduce',
            'condition_amount'=> 0,
            'reduce_amount'   => 0,
            'total_stock'     => 0,
            'per_user_limit'  => 1,
            'start_time'      => 0,
            'end_time'        => 0,
            'scope_type'      => 'all',
            'status'          => 0,
        ];
        $templateArr = array_merge($defaults, $templateArr);

        return $this->view('/coupon_edit', ['action' => 'edit', 'template' => $templateArr]);
    }

    /**
     * 删除优惠券模板（仅草稿可删）
     */
    public function delete(int $id)
    {
        $template = CouponTemplate::find($id);
        if (!$template) {
            return json(['success' => false, 'msg' => '模板不存在']);
        }

        if ($template->status !== 0) {
            return json(['success' => false, 'msg' => '仅草稿状态可删除']);
        }

        $template->delete();
        return json(['success' => true, 'msg' => '删除成功']);
    }

    /**
     * 发放优惠券（手动发放）
     */
    public function issue(Request $request)
    {
        if ($request->isPost()) {
            $memberId   = (int) $request->post('member_id', 0);
            $templateId = (int) $request->post('template_id', 0);
            $quantity    = (int) $request->post('quantity', 1);

            $result = CouponService::issueCoupon($memberId, $templateId, $quantity);
            return json($result);
        }

        $templates = CouponTemplate::where('status', 1)->select();
        return $this->view('/coupon_issue', ['templates' => $templates]);
    }

    /**
     * 已发放优惠券记录
     */
    public function records(int $templateId = 0)
    {
        $query = \app\common\model\UserCoupon::order('id', 'desc');

        if ($templateId > 0) {
            $query->where('template_id', $templateId);
        }

        $list = $query->paginate(30);
        return $this->view('/coupon_records', ['list' => $list, 'templateId' => $templateId]);
    }

    /**
     * 切换状态（启用/停用）
     */
    public function toggleStatus(int $id)
    {
        $template = CouponTemplate::find($id);
        if (!$template) {
            return json(['success' => false, 'msg' => '模板不存在']);
        }

        $template->status = $template->status === 1 ? 2 : 1;
        $template->save();

        $statusName = $template->status === 1 ? '启用' : '停用';
        return json(['success' => true, 'msg' => $statusName . '成功']);
    }
}
