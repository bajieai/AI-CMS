<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\RatingService;
use think\facade\Request;

/**
 * 评价管理控制器 - V2.9新增
 */
class RatingController extends AdminBaseController
{
    /**
     * 评价列表
     */
    public function index()
    {
        $status  = (int) Request::get('status', -1);
        $keyword = Request::get('keyword', '');

        $query = \app\common\model\ContentRating::alias('r')
            ->leftJoin('content c', 'c.id = r.content_id')
            ->leftJoin('member m', 'm.id = r.member_id')
            ->field('r.*, c.title as content_title, m.nickname as member_nickname');

        if ($status >= 0) {
            $query->where('r.status', $status);
        }

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('c.title', 'like', "%{$keyword}%")
                  ->whereOr('m.nickname', 'like', "%{$keyword}%");
            });
        }

        $list = $query->order('r.id', 'desc')
            ->paginate(20);

        // 统计
        $stats = [
            'pending'   => \app\common\model\ContentRating::where('status', 0)->count(),
            'approved'  => \app\common\model\ContentRating::where('status', 1)->count(),
            'total'     => \app\common\model\ContentRating::count(),
            'avg_rating'=> \app\common\model\ContentRating::where('status', 1)->avg('rating') ?: 0,
        ];

        return $this->view('/rating_index', [
            'list'   => $list,
            'status' => $status,
            'keyword'=> $keyword,
            'stats'  => $stats,
        ]);
    }

    /**
     * 查看评价详情
     */
    public function detail()
    {
        $id = (int) Request::get('id', 0);
        if (!$id) {
            return redirect('/admin/rating/index');
        }

        $rating = \app\common\model\ContentRating::alias('r')
            ->leftJoin('content c', 'c.id = r.content_id')
            ->leftJoin('member m', 'm.id = r.member_id')
            ->field('r.*, c.title as content_title, m.nickname as member_nickname, m.avatar as member_avatar')
            ->where('r.id', $id)
            ->find();

        if (!$rating) {
            return redirect('/admin/rating/index');
        }

        // 解析媒体URL
        if ($rating->media_urls) {
            $rating->media_urls = json_decode($rating->media_urls, true);
        }

        return $this->view('/rating_view', [
            'rating' => $rating,
        ]);
    }

    /**
     * 审核通过
     */
    public function approve()
    {
        $id = (int) Request::post('id', 0);
        if (!$id) {
            return json(['code' => 0, 'msg' => 'ID无效']);
        }

        $result = RatingService::approveRating($id);
        if ($result) {
            return json(['code' => 1, 'msg' => '审核通过']);
        }
        return json(['code' => 0, 'msg' => '操作失败']);
    }

    /**
     * 拒绝评价
     */
    public function reject()
    {
        $id = (int) Request::post('id', 0);
        if (!$id) {
            return json(['code' => 0, 'msg' => 'ID无效']);
        }

        $result = RatingService::rejectRating($id);
        if ($result) {
            return json(['code' => 1, 'msg' => '已拒绝']);
        }
        return json(['code' => 0, 'msg' => '操作失败']);
    }

    /**
     * 删除评价
     */
    public function delete()
    {
        $id = (int) Request::post('id', 0);
        if (!$id) {
            return json(['code' => 0, 'msg' => 'ID无效']);
        }

        $result = RatingService::deleteRating($id);
        if ($result) {
            return json(['code' => 1, 'msg' => '删除成功']);
        }
        return json(['code' => 0, 'msg' => '删除失败']);
    }

    /**
     * 评价设置
     */
    public function settings()
    {
        if (Request::isPost()) {
            $configs = [
                'rating_require_purchase'   => (int) Request::post('rating_require_purchase', 0),
                'rating_anonymous_allowed'  => (int) Request::post('rating_anonymous_allowed', 1),
                'rating_auto_approve'      => (int) Request::post('rating_auto_approve', 0),
                'rating_enabled'           => (int) Request::post('rating_enabled', 1),
            ];

            foreach ($configs as $key => $value) {
                \app\common\service\ConfigService::set($key, $value);
            }

            $this->success('保存成功');
        }

        $config = [
            'rating_require_purchase'  => \app\common\service\ConfigService::get('rating_require_purchase', 1),
            'rating_anonymous_allowed' => \app\common\service\ConfigService::get('rating_anonymous_allowed', 1),
            'rating_auto_approve'     => \app\common\service\ConfigService::get('rating_auto_approve', 0),
            'rating_enabled'          => \app\common\service\ConfigService::get('rating_enabled', 1),
        ];

        return $this->view('/rating_settings', ['config' => $config]);
    }
}
