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

namespace app\api\controller;

use app\common\service\RatingService;
use think\facade\Request;

/**
 * 评价评分 API
 * @api_group 评价评分
 * @api_desc 内容评价提交、列表、点赞、统计等接口
 */
class RatingController extends BaseController
{
    /**
     * 提交评价
     * @api 提交评价
     * @api_desc 会员对内容进行评分和文字评价，支持匿名和媒体附件
     * @param int $content_id 内容ID
     * @param int $rating 评分(1-5星)
     * @param string $title 评价标题
     * @param string $content 评价内容
     * @param int $is_anonymous 是否匿名(0/1)
     * @param array $media_urls 附件图片URL数组
     * @return json 返回评价结果
     * @api_auth yes
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
     * @api 评价列表
     * @api_desc 分页获取指定内容的评价列表，可按评分筛选
     * @param int $content_id 内容ID
     * @param int $page 页码
     * @param int $limit 每页数量
     * @param int $rating 评分筛选(0=全部/1-5星)
     * @return json 返回评价列表和统计
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
     * @api 我的评价
     * @api_desc 获取当前会员提交的所有评价记录
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return json 返回我的评价列表
     * @api_auth yes
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
     * @api 评价点赞
     * @api_desc 会员对评价进行点赞（Redis防重复）
     * @param int $rating_id 评价ID
     * @return json 返回点赞结果
     * @api_auth yes
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
     * @api 评价统计
     * @api_desc 获取内容的评价统计数据（总评分数/平均分/各星级分布）
     * @param int $content_id 内容ID
     * @return json 返回评分分布统计
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
