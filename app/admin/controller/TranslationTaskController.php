<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\i18n\TranslationWorkflowService;

/**
 * 翻译任务管理后台
 * V2.9.39 I18N-V2-1
 */
class TranslationTaskController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 任务列表
     */
    public function index()
    {
        $service = new TranslationWorkflowService();

        $filters = [
            'status'        => $this->request->get('status', ''),
            'priority'      => $this->request->get('priority', ''),
            'translator_id' => (int) $this->request->get('translator_id', 0),
            'reviewer_id'   => (int) $this->request->get('reviewer_id', 0),
            'target_lang'   => $this->request->get('target_lang', ''),
            'task_type'     => $this->request->get('task_type', ''),
        ];
        $page = (int) $this->request->get('page', 1);
        $pageSize = (int) $this->request->get('page_size', 20);

        $list = $service->getTaskList($filters, $page, $pageSize);
        $stats = $service->getStatistics();

        return $this->view('/translation_task_index', [
            'list'    => $list,
            'stats'   => $stats,
            'filters' => $filters,
        ]);
    }

    /**
     * 任务详情
     */
    public function detail()
    {
        $id = (int) $this->request->get('id', 0);
        if ($id <= 0) {
            return $this->error('参数错误');
        }
        $service = new TranslationWorkflowService();
        $task = $service->getTaskDetail($id);
        if (!$task) {
            return $this->error('任务不存在');
        }
        return $this->view('/translation_task_detail', ['task' => $task]);
    }

    /**
     * 创建任务
     */
    public function create()
    {
        if ($this->request->isPost()) {
            $data = [
                'source_content_id' => (int) $this->request->post('source_content_id', 0),
                'source_lang'       => $this->request->post('source_lang', 'zh-cn'),
                'target_lang'       => $this->request->post('target_lang', 'en'),
                'task_type'         => $this->request->post('task_type', 'content'),
                'priority'          => $this->request->post('priority', 'normal'),
                'deadline'          => $this->request->post('deadline', '') ?: null,
                'translator_id'     => (int) $this->request->post('translator_id', 0),
                'reviewer_id'       => (int) $this->request->post('reviewer_id', 0),
            ];
            $service = new TranslationWorkflowService();
            $id = $service->createTask($data);
            $this->recordLog('create', '创建翻译任务#' . $id);
            return $this->success('创建成功', ['id' => $id]);
        }
        return $this->view('/translation_task_create');
    }

    /**
     * 批量创建任务
     */
    public function batchCreate()
    {
        $contentId = (int) $this->request->post('content_id', 0);
        $targetLangs = (array) $this->request->post('target_langs', []);
        $sourceLang = $this->request->post('source_lang', 'zh-cn');

        if ($contentId <= 0 || empty($targetLangs)) {
            return $this->error('参数错误');
        }
        $service = new TranslationWorkflowService();
        $result = $service->batchCreateTasks($contentId, $targetLangs, $sourceLang);
        $this->recordLog('batch_create', "批量创建翻译任务: contentId={$contentId} created={$result['created']}");
        return $this->success("创建{$result['created']}个任务，跳过{$result['skipped']}个", $result);
    }

    /**
     * 执行AI翻译
     */
    public function aiTranslate()
    {
        $id = (int) $this->request->post('id', 0);
        if ($id <= 0) {
            return $this->error('参数错误');
        }
        $service = new TranslationWorkflowService();
        $result = $service->executeAiTranslation($id);
        $this->recordLog('ai_translate', 'AI翻译任务#' . $id);
        return $result['success']
            ? $this->success($result['msg'])
            : $this->error($result['msg']);
    }

    /**
     * 批量AI翻译
     */
    public function batchAiTranslate()
    {
        $limit = (int) $this->request->post('limit', 50);
        $service = new TranslationWorkflowService();
        $result = $service->batchExecuteAiTranslation($limit);
        $this->recordLog('batch_ai_translate', "批量AI翻译: total={$result['total']}");
        return $this->success("处理{$result['total']}个，成功{$result['success']}个，失败{$result['failed']}个", $result);
    }

    /**
     * 提交人工翻译
     */
    public function submitTranslation()
    {
        $id = (int) $this->request->post('id', 0);
        $translation = $this->request->post('translation', '');
        $user = $this->getCurrentUser();
        if ($id <= 0 || empty($translation)) {
            return $this->error('参数错误');
        }
        $service = new TranslationWorkflowService();
        $result = $service->submitHumanTranslation($id, $translation, (int) ($user['id'] ?? 0));
        $this->recordLog('submit', '提交翻译#' . $id);
        return $result['success']
            ? $this->success($result['msg'])
            : $this->error($result['msg']);
    }

    /**
     * 审核通过
     */
    public function approve()
    {
        $id = (int) $this->request->post('id', 0);
        $comment = $this->request->post('comment', '');
        $user = $this->getCurrentUser();
        if ($id <= 0) {
            return $this->error('参数错误');
        }
        $service = new TranslationWorkflowService();
        $result = $service->approveTask($id, (int) ($user['id'] ?? 0), $comment);
        $this->recordLog('approve', '审核通过翻译#' . $id);
        return $result['success']
            ? $this->success($result['msg'])
            : $this->error($result['msg']);
    }

    /**
     * 审核驳回
     */
    public function reject()
    {
        $id = (int) $this->request->post('id', 0);
        $comment = $this->request->post('comment', '');
        $user = $this->getCurrentUser();
        if ($id <= 0 || empty($comment)) {
            return $this->error('参数错误或缺少驳回原因');
        }
        $service = new TranslationWorkflowService();
        $result = $service->rejectTask($id, (int) ($user['id'] ?? 0), $comment);
        $this->recordLog('reject', '驳回翻译#' . $id);
        return $result['success']
            ? $this->success($result['msg'])
            : $this->error($result['msg']);
    }

    /**
     * 分配翻译人员
     */
    public function assignTranslator()
    {
        $id = (int) $this->request->post('id', 0);
        $translatorId = (int) $this->request->post('translator_id', 0);
        if ($id <= 0 || $translatorId <= 0) {
            return $this->error('参数错误');
        }
        $service = new TranslationWorkflowService();
        $result = $service->assignTranslator($id, $translatorId);
        $this->recordLog('assign_translator', "分配翻译人员 任务#{$id} -> user#{$translatorId}");
        return $result['success']
            ? $this->success($result['msg'])
            : $this->error($result['msg']);
    }

    /**
     * 分配审核人员
     */
    public function assignReviewer()
    {
        $id = (int) $this->request->post('id', 0);
        $reviewerId = (int) $this->request->post('reviewer_id', 0);
        if ($id <= 0 || $reviewerId <= 0) {
            return $this->error('参数错误');
        }
        $service = new TranslationWorkflowService();
        $result = $service->assignReviewer($id, $reviewerId);
        $this->recordLog('assign_reviewer', "分配审核人员 任务#{$id} -> user#{$reviewerId}");
        return $result['success']
            ? $this->success($result['msg'])
            : $this->error($result['msg']);
    }

    /**
     * 转交任务
     */
    public function transfer()
    {
        $id = (int) $this->request->post('id', 0);
        $newTranslatorId = (int) $this->request->post('translator_id', 0);
        $reason = $this->request->post('reason', '');
        if ($id <= 0 || $newTranslatorId <= 0) {
            return $this->error('参数错误');
        }
        $service = new TranslationWorkflowService();
        $result = $service->transferTask($id, $newTranslatorId, $reason);
        $this->recordLog('transfer', "转交任务#{$id} -> user#{$newTranslatorId}");
        return $result['success']
            ? $this->success($result['msg'])
            : $this->error($result['msg']);
    }

    /**
     * 统计数据
     */
    public function statistics()
    {
        $service = new TranslationWorkflowService();
        $stats = $service->getStatistics();
        $pending = $service->getPendingTasks();
        return $this->view('/translation_task_statistics', [
            'stats'   => $stats,
            'pending' => $pending,
        ]);
    }
}
