<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ReviewService;
use think\facade\Request;

/**
 * 内容审核控制器
 */
class ReviewController extends AdminBaseController
{
    /**
     * 待审内容列表
     */
    public function index()
    {
        $params = [
            'type'    => Request::get('type', ''),
            'keyword' => Request::get('keyword', ''),
        ];

        $reviewService = new ReviewService();
        $list = $reviewService->getPendingList($params, 20);
        $stats = $reviewService->getStats();

        $this->app->view->assign('list', $list);
        $this->app->view->assign('params', $params);
        $this->app->view->assign('stats', $stats);

        return $this->app->view->fetch('/review_list');
    }

    /**
     * 审核通过
     */
    public function approve(int $id)
    {
        $remark = Request::post('remark', '');

        $reviewService = new ReviewService();
        if ($reviewService->approve($id, $remark)) {
            $this->recordLog('approve', '审核通过内容ID：' . $id);
            return $this->success('审核通过');
        }

        return $this->error('操作失败，内容不存在或状态不符');
    }

    /**
     * 审核驳回
     */
    public function reject(int $id)
    {
        $remark = Request::post('remark', '');

        if (empty($remark)) {
            return $this->error('请输入驳回原因');
        }

        $reviewService = new ReviewService();
        if ($reviewService->reject($id, $remark)) {
            $this->recordLog('reject', '审核驳回内容ID：' . $id);
            return $this->success('已驳回并退回草稿');
        }

        return $this->error('操作失败，内容不存在或状态不符');
    }

    /**
     * 查看审核历史
     */
    public function history(int $id)
    {
        $reviewService = new ReviewService();
        $history = $reviewService->getHistory($id);

        return $this->success('获取成功', $history);
    }
}
