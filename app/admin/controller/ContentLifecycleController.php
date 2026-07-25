<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\operation\ContentLifecycleService;

/**
 * 内容生命周期控制器 — V2.9.34 OPS2-3
 */
class ContentLifecycleController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $service = new ContentLifecycleService();
        $stats = $service->getStats();
        $this->assign('stats', $stats);
        $this->assign('menuActive', 'content_lifecycle');
        return $this->view('/content_lifecycle/index');
    }

    public function transition()
    {
        $contentId = (int)$this->request->post('content_id', 0);
        $toStatus = (string)$this->request->post('to_status', '');
        $service = new ContentLifecycleService();
        $result = $service->transition($contentId, $toStatus);
        if ($result['success'] ?? false) {
            return $this->success('状态变更成功', $result);
        }
        return $this->error($result['message'] ?? '状态变更失败');
    }

    public function batchTransition()
    {
        $ids = $this->request->post('ids', []);
        $toStatus = (string)$this->request->post('to_status', '');
        $service = new ContentLifecycleService();
        $result = $service->batchTransition($ids, $toStatus);
        if ($result['success'] ?? false) {
            return $this->success('批量变更成功', $result);
        }
        return $this->error($result['message'] ?? '批量变更失败');
    }

    public function archive()
    {
        $contentId = (int)$this->request->post('content_id', 0);
        $reason = (string)$this->request->post('reason', '');
        $service = new ContentLifecycleService();
        $result = $service->archive($contentId, $reason);
        if ($result['success'] ?? false) {
            return $this->success('归档成功', $result);
        }
        return $this->error($result['message'] ?? '归档失败');
    }

    public function restore()
    {
        $contentId = (int)$this->request->param('id', 0);
        $service = new ContentLifecycleService();
        $result = $service->restore($contentId);
        if ($result['success'] ?? false) {
            return $this->success('恢复成功', $result);
        }
        return $this->error($result['message'] ?? '恢复失败');
    }

    public function emptyTrash()
    {
        $service = new ContentLifecycleService();
        $result = $service->emptyTrash();
        if ($result['success'] ?? false) {
            return $this->success('清空成功', $result);
        }
        return $this->error($result['message'] ?? '清空失败');
    }
}
