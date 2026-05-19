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

use app\common\service\CouponService;
use app\common\model\CouponTemplate;
use think\facade\Request;

/**
 * 优惠券前台API - V2.9新增
 */
class CouponController extends BaseController
{
    /**
     * 获取可领取优惠券列表
     * GET /api/coupon
     */
    public function index()
    {
        $page  = (int) Request::get('page', 1);
        $limit = (int) Request::get('limit', 20);

        $list = CouponTemplate::where('status', 1)
            ->where('start_time', '<=', time())
            ->where(function ($query) {
                $query->where('end_time', 0)->whereOr('end_time', '>=', time());
            })
            ->where('remain_stock', '>', 0)
            ->order('sort desc, id desc')
            ->paginate(['list_rows' => $limit, 'page' => $page]);

        return $this->success([
            'list'  => $list->items(),
            'total' => $list->total(),
            'page'  => $page,
        ]);
    }

    /**
     * 获取我的优惠券
     * GET /api/coupon/my
     */
    public function my()
    {
        $memberId = $this->getMemberId();
        if (!$memberId) {
            return $this->error('请先登录', 401);
        }

        $status = (int) Request::get('status', -1); // -1全部 0未使用 1已使用 2已过期
        $page   = (int) Request::get('page', 1);
        $limit  = (int) Request::get('limit', 20);

        $result = CouponService::getMemberCoupons($memberId, $status, $page, $limit);

        return $this->success($result);
    }

    /**
     * 领取优惠券
     * POST /api/coupon/receive
     */
    public function receive()
    {
        $memberId = $this->getMemberId();
        if (!$memberId) {
            return $this->error('请先登录', 401);
        }

        $templateId = (int) Request::post('template_id', 0);
        if ($templateId <= 0) {
            return $this->error('优惠券模板ID错误');
        }

        $result = CouponService::issueCoupon($memberId, $templateId, 1);

        if ($result['success']) {
            return $this->success(['coupon_code' => $result['data']['coupon_codes'][0] ?? ''], '领取成功');
        }
        return $this->error($result['msg']);
    }

    /**
     * 获取新人券
     * GET /api/coupon/newbie
     */
    public function newbie()
    {
        $memberId = $this->getMemberId();
        if (!$memberId) {
            return $this->error('请先登录', 401);
        }

        $templateId = (int) \think\facade\Config::get('coupon.newbie_template_id', 0);
        if ($templateId <= 0) {
            return $this->error('未配置新人券');
        }

        // 检查是否已领取过新人券
        $hasReceived = \app\common\model\UserCoupon::where('member_id', $memberId)
            ->where('template_id', $templateId)
            ->find();
        if ($hasReceived) {
            return $this->success(['received' => true], '已领取过新人券');
        }

        $result = CouponService::issueCoupon($memberId, $templateId, 1);
        if ($result['success']) {
            return $this->success(['received' => true, 'coupon_code' => $result['data']['coupon_codes'][0] ?? ''], '新人券领取成功');
        }
        return $this->error($result['msg']);
    }

    /**
     * 验证优惠券可用性（结算前调用）
     * GET /api/coupon/validate?coupon_id=1&order_amount=100&content_id=1
     */
    public function validate()
    {
        $memberId = $this->getMemberId();
        if (!$memberId) {
            return $this->error('请先登录', 401);
        }

        $couponId    = Request::get('coupon_id', 0);
        $orderAmount = (float) Request::get('order_amount', 0);
        $contentId   = (int) Request::get('content_id', 0);

        if (empty($couponId) || $orderAmount <= 0) {
            return $this->error('参数错误');
        }

        $result = CouponService::validateCoupon($memberId, $couponId, $orderAmount, $contentId);

        return $this->success([
            'valid'    => $result['success'],
            'discount' => $result['discount'],
            'msg'      => $result['msg'],
        ]);
    }
}
