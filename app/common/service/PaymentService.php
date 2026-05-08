<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\PaidOrder;
use app\common\model\PaymentLog;
use app\common\service\ConfigService;
use app\common\service\PointsService;
use app\common\service\payment\PaymentChannelInterface;
use app\common\service\payment\WechatPaymentChannel;
use think\facade\Db;
use think\facade\Log;

/**
 * 支付服务 - V2.5新增
 * 门面类：支付通道管理、支付日志、订单超时关闭
 */
class PaymentService
{
    /**
     * 获取支付通道实例
     */
    public static function getChannel(string $channel = 'wechat'): ?PaymentChannelInterface
    {
        return match ($channel) {
            'wechat' => new WechatPaymentChannel(),
            default => null,
        };
    }

    /**
     * 创建微信支付订单
     * @return array [trade_type, code_url/prepay_id/jsapi_params]
     */
    public static function createWechatPayOrder(string $orderSn, int $memberId): array
    {
        $order = PaidOrder::where('order_sn', $orderSn)
            ->where('member_id', $memberId)
            ->where('status', 0)
            ->find();

        if (!$order) {
            throw new \Exception('订单不存在或已处理');
        }

        if ($order->pay_type !== 'money') {
            throw new \Exception('该订单不支持微信支付');
        }

        $channel = self::getChannel('wechat');
        if (!$channel || !$channel->isAvailable()) {
            throw new \Exception('微信支付未配置');
        }

        // 判断支付方式
        $isMobile = self::isMobile();
        $isWechat = self::isWechatBrowser();

        $tradeType = 'NATIVE'; // 默认PC扫码
        if ($isWechat) {
            $tradeType = 'JSAPI';
        } elseif ($isMobile) {
            $tradeType = 'H5';
        }

        $content = $order->content;
        $subject = $content ? $content->title : '付费内容';

        $payOrder = [
            'order_sn' => $order->order_sn,
            'amount' => (float) $order->price,
            'subject' => $subject,
            'trade_type' => $tradeType,
            'client_ip' => request()->ip(),
        ];

        try {
            $result = $channel->createOrder($payOrder);

            // 记录支付日志
            self::logPayment($orderSn, 'request', $payOrder, $result);

            return $result;
        } catch (\Exception $e) {
            self::logPayment($orderSn, 'request', $payOrder, [], $e->getMessage());
            throw $e;
        }
    }

    /**
     * 小程序微信支付（JSAPI）
     * @param int $orderId   订单ID（i8j_paid_order）
     * @param int $memberId 会员ID
     * @return array JSAPI参数（timeStamp, nonceStr, package, signType, paySign）
     */
    public static function createMiniProgramPay(int $orderId, int $memberId): array
    {
        $order = \app\common\model\PaidOrder::find($orderId);
        if (!$order || $order->member_id != $memberId) {
            throw new \Exception('订单不存在');
        }
        if ($order->status !== 0) {
            throw new \Exception('订单已处理');
        }

        $member = \app\common\model\Member::find($memberId);
        if (!$member) {
            throw new \Exception('会员不存在');
        }

        // 获取微信支付小程序配置
        $config = self::getMiniProgramPayConfig();
        if (empty($config['app_id']) || empty($config['mch_id']) || empty($config['api_key'])) {
            throw new \Exception('微信支付小程序配置缺失');
        }

        $channel = self::getChannel('wechat');
        if (!$channel) {
            throw new \Exception('微信支付渠道不可用');
        }

        // 构造JSAPI支付参数
        $jsApiParams = $channel->createMiniProgramOrder([
            'order_sn'  => $order->order_sn,
            'amount'    => (float) $order->price,
            'subject'   => mb_substr($order->content_title ?? '付费内容', 0, 32),
            'openid'   => $member->wx_openid ?? '',
        ]);

        // 记录支付日志
        self::logPayment($order->order_sn, 'miniprogram_request', $jsApiParams, []);

        return $jsApiParams;
    }

