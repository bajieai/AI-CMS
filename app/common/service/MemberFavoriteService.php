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

        // V2.4: 被收藏者获得积分（内容作者）
        try {
            $content = \app\common\model\Content::find($contentId);
            if ($content && $content->member_id > 0 && $content->member_id != $memberId) {
                if (PointsService::checkDailyLimit('content_favorited', $content->member_id)) {
                    $points = PointsService::getConfig('content_favorited', 5);
                    if ($points > 0) {
                        PointsService::add($content->member_id, $points, 'content_favorited', $contentId, '内容被收藏积分');
                    }
                }
            }
        } catch (\Throwable) {
            // 积分添加失败不影响收藏流程
        }

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
