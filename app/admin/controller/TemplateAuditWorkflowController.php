<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\template\TemplateAuditWorkflowService;

/**
 * 模板审核工作流控制器 — V2.9.28 M-5
 */
class TemplateAuditWorkflowController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    /**
     * 待审核列表
     */
    public function index()
    {
        $stage = $this->request->get('stage', '');
        $service = new TemplateAuditWorkflowService();
        $data = $service->getPendingList($stage, (int)$this->request->get('page', 1), 20);

        $this->assign([
            'list' => $data['list'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'stage' => $stage,
            'menuActive' => 'template_audit_workflow',
        ]);

        return $this->view('/template_store/audit_list');
    }

    /**
     * 审核详情
     */
    public function detail(int $id)
    {
        $service = new TemplateAuditWorkflowService();
        $history = $service->getHistory($id);
        $template = \app\common\model\TemplateStore::find($id);
        $config = \app\common\model\TemplateAuditConfig::getForTemplate($id);

        // 获取驳回原因列表
        $rejectReasons = \app\common\model\TemplateRejectReason::getActiveReasons();

        $this->assign([
            'template' => $template,
            'history' => $history,
            'config' => $config,
            'rejectReasons' => $rejectReasons,
            'menuActive' => 'template_audit_workflow',
        ]);

        return $this->view('/template_store/audit_detail');
    }

    /**
     * 初审通过
     */
    public function firstPass(int $id)
    {
        $comment = $this->request->post('comment', '');
        $service = new TemplateAuditWorkflowService();
        $result = $service->firstReviewPass($id, $this->adminInfo['id'] ?? 0, $this->adminInfo['name'] ?? 'admin', $comment);
        if ($result['success']) {
            $this->recordLog('初审通过', "模板ID:{$id}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * 终审通过
     */
    public function finalPass(int $id)
    {
        $comment = $this->request->post('comment', '');
        $service = new TemplateAuditWorkflowService();
        $result = $service->finalReviewPass($id, $this->adminInfo['id'] ?? 0, $this->adminInfo['name'] ?? 'admin', $comment);
        if ($result['success']) {
            $this->recordLog('终审通过', "模板ID:{$id}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * 驳回
     */
    public function reject(int $id)
    {
        $reason = $this->request->post('reason', '');
        $reasonId = (int)$this->request->post('reason_id', 0);
        if (empty($reason)) return $this->error('请填写或选择驳回原因');

        $service = new TemplateAuditWorkflowService();
        $result = $service->reject($id, $this->adminInfo['id'] ?? 0, $this->adminInfo['name'] ?? 'admin', $reason, $reasonId);
        if ($result['success']) {
            $this->recordLog('驳回审核', "模板ID:{$id}, 原因:{$reason}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * 版本对比
     */
    public function diff(int $id)
    {
        $service = new TemplateAuditWorkflowService();
        $diff = $service->getVersionDiff($id);

        $this->assign([
            'templateId' => $id,
            'diff' => $diff,
            'menuActive' => 'template_audit_workflow',
        ]);

        return $this->view('/template_store/audit_diff');
    }

    /**
     * 保存审核配置
     */
    public function saveConfig(int $id)
    {
        $data = [
            'audit_level' => (int)$this->request->post('audit_level', 2),
            'first_reviewer_id' => (int)$this->request->post('first_reviewer_id', 0),
            'final_reviewer_id' => (int)$this->request->post('final_reviewer_id', 0),
            'need_file_diff' => (int)$this->request->post('need_file_diff', 1),
        ];

        $service = new TemplateAuditWorkflowService();
        $result = $service->saveAuditConfig($id, $data);
        if ($result['success']) {
            $this->recordLog('保存审核配置', "模板ID:{$id}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }
}
