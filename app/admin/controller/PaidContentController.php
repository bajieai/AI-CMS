<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\member\PaidContentService;

/**
 * 付费内容管理控制器 — V2.9.34 MEM-3/MEM-5
 */
class PaidContentController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $service = new PaidContentService();
        $stats = $service->getStats();
        $this->assign('stats', $stats);
        $this->assign('menuActive', 'paid_content');
        return $this->view('/paid_content/index');
    }

    public function save()
    {
        $data = $this->request->post();
        $contentId = (int)($data['content_id'] ?? 0);
        $service = new PaidContentService();
        $result = $service->setPaid($contentId, $data);
        if ($result['success'] ?? false) {
            return $this->success('保存成功', $result);
        }
        return $this->error($result['message'] ?? '保存失败');
    }

    public function preview()
    {
        $contentId = (int)$this->request->param('content_id', 0);
        $service = new PaidContentService();
        $result = $service->getPreviewContent($contentId);
        return json($result);
    }

    public function checkPurchased()
    {
        $memberId = (int)$this->request->param('member_id', 0);
        $contentId = (int)$this->request->param('content_id', 0);
        $service = new PaidContentService();
        $purchased = $service->checkPurchased($memberId, $contentId);
        return json(['purchased' => $purchased]);
    }
}
