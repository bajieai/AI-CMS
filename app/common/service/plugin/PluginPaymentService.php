<?php

declare(strict_types=1);

namespace app\common\service\plugin;

use think\facade\Db;
use think\facade\Cache;

/**
 * PLUG-SHOP-3: 插件付费下载服务 — V2.9.36
 */
class PluginPaymentService
{
    private const CACHE_TAG = 'plugin_store';

    /**
     * 发起支付
     */
    public function pay(int $orderId, string $payType): array
    {
        $order = Db::name('plugin_order')->find($orderId);
        if (!$order) {
            return ['code' => 1, 'msg' => '订单不存在'];
        }
        if ($order['pay_status'] !== 'pending') {
            return ['code' => 1, 'msg' => '订单状态不允许支付'];
        }

        $payType = strtolower($payType);
        if (!in_array($payType, ['alipay', 'wechat', 'balance'])) {
            return ['code' => 1, 'msg' => '不支持的支付方式'];
        }

        // 更新支付方式
        Db::name('plugin_order')->where('id', $orderId)->update([
            'pay_type'    => $payType,
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        // 模拟支付参数（实际应复用 PaymentService）
        $payParams = [
            'order_no' => $order['order_no'],
            'amount'   => $order['price'],
            'subject'  => '插件购买: ' . $order['plugin_name'],
            'pay_type' => $payType,
        ];

        return [
            'code' => 0,
            'msg'  => '支付参数已生成',
            'data' => $payParams,
        ];
    }

    /**
     * 支付回调
     */
    public function handleCallback(string $payType, array $data): array
    {
        $orderNo = $data['order_no'] ?? $data['out_trade_no'] ?? '';
        if (empty($orderNo)) {
            return ['code' => 1, 'msg' => '订单号缺失'];
        }

        $order = Db::name('plugin_order')->where('order_no', $orderNo)->find();
        if (!$order) {
            return ['code' => 1, 'msg' => '订单不存在'];
        }

        if ($order['pay_status'] === 'paid') {
            return ['code' => 0, 'msg' => '订单已支付（重复回调）'];
        }

        // 标记为已支付
        $tradeService = new PluginTradeService();
        $license = $tradeService->generateLicense($order['id']);

        Db::name('plugin_order')->where('id', $order['id'])->update([
            'pay_status'  => 'paid',
            'pay_time'    => date('Y-m-d H:i:s'),
            'license_key' => $license,
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        // 增加下载次数
        Db::name('plugin')->where('id', $order['plugin_id'])->inc('download_count')->update();

        // 创建分成记录
        $this->createPayoutRecord($order);

        return ['code' => 0, 'msg' => '支付成功'];
    }

    /**
     * 获取分成配置
     */
    public function getPayoutConfig(): array
    {
        return Cache::remember('plugin_payout_config', function () {
            $row = Db::name('config')
                ->where('name', 'plugin_payout_config')
                ->find();

            if ($row && $row['value']) {
                $config = json_decode($row['value'], true);
                if (is_array($config)) return $config;
            }

            // 默认配置
            return [
                'default_platform_ratio'  => 30,
                'default_developer_ratio' => 70,
                'tiers' => [
                    ['min' => 0, 'max' => 1000, 'developer_ratio' => 70],
                    ['min' => 1000, 'max' => 5000, 'developer_ratio' => 75],
                    ['min' => 5000, 'max' => 999999999, 'developer_ratio' => 80],
                ],
            ];
        }, 3600);
    }

    /**
     * 计算分成（阶梯比例）
     */
    public function calculatePayout(float $orderAmount, int $developerId): array
    {
        $config = $this->getPayoutConfig();
        $tiers = $config['tiers'] ?? [];
        $defaultDevRatio = $config['default_developer_ratio'] ?? 70;

        // 累计开发者收入
        $totalEarned = Db::name('plugin_payout')
            ->where('developer_id', $developerId)
            ->where('payout_status', 'settled')
            ->sum('developer_amount');

        $cumulative = (float) $totalEarned + $orderAmount;

        // 确定阶梯
        $devRatio = $defaultDevRatio;
        foreach ($tiers as $tier) {
            if ($cumulative >= $tier['min'] && $cumulative < $tier['max']) {
                $devRatio = $tier['developer_ratio'];
                break;
            }
        }

        $platformRatio = 100 - $devRatio;

        return [
            'order_amount'     => $orderAmount,
            'platform_ratio'   => $platformRatio,
            'developer_ratio'  => $devRatio,
            'platform_amount'  => round($orderAmount * $platformRatio / 100, 2),
            'developer_amount' => round($orderAmount * $devRatio / 100, 2),
        ];
    }

    /**
     * 结算单条记录
     */
    public function settlePayout(int $payoutId): array
    {
        $payout = Db::name('plugin_payout')->find($payoutId);
        if (!$payout) {
            return ['code' => 1, 'msg' => '结算记录不存在'];
        }
        if ($payout['payout_status'] !== 'pending') {
            return ['code' => 1, 'msg' => '结算记录状态不允许结算'];
        }

        Db::name('plugin_payout')->where('id', $payoutId)->update([
            'payout_status' => 'settled',
            'payout_time'   => date('Y-m-d H:i:s'),
        ]);

        return ['code' => 0, 'msg' => '结算成功'];
    }

    /**
     * 月度结算
     */
    public function monthlySettle(): array
    {
        $pendingPayouts = Db::name('plugin_payout')
            ->where('payout_status', 'pending')
            ->select()->toArray();

        $settled = 0;
        $totalAmount = 0.0;
        foreach ($pendingPayouts as $payout) {
            $result = $this->settlePayout($payout['id']);
            if ($result['code'] === 0) {
                $settled++;
                $totalAmount += (float) $payout['developer_amount'];
            }
        }

        return [
            'code' => 0,
            'msg'  => sprintf('月度结算完成：共结算 %d 笔，开发者分成总额 ¥%.2f', $settled, $totalAmount),
            'data' => ['settled_count' => $settled, 'total_amount' => $totalAmount],
        ];
    }

    /**
     * 退款流程
     */
    public function requestRefund(int $orderId): array
    {
        $tradeService = new PluginTradeService();
        $result = $tradeService->refundOrder($orderId);
        if ($result['code'] !== 0) {
            return $result;
        }

        // 取消相关分成记录
        Db::name('plugin_payout')
            ->where('order_id', $orderId)
            ->where('payout_status', 'pending')
            ->update([
                'payout_status' => 'cancelled',
            ]);

        return ['code' => 0, 'msg' => '退款已处理，相关分成已取消'];
    }

    /**
     * 创建分成记录
     */
    private function createPayoutRecord(array $order): void
    {
        // 获取插件开发者信息
        $plugin = Db::name('plugin')->find($order['plugin_id']);
        if (!$plugin) return;

        $developerId = (int) ($plugin['developer_id'] ?? 0);
        if ($developerId <= 0) return;

        $price = (float) $order['price'];
        if ($price <= 0) return;

        $payout = $this->calculatePayout($price, $developerId);

        Db::name('plugin_payout')->insert([
            'developer_id'      => $developerId,
            'developer_name'    => (string) $developerId,
            'order_id'          => $order['id'],
            'plugin_id'         => $order['plugin_id'],
            'order_amount'      => $price,
            'platform_ratio'    => $payout['platform_ratio'],
            'developer_ratio'   => $payout['developer_ratio'],
            'platform_amount'   => $payout['platform_amount'],
            'developer_amount'  => $payout['developer_amount'],
            'payout_status'     => 'pending',
            'create_time'       => date('Y-m-d H:i:s'),
        ]);
    }
}
