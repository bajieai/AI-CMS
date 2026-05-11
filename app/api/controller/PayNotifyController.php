<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\service\PaymentService;
use think\facade\Log;

/**
 * V2.9.4 支付回调控制器（无需登录认证）
 */
class PayNotifyController
{
    /**
     * 微信支付回调
     */
    public function wechat()
    {
        $params = input('post.');
        Log::info('[PayNotify] 微信回调: ' . json_encode($params));

        $result = PaymentService::handleNotify('wechat', $params);

        if ($result['success']) {
            // 返回微信要求的格式
            return xml(['return_code' => 'SUCCESS', 'return_msg' => 'OK']);
        }

        return xml(['return_code' => 'FAIL', 'return_msg' => $result['msg'] ?? '处理失败']);
    }

    /**
     * 支付宝回调
     */
    public function alipay()
    {
        $params = input('post.');
        Log::info('[PayNotify] 支付宝回调: ' . json_encode($params));

        $result = PaymentService::handleNotify('alipay', $params);

        if ($result['success']) {
            return 'success';
        }

        return 'fail';
    }
}
