<?php

declare(strict_types=1);

namespace app\common\service\plugin;

use think\facade\Db;

/**
 * PLUG-SHOP-2: 插件交易流程服务 — V2.9.36
 */
class PluginTradeService
{
    /**
     * 创建订单
     */
    public function createOrder(int $pluginId, int $memberId): array
    {
        $plugin = Db::name('plugin')->find($pluginId);
        if (!$plugin) {
            return ['code' => 1, 'msg' => '插件不存在'];
        }

        // 检查是否已购买
        $existing = Db::name('plugin_order')
            ->where('plugin_id', $pluginId)
            ->where('member_id', $memberId)
            ->whereIn('pay_status', ['paid'])
            ->find();
        if ($existing) {
            return ['code' => 1, 'msg' => '您已购买过此插件，无需重复购买'];
        }

        // 免费插件直接生成已支付订单
        $price = (float) ($plugin['price'] ?? 0);
        $orderNo = $this->generateOrderNo();
        $payStatus = $price <= 0 ? 'paid' : 'pending';

        $orderId = Db::name('plugin_order')->insertGetId([
            'order_no'       => $orderNo,
            'plugin_id'      => $pluginId,
            'plugin_name'    => $plugin['name'],
            'plugin_version' => $plugin['version'] ?? '1.0.0',
            'price'          => $price,
            'member_id'      => $memberId,
            'member_name'    => (string) $memberId,
            'pay_type'       => '',
            'pay_status'     => $payStatus,
            'pay_time'       => $price <= 0 ? date('Y-m-d H:i:s') : null,
            'license_key'    => '',
            'license_domain' => '',
            'order_data'     => json_encode(['plugin_code' => $plugin['code'] ?? ''], JSON_UNESCAPED_UNICODE),
            'create_time'    => date('Y-m-d H:i:s'),
            'update_time'    => date('Y-m-d H:i:s'),
        ]);

        // 免费插件自动生成许可证
        if ($price <= 0) {
            $license = $this->generateLicense($orderId);
            Db::name('plugin_order')->where('id', $orderId)->update([
                'license_key' => $license,
            ]);
            // 增加下载次数
            Db::name('plugin')->where('id', $pluginId)->inc('download_count')->update();
        }

        return [
            'code' => 0,
            'msg'  => $price <= 0 ? '免费插件已添加到您的订单' : '订单创建成功',
            'data' => ['order_id' => $orderId, 'order_no' => $orderNo, 'price' => $price],
        ];
    }

    /**
     * 订单列表
     */
    public function getOrderList(int $memberId, int $page = 1): array
    {
        $pageSize = 20;
        $query = Db::name('plugin_order')->where('member_id', $memberId);
        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $pageSize)
            ->select()->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    /**
     * 订单详情
     */
    public function getOrderById(int $id): ?array
    {
        return Db::name('plugin_order')->find($id);
    }

    /**
     * 取消订单
     */
    public function cancelOrder(int $id): array
    {
        $order = Db::name('plugin_order')->find($id);
        if (!$order) {
            return ['code' => 1, 'msg' => '订单不存在'];
        }
        if ($order['pay_status'] !== 'pending') {
            return ['code' => 1, 'msg' => '订单状态不允许取消'];
        }

        Db::name('plugin_order')->where('id', $id)->update([
            'pay_status'  => 'cancelled',
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        return ['code' => 0, 'msg' => '订单已取消'];
    }

    /**
     * 退款（7天无理由）
     */
    public function refundOrder(int $id): array
    {
        $order = Db::name('plugin_order')->find($id);
        if (!$order) {
            return ['code' => 1, 'msg' => '订单不存在'];
        }
        if ($order['pay_status'] !== 'paid') {
            return ['code' => 1, 'msg' => '订单未支付，无法退款'];
        }

        // 7天无理由检查
        $payTime = strtotime($order['pay_time'] ?? '');
        if ($payTime && (time() - $payTime) > 7 * 86400) {
            return ['code' => 1, 'msg' => '已超过7天无理由退款期限'];
        }

        Db::name('plugin_order')->where('id', $id)->update([
            'pay_status'  => 'refunded',
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        // 撤销许可证
        Db::name('plugin_order')->where('id', $id)->update(['license_key' => '']);

        return ['code' => 0, 'msg' => '退款已处理'];
    }

    /**
     * 生成许可证
     */
    public function generateLicense(int $orderId): string
    {
        $order = Db::name('plugin_order')->find($orderId);
        if (!$order) {
            return '';
        }

        return hash('sha256', $order['order_no'] . '.' . $order['plugin_id'] . '.' . $order['member_id'] . '.' . time());
    }

    /**
     * 验证许可证
     */
    public function verifyLicense(string $licenseKey, string $domain = ''): bool
    {
        if (empty($licenseKey)) return false;

        $order = Db::name('plugin_order')
            ->where('license_key', $licenseKey)
            ->where('pay_status', 'paid')
            ->find();

        if (!$order) return false;

        // 域名校验
        if ($domain && $order['license_domain'] && $order['license_domain'] !== $domain) {
            return false;
        }

        // 过期检查
        if ($order['license_expire'] && strtotime($order['license_expire']) < time()) {
            return false;
        }

        return true;
    }

    /**
     * 获取下载链接（24小时有效）
     */
    public function getDownloadUrl(int $orderId): array
    {
        $order = Db::name('plugin_order')->find($orderId);
        if (!$order) {
            return ['code' => 1, 'msg' => '订单不存在'];
        }
        if ($order['pay_status'] !== 'paid') {
            return ['code' => 1, 'msg' => '订单未支付，无法下载'];
        }

        // 生成有时效性的token
        $expire = time() + 86400; // 24h
        $token = hash('sha256', $order['order_no'] . '.' . $order['license_key'] . '.' . $expire);

        $downloadUrl = '/admin/plugin_payment/download?order_id=' . $orderId . '&token=' . $token . '&expire=' . $expire;

        // 增加下载次数
        Db::name('plugin')->where('id', $order['plugin_id'])->inc('download_count')->update();

        return [
            'code' => 0,
            'msg'  => 'ok',
            'data' => [
                'url'    => $downloadUrl,
                'expire' => date('Y-m-d H:i:s', $expire),
            ],
        ];
    }

    /**
     * 生成订单号
     */
    private function generateOrderNo(): string
    {
        return 'PLG' . date('YmdHis') . str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
