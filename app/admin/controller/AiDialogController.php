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
use app\common\service\ai\AiDialogService;
use app\common\service\ai\AiDialogContextService;

/**
 * AI对话管理后台控制器 — V2.9.39 AI-DEEP-1
 *
 * 功能：
 *   - 对话列表页面
 *   - 对话详情页面
 *   - 发送消息（AJAX）
 *   - 创建/删除/归档对话
 *   - 对话搜索
 *   - 对话导出/导入
 */
class AiDialogController extends AdminBaseController
{
    private AiDialogService $dialogService;
    private AiDialogContextService $contextService;

    public function __construct()
    {
        parent::__construct(app());
        $this->dialogService = new AiDialogService();
        $this->contextService = new AiDialogContextService();
    }

    /**
     * 对话列表页面
     */
    public function index()
    {
        $page = (int) $this->request->param('page', 1);
        $limit = (int) $this->request->param('limit', 20);
        $status = (int) $this->request->param('status', -1);

        $userId = (int) session('user_id');
        $result = $this->dialogService->listDialogs($userId, $page, $limit, $status);

        if ($this->isRealAjax()) {
            return $this->success('ok', $result);
        }

        $this->assign('list', $result['list']);
        $this->assign('total', $result['total']);
        $this->assign('page', $result['page']);
        $this->assign('limit', $result['limit']);
        return $this->view('/ai_dialog/index');
    }

    /**
     * 对话详情页面
     */
    public function view()
    {
        $dialogId = (int) $this->request->param('id', 0);
        if ($dialogId <= 0) {
            return $this->error('参数错误');
        }

        $dialog = $this->dialogService->getDialog($dialogId);
        if (!$dialog) {
            return $this->error('对话不存在');
        }

        $messages = $this->dialogService->getMessages($dialogId, 1, 200);
        $contextStats = $this->contextService->getContextStats($dialogId);

        $this->assign('dialog', $dialog);
        $this->assign('messages', $messages['list']);
        $this->assign('contextStats', $contextStats);
        return $this->view('/ai_dialog/view');
    }

    /**
     * 创建新对话
     */
    public function create()
    {
        if ($this->request->isPost()) {
            $title = $this->request->post('title', '');
            $model = $this->request->post('model', '');
            $userId = (int) session('user_id');

            $dialogId = $this->dialogService->createDialog($userId, $title, $model);

            $this->recordLog('create_dialog', "创建AI对话 #{$dialogId}");

            return $this->success('对话创建成功', ['dialog_id' => $dialogId]);
        }

        return $this->view('/ai_dialog/create');
    }

