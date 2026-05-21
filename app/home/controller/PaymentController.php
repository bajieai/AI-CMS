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

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\service\PaidService;
use app\common\service\PaymentService;

/**
 * 前台支付控制器 - V2.5新增
 * 处理付费内容创建订单+支付+轮询状态
 */
class PaymentController extends FrontBaseController
{
    protected bool $enablePageCache = false;

    /**
     * 创建支付订单
     */
    public function createOrder()
    {
        if (!$this->isMemberLogin) {
            return json(['code' => 1, 'msg' => '请先登录', 'data' => ['login_url' => '/member/login']]);
        }

        $contentId = (int) $this->request->post('content_id', 0);
        $payType = $this->request->post('pay_type', 'points');

        if ($contentId <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        try {
            $order = PaidService::createOrder($this->memberInfo['id'], $contentId, $payType);

            // 积分支付直接完成
            if ($payType === 'points') {
                PaidService::completePayment($order['order_sn'], $this->memberInfo['id']);
                return json(['code' => 0, 'msg' => '购买成功', 'data' => ['order_sn' => $order['order_sn'], 'pay_type' => 'points']]);
            }

            // 微信支付：创建微信支付订单
            if ($payType === 'money') {
                $payResult = PaymentService::createWechatPayOrder($order['order_sn'], $this->memberInfo['id']);
                return json(['code' => 0, 'msg' => '订单创建成功', 'data' => array_merge($order, $payResult)]);
            }

            return json(['code' => 0, 'msg' => '订单创建成功', 'data' => $order]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 查询订单支付状态（前端轮询）
     */
    public function queryStatus()
    {
        $orderSn = $this->request->param('order_sn', '');
        if (empty($orderSn)) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        try {
            $result = PaymentService::queryOrderStatus($orderSn);
            return json(['code' => 0, 'data' => $result]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 支付结果页面
     */
    public function result()
    {
        $orderSn = $this->request->param('order_sn', '');
        $this->assign('order_sn', $orderSn);
        return $this->view('/payment_result');
    }
}
