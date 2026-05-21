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

namespace app\common\service;

use app\common\model\Content;
use app\common\model\Member;
use app\common\model\MemberLevel;
use app\common\model\PaidOrder;
use app\common\model\UserChapter;
use app\common\service\ConfigService;
use think\facade\Db;

/**
 * 付费内容服务 - V2.5增强
 * 新增：微信支付支持、VIP免费权益、会员等级内容限制
 */
class PaidService
{
    /**
     * 检查会员是否有权限访问付费内容
     * V2.7规范化：VIP判断统一使用is_vip字段，替代discount<=0语义
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

        // V2.7：VIP权益校验（is_vip=1且vip_expire_time未到期）
        $member = Member::find($memberId);
        if ($member && $member->level_id && $member->vip_expire_time > time()) {
            $level = MemberLevel::find($member->level_id);
            if ($level && $level->is_vip) {
                // V2.8: VIP免费阅读范围二元开关（0=不免费 1=全部免费）
                $vipFreeMode = (int) ConfigService::get('vip_free_read_mode', 0);
                if ($vipFreeMode === 1) {
                    return true; // 全部免费模式：VIP有效期内免费阅读所有付费内容
                }
                // V2.8默认模式(0=不免费)：继续检查折扣或已购买
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

        // V2.9.2 M20: 计算折扣后价格（统一倍率语义：1.0=无折扣，0.8=8折）
        $finalPrice = $content->paid_price;
        $member = Member::find($memberId);
        if ($member && $member->level_id) {
            $level = MemberLevel::find($member->level_id);
            if ($level && $level->discount > 0) {
                $discount = (float) $level->discount;
                // 兼容旧数据：discount>1视为百分比(如80=80%)，discount<1视为倍率(如0.8=8折)
                if ($discount >= 1 && $discount <= 100) {
                    $finalPrice = round($content->paid_price * ($discount / 100), 2);
                } elseif ($discount < 1) {
                    $finalPrice = round($content->paid_price * $discount, 2);
                }
                if ($finalPrice < 0.01) {
                    $finalPrice = 0;
                }
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

        $paidOrderData = [
            'order_sn'  => $orderSn,
            'member_id' => $memberId,
            'content_id' => $contentId,
            'type'      => PaidOrder::TYPE_CONTENT_PURCHASE,
            'price'     => $finalPrice,
            'pay_type'  => $orderPayType,
            'status'    => 0,
        ];

        // V2.9.5 双订单桥接：真钱支付时委托PaymentService创建统一支付订单
        if ($orderPayType === 'money' && $finalPrice > 0) {
            $paymentResult = PaymentService::createPayment(
                $memberId,
                'content',
                (string) $contentId,
                (float) $finalPrice,
                'wechat' // 默认微信支付，前端可覆盖
            );
            if (!$paymentResult['success']) {
                throw new \Exception('创建支付订单失败: ' . ($paymentResult['msg'] ?? '未知错误'));
            }
            $paidOrderData['payment_order_no'] = $paymentResult['order_no'];
        }

        $order = PaidOrder::create($paidOrderData);

        return $order->toArray();
    }

    /**
     * V2.9.5 创建打赏订单
     * 复用 PaymentService 支付链路，跳过优惠/等级/积分折扣逻辑
     * @param int $memberId 打赏者ID
     * @param int $contentId 被打赏内容ID
     * @param float $amount 打赏金额（元），范围 0.01 ~ 999
     * @param string $payType 支付方式（默认 wechat）
     * @return array 订单信息
     * @throws \Exception
     */
    public static function createRewardOrder(int $memberId, int $contentId, float $amount, string $payType = 'wechat'): array
    {
        // 金额校验
        if ($amount < 0.01 || $amount > 999) {
            throw new \Exception('打赏金额需在 0.01 ~ 999 元之间');
        }

        $content = Content::find($contentId);
        if (!$content || $content->status != 2) {
            throw new \Exception('内容不存在或未发布');
        }

        $orderSn = 'R' . date('YmdHis') . str_pad((string) $memberId, 6, '0', STR_PAD_LEFT) . rand(100, 999);

        $paidOrderData = [
            'order_sn'   => $orderSn,
            'member_id'  => $memberId,
            'content_id' => $contentId,
            'type'       => PaidOrder::TYPE_REWARD,
            'price'      => $amount,
            'pay_type'   => 'money',
            'status'     => 0,
        ];

        // 委托 PaymentService 创建统一支付订单
        $paymentResult = PaymentService::createPayment(
            $memberId,
            'reward',
            (string) $contentId,
            $amount,
            $payType
        );
        if (!$paymentResult['success']) {
            throw new \Exception('创建支付订单失败: ' . ($paymentResult['msg'] ?? '未知错误'));
        }
        $paidOrderData['payment_order_no'] = $paymentResult['order_no'];

        $order = PaidOrder::create($paidOrderData);

        return $order->toArray();
    }

    /**
     * 完成支付
     * V2.5增强：支持微信支付订单完成
     * V2.9.5增强：支持通过payment_order_no完成支付（桥接模式）
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

        return self::doCompletePayment($order, $memberId);
    }

    /**
     * V2.9.5 通过payment_order_no完成支付（支付回调桥接用）
     */
    public static function completePaymentByOrderNo(string $paymentOrderNo, int $memberId): bool
    {
        $order = PaidOrder::where('payment_order_no', $paymentOrderNo)
            ->where('member_id', $memberId)
            ->where('status', 0)
            ->find();

        if (!$order) {
            throw new \Exception('订单不存在或已处理');
        }

        return self::doCompletePayment($order, $memberId);
    }

