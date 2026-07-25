<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\ReviewWorkflow;
use app\common\model\ReviewRecord;
use app\common\model\ReviewLog;
use app\common\service\WorkflowService;

/**
 * 内容工作流审批后台控制器 - V2.6
 */
class WorkflowController extends AdminBaseController
{
    /**
     * 工作流列表
     */
    public function index()
    {
        $this->app->view->assign('menuActive', 'workflow');
        $list = ReviewWorkflow::order('id', 'desc')->select();
        $this->assign('list', $list);
        return $this->view('/workflow_index');
    }

    /**
     * 添加/编辑工作流
     */
    public function edit(int $id = 0)
    {
        $workflow = $id ? ReviewWorkflow::find($id) : null;
        $this->assign('workflow', $workflow);
        return $this->view('/workflow_edit');
    }

    /**
     * 保存工作流
     */
    public function save()
    {
        $data = [
            'id' => (int) $this->request->post('id', 0),
            'name' => $this->request->post('name', ''),
            'module' => $this->request->post('module', 'content'),
            'steps' => $this->request->post('steps/a', []),
            'is_default' => (int) $this->request->post('is_default', 0),
            'is_enabled' => (int) $this->request->post('is_enabled', 1),
        ];

        if (empty($data['name'])) {
            return json(['code' => 1, 'msg' => '流程名称不能为空']);
        }

        try {
            if (!empty($data['id'])) {
                $workflow = ReviewWorkflow::find($data['id']);
                if (!$workflow) throw new \Exception('工作流不存在');
            } else {
                $workflow = new ReviewWorkflow();
                $workflow->create_time = time();
            }

            if ($data['is_default']) {
                ReviewWorkflow::where('module', $data['module'])->update(['is_default' => 0]);
            }

            $workflow->name = $data['name'];
            $workflow->module = $data['module'];
            $workflow->steps = $data['steps'];
            $workflow->is_default = $data['is_default'];
            $workflow->is_enabled = $data['is_enabled'];
            $workflow->update_time = time();
            $workflow->save();

            return json(['code' => 0, 'msg' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 删除工作流
     */
    public function delete()
    {
        $id = (int) $this->request->post('id', 0);
        $workflow = ReviewWorkflow::find($id);
        if (!$workflow) return json(['code' => 1, 'msg' => '工作流不存在']);

        // 检查是否有关联的审核记录
        $count = ReviewRecord::where('workflow_id', $id)->count();
        if ($count > 0) {
            return json(['code' => 1, 'msg' => "该工作流下有{$count}条审核记录，不能删除"]);
        }

        $workflow->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    /**
     * 审核记录列表（V2.9.9增强：审批历史时间线+驳回引导）
     */
    public function records()
    {
        $this->app->view->assign('menuActive', 'workflow_records');
        $status = (int) $this->request->get('status', -1);
        $query = ReviewRecord::order('id', 'desc');
        if ($status >= 0) {
            $query->where('status', $status);
        }
        $list = $query->paginate(20);

        // V2.9.9: 加载每条记录的审批日志
        $recordIds = $list->column('id');
        $logs = [];
        if (!empty($recordIds)) {
            $logList = ReviewLog::whereIn('record_id', $recordIds)
                ->order('create_time', 'asc')
                ->select();
            foreach ($logList as $log) {
                $logs[$log->record_id][] = $log;
            }
        }

        // 待审核数量角标
        $pendingCount = ReviewRecord::whereIn('status', [0, 1])->count();

        $this->assign([
            'list' => $list,
            'logs' => $logs,
            'pending_count' => $pendingCount,
        ]);
        return $this->view('/workflow_records');
    }

    /**
     * 审核操作
     */
    public function review()
    {
        $recordId = (int) $this->request->post('record_id', 0);
        $action = $this->request->post('action', '');
        $comment = $this->request->post('comment', '');

        if (!in_array($action, ['pass', 'reject', 'withdraw'])) {
            return json(['code' => 1, 'msg' => '无效的操作']);
        }

        $result = WorkflowService::review($recordId, (int) session('user_id'), $action, $comment);
        if ($result['success']) {
            $this->recordLog('审核操作', "record:{$recordId}, action:{$action}");
        }
        return json($result['success'] ? ['code' => 0, 'msg' => $result['msg']] : ['code' => 1, 'msg' => $result['msg']]);
    }
}
