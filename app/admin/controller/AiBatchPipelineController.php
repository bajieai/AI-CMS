<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ai\AiBatchPipelineService;
use think\facade\Json;

/**
 * AI批量生产控制器
 * V2.9.38 AI-PLUS-2
 */
class AiBatchPipelineController extends AdminBaseController
{
    protected AiBatchPipelineService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new AiBatchPipelineService();
    }

    public function index()
    {
        $list = $this->service->getTaskList(15);
        $this->view->assign('list', $list);
        return $this->view('ai_batch/index');
    }

    public function create()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post();
            $taskId = $this->service->createBatchTask($params);
            return Json::success('批量任务已创建', ['task_id' => $taskId]);
        }
        return $this->view('ai_batch/create');
    }

    public function start()
    {
        $taskId = (int) $this->request->param('task_id', 0);
        $this->service->start($taskId);
        return Json::success('任务已启动');
    }

    public function pause()
    {
        $taskId = (int) $this->request->param('task_id', 0);
        $this->service->pause($taskId);
        return Json::success('任务已暂停');
    }

    public function resume()
    {
        $taskId = (int) $this->request->param('task_id', 0);
        $this->service->resume($taskId);
        return Json::success('任务已恢复');
    }

    public function cancel()
    {
        $taskId = (int) $this->request->param('task_id', 0);
        $this->service->cancel($taskId);
        return Json::success('任务已取消');
    }

    public function progress()
    {
        $taskId = (int) $this->request->param('task_id', 0);
        $progress = $this->service->getProgress($taskId);
        return Json::success('ok', $progress);
    }

    public function results()
    {
        $taskId = (int) $this->request->param('task_id', 0);
        $page = (int) $this->request->param('page', 1);
        $result = $this->service->getResults($taskId, $page);
        return $this->view('ai_batch/results', $result);
    }

    public function importCsv()
    {
        $taskId = (int) $this->request->param('task_id', 0);
        $file = $this->request->file('file');
        if (!$file) return Json::fail('请上传CSV文件');
        $result = $this->service->importFromCsv($file->getRealPath(), $taskId);
        return Json::success('导入成功', $result);
    }
}
