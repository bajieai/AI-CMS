<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\template\TemplateReviewAdminService;

/**
 * 模板评价管理控制器 — V2.9.28 M-2
 */
class TemplateReviewAdminController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    /**
     * 评价列表
     */
    public function index()
    {
        $params = $this->request->get();
        $params['status'] = $params['status'] ?? 'all';
        $params['keyword'] = $params['keyword'] ?? '';
        $params['store_id'] = $params['store_id'] ?? '';
        $params['rating'] = $params['rating'] ?? '';
        $params['start_date'] = $params['start_date'] ?? '';
        $params['end_date'] = $params['end_date'] ?? '';

        $service = new TemplateReviewAdminService();
        $data = $service->getList($params, 20);
        $stats = $service->getStats();

        $this->assign([
            'list' => $data['list'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'params' => $params,
            'stats' => $stats,
            'menuActive' => 'template_review_admin',
        ]);

        return $this->view('/template_store/review_list');
    }

    /**
     * 回复评价页面
     */
    public function reply(int $id)
    {
        $review = \app\common\model\TemplateReview::with(['store', 'member'])->find($id);
        if (!$review) return $this->error('评价不存在');

        $this->assign(['review' => $review, 'menuActive' => 'template_review_admin']);
        return $this->view('/template_store/review_reply');
    }

    /**
     * 保存回复
     */
    public function saveReply(int $id)
    {
        $reply = $this->request->post('reply', '');
        if (empty($reply)) return $this->error('请输入回复内容');

        $service = new TemplateReviewAdminService();
        $result = $service->reply($id, $reply);
        if ($result['success']) {
            $this->recordLog('回复评价', "评价ID:{$id}");
            return $this->success($result['message'], ['redirect' => '/admin/template_review_admin/index']);
        }
        return $this->error($result['message']);
    }

    /**
     * 审核评价
     */
    public function audit(int $id)
    {
        $status = (int)$this->request->post('status', 1);
        $service = new TemplateReviewAdminService();
        $result = $service->audit($id, $status);
        if ($result['success']) {
            $this->recordLog('审核评价', "评价ID:{$id}, 状态:{$status}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * 举报列表
     */
    public function reports()
    {
        $list = \app\common\model\TemplateReviewReport::with(['review.store'])
            ->order('id', 'desc')
            ->paginate(20, false, ['page' => $this->request->get('page', 1)]);

        $this->assign([
            'list' => $list->items(),
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'limit' => $list->listRows(),
            'menuActive' => 'template_review_admin',
        ]);

        return $this->view('/template_store/review_reports');
    }

    /**
     * 处理举报
     */
    public function handleReport(int $id)
    {
        $status = (int)$this->request->post('status', 0);
        $remark = $this->request->post('admin_remark', '');

        $service = new TemplateReviewAdminService();
        $result = $service->handleReport($id, $status, $remark);
        if ($result['success']) {
            $this->recordLog('处理评价举报', "举报ID:{$id}, 状态:{$status}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * 评价统计
     */
    public function stats()
    {
        $service = new TemplateReviewAdminService();
        $stats = $service->getStats();

        $this->assign(['stats' => $stats, 'menuActive' => 'template_review_admin']);
        return $this->view('/template_store/review_stats');
    }
}
