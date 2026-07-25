<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\content\ContentAuditLogService;

/**
 * 内容审计日志管理控制器 - V2.9.29 Sprint I-6
 */
class ContentAuditLogController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $contentId = (int) $this->request->get('content_id', 0);
        $operation = $this->request->get('operation', '');

        $service = new ContentAuditLogService();
        $list = $service->getList($contentId, $operation);

        $this->assign('list', $list);
        $this->assign('content_id', $contentId);
        $this->assign('operation', $operation);
        return $this->view('/content_audit_logs');
    }

    public function rollback(int $id = 0)
    {
        $service = new ContentAuditLogService();
        $result = $service->rollback($id);
        return $result ? $this->success('回滚成功') : $this->error('回滚失败');
    }
}
