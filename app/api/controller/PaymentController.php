<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\controller\AdminBaseController;
use app\common\service\PaymentService;
use app\common\model\PaymentLog;
use think\facade\Log;

/**
 * 支付回调API控制器 - V2.5新增
 * 处理微信支付异步通知（无需认证）
 */
class PaymentController
{
    /**
     * 微信支付回调
     * 路由: /api/payment/wechat/notify
     */
    public function wechatNotify(): \think\Response
    {
        $body = file_get_contents('php://input');
        $headers = [
            'wechatpay-timestamp' => request()->header('wechatpay-timestamp', ''),
            'wechatpay-nonce' => request()->header('wechatpay-nonce', ''),
            'wechatpay-signature' => request()->header('wechatpay-signature', ''),
            'wechatpay-serial' => request()->header('wechatpay-serial', ''),
        ];

        try {
            $result = PaymentService::handleNotify($body, $headers, 'wechat');

            if ($result) {
                // 返回成功响应给微信
                return json(['code' => 'SUCCESS', 'message' => '处理成功']);
            } else {
                return json(['code' => 'FAIL', 'message' => '处理失败'], 500);
            }
        } catch (\Exception $e) {
            Log::error('微信支付回调异常: ' . $e->getMessage());
            return json(['code' => 'FAIL', 'message' => '处理异常'], 500);
        }
    }

    /**
     * 查询订单支付状态（前端轮询）
     */
    public function queryStatus(): \think\Response
    {
        $orderSn = request()->param('order_sn', '');
        if (empty($orderSn)) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $result = PaymentService::queryOrderStatus($orderSn);
        return json(['code' => 0, 'data' => $result]);
    }

    /**
     * 创建微信支付订单
     */
    public function createOrder(): \think\Response
    {
        $orderSn = request()->param('order_sn', '');
        $memberId = request()->param('member_id', 0);

        try {
            $result = PaymentService::createWechatPayOrder($orderSn, (int) $memberId);
            return json(['code' => 0, 'data' => $result]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 验证码获取
     */
    public function captcha(): \think\Response
    {
        $captcha = \app\common\service\CaptchaService::generateMath();
        return json(['code' => 0, 'data' => $captcha]);
    }

    /**
     * 验证码验证
     */
    public function captchaVerify(): \think\Response
    {
        $key = request()->param('key', '');
        $answer = request()->param('answer', '');
        $valid = \app\common\service\CaptchaService::verify($key, $answer);

        return json(['code' => $valid ? 0 : 1, 'msg' => $valid ? '验证成功' : '验证码错误']);
    }
}
