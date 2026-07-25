<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\task\TaskAssignService;
use app\common\service\task\TaskProgressService;
use app\common\service\task\TaskNotifyService;
use app\common\service\task\TaskStatsService;
use app\common\service\task\TaskTemplateService;
use think\App;

class TaskEnhanceController extends AdminBaseController
{
    protected TaskAssignService $assignSrv;
    protected TaskProgressService $progressSrv;
    protected TaskNotifyService $notifySrv;
    protected TaskStatsService $statsSrv;
    protected TaskTemplateService $tplSrv;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->assignSrv = new TaskAssignService();
        $this->progressSrv = new TaskProgressService();
        $this->notifySrv = new TaskNotifyService();
        $this->statsSrv = new TaskStatsService();
        $this->tplSrv = new TaskTemplateService();
    }

    public function index() { return $this->stats(); }
    public function assign() { $d=$this->request->post(); return json($this->assignSrv->assignTask((int)($d['task_id']??0),$d)); }
    public function reassign() { $d=$this->request->post(); return json($this->assignSrv->reassignTask((int)($d['task_id']??0),(int)($d['new_assignee_id']??0),$d['reason']??'')); }
    public function batchAssign() { $d=$this->request->post(); return json($this->assignSrv->batchAssign($d['task_ids']??[],(int)($d['assignee_id']??0))); }
    public function autoAssign() { $d=$this->request->post(); return json($this->assignSrv->autoAssign((int)($d['task_id']??0),$d['strategy']??'balanced')); }
    public function assignPage() { return $this->view('task_enhance/assign',['tasks'=>[]]); }
    public function progress(int $id) { return json(['code'=>0,'data'=>$this->progressSrv->getProgress($id)]); }
    public function updateProgress(int $id) { $d=$this->request->post(); return json($this->progressSrv->updateProgress($id,(int)($d['progress']??0),$d['note']??'')); }
    public function milestones(int $id) { return json(['code'=>0,'data'=>$this->progressSrv->getMilestones($id)]); }
    public function gantt() { return json(['code'=>0,'data'=>$this->progressSrv->getGanttData()]); }
    public function notify() { return $this->view('task_enhance/notify',['templates'=>$this->notifySrv->getNotifyTemplates()]); }
    public function notifyPage() { return $this->notify(); }
    public function checkNotify() { return json($this->notifySrv->checkAndNotify()); }
    public function stats() { return $this->view('task_enhance/stats',['overview'=>$this->statsSrv->getOverview()]); }
    public function overview() { return json(['code'=>0,'data'=>$this->statsSrv->getOverview()]); }
    public function efficiency() { return json(['code'=>0,'data'=>$this->statsSrv->getEfficiencyAnalysis()]); }
    public function bottleneck() { return json(['code'=>0,'data'=>$this->statsSrv->getBottleneckAnalysis()]); }
    public function trend() { return json(['code'=>0,'data'=>$this->statsSrv->getTrend()]); }
    public function report() { return json(['code'=>0,'data'=>$this->statsSrv->getReport($this->request->param('type','daily'))]); }
    public function templates() { return json(['code'=>0,'data'=>$this->tplSrv->getTemplates()]); }
    public function templatePage() { return $this->view('task_enhance/template',['templates'=>$this->tplSrv->getTemplates()]); }
    public function templateCreate() { return json($this->tplSrv->createTemplate($this->request->post())); }
    public function templateUpdate(int $id) { return json($this->tplSrv->updateTemplate($id,$this->request->post())); }
    public function templateDelete(int $id) { return json($this->tplSrv->deleteTemplate($id)); }
    public function createFromTemplate() { $d=$this->request->post(); return json($this->tplSrv->createTaskFromTemplate((int)($d['template_id']??0),$d['variables']??[])); }
}
