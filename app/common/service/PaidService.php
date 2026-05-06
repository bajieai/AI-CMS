<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Content;
use app\common\model\Member;
use app\common\model\MemberLevel;
use app\common\model\PaidOrder;
use think\facade\Db;

/**
 * 付费内容服务 - V2.5增强
 * 新增：微信支付支持、VIP免费权益、会员等级内容限制
 */
class PaidService
{
    /**
     * 检查会员是否有权限访问付费内容
     * V2.6修复：VIP权益增加vip_expire_time到期校验，修复discount<=0死代码
     */
    public static function canAccess(?int $memberId, int $contentId): bool
    {
        $content = Content::find($contentId);
        if (!$content) return false;

        // 免费内容直接放行（但检查等级限制）
        if (empty($content->is_paid)) {
            return self::checkLevelAccess($memberId, $content);
        }

        if (!$memberId) return false;

        // V2.6：VIP权益校验（增加到期时间判断）
        $member = Member::find($memberId);
        if ($member && $member->level_id && $member->vip_expire_time > time()) {
            $level = MemberLevel::find($member->level_id);
            if ($level && $level->discount <= 0) {
                return true; // VIP有效期内且折扣0=免费
            }
        }

        // 检查是否已购买
        return PaidOrder::where('member_id', $memberId)
            ->where('content_id', $contentId)
            ->where('status', 1)
            ->count() > 0;
    }

    /**
     * 检查会员等级是否满足内容最低等级要求
     */
    public static function checkLevelAccess(?int $memberId, Content $content): bool
    {
        if (empty($content->min_level_id) || $content->min_level_id <= 0) {
            return true; // 无等级限制
        }

        if (!$memberId) return false;

        $member = Member::find($memberId);
        if (!$member) return false;

        // 获取会员等级的排序值（越高等级sort越大）
        $memberLevel = MemberLevel::find($member->level_id);
        $requiredLevel = MemberLevel::find($content->min_level_id);

        if (!$memberLevel || !$requiredLevel) return true;

        return $memberLevel->sort >= $requiredLevel->sort;
    }

    /**
     * 获取内容的安全展示版本
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
     * V2.5增强：支持money支付类型
     */
    public static function createOrder(int $memberId, int $contentId, string $payType = ''): array
    {
        $content = Content::find($contentId);
        if (!$content || empty($content->is_paid)) {
            throw new \Exception('该内容无需付费');
        }

        if (self::canAccess($memberId, $contentId)) {
            throw new \Exception('您已购买该内容');
        }

        // V2.5：检查等级限制
        if (!self::checkLevelAccess($memberId, $content)) {
            throw new \Exception('您的会员等级不足，无法访问此内容');
        }

        // V2.6：计算折扣后价格（增加VIP到期校验）
        $finalPrice = $content->paid_price;
        $member = Member::find($memberId);
        if ($member && $member->level_id && $member->vip_expire_time > time()) {
            $level = MemberLevel::find($member->level_id);
            if ($level && $level->discount > 0 && $level->discount < 100) {
                $finalPrice = round($content->paid_price * ($level->discount / 100), 2);
            } elseif ($level && $level->discount <= 0) {
                throw new \Exception('VIP会员可免费阅读');
            }
        }

        // V2.5：确定支付类型
        $orderPayType = $payType ?: $content->paid_type;
        if ($orderPayType === 'money' && $finalPrice > 0) {
            // 真钱支付
        } elseif ($orderPayType === 'points' || $content->paid_type === 'points') {
            $orderPayType = 'points';
        }

        $orderSn = 'P' . date('YmdHis') . str_pad((string) $memberId, 6, '0', STR_PAD_LEFT) . rand(100, 999);

        $order = PaidOrder::create([
            'order_sn'  => $orderSn,
            'member_id' => $memberId,
            'content_id' => $contentId,
            'type'      => 'content',
            'price'     => $finalPrice,
            'pay_type'  => $orderPayType,
            'status'    => 0,
        ]);

        return $order->toArray();
    }

    /**
     * 完成支付
     * V2.5增强：支持微信支付订单完成
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
            // 微信支付由回调处理，此处仅处理积分支付

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
        $order = self::createOrder($memberId, $contentId, 'points');
        self::completePayment($order['order_sn'], $memberId);
        return ['success' => true, 'msg' => '购买成功', 'data' => $order];
    }

    /**
     * V2.6: 检查会员是否有权限访问指定章节
     * 规则：1) 免费试读章节直接放行 2) 已购买父内容放行 3) VIP免费放行
     */
    public static function canAccessChapter(?int $memberId, int $parentContentId, int $chapterId): bool
    {
        $chapter = Content::find($chapterId);
        if (!$chapter || $chapter->parent_id != $parentContentId) {
            return false;
        }

        // 免费试读章节
        if (!empty($chapter->is_free_chapter)) {
            return true;
        }

        if (!$memberId) return false;

        // 已购买父内容
        if (self::canAccess($memberId, $parentContentId)) {
            return true;
        }

        // VIP权益
        $member = Member::find($memberId);
        if ($member && $member->level_id && $member->vip_expire_time > time()) {
            $level = MemberLevel::find($member->level_id);
            if ($level && $level->discount <= 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * V2.6: 检查会员是否有付费下载权限
     */
    public static function canAccessDownload(?int $memberId, int $contentId): bool
    {
        if (!$memberId) return false;

        // 检查是否有独立的下载付费订单
        $hasDownloadOrder = PaidOrder::where('member_id', $memberId)
            ->where('content_id', $contentId)
            ->where('type', 'download')
            ->where('status', 1)
            ->count() > 0;

        if ($hasDownloadOrder) return true;

        // 已购买内容本身也包含下载权限
        return self::canAccess($memberId, $contentId);
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
