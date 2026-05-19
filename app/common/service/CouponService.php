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

use app\common\model\CouponTemplate;
use app\common\model\UserCoupon;
use think\facade\Db;
use think\facade\Log;

/**
 * 优惠券服务 - V2.9新增
 * 独立于PaidService，通过validate()返回折扣信息
 */
class CouponService
{
    /**
     * 创建优惠券模板
     */
    public static function createTemplate(array $data): CouponTemplate
    {
        $template = CouponTemplate::create([
            'coupon_name'      => $data['coupon_name'],
            'coupon_type'      => $data['coupon_type'],
            'condition_amount'  => (float) ($data['condition_amount'] ?? 0),
            'reduce_amount'     => (float) ($data['reduce_amount'] ?? 0),
            'total_stock'      => (int) ($data['total_stock'] ?? 0),
            'remain_stock'     => (int) ($data['total_stock'] ?? 0),
            'per_user_limit'   => (int) ($data['per_user_limit'] ?? 1),
            'start_time'       => !empty($data['start_time']) ? strtotime($data['start_time']) : 0,
            'end_time'         => !empty($data['end_time']) ? strtotime($data['end_time']) : 0,
            'scope_type'       => $data['scope_type'] ?? 'all',
            'scope_value'      => !empty($data['scope_value']) ? json_encode($data['scope_value']) : null,
            'status'           => (int) ($data['status'] ?? 0),
        ]);

        Log::info("[CouponService] 创建优惠券模板: {$template->coupon_name}, id={$template->id}");
        return $template;
    }

    /**
     * 更新优惠券模板
     */
    public static function updateTemplate(int $id, array $data): bool
    {
        $template = CouponTemplate::find($id);
        if (!$template) return false;

        $update = [];
        foreach (['coupon_name', 'coupon_type', 'condition_amount', 'reduce_amount', 'total_stock', 'remain_stock', 'per_user_limit', 'start_time', 'end_time', 'scope_type', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                if (in_array($field, ['start_time', 'end_time'])) {
                    $update[$field] = !empty($data[$field]) ? strtotime($data[$field]) : 0;
                } elseif (in_array($field, ['condition_amount', 'reduce_amount'])) {
                    $update[$field] = (float) $data[$field];
                } elseif (in_array($field, ['total_stock', 'remain_stock', 'per_user_limit', 'status'])) {
                    $update[$field] = (int) $data[$field];
                } else {
                    $update[$field] = $data[$field];
                }
            }
        }

        if (!empty($data['scope_value'])) {
            $update['scope_value'] = json_encode($data['scope_value']);
        }

        return $template->save($update) !== false;
    }

    /**
     * 发放优惠券给会员
     *
     * @param int   $memberId   会员ID
     * @param int   $templateId 模板ID
     * @param int   $quantity    发放数量
     * @return array ['success' => true/false, 'msg' => '', 'data' => ['coupon_codes' => [...]]]
     */
    public static function issueCoupon(int $memberId, int $templateId, int $quantity = 1): array
    {
        $template = CouponTemplate::find($templateId);
        if (!$template || !$template->isValid()) {
            return ['success' => false, 'msg' => '优惠券模板不可用'];
        }

        // 检查每人限领
        $userReceived = UserCoupon::where('member_id', $memberId)
            ->where('template_id', $templateId)
            ->count();
        if ($userReceived + $quantity > $template->per_user_limit) {
            return ['success' => false, 'msg' => '超过每人限领数量'];
        }

        Db::startTrans();
        try {
            // 原子扣减库存（高并发安全）
            if ($template->total_stock > 0) {
                $affected = Db::name('coupon_template')
                    ->where('id', $templateId)
                    ->where('remain_stock', '>=', $quantity)
                    ->dec('remain_stock', $quantity)
                    ->update();
                if (!$affected) {
                    Db::rollback();
                    return ['success' => false, 'msg' => '库存不足'];
                }
            }

            $couponCodes = [];
            for ($i = 0; $i < $quantity; $i++) {
                $code = self::generateCouponCode();
                $expireAt = self::calculateExpireAt($template);

                UserCoupon::create([
                    'member_id'     => $memberId,
                    'template_id'   => $templateId,
                    'code'          => $code,
                    'coupon_type'   => $template->coupon_type,
                    'condition_amount' => $template->condition_amount,
                    'reduce_amount' => $template->reduce_amount,
                    'status'        => 0,
                    'expire_at'     => $expireAt,
                ]);
                $couponCodes[] = $code;
            }

            Db::commit();
            return ['success' => true, 'msg' => '发放成功', 'data' => ['coupon_codes' => $couponCodes]];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error("[CouponService] 发放优惠券失败: " . $e->getMessage());
            return ['success' => false, 'msg' => '发放失败: ' . $e->getMessage()];
        }
    }

