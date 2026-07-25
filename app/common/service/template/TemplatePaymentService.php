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

namespace app\common\service\template;

use app\common\model\TemplateOrder;
use app\common\model\TemplateStore;
use app\common\service\PaymentService;
use think\facade\Log;

/**
 * 模板支付服务 - V2.9.12新增
 * 复用 PaymentService 统一支付能力
 */
class TemplatePaymentService
{
    /**
     * 创建模板购买订单并发起支付
     */
    public function createOrder(int $storeId, int $memberId, string $payMethod = 'wechat'): array
    {
        $store = TemplateStore::find($storeId);
        if (empty($store)) {
            return ['success' => false, 'msg' => '模板不存在'];
        }

        if ($store->price <= 0) {
            return ['success' => false, 'msg' => '免费模板无需购买'];
        }

        // 创建模板业务订单记录
        $tplOrder = new TemplateOrder();
        $tplOrder->order_no = TemplateOrder::generateOrderNo();
        $tplOrder->store_id = $storeId;
        $tplOrder->member_id = $memberId;
        $tplOrder->amount = $store->price;
        $tplOrder->status = TemplateOrder::STATUS_PENDING;
        $tplOrder->save();

        // 调用统一支付服务
        $payResult = PaymentService::createPayment(
            $memberId,
            'template',
            (string) $tplOrder->id,
            $store->price,
            $payMethod
        );

        if (!$payResult['success']) {
            // 支付创建失败，关闭业务订单
            $tplOrder->status = TemplateOrder::STATUS_CLOSED;
            $tplOrder->save();
            return ['success' => false, 'msg' => $payResult['msg'] ?? '支付创建失败'];
        }

        return [
            'success' => true,
            'order_no' => $tplOrder->order_no,
            'tpl_order_id' => $tplOrder->id,
            'pay_data' => $payResult['pay_data'],
        ];
    }

    /**
     * 支付成功后处理：自动安装模板
     */
    public function handlePayNotify(int $tplOrderId): array
    {
        $tplOrder = TemplateOrder::find($tplOrderId);
        if (empty($tplOrder)) {
            return ['success' => false, 'msg' => '订单不存在'];
        }

        // 幂等检查
        if ($tplOrder->status === TemplateOrder::STATUS_PAID) {
            return ['success' => true, 'msg' => '已处理'];
        }

        // 更新订单状态
        $tplOrder->status = TemplateOrder::STATUS_PAID;
        $tplOrder->pay_time = time();
        $tplOrder->save();

        // 自动安装模板
        try {
            $storeService = new TemplateStoreService();
            $result = $storeService->installTheme($tplOrder->store_id, $tplOrder->member_id);
            Log::info('[TemplatePayment] 支付后自动安装成功: order_no=' . $tplOrder->order_no);
            return ['success' => true, 'msg' => '支付成功，模板已自动安装', 'install' => $result];
        } catch (\Throwable $e) {
            Log::error('[TemplatePayment] 支付后自动安装失败: ' . $e->getMessage());
            return ['success' => true, 'msg' => '支付成功，但自动安装失败，请手动安装'];
        }
    }
}
