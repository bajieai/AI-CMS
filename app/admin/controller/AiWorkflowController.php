<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ai\AiWorkflowService;
use think\facade\Json;

/**
 * AI工作流控制器
 * V2.9.38 AI-PLUS-1
 */
class AiWorkflowController extends AdminBaseController
{
    protected AiWorkflowService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new AiWorkflowService();
    }

    public function index()
    {
        $params = $this->request->param();
        $result = $this->service->listWorkflows($params);
        return $this->view('ai_workflow/index', $result);
    }

    public function create()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['creator_id'] = $this->adminId ?? 0;
            $id = $this->service->createWorkflow($data);
            return Json::success('创建成功', ['id' => $id]);
        }
        $templates = $this->service->getTemplates();
        return $this->view('ai_workflow/create', ['templates' => $templates]);
    }

    public function edit()
    {
        $id = (int) $this->request->param('id', 0);
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $this->service->updateWorkflow($id, $data);
            return Json::success('更新成功');
        }
        $workflow = $this->service->getWorkflow($id);
        return $this->view('ai_workflow/edit', ['workflow' => $workflow]);
    }

    public function delete()
    {
        $id = (int) $this->request->param('id', 0);
        $this->service->deleteWorkflow($id);
        return Json::success('删除成功');
    }

    public function templates()
    {
        $templates = $this->service->getTemplates();
        return $this->view('ai_workflow/templates', ['templates' => $templates]);
    }

    public function useTemplate()
    {
        $templateId = (int) $this->request->param('template_id', 0);
        $id = $this->service->createFromTemplate($templateId, ['creator_id' => $this->adminId ?? 0]);
        return Json::success('已从模板创建工作流', ['id' => $id]);
    }

    public function execute()
    {
        $workflowId = (int) $this->request->param('workflow_id', 0);
        $targetIds = $this->request->param('target_ids', []);
        $execId = $this->service->execute($workflowId, $targetIds, 'manual', $this->adminId ?? 0);
        return Json::success('工作流已启动', ['exec_id' => $execId]);
    }

    public function cancelExec()
    {
        $execId = (int) $this->request->param('exec_id', 0);
        $this->service->cancelExecution($execId);
        return Json::success('已取消执行');
    }

    public function retryNode()
    {
        $execId = (int) $this->request->param('exec_id', 0);
        $nodeId = $this->request->param('node_id', '');
        $this->service->retryNode($execId, $nodeId);
        return Json::success('节点已重试');
    }

    public function logs()
    {
        $workflowId = (int) $this->request->param('workflow_id', 0);
        $page = (int) $this->request->param('page', 1);
        $result = $this->service->getExecLogs($workflowId, $page);
        return $this->view('ai_workflow/logs', $result);
    }

    public function stats()
    {
        $workflowId = (int) $this->request->param('workflow_id', 0);
        $stats = $this->service->getStats($workflowId);
        $ranking = $this->service->getNodeDurationRanking($workflowId);
        return Json::success('ok', ['stats' => $stats, 'node_ranking' => $ranking]);
    }

    public function export()
    {
        $id = (int) $this->request->param('id', 0);
        $json = $this->service->exportWorkflow($id);
        return download($json, 'workflow_' . $id . '.json');
    }

    public function import()
    {
        $file = $this->request->file('file');
        if (!$file) return Json::fail('请上传文件');
        $json = file_get_contents($file->getRealPath());
        $id = $this->service->importWorkflow($json, $this->adminId ?? 0);
        return Json::success('导入成功', ['id' => $id]);
    }
}
