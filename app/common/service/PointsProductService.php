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

use app\common\model\PointsProduct;
use app\common\model\PointsExchange;
use app\common\model\PointsLog;
use app\common\model\Member;
use think\facade\Db;

/**
 * 积分商城服务 - V2.6
 */
class PointsProductService
{
    /**
     * 获取商品列表
     */
    public static function getList(int $page = 1, int $limit = 20, bool $onlyEnabled = true): array
    {
        $query = PointsProduct::order('sort', 'desc')->order('id', 'desc');
        if ($onlyEnabled) {
            $query->where('is_enabled', 1);
        }
        return $query->page($page, $limit)->select()->toArray();
    }

    /**
     * 兑换商品
     */
    public static function exchange(int $userId, int $productId, array $deliveryInfo = []): array
    {
        $product = PointsProduct::find($productId);
        if (!$product || empty($product->is_enabled)) {
            throw new \Exception('商品不存在或已下架');
        }
        if ($product->stock >= 0 && $product->stock <= 0) {
            throw new \Exception('商品库存不足');
        }

        $member = Member::find($userId);
        if (!$member) {
            throw new \Exception('用户不存在');
        }
        if ($member->points < $product->points) {
            throw new \Exception('积分不足');
        }

        Db::startTrans();
        try {
            // 扣减积分
            $affected = Db::name('member')
                ->where('id', $userId)
                ->where('points', '>=', $product->points)
                ->dec('points', $product->points)
                ->update();

            if ($affected === 0) {
                throw new \Exception('积分不足');
            }

            // 扣减库存
            if ($product->stock > 0) {
                $product->stock -= 1;
                $product->save();
            }

            // 创建兑换记录
            $exchange = PointsExchange::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'points' => $product->points,
                'status' => 0,
                'delivery_info' => $deliveryInfo,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 积分日志
            PointsLog::create([
                'member_id' => $userId,
                'points' => -$product->points,
                'type' => 'exchange',
                'source_id' => $exchange->id,
                'note' => "兑换商品: {$product->title}",
            ]);

            Db::commit();
            return ['success' => true, 'exchange_id' => $exchange->id];
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 审核兑换（发货/拒绝）
     */
    public static function audit(int $exchangeId, int $status, string $remark = ''): bool
    {
        if (!in_array($status, [1, 2])) {
            throw new \Exception('无效的审核状态');
        }

        $exchange = PointsExchange::find($exchangeId);
        if (!$exchange || $exchange->status !== 0) {
            throw new \Exception('兑换记录不存在或已处理');
        }

        $exchange->status = $status;
        $exchange->remark = $remark;
        $exchange->update_time = time();
        return $exchange->save();
    }
}
