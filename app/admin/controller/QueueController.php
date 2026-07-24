<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\perf\QueueService;
use app\common\service\perf\QueueWorkerManager;
use think\facade\Json;

/**
 * 队列监控控制器
 * V2.9.38 PERF-II-2
 */
class QueueController extends AdminBaseController
{
    protected QueueService $service;
    protected QueueWorkerManager $workerManager;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new QueueService();
        $this->workerManager = new QueueWorkerManager();
    }

    public function index()
    {
        try {
            $stats = $this->service->getQueueStats();
            $workers = $this->workerManager->getWorkerStatus();
        } catch (\Throwable $e) {
            $stats = ['pending' => 0, 'running' => 0, 'completed' => 0, 'failed' => 0, 'total' => 0, 'by_queue' => []];
            $workers = [];
        }
        return $this->view('queue/index', ['stats' => $stats, 'workers' => $workers]);
    }

    public function failed()
    {
        $page = (int) $this->request->param('page', 1);
        $result = $this->service->getFailedJobs($page);
        return $this->view('queue/failed', $result);
    }

    public function retry()
    {
        $jobId = (int) $this->request->param('job_id', 0);
        $this->service->retryFailed($jobId);
        return Json::success('已重试');
    }

    public function cancel()
    {
        $jobId = (int) $this->request->param('job_id', 0);
        $this->service->cancelJob($jobId);
        return Json::success('已取消');
    }

    public function clear()
    {
        $queue = $this->request->param('queue', 'default');
        $this->service->clearQueue($queue);
        return Json::success('队列已清空');
    }

    public function startWorker()
    {
        $queue = $this->request->param('queue', 'default');
        $workers = (int) $this->request->param('workers', 1);
        $result = $this->workerManager->startWorker($queue, $workers);
        return Json::success('工作进程已启动', $result);
    }

    public function stopWorker()
    {
        $workerId = $this->request->param('worker_id', '');
        $this->workerManager->stopWorker($workerId);
        return Json::success('工作进程已停止');
    }
}