    /**
     * 获取小程序支付配置
     */
    private static function getMiniProgramPayConfig(): array
    {
        return [
            'app_id'  => \think\facade\Config::get('wechat.mini_appid', ''),
            'mch_id'  => \think\facade\Config::get('payment.wechat.mch_id', ''),
            'api_key' => \think\facade\Config::get('payment.wechat.api_key', ''),
        ];
    }

    /**
     * 处理支付回调通知
     */
    public static function handleNotify(string $body, array $headers, string $channel = 'wechat'): bool
    {
        $payChannel = self::getChannel($channel);
        if (!$payChannel) return false;

        $notifyData = $payChannel->verifyNotify($body, $headers);
        if (!$notifyData) {
            Log::error('支付回调验签失败');
            return false;
        }

        // 记录通知日志
        self::logPayment($notifyData['out_trade_no'] ?? '', 'notify', $notifyData, []);

        $orderSn = $notifyData['out_trade_no'] ?? '';
        $tradeState = $notifyData['trade_state'] ?? '';

        if ($tradeState !== 'SUCCESS') {
            return true; // 非成功状态，返回正常但不处理
        }

        $order = PaidOrder::where('order_sn', $orderSn)->where('status', 0)->find();
        if (!$order) {
            Log::warning("支付回调：订单不存在或已处理 - {$orderSn}");
            return true;
        }

        Db::startTrans();
        try {
            $order->status = 1;
            $order->paid_at = time();
            $order->transaction_id = $notifyData['transaction_id'] ?? '';
            $order->save();

            Db::commit();

            // V2.7: 消费返积分
            $ratio = (float) ConfigService::get('points_consume_ratio', 0);
            if ($ratio > 0 && $order->price > 0) {
                $rewardPoints = (int) round($order->price * $ratio);
                if ($rewardPoints > 0) {
                    try {
                        PointsService::add($order->member_id, $rewardPoints, 'consume_reward', $order->id, "消费返积分(订单{$orderSn})");
                    } catch (\Throwable $e) {
                        Log::warning("消费返积分失败: " . $e->getMessage());
                    }
                }
            }

            // 触发支付完成事件（供插件Hook）
            if (class_exists('app\common\service\PluginService')) {
                PluginService::fire('payment.completed', [
                    'order_id' => $order->id,
                    'order_sn' => $orderSn,
                    'amount' => $order->price,
                    'pay_type' => $order->pay_type,
                ]);
            }

            // 发送付费成功邮件
            if (class_exists('app\common\service\EmailService')) {
                EmailService::sendByTemplate('payment_success', $order->member_id, [
                    'content_title' => $order->content ? $order->content->title : '',
                    'amount' => $order->price,
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('支付回调处理失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 查询订单支付状态
     */
    public static function queryOrderStatus(string $orderSn): array
    {
        $order = PaidOrder::where('order_sn', $orderSn)->find();
        if (!$order) {
            return ['status' => 'not_found'];
        }

        if ($order->status === 1) {
            return ['status' => 'paid', 'paid_at' => $order->paid_at];
        }

        if ($order->status === 2) {
            return ['status' => 'refunded'];
        }

        // 尝试向微信查询
        try {
            $channel = self::getChannel('wechat');
            if ($channel && $channel->isAvailable()) {
                $result = $channel->queryOrder($orderSn);
                return ['status' => $result['trade_state'] ?? 'UNKNOWN', 'query_result' => $result];
            }
        } catch (\Throwable) {}

        return ['status' => 'pending'];
    }

    /**
     * 退款
     */
    public static function refund(int $orderId, string $reason = ''): array
    {
        $order = PaidOrder::find($orderId);
        if (!$order || $order->status !== 1) {
            throw new \Exception('订单不存在或未支付');
        }

        Db::startTrans();
        try {
            $refundSn = 'R' . date('YmdHis') . str_pad((string) $order->member_id, 6, '0', STR_PAD_LEFT) . rand(100, 999);

            if ($order->pay_type === 'money' && !empty($order->transaction_id)) {
                // 微信退款
                $channel = self::getChannel('wechat');
                if (!$channel || !$channel->isAvailable()) {
                    throw new \Exception('微信支付未配置，无法退款');
                }

                $result = $channel->refund([
                    'order_sn' => $order->order_sn,
                    'refund_sn' => $refundSn,
                    'total_amount' => (float) $order->price,
                    'refund_amount' => (float) $order->price,
                    'reason' => $reason ?: '用户申请退款',
                ]);

                self::logPayment($order->order_sn, 'refund', ['refund_sn' => $refundSn], $result);
            } elseif ($order->pay_type === 'points') {
                // 积分退还
                PointsService::add($order->member_id, (int) $order->price, 'refund', $order->id, '退款返还积分');
            }

            $order->status = 2;
            $order->refund_sn = $refundSn;
            $order->refund_amount = $order->price;
            $order->refund_time = time();
            $order->refund_reason = $reason;
            $order->save();

            Db::commit();
            return ['success' => true, 'refund_sn' => $refundSn];
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 关闭超时未支付订单（30分钟）
     */
    public static function closeExpiredOrders(int $expireMinutes = 30): int
    {
        $expireTime = time() - $expireMinutes * 60;
        $count = PaidOrder::where('status', 0)
            ->where('pay_type', 'money')
            ->where('create_time', '<', $expireTime)
            ->update(['status' => 3]); // 3=已关闭

        return $count;
    }

    /**
     * 收入统计
     */
    public static function getRevenueStats(): array
    {
        $today = strtotime('today');
        $monthStart = strtotime(date('Y-m-01'));

        $todayRevenue = PaidOrder::where('status', 1)
            ->where('pay_type', 'money')
            ->where('paid_at', '>=', $today)
            ->sum('price');

        $monthRevenue = PaidOrder::where('status', 1)
            ->where('pay_type', 'money')
            ->where('paid_at', '>=', $monthStart)
            ->sum('price');

        $totalRevenue = PaidOrder::where('status', 1)
            ->where('pay_type', 'money')
            ->sum('price');

        $todayOrders = PaidOrder::where('status', 1)
            ->where('paid_at', '>=', $today)
            ->count();

        $monthOrders = PaidOrder::where('status', 1)
            ->where('paid_at', '>=', $monthStart)
            ->count();

        $totalOrders = PaidOrder::where('status', 1)
            ->count();

        return [
            'today_revenue' => round((float) $todayRevenue, 2),
            'month_revenue' => round((float) $monthRevenue, 2),
            'total_revenue' => round((float) $totalRevenue, 2),
            'today_orders'  => $todayOrders,
            'month_orders'  => $monthOrders,
            'total_orders'  => $totalOrders,
        ];
    }

    /**
     * 记录支付日志
     */
    public static function logPayment(string $orderSn, string $type, mixed $requestData = [], mixed $responseData = [], string $errorMsg = ''): void
    {
        try {
            PaymentLog::create([
                'order_sn' => $orderSn,
                'type' => $type,
                'request_data' => is_array($requestData) ? json_encode($requestData, JSON_UNESCAPED_UNICODE) : (string) $requestData,
                'response_data' => is_array($responseData) ? json_encode($responseData, JSON_UNESCAPED_UNICODE) : (string) $responseData,
                'status' => empty($errorMsg) ? 1 : 0,
                'error_msg' => $errorMsg,
            ]);
        } catch (\Throwable) {
            Log::error('支付日志写入失败: ' . $errorMsg);
        }
    }

    /**
     * 判断是否移动端
     */
    protected static function isMobile(): bool
    {
        $ua = request()->header('user-agent', '');
        return (bool) preg_match('/Android|iPhone|iPad|iPod|Mobile/i', $ua);
    }

    /**
     * 判断是否微信浏览器
     */
    protected static function isWechatBrowser(): bool
    {
        $ua = request()->header('user-agent', '');
        return str_contains($ua, 'MicroMessenger');
    }
}
