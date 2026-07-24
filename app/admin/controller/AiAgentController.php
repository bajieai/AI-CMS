<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ai\AiAgentService;
use think\facade\Json;

/**
 * AI智能体控制器
 * V2.9.38 AI-PLUS-3
 */
class AiAgentController extends AdminBaseController
{
    protected AiAgentService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new AiAgentService();
    }

    public function index()
    {
        $result = $this->service->listAgents($this->request->param());
        return $this->view('ai_agent/index', $result);
    }

    public function create()
    {
        if ($this->request->isPost()) {
            $config = $this->request->post();
            $id = $this->service->createAgent($config);
            return Json::success('智能体已创建', ['id' => $id]);
        }
        return $this->view('ai_agent/create');
    }

    public function edit()
    {
        $id = $this->request->param('id', '');
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $this->service->updateAgent($id, $data);
            return Json::success('更新成功');
        }
        $agent = $this->service->getAgent($id);
        return $this->view('ai_agent/edit', ['agent' => $agent]);
    }

    public function delete()
    {
        $id = $this->request->param('id', '');
        $this->service->deleteAgent($id);
        return Json::success('删除成功');
    }

    public function run()
    {
        $id = $this->request->param('id', '');
        $task = $this->request->param('task', '');
        $context = $this->request->param('context', []);
        $taskId = $this->service->run($id, $task, $context);
        return Json::success('智能体已启动', ['task_id' => $taskId]);
    }

    public function monitor()
    {
        $agentId = $this->request->param('id', '');
        $data = $this->service->getMonitorData($agentId);
        return $this->view('ai_agent/monitor', ['monitors' => $data]);
    }
}
