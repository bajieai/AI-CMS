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

namespace app\common\service;

/**
 * 物流服务 - V2.9.1 M16a
 *
 * 提供运费查询、免邮券计算、虚拟免邮模式
 */
class ShippingService
{
    /**
     * 计算订单运费
     *
     * @param float $orderAmount 订单金额
     * @param array $coupons 已应用的优惠券列表
     * @return array ['fee'=>float, 'free'=>bool, 'reason'=>string]
     */
    public static function calculateFee(float $orderAmount, array $coupons = []): array
    {
        // 1. 检查是否触发免邮阈值
        $freeThreshold = (float) ConfigService::get('shipping_free_threshold', 0);
        if ($freeThreshold > 0 && $orderAmount >= $freeThreshold) {
            return [
                'fee'    => 0.00,
                'free'   => true,
                'reason' => '订单金额满' . $freeThreshold . '元免邮',
            ];
        }

        // 2. 检查是否使用了免邮券
        foreach ($coupons as $coupon) {
            if (!empty($coupon['type']) && $coupon['type'] === 'free_shipping') {
                return [
                    'fee'    => 0.00,
                    'free'   => true,
                    'reason' => '使用免邮券',
                ];
            }
        }

        // 3. 默认运费
        $defaultFee = (float) ConfigService::get('shipping_default_fee', 10);

        return [
            'fee'    => $defaultFee,
            'free'   => false,
            'reason' => '',
        ];
    }

    /**
     * 获取物流配置
     */
    public static function getConfig(): array
    {
        return [
            'free_threshold' => (float) ConfigService::get('shipping_free_threshold', 0),
            'default_fee'    => (float) ConfigService::get('shipping_default_fee', 10),
            'enabled'        => (bool) ConfigService::get('shipping_enabled', true),
        ];
    }

    /**
     * 保存物流配置
     */
    public static function saveConfig(array $data): bool
    {
        try {
            ConfigService::set('shipping_free_threshold', $data['free_threshold'] ?? 0);
            ConfigService::set('shipping_default_fee', $data['default_fee'] ?? 10);
            ConfigService::set('shipping_enabled', $data['enabled'] ?? true);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