    /**
     * 验证优惠券（结算时使用）
     *
     * @param int   $memberId  会员ID
     * @param int   $couponId   用户优惠券ID 或 优惠券码
     * @param float $orderAmount 订单金额
     * @param int   $contentId  内容ID（用于检查适用范围）
     * @return array ['success' => true/false, 'discount' => 折扣金额, 'msg' => '']
     */
    public static function validateCoupon(int $memberId, $couponId, float $orderAmount, int $contentId = 0): array
    {
        if (is_string($couponId)) {
            $coupon = UserCoupon::where('code', $couponId)->where('member_id', $memberId)->find();
        } else {
            $coupon = UserCoupon::where('id', $couponId)->where('member_id', $memberId)->find();
        }

        if (!$coupon) {
            return ['success' => false, 'discount' => 0, 'msg' => '优惠券不存在'];
        }

        if (!$coupon->isValid()) {
            $msg = $coupon->status === 2 ? '优惠券已过期' : '优惠券不可用';
            return ['success' => false, 'discount' => 0, 'msg' => $msg];
        }

        $template = $coupon->template;
        if (!$template || !$template->isValid()) {
            return ['success' => false, 'discount' => 0, 'msg' => '优惠券模板不可用'];
        }

        // 检查适用范围
        if ($template->scope_type !== 'all' && $contentId > 0) {
            $scopeValue = json_decode($template->scope_value, true) ?? [];
            if ($template->scope_type === 'content' && !in_array($contentId, $scopeValue)) {
                return ['success' => false, 'discount' => 0, 'msg' => '该优惠券不适用于此商品'];
            }
            if ($template->scope_type === 'category' && !empty($contentId)) {
                $content = \app\common\model\Content::find($contentId);
                if ($content && !in_array($content->cate_id, $scopeValue)) {
                    return ['success' => false, 'discount' => 0, 'msg' => '该优惠券不适用于此商品分类'];
                }
            }
        }

        $discount = $template->calculateDiscount($orderAmount);
        if ($discount <= 0) {
            return ['success' => false, 'discount' => 0, 'msg' => '订单金额不满足使用条件'];
        }

        return ['success' => true, 'discount' => $discount, 'msg' => '可以使用的', 'data' => ['coupon' => $coupon->toArray()]];
    }

    /**
     * 使用优惠券（支付成功后调用）
     */
    public static function useCoupon(int $couponId, int $orderId): bool
    {
        $coupon = UserCoupon::find($couponId);
        if (!$coupon || $coupon->status !== 0) return false;

        $coupon->status = 1;
        $coupon->used_at = time();
        $coupon->used_order_id = $orderId;
        return $coupon->save();
    }

    /**
     * 退还优惠券（全额退款时调用）
     */
    public static function refundCoupon(int $couponId): bool
    {
        $coupon = UserCoupon::find($couponId);
        if (!$coupon || $coupon->status !== 1) return false;

        // 检查配置：是否退还优惠券
        $refundReturn = (int) ConfigService::get('coupon_refund_return', 1);
        if (!$refundReturn) return false;

        $coupon->status = 4; // 已退还
        return $coupon->save();
    }

    /**
     * 获取会员优惠券列表
     */
    public static function getMemberCoupons(int $memberId, int $status = -1, int $page = 1, int $limit = 20): array
    {
        $query = UserCoupon::where('member_id', $memberId);

        if ($status >= 0) {
            $query->where('status', $status);
        }

        $list = $query->order('id', 'desc')
            ->paginate(['list_rows' => $limit, 'page' => $page]);

        // 自动标记过期
        foreach ($list as $coupon) {
            if ($coupon->expire_at > 0 && $coupon->expire_at < time() && $coupon->status === 0) {
                $coupon->status = 2;
                $coupon->save();
            }
        }

        return [
            'list'  => $list->items(),
            'total' => $list->total(),
            'page'  => $page,
        ];
    }

    /**
     * 生成优惠券码
     */
    public static function generateCouponCode(): string
    {
        $code = 'CP' . date('Ymd') . str_pad((string) rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        while (UserCoupon::where('code', $code)->find()) {
            $code = 'CP' . date('Ymd') . str_pad((string) rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        }
        return $code;
    }

    /**
     * 计算优惠券过期时间
     */
    public static function calculateExpireAt(CouponTemplate $template): int
    {
        if ($template->end_time > 0) {
            return $template->end_time;
        }
        // 如果没有设置结束时间，默认30天后过期
        return time() + 30 * 86400;
    }

    /**
     * 新人券发放（注册时调用）
     */
    public static function issueNewbieCoupon(int $memberId): void
    {
        $enabled = (int) ConfigService::get('coupon_newbie_enabled', 1);
        if (!$enabled) return;

        $templateId = (int) ConfigService::get('coupon_newbie_template_id', 0);
        if ($templateId <= 0) return;

        try {
            self::issueCoupon($memberId, $templateId, 1);
            Log::info("[CouponService] 新人券发放成功: memberId={$memberId}, templateId={$templateId}");
        } catch (\Throwable $e) {
            Log::warning("[CouponService] 新人券发放失败: " . $e->getMessage());
        }
    }
}
