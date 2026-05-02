<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Content;
use app\common\model\Member;
use app\common\model\MemberLevel;
use app\common\model\PaidOrder;
use think\facade\Db;

/**
 * 付费内容服务
 */
class PaidService
{
    /**
     * 检查会员是否有权限访问付费内容
     */
    public static function canAccess(?int $memberId, int $contentId): bool
    {
        $content = Content::find($contentId);
        if (!$content || empty($content->is_paid)) {
            return true;
        }

        if (!$memberId) {
            return false;
        }

        // 检查是否已购买
        $hasOrder = PaidOrder::where('member_id', $memberId)
            ->where('content_id', $contentId)
            ->where('status', 1)
            ->find();
        if ($hasOrder) return true;

        return false;
    }

    /**
     * 获取内容的安全展示版本
     * 付费内容在后端截断，不可通过前端绕过
     */
    public static function getSafeContent($content, ?int $memberId = null): array
    {
        if (empty($content->is_paid)) {
            return ['full' => $content->content, 'is_paid_content' => false];
        }

        if ($memberId && self::canAccess($memberId, $content->id)) {
            return [
                'full'            => $content->content,
                'is_paid_content' => true,
                'is_unlocked'     => true,
            ];
        }

        // 返回试读内容（后端截断，安全）
        $previewLength = $content->preview_length ?: 500;
        $preview = mb_substr(strip_tags($content->content), 0, $previewLength);

        return [
            'preview'         => $preview,
            'full'            => null,
            'is_paid_content' => true,
            'is_unlocked'     => false,
            'price'           => $content->paid_price,
            'paid_type'       => $content->paid_type,
        ];
    }

    /**
     * 创建付费订单
     */
    public static function createOrder(int $memberId, int $contentId): array
    {
        $content = Content::find($contentId);
        if (!$content || empty($content->is_paid)) {
            throw new \Exception('该内容无需付费');
        }

        if (self::canAccess($memberId, $contentId)) {
            throw new \Exception('您已购买该内容');
        }

        // 计算折扣后价格
        $finalPrice = $content->paid_price;
        $member = Member::find($memberId);
        if ($member && $member->level_id) {
            $level = MemberLevel::find($member->level_id);
            if ($level && $level->discount < 100) {
                $finalPrice = round($content->paid_price * ($level->discount / 100), 2);
            }
        }

        $orderSn = 'P' . date('YmdHis') . str_pad((string) $memberId, 6, '0', STR_PAD_LEFT) . rand(100, 999);

        $order = PaidOrder::create([
            'order_sn'  => $orderSn,
            'member_id' => $memberId,
            'content_id' => $contentId,
            'type'      => 'content',
            'price'     => $finalPrice,
            'pay_type'  => $content->paid_type,
            'status'    => 0,
        ]);

        return $order->toArray();
    }

    /**
     * 完成支付（积分支付）
     */
    public static function completePayment(string $orderSn, int $memberId): bool
    {
        $order = PaidOrder::where('order_sn', $orderSn)
            ->where('member_id', $memberId)
            ->where('status', 0)
            ->find();

        if (!$order) {
            throw new \Exception('订单不存在或已处理');
        }

        Db::startTrans();
        try {
            if ($order->pay_type === 'points') {
                PointsService::consume($memberId, (int) $order->price, 'purchase', $order->content_id, '购买付费内容');
            }

            $order->status = 1;
            $order->paid_at = time();
            $order->save();

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 一键购买并支付（积分）
     */
    public static function quickBuy(int $memberId, int $contentId): array
    {
        $order = self::createOrder($memberId, $contentId);
        self::completePayment($order['order_sn'], $memberId);
        return ['success' => true, 'msg' => '购买成功', 'data' => $order];
    }

    /**
     * 获取会员已购内容列表
     */
    public static function getPurchasedList(int $memberId, int $page = 1, int $limit = 20): array
    {
        return PaidOrder::where('member_id', $memberId)
            ->where('status', 1)
            ->order('paid_at', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
    }
}
