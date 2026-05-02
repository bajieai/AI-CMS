<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\MemberLike as MemberLikeModel;
use app\common\model\Content as ContentModel;
use think\facade\Cache;

/**
 * 会员点赞服务
 */
class MemberLikeService
{
    /**
     * 点赞
     */
    public function like(int $memberId, int $contentId): array
    {
        if ($memberId <= 0 || $contentId <= 0) {
            return ['success' => false, 'msg' => '参数错误'];
        }

        $exists = MemberLikeModel::where('member_id', $memberId)
            ->where('content_id', $contentId)
            ->find();

        if ($exists) {
            return ['success' => false, 'msg' => '已点赞'];
        }

        MemberLikeModel::create([
            'member_id'  => $memberId,
            'content_id' => $contentId,
        ]);

        ContentModel::where('id', $contentId)->inc('like_count')->update();
        Cache::tag(CacheService::TAG_CONTENT)->clear();

        // V2.4: 被点赞者获得积分（内容作者）
        try {
            $content = ContentModel::find($contentId);
            if ($content && $content->member_id > 0 && $content->member_id != $memberId) {
                if (PointsService::checkDailyLimit('content_liked', $content->member_id)) {
                    $points = PointsService::getConfig('content_liked', 3);
                    if ($points > 0) {
                        PointsService::add($content->member_id, $points, 'content_liked', $contentId, '内容被点赞积分');
                    }
                }
            }
        } catch (\Throwable) {
            // 积分添加失败不影响点赞流程
        }

        return ['success' => true, 'msg' => '点赞成功'];
    }

    /**
     * 取消点赞
     */
    public function unlike(int $memberId, int $contentId): array
    {
        $like = MemberLikeModel::where('member_id', $memberId)
            ->where('content_id', $contentId)
            ->find();

        if (!$like) {
            return ['success' => false, 'msg' => '未点赞'];
        }

        $like->delete();
        ContentModel::where('id', $contentId)->dec('like_count')->update();
        Cache::tag(CacheService::TAG_CONTENT)->clear();

        return ['success' => true, 'msg' => '已取消点赞'];
    }

    /**
     * 获取会员点赞列表
     */
    public function getList(int $memberId, int $page = 1, int $limit = 10): array
    {
        $list = MemberLikeModel::where('member_id', $memberId)
            ->order('create_time', 'desc')
            ->page($page, $limit)
            ->select();

        return ['success' => true, 'data' => $list];
    }
}
