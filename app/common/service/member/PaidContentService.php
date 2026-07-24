<?php
declare(strict_types=1);

namespace app\common\service\member;

use app\common\model\Content;
use app\common\model\Member;
use app\common\service\PaymentService;
use think\facade\Db;
use think\facade\Cache;

class PaidContentService
{
    private const CACHE_TAG = 'paid_content';

    public function setPaid(int $contentId, array $data): array
    {
        Content::where('id', $contentId)->update([
            'is_paid' => 1, 'paid_type' => $data['paid_type'] ?? 'pay_read',
            'paid_price' => (float)($data['paid_price'] ?? 0), 'paid_points' => (int)($data['paid_points'] ?? 0),
            'paid_preview_ratio' => (int)($data['paid_preview_ratio'] ?? 20), 'paid_download_limit' => (int)($data['paid_download_limit'] ?? 3),
            'paid_author_ratio' => (int)($data['paid_author_ratio'] ?? 0),
        ]);
        Cache::clear();
        return ['success' => true];
    }

    public function getPreviewContent(int $contentId): array
    {
        $content = Content::find($contentId);
        if (!$content || !$content->is_paid) return ['full' => true, 'content' => $content->content ?? ''];
        $ratio = (int)$content->paid_preview_ratio;
        $fullText = $content->content ?? '';
        $previewLength = (int)mb_strlen($fullText) * $ratio / 100;
        return ['full' => false, 'preview' => mb_substr($fullText, 0, (int)$previewLength), 'paid_type' => $content->paid_type, 'paid_price' => $content->paid_price, 'paid_points' => $content->paid_points];
    }

    public function payByPoints(int $memberId, int $contentId): array
    {
        $content = Content::find($contentId);
        if (!$content || !$content->is_paid) return ['success' => false, 'message' => '非付费内容'];
        if ($this->checkPurchased($memberId, $contentId)) return ['success' => true, 'message' => '已购买'];
        $pointsService = new MemberPointsService();
        $result = $pointsService->deductPoints($memberId, (int)$content->paid_points, 'pay_read', "付费阅读: {$content->title}", $contentId);
        if (!$result['success']) return $result;
        $this->recordPurchase($memberId, $contentId, 'points', (int)$content->paid_points);
        return ['success' => true];
    }

    public function payByWechat(int $memberId, int $contentId): array
    {
        $content = Content::find($contentId);
        if (!$content || !$content->is_paid) return ['success' => false, 'message' => '非付费内容'];
        if ($this->checkPurchased($memberId, $contentId)) return ['success' => true, 'message' => '已购买'];

        $paymentService = new PaymentService();
        $order = $paymentService->createOrder($memberId, (float)$content->paid_price, 'paid_content', $contentId, "付费阅读: {$content->title}");

        // 调用微信支付适配器
        $adapter = new \app\common\adapter\WechatPayAdapter();
        try {
            $payResult = $adapter->createPayment($order);
            return ['success' => true, 'order' => $order, 'pay_data' => $payResult, 'pay_method' => 'wechat'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => '微信支付创建失败: ' . $e->getMessage()];
        }
    }

    public function payByAlipay(int $memberId, int $contentId): array
    {
        $content = Content::find($contentId);
        if (!$content || !$content->is_paid) return ['success' => false, 'message' => '非付费内容'];
        if ($this->checkPurchased($memberId, $contentId)) return ['success' => true, 'message' => '已购买'];

        $paymentService = new PaymentService();
        $order = $paymentService->createOrder($memberId, (float)$content->paid_price, 'paid_content', $contentId, "付费阅读: {$content->title}");

        // 调用支付宝适配器
        $adapter = new \app\common\adapter\AlipayAdapter();
        try {
            $payResult = $adapter->createPayment($order);
            return ['success' => true, 'order' => $order, 'pay_data' => $payResult, 'pay_method' => 'alipay'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => '支付宝支付创建失败: ' . $e->getMessage()];
        }
    }

    public function checkPurchased(int $memberId, int $contentId): bool
    {
        return Db::name('paid_content_record')->where('member_id', $memberId)->where('content_id', $contentId)->where('status', 1)->count() > 0;
    }

    public function getDownloadUrl(int $contentId, int $memberId): array
    {
        if (!$this->checkPurchased($memberId, $contentId)) return ['success' => false, 'message' => '未购买'];
        $token = md5($contentId . $memberId . time() . config('app.key'));
        $expireTime = time() + 86400;
        Cache::set("paid_download_{$token}", ['content_id' => $contentId, 'member_id' => $memberId, 'expire' => $expireTime], 86400);
        return ['success' => true, 'token' => $token, 'expire_time' => $expireTime];
    }

    public function getStats(): array
    {
        return Cache::remember('stats', function() {
            $totalRevenue = Db::name('paid_content_record')->where('status', 1)->sum('amount');
            $totalOrders = Db::name('paid_content_record')->where('status', 1)->count();
            $paidUsers = Db::name('paid_content_record')->where('status', 1)->distinct('member_id')->count();
            return ['total_revenue' => $totalRevenue, 'total_orders' => $totalOrders, 'paid_users' => $paidUsers, 'arpu' => $paidUsers > 0 ? round($totalRevenue / $paidUsers, 2) : 0];
        }, 300);
    }

    public function getAuthorIncome(int $authorId): array
    {
        return Cache::remember("author_{$authorId}", function() use ($authorId) {
            $contents = Content::where('member_id', $authorId)->where('is_paid', 1)->column('id');
            if (empty($contents)) return ['total_income' => 0, 'month_income' => 0, 'orders' => 0];
            $totalIncome = Db::name('paid_content_record')->whereIn('content_id', $contents)->where('status', 1)->sum('amount');
            $monthStart = strtotime(date('Y-m-01'));
            $monthIncome = Db::name('paid_content_record')->whereIn('content_id', $contents)->where('status', 1)->where('create_time', '>=', $monthStart)->sum('amount');
            $orders = Db::name('paid_content_record')->whereIn('content_id', $contents)->where('status', 1)->count();
            return ['total_income' => $totalIncome, 'month_income' => $monthIncome, 'orders' => $orders];
        }, 300);
    }

    private function recordPurchase(int $memberId, int $contentId, string $payType, float $amount): void
    {
        Db::name('paid_content_record')->insert(['member_id' => $memberId, 'content_id' => $contentId, 'pay_type' => $payType, 'amount' => $amount, 'status' => 1, 'create_time' => time()]);
        Cache::clear();
    }
}
