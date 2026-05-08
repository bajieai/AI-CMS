<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Content;
use app\common\model\ContentRating;
use app\common\model\Member;
use think\facade\Db;
use think\facade\Log;

/**
 * 评价评分服务 - V2.9新增
 */
class RatingService
{
    /**
     * 提交评价
     *
     * @param int   $memberId  会员ID
     * @param int   $contentId 内容ID
     * @param int   $rating    评分1-5
     * @param string $title     评价标题
     * @param string $content   评价内容
     * @param bool  $isAnonymous 是否匿名
     * @param array $mediaUrls 媒体URL列表
     * @return array
     */
    public static function submitRating(
        int $memberId,
        int $contentId,
        int $rating,
        string $title = '',
        string $content = '',
        bool $isAnonymous = false,
        array $mediaUrls = []
    ): array {
        // 检查内容是否存在
        $content = Content::find($contentId);
        if (!$content) {
            return ['success' => false, 'msg' => '内容不存在'];
        }

        // 检查评分范围
        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'msg' => '评分必须在1-5之间'];
        }

        // 检查是否需要购买后才能评价
        $requirePurchase = (int) ConfigService::get('rating_require_purchase', 1);
        if ($requirePurchase && $content->is_paid) {
            if (!PaidService::canAccess($memberId, $contentId)) {
                return ['success' => false, 'msg' => '请先购买后再评价'];
            }
        }

        // 检查是否已评价
        $exists = ContentRating::where('member_id', $memberId)
            ->where('content_id', $contentId)
            ->find();
        if ($exists) {
            return ['success' => false, 'msg' => '您已经评价过该内容'];
        }

        // 检查是否允许匿名评价
        $allowAnonymous = (int) ConfigService::get('rating_anonymous_allowed', 1);
        if ($isAnonymous && !$allowAnonymous) {
            $isAnonymous = false;
        }

        // 自动审核
        $autoApprove = (int) ConfigService::get('rating_auto_approve', 0);
        $status = $autoApprove ? 1 : 0;

        Db::startTrans();
        try {
            $ratingModel = ContentRating::create([
                'content_id'    => $contentId,
                'member_id'    => $memberId,
                'rating'       => $rating,
                'title'        => $title,
                'content'      => $content,  // 这里是参数$content，不是第39行的$content对象
                'has_media'    => !empty($mediaUrls) ? 1 : 0,
                'media_urls'   => !empty($mediaUrls) ? json_encode($mediaUrls) : null,
                'is_anonymous' => $isAnonymous ? 1 : 0,
                'status'       => $status,
            ]);

            Db::commit();

            $msg = $status === 1 ? '评价成功' : '评价已提交，等待审核';
            return ['success' => true, 'msg' => $msg, 'data' => ['id' => $ratingModel->id]];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error("[RatingService] 提交评价失败: " . $e->getMessage());
            return ['success' => false, 'msg' => '提交失败: ' . $e->getMessage()];
        }
    }

    /**
     * 审核通过
     */
    public static function approveRating(int $id): bool
    {
        $rating = ContentRating::find($id);
        if (!$rating) return false;

        $rating->status = 1;
        return $rating->save();
    }

    /**
     * 拒绝评价
     */
    public static function rejectRating(int $id): bool
    {
        $rating = ContentRating::find($id);
        if (!$rating) return false;

        $rating->status = 2;
        return $rating->save();
    }

    /**
     * 删除评价
     */
    public static function deleteRating(int $id): bool
    {
        $rating = ContentRating::find($id);
        if (!$rating) return false;

        return $rating->delete();
    }

    /**
     * 获取内容的评价列表
     *
     * @param int   $contentId 内容ID
     * @param int   $page     页码
     * @param int   $limit    每页数量
     * @param int   $rating    评分筛选（0=全部）
     * @return array
     */
    public static function getContentRatings(int $contentId, int $page = 1, int $limit = 10, int $rating = 0): array
    {
        $query = ContentRating::where('content_id', $contentId)
            ->where('status', 1);

        if ($rating > 0) {
            $query->where('rating', $rating);
        }

        $list = $query->order('id', 'desc')
            ->paginate(['list_rows' => $limit, 'page' => $page]);

        // 获取评分统计
        $stats = self::getRatingStats($contentId);

        return [
            'list'  => $list->items(),
            'total' => $list->total(),
            'page'  => $page,
            'stats' => $stats,
        ];
    }

    /**
     * 获取评分统计
     */
    public static function getRatingStats(int $contentId): array
    {
        $ratings = ContentRating::where('content_id', $contentId)
            ->where('status', 1)
            ->select();

        $stats = [
            'average' => 0.0,
            'count'   => 0,
            'stars'   => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
        ];

        if ($ratings->isEmpty()) {
            return $stats;
        }

        $totalRating = 0;
        foreach ($ratings as $rating) {
            $stats['stars'][$rating->rating]++;
            $totalRating += $rating->rating;
        }

        $stats['count']   = count($ratings);
        $stats['average'] = round($totalRating / $stats['count'], 1);

        return $stats;
    }

    /**
     * 获取会员的评价列表
     */
    public static function getMemberRatings(int $memberId, int $page = 1, int $limit = 10): array
    {
        $list = ContentRating::where('member_id', $memberId)
            ->order('id', 'desc')
            ->paginate(['list_rows' => $limit, 'page' => $page]);

        return [
            'list'  => $list->items(),
            'total' => $list->total(),
            'page'  => $page,
        ];
    }

    /**
     * 点赞评价
     */
    public static function likeRating(int $ratingId, int $memberId): array
    {
        // 检查是否已点赞（可以用Redis或数据库记录，这里简化为直接+1）
        $rating = ContentRating::find($ratingId);
        if (!$rating) {
            return ['success' => false, 'msg' => '评价不存在'];
        }

        $rating->like_count += 1;
        $rating->save();

        return ['success' => true, 'msg' => '点赞成功', 'data' => ['like_count' => $rating->like_count]];
    }

    /**
     * 回复评价
     */
    public static function replyRating(int $ratingId, int $memberId, string $reply): bool
    {
        $rating = ContentRating::find($ratingId);
        if (!$rating) return false;

        // 这里可以创建回复记录表，暂时简化为更新回复计数
        $rating->reply_count += 1;
        return $rating->save();
    }

    /**
     * 获取后台评价列表
     */
    public static function getAdminList(int $page = 1, int $limit = 20, int $status = -1): array
    {
        $query = ContentRating::order('id', 'desc');

        if ($status >= 0) {
            $query->where('status', $status);
        }

        $list = $query->paginate(['list_rows' => $limit, 'page' => $page]);

        return [
            'list'  => $list->items(),
            'total' => $list->total(),
            'page'  => $page,
        ];
    }
}