    /**
     * 发送消息（AJAX）
     */
    public function send()
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方法不允许');
        }

        $dialogId = (int) $this->request->post('dialog_id', 0);
        $message = $this->request->post('message', '');
        $systemPrompt = $this->request->post('system_prompt', '');
        $temperature = (float) $this->request->post('temperature', 0.7);
        $maxTokens = (int) $this->request->post('max_tokens', 2000);

        if ($dialogId <= 0 || empty($message)) {
            return $this->error('参数错误');
        }

        $userId = (int) session('user_id');

        $options = [
            'temperature' => $temperature,
            'max_tokens'  => $maxTokens,
        ];
        if (!empty($systemPrompt)) {
            $options['system_prompt'] = $systemPrompt;
        }

        $result = $this->dialogService->sendMessage($dialogId, $userId, $message, $options);

        if ($result['success']) {
            return $this->success('ok', [
                'reply'     => $result['reply'],
                'dialog_id' => $result['dialog_id'],
            ]);
        }

        return $this->error($result['message'] ?? '发送失败');
    }

    /**
     * 搜索对话
     */
    public function search()
    {
        $keyword = $this->request->param('keyword', '');
        $page = (int) $this->request->param('page', 1);
        $limit = (int) $this->request->param('limit', 20);

        if (empty($keyword)) {
            return $this->error('请输入搜索关键词');
        }

        $userId = (int) session('user_id');
        $result = $this->dialogService->searchDialogs($userId, $keyword, $page, $limit);

        if ($this->isRealAjax()) {
            return $this->success('ok', $result);
        }

        $this->assign('list', $result['list']);
        $this->assign('total', $result['total']);
        $this->assign('keyword', $keyword);
        return $this->view('/ai_dialog/search');
    }

    /**
     * 删除对话
     */
    public function delete()
    {
        $dialogId = (int) $this->request->post('id', 0);
        if ($dialogId <= 0) {
            return $this->error('参数错误');
        }

        $result = $this->dialogService->deleteDialog($dialogId);

        if ($result) {
            $this->recordLog('delete_dialog', "删除AI对话 #{$dialogId}");
            return $this->success('删除成功');
        }

        return $this->error('删除失败');
    }

    /**
     * 归档对话
     */
    public function archive()
    {
        $dialogId = (int) $this->request->post('id', 0);
        if ($dialogId <= 0) {
            return $this->error('参数错误');
        }

        $result = $this->dialogService->archiveDialog($dialogId);

        if ($result) {
            $this->recordLog('archive_dialog', "归档AI对话 #{$dialogId}");
            return $this->success('归档成功');
        }

        return $this->error('归档失败');
    }

    /**
     * 更新对话信息
     */
    public function update()
    {
        $dialogId = (int) $this->request->post('id', 0);
        if ($dialogId <= 0) {
            return $this->error('参数错误');
        }

        $title = $this->request->post('title', '');
        $model = $this->request->post('model', '');
        $status = (int) $this->request->post('status', -1);

        $data = [];
        if (!empty($title)) {
            $data['title'] = $title;
        }
        if (!empty($model)) {
            $data['model'] = $model;
        }
        if ($status >= 0) {
            $data['status'] = $status;
        }

        if (empty($data)) {
            return $this->error('无更新数据');
        }

        $result = $this->dialogService->updateDialog($dialogId, $data);

        if ($result) {
            $this->recordLog('update_dialog', "更新AI对话 #{$dialogId}");
            return $this->success('更新成功');
        }

        return $this->error('更新失败');
    }

    /**
     * 导出对话
     */
    public function export()
    {
        $dialogId = (int) $this->request->param('id', 0);
        $format = $this->request->param('format', 'markdown');

        if ($dialogId <= 0) {
            return $this->error('参数错误');
        }

        $content = $this->dialogService->exportDialog($dialogId, $format);

        $this->recordLog('export_dialog', "导出AI对话 #{$dialogId} ({$format})");

        $filename = 'dialog_' . $dialogId . '_' . date('YmdHis');
        $filename .= ($format === 'json') ? '.json' : '.md';

        return download($content, $filename);
    }

    /**
     * 导入对话
     */
    public function import()
    {
        if (!$this->request->isPost()) {
            return $this->view('/ai_dialog/import');
        }

        $file = $this->request->file('file');
        if (!$file) {
            return $this->error('请上传文件');
        }

        $json = file_get_contents($file->getRealPath());
        $userId = (int) session('user_id');

        try {
            $dialogId = $this->dialogService->importDialog($userId, $json);

            $this->recordLog('import_dialog', "导入AI对话 #{$dialogId}");

            return $this->success('导入成功', ['dialog_id' => $dialogId]);
        } catch (\Throwable $e) {
            return $this->error('导入失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取对话消息列表（AJAX）
     */
    public function messages()
    {
        $dialogId = (int) $this->request->param('dialog_id', 0);
        $page = (int) $this->request->param('page', 1);
        $limit = (int) $this->request->param('limit', 50);

        if ($dialogId <= 0) {
            return $this->error('参数错误');
        }

        $result = $this->dialogService->getMessages($dialogId, $page, $limit);

        return $this->success('ok', $result);
    }

    /**
     * 压缩对话上下文
     */
    public function compress()
    {
        $dialogId = (int) $this->request->post('dialog_id', 0);
        if ($dialogId <= 0) {
            return $this->error('参数错误');
        }

        $result = $this->contextService->compressContext($dialogId);

        if ($result['success']) {
            $this->recordLog('compress_dialog', "压缩AI对话上下文 #{$dialogId}");
            return $this->success('压缩成功', $result);
        }

        return $this->error($result['message'] ?? '压缩失败');
    }

    /**
     * 获取上下文统计信息
     */
    public function contextStats()
    {
        $dialogId = (int) $this->request->param('dialog_id', 0);
        if ($dialogId <= 0) {
            return $this->error('参数错误');
        }

        $stats = $this->contextService->getContextStats($dialogId);

        return $this->success('ok', $stats);
    }
}