    /**
     * 统一完成支付业务逻辑
     */
    protected static function doCompletePayment(\app\common\model\PaidOrder $order, int $memberId): bool
    {
        Db::startTrans();
        try {
            if ($order->pay_type === 'points') {
                PointsService::consume($memberId, (int) $order->price, 'purchase', $order->content_id, '购买付费内容');
            }
            // 真钱支付由PaymentService回调处理，此处仅处理积分支付

            $order->status = 1;
            $order->paid_at = time();
            $order->save();

            Db::commit();

            // V2.9 邀请奖励：首次付费触发邀请人奖励
            InviteRewardService::onMemberEvent($memberId, 'pay');

            // V2.7: 消费返积分（积分支付也返）
            $ratio = (float) ConfigService::get('points_consume_ratio', 0);
            if ($ratio > 0 && $order->price > 0) {
                $rewardPoints = (int) round($order->price * $ratio);
                if ($rewardPoints > 0) {
                    try {
                        PointsService::add($memberId, $rewardPoints, 'consume_reward', $order->id, "消费返积分(订单{$order->order_sn})");
                    } catch (\Throwable $e) {
                        \think\facade\Log::warning("消费返积分失败: " . $e->getMessage());
                    }
                }
            }

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
     * V2.7: 检查会员是否有权限访问指定章节
     * 规则：1) 免费试读章节直接放行 2) 已购买父内容放行 3) VIP免费放行 4) 单章购买放行
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

        // 已购买父内容（整本购买）
        if (self::canAccess($memberId, $parentContentId)) {
            return true;
        }

        // 单章购买
        if (UserChapter::hasPurchased($memberId, $chapterId)) {
            return true;
        }

        // VIP权益（V2.7改用is_vip字段）
        $member = Member::find($memberId);
        if ($member && $member->level_id && $member->vip_expire_time > time()) {
            $level = MemberLevel::find($member->level_id);
            if ($level && $level->is_vip) {
                // V2.8: VIP免费阅读范围二元开关
                $vipFreeMode = (int) ConfigService::get('vip_free_read_mode', 0);
                if ($vipFreeMode === 1) {
                    return true; // 全部免费模式
                }
            }
        }

        return false;
    }

    /**
     * V2.7: 获取章节列表及访问状态（前台阅读页用）
     */
    public static function getChapterListWithAccess(int $parentId, ?int $memberId = null): array
    {
        $chapters = Content::where('parent_id', $parentId)
            ->where('is_chapter', 1)
            ->where('status', 2)
            ->order('chapter_sort', 'asc')
            ->order('id', 'asc')
            ->select();

        $result = [];
        foreach ($chapters as $chapter) {
            $canAccess = self::canAccessChapter($memberId, $parentId, $chapter->id);
            $result[] = [
                'id'           => $chapter->id,
                'title'        => $chapter->chapter_title ?: $chapter->title,
                'sort'         => $chapter->chapter_sort,
                'is_free'      => !empty($chapter->is_free_chapter),
                'is_unlocked'  => $canAccess,
                'price'        => $chapter->chapter_price ?? 0,
                'create_time'  => $chapter->create_time,
            ];
        }
        return $result;
    }

    /**
     * V2.7: 购买单章（积分支付）
     */
    public static function buyChapter(int $memberId, int $chapterId): array
    {
        $chapter = Content::find($chapterId);
        if (!$chapter || empty($chapter->is_chapter) || $chapter->parent_id <= 0) {
            throw new \Exception('章节不存在');
        }

        $parentId = $chapter->parent_id;

        if (self::canAccessChapter($memberId, $parentId, $chapterId)) {
            throw new \Exception('您已拥有该章节权限');
        }

        $price = (float) ($chapter->chapter_price ?? 0);
        if ($price <= 0) {
            $parent = Content::find($parentId);
            $price = $parent ? (float) $parent->paid_price : 0;
        }

        if ($price > 0) {
            PointsService::consume($memberId, (int) $price, 'chapter', $chapterId, '购买章节');
        }

        $orderSn = 'C' . date('YmdHis') . str_pad((string) $memberId, 6, '0', STR_PAD_LEFT) . rand(100, 999);

        UserChapter::create([
            'member_id'  => $memberId,
            'content_id' => $chapterId,
            'parent_id'  => $parentId,
            'order_sn'   => $orderSn,
            'price'      => $price,
        ]);

        return ['success' => true, 'msg' => '购买成功', 'order_sn' => $orderSn];
    }

    /**
     * V2.7: 购买整本（复用paid_order，type=chapter）
     */
    public static function buyWholeBook(int $memberId, int $parentId): array
    {
        $book = Content::find($parentId);
        if (!$book || empty($book->is_paid)) {
            throw new \Exception('该内容无需付费');
        }

        if (self::canAccess($memberId, $parentId)) {
            throw new \Exception('您已购买该内容');
        }

        $order = self::createOrder($memberId, $parentId, 'points');
        self::completePayment($order['order_sn'], $memberId);

        return ['success' => true, 'msg' => '购买成功', 'order_sn' => $order['order_sn']];
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
            ->where('type', PaidOrder::TYPE_DOWNLOAD)
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
        return PaidOrder::with(['content' => ['cate']])
            ->where('member_id', $memberId)
            ->where('status', 1)
            ->order('paid_at', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
    }
}
