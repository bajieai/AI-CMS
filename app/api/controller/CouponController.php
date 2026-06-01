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

use app\common\service\CouponService;
use app\common\model\CouponTemplate;
use think\facade\Request;

/**
 * 优惠券前台API
 * @api_group 优惠券
 * @api_desc 优惠券列表、领取、验证等前台接口
 */
class CouponController extends BaseController
{
    /**
     * 获取可领取优惠券列表
     * @api 优惠券列表
     * @api_desc 分页获取当前可领取的优惠券（有效期内+库存充足）
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return json 返回优惠券列表和分页信息
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
     * @api 我的优惠券
     * @api_desc 获取当前会员领取的优惠券，可按状态筛选
     * @param int $status 状态筛选(-1全部/0未使用/1已使用/2已过期)
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return json 返回我的优惠券列表
     * @api_auth yes
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
     * @api 领取优惠券
     * @api_desc 会员领取指定模板的优惠券，需校验库存和每人限领
     * @param int $template_id 优惠券模板ID
     * @return json 返回领取结果和券码
     * @api_auth yes
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
     * @api 新人券领取
     * @api_desc 新会员领取专属新人优惠券（仅限首次）
     * @return json 返回领取结果，已领取过则返回received=true
     * @api_auth yes
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
     * 验证优惠券可用性
     * @api 验证优惠券
     * @api_desc 结算前校验优惠券是否可用，返回折扣金额
     * @param int $coupon_id 优惠券ID
     * @param float $order_amount 订单金额
     * @param int $content_id 内容ID（用于范围校验）
     * @return json 返回valid/discount/msg
     * @api_auth yes
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
