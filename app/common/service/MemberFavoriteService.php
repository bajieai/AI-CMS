<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\MemberFavorite as MemberFavoriteModel;
use think\facade\Cache;

/**
 * 会员收藏服务
 */
class MemberFavoriteService
{
    /**
     * 收藏
     */
    public function add(int $memberId, int $contentId): array
    {
        if ($memberId <= 0 || $contentId <= 0) {
            return ['success' => false, 'msg' => '参数错误'];
        }

        $exists = MemberFavoriteModel::where('member_id', $memberId)
            ->where('content_id', $contentId)
            ->find();

        if ($exists) {
            return ['success' => false, 'msg' => '已收藏'];
        }

        MemberFavoriteModel::create([
            'member_id'  => $memberId,
            'content_id' => $contentId,
        ]);

        Cache::tag(CacheService::TAG_CONTENT)->clear();
        return ['success' => true, 'msg' => '收藏成功'];
    }

    /**
     * 取消收藏
     */
    public function remove(int $memberId, int $contentId): array
    {
        $favorite = MemberFavoriteModel::where('member_id', $memberId)
            ->where('content_id', $contentId)
            ->find();

        if (!$favorite) {
            return ['success' => false, 'msg' => '未收藏'];
        }

        $favorite->delete();
        Cache::tag(CacheService::TAG_CONTENT)->clear();
        return ['success' => true, 'msg' => '已取消收藏'];
    }

    /**
     * 获取会员收藏列表
     */
    public function getList(int $memberId, int $page = 1, int $limit = 10): array
    {
        $list = MemberFavoriteModel::where('member_id', $memberId)
            ->with('content')
            ->order('create_time', 'desc')
            ->page($page, $limit)
            ->select();

        return ['success' => true, 'data' => $list];
    }
}
