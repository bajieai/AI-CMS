<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\service\RatingService;
use think\facade\Request;

/**
 * 评价评分 API - V2.9新增
 */
class RatingController extends BaseController
{
    /**
     * 提交评价
     * POST /api/rating/submit
     */
    public function submit()
    {
        $memberId = $this->getMemberId();
        if (!$memberId) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }

        $contentId   = (int) Request::post('content_id', 0);
        $rating      = (int) Request::post('rating', 0);
        $title       = Request::post('title', '');
        $content     = Request::post('content', '');
        $isAnonymous = (bool) Request::post('is_anonymous', 0);
        $mediaUrls   = Request::post('media_urls/a', []);

        if ($contentId <= 0) {
            return json(['code' => 400, 'msg' => '内容ID无效']);
        }

        $result = RatingService::submitRating(
            $memberId,
            $contentId,
            $rating,
            $title,
            $content,
            $isAnonymous,
            $mediaUrls
        );

        return json([
            'code' => $result['success'] ? 1 : 0,
            'msg'  => $result['msg'],
            'data' => $result['data'] ?? [],
        ]);
    }

    /**
     * 获取内容的评价列表
     * GET /api/rating/list?content_id=1&page=1&limit=10&rating=0
     */
    public function list()
    {
        $contentId = (int) Request::get('content_id', 0);
        $page      = (int) Request::get('page', 1);
        $limit     = (int) Request::get('limit', 10);
        $rating    = (int) Request::get('rating', 0);

        if ($contentId <= 0) {
            return json(['code' => 400, 'msg' => '内容ID无效']);
        }

        $result = RatingService::getContentRatings($contentId, $page, $limit, $rating);

        return json([
            'code' => 1,
            'msg'  => 'success',
            'data' => $result,
        ]);
    }

    /**
     * 获取我的评价列表
     * GET /api/rating/my?page=1&limit=10
     */
    public function my()
    {
        $memberId = $this->getMemberId();
        if (!$memberId) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }

        $page  = (int) Request::get('page', 1);
        $limit = (int) Request::get('limit', 10);

        $result = RatingService::getMemberRatings($memberId, $page, $limit);

        return json([
            'code' => 1,
            'msg'  => 'success',
            'data' => $result,
        ]);
    }

    /**
     * 点赞评价
     * POST /api/rating/like
     */
    public function like()
    {
        $memberId = $this->getMemberId();
        if (!$memberId) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }

        $ratingId = (int) Request::post('rating_id', 0);
        if ($ratingId <= 0) {
            return json(['code' => 400, 'msg' => '评价ID无效']);
        }

        $result = RatingService::likeRating($ratingId, $memberId);

        return json([
            'code' => $result['success'] ? 1 : 0,
            'msg'  => $result['msg'],
            'data' => $result['data'] ?? [],
        ]);
    }

    /**
     * 获取评价统计
     * GET /api/rating/stats?content_id=1
     */
    public function stats()
    {
        $contentId = (int) Request::get('content_id', 0);
        if ($contentId <= 0) {
            return json(['code' => 400, 'msg' => '内容ID无效']);
        }

        $stats = RatingService::getRatingStats($contentId);

        return json([
            'code' => 1,
            'msg'  => 'success',
            'data' => $stats,
        ]);
    }
}
