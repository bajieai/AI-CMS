<?php
declare(strict_types=1);
namespace app\common\service\payment;

use think\facade\Log;
use think\facade\Db;
use app\common\model\TemplateLicense;

/**
 * V2.9.27 U-3: 统一支付回调处理 — 3道防线
 */
class PaymentNotifyService
{
    public static function handle(string $payMethod, string $body, array $headers = []): array
    {
        try {
            $channel = self::getChannel($payMethod);
            if (!$channel) return ['success' => false, 'msg' => 'Unsupported payment method'];

            $clientIp = request()->ip();
            if (!self::checkIpWhitelist($payMethod, $clientIp)) {
                Log::error('Payment notify IP not in whitelist: ' . $clientIp);
                return ['success' => false, 'msg' => 'IP not allowed'];
            }

            $notifyData = $channel->verifyNotify($body, $headers);
            if (!$notifyData) return ['success' => false, 'msg' => 'Signature verification failed'];

            $orderNo = $notifyData['order_no'] ?? '';
            $amount = $notifyData['amount'] ?? 0;
            $order = Db::name('template_order')->where('order_no', $orderNo)->find();
            if (!$order) return ['success' => false, 'msg' => 'Order not found'];
            if ($order['status'] == 2) return ['success' => true, 'msg' => 'Already processed'];

            $orderAmount = $order['pay_amount'] ?? $order['amount'] ?? 0;
            if (bccomp((string)$amount, (string)$orderAmount, 2) !== 0) {
                Log::error('Payment amount mismatch: notify=' . $amount . ' order=' . $orderAmount);
                return ['success' => false, 'msg' => 'Amount mismatch'];
            }

            Db::name('template_order')->where('id', $order['id'])->update(['status' => 2, 'pay_time' => time()]);
            self::generateLicense($order);
            self::dispatch($order);
            return ['success' => true, 'msg' => 'OK'];
        } catch (\Throwable $e) {
            Log::error('Payment notify error: ' . $e->getMessage());
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    private static function generateLicense(array $order): void
    {
        try {
            $license = TemplateLicense::create([
                'license_code' => TemplateLicense::generateLicenseCode(),
                'template_id' => $order['store_id'] ?? 0, 'order_id' => $order['id'],
                'member_id' => $order['member_id'] ?? 0, 'license_type' => 'permanent',
                'domains' => [], 'expires_at' => 0, 'status' => 1, 'create_time' => time(),
            ]);
            Db::name('template_order')->where('id', $order['id'])->update(['license_id' => $license->id]);
        } catch (\Throwable $e) { Log::error('License generation failed: ' . $e->getMessage()); }
    }

    private static function dispatch(array $order): void
    {
        try {
            $svc = new \app\common\service\template\TemplatePaymentService();
            $svc->handlePayNotify($order['id']);
        } catch (\Throwable $e) { Log::error('Template payment dispatch failed: ' . $e->getMessage()); }
    }

    private static function getChannel(string $payMethod): ?PaymentChannelInterface
    {
        return match ($payMethod) {
            'alipay' => new AlipayPaymentChannel(),
            'wechat' => new WechatPaymentChannel(),
            default => null,
        };
    }

    private static function checkIpWhitelist(string $payMethod, string $ip): bool
    {
        $whitelist = \think\facade\Config::get('payment.' . $payMethod . '.ip_whitelist', '');
        if (empty($whitelist)) return true;
        return in_array($ip, array_map('trim', explode(',', $whitelist)));
    }
}
