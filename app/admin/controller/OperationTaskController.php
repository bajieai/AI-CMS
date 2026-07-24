<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\operation\OperationTaskService;

/**
 * 运营任务系统控制器 — V2.9.34 OPS2-4
 */
class OperationTaskController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $filters = $this->request->param();
        $service = new OperationTaskService();
        $list = $service->getList($filters);
        $stats = $service->getStats();
        $this->assign('list', $list);
        $this->assign('stats', $stats);
        $this->assign('menuActive', 'operation_task');
        return $this->view('/operation_task/index');
    }

    public function save()
    {
        $data = $this->request->post();
        $service = new OperationTaskService();
        $result = $service->create($data);
        if ($result['success'] ?? false) {
            return $this->success('创建成功', $result);
        }
        return $this->error($result['message'] ?? '创建失败');
    }

    public function assign()
    {
        $taskId = (int)$this->request->post('task_id', 0);
        $memberId = (int)$this->request->post('member_id', 0);
        $service = new OperationTaskService();
        $result = $service->assign($taskId, $memberId);
        if ($result['success'] ?? false) {
            return $this->success('分配成功', $result);
        }
        return $this->error($result['message'] ?? '分配失败');
    }

    public function updateStatus()
    {
        $taskId = (int)$this->request->post('task_id', 0);
        $status = (string)$this->request->post('status', '');
        $service = new OperationTaskService();
        $result = $service->updateStatus($taskId, $status);
        if ($result['success'] ?? false) {
            return $this->success('状态更新成功', $result);
        }
        return $this->error($result['message'] ?? '状态更新失败');
    }
}
