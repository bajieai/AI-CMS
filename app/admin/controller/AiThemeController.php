<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\middleware\ThemePreviewMiddleware;
use app\common\model\AiThemeRecord;
use app\common\model\Config as ConfigModel;
use app\common\service\theme\AiThemeGenerateService;
use app\common\service\theme\ThemeFileService;
use app\common\service\TemplateService;
use think\facade\Cache;

/**
 * AI主题管理后台控制器 - V3.0 Phase 2
 *
 * 功能：生成/审核/发布/预览/CSS变量配置
 */
class AiThemeController extends AdminBaseController
{
    protected array $noNeedPermission = ['progress', 'preview_url'];

    /**
     * AI主题列表页（审核列表）
     */
    public function index(): string
    {
        $page = (int) $this->request->param('page', 1);
        $status = $this->request->param('status', '');
        $limit = 15;

        $query = AiThemeRecord::order('created_at', 'desc');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        // 状态选项
        $statusOptions = [
            ['value' => '', 'label' => '全部'],
            ['value' => (string) AiThemeRecord::STATUS_GENERATING, 'label' => '生成中'],
            ['value' => (string) AiThemeRecord::STATUS_PENDING_REVIEW, 'label' => '待审核'],
            ['value' => (string) AiThemeRecord::STATUS_VALIDATED, 'label' => '校验通过'],
            ['value' => (string) AiThemeRecord::STATUS_PUBLISHED, 'label' => '已发布'],
            ['value' => (string) AiThemeRecord::STATUS_REJECTED, 'label' => '已拒绝'],
            ['value' => (string) AiThemeRecord::STATUS_GENERATE_FAILED, 'label' => '生成失败'],
            ['value' => (string) AiThemeRecord::STATUS_VALIDATE_FAILED, 'label' => '校验失败'],
        ];

        $this->app->view->assign([
            'list'    => $list,
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
            'status'  => $status,
            'statusOptions' => $statusOptions,
        ]);

        return $this->app->view->fetch('ai_theme/index');
    }

    /**
     * AI主题生成页面
     */
    public function generate(): string
    {
        $service = new AiThemeGenerateService();
        $remaining = $service->getRemainingQuota();
        $dailyLimit = (int) config('ai.theme_generate.daily_limit', 50);

        $this->app->view->assign([
            'remaining'  => $remaining,
            'daily_limit'=> $dailyLimit,
            'can_generate'=> $remaining > 0,
        ]);

        return $this->app->view->fetch('ai_theme/generate');
    }

    /**
     * 提交生成请求（AJAX）
     */
    public function doGenerate(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $userId = (int) session('user_id');
        $description = trim($this->request->post('description', ''));
        $options = $this->request->post('options', []);

        if (empty($description)) {
            return $this->error('请输入主题描述');
        }

        $service = new AiThemeGenerateService();

        if (!$service->checkDailyLimit()) {
            return $this->error('今日生成次数已达上限');
        }

        try {
            $recordId = $service->createTask($userId, $description, $options);
            return $this->success('生成任务已创建', [
                'record_id' => $recordId,
            ]);
        } catch (\Throwable $e) {
            return $this->error('创建任务失败: ' . $e->getMessage());
        }
    }

    /**
     * 查询生成进度（AJAX轮询）
     */
    public function progress(): \think\Response
    {
        $id = (int) $this->request->param('id', 0);
        if ($id <= 0) {
            return $this->error('参数错误');
        }

        $record = AiThemeRecord::find($id);
        if (!$record) {
            return $this->error('记录不存在');
        }

        $data = [
            'id'       => $record->id,
            'status'   => $record->status,
            'status_text' => $record->status_text,
            'status_style'=> $record->status_style,
        ];

        if (in_array((int) $record->status, [
            AiThemeRecord::STATUS_PENDING_REVIEW,
            AiThemeRecord::STATUS_VALIDATED,
            AiThemeRecord::STATUS_PUBLISHED,
            AiThemeRecord::STATUS_REJECTED,
            AiThemeRecord::STATUS_VALIDATE_FAILED,
        ], true)) {
            $data['files_tree'] = $record->files_tree;
            $data['validate_result'] = $record->validate_result;
        }

        if ((int) $record->status === AiThemeRecord::STATUS_GENERATE_FAILED) {
            $data['error_msg'] = $record->error_msg;
        }

        return $this->success('ok', $data);
    }

    /**
     * 审核详情页
     */
    public function detail(): string
    {
        $id = (int) $this->request->param('id', 0);
        $record = AiThemeRecord::find($id);

        if (!$record) {
            return $this->app->view->fetch('common/404');
        }

        $themePath = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $record->theme_name;
        $fileService = new ThemeFileService();

        $filesTree = $record->files_tree ?: $fileService->scanFilesTree($themePath);

        $viewFile = $this->request->param('file', '');
        $fileContent = '';
        if (!empty($viewFile)) {
            try {
                $fileContent = $fileService->readThemeFile($themePath, $viewFile);
            } catch (\Throwable $e) {
                $fileContent = '文件读取失败: ' . $e->getMessage();
            }
        }

        $this->app->view->assign([
            'record'      => $record->toArray(),
            'files_tree'  => $filesTree,
            'view_file'   => $viewFile,
            'file_content'=> $fileContent,
            'validate_result'=> $record->validate_result ?: [],
        ]);

        return $this->app->view->fetch('ai_theme/detail');
    }

    /**
     * 通过审核
     */
    public function approve(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $id = (int) $this->request->post('id', 0);
        $record = AiThemeRecord::find($id);

        if (!$record) {
            return $this->error('记录不存在');
        }

        try {
            $record->transitionTo(AiThemeRecord::STATUS_VALIDATED);
            $record->save();
            return $this->success('审核通过');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 发布主题
     */
    public function publish(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $id = (int) $this->request->post('id', 0);
        $record = AiThemeRecord::find($id);

        if (!$record) {
            return $this->error('记录不存在');
        }

        if ((int) $record->status !== AiThemeRecord::STATUS_VALIDATED) {
            return $this->error('当前状态不允许发布');
        }

        try {
            $record->transitionTo(AiThemeRecord::STATUS_PUBLISHED);
            $record->save();

            $setActive = (bool) $this->request->post('set_active', false);
            if ($setActive) {
                ConfigModel::where('name', 'frontend_theme')->update(['value' => $record->theme_name]);
                TemplateService::clearCache();
            }

            return $this->success('发布成功');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 拒绝主题
     */
    public function reject(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $id = (int) $this->request->post('id', 0);
        $record = AiThemeRecord::find($id);

        if (!$record) {
            return $this->error('记录不存在');
        }

        try {
            $record->transitionTo(AiThemeRecord::STATUS_REJECTED);
            $record->save();
            return $this->success('已拒绝');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 重新生成
     */
    public function retry(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $id = (int) $this->request->post('id', 0);
        $service = new AiThemeGenerateService();

        $result = $service->retryTask($id);
        if ($result['success']) {
            return $this->success('重试成功');
        }
        return $this->error($result['message']);
    }

    /**
     * 生成预览URL
     */
    public function preview_url(): \think\Response
    {
        $id = (int) $this->request->param('id', 0);
        $record = AiThemeRecord::find($id);

        if (!$record) {
            return $this->error('记录不存在');
        }

        $themePath = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $record->theme_name;
        if (!is_dir($themePath)) {
            return $this->error('主题目录不存在');
        }

        $hash = ThemePreviewMiddleware::generateHash($record->theme_name);
        $previewUrl = url('/index/index') . '?preview=' . $hash;

        return $this->success('ok', [
            'preview_url' => $previewUrl,
            'hash' => $hash,
            'expires_in' => 86400,
        ]);
    }

    /**
     * CSS变量配置面板
     */
    public function tweak(): string
    {
        $id = (int) $this->request->param('id', 0);
        $record = AiThemeRecord::find($id);

        if (!$record) {
            return $this->app->view->fetch('common/404');
        }

        $configKey = 'theme_vars_' . $record->theme_name;
        $savedVars = [];
        try {
            $saved = ConfigModel::where('name', $configKey)->value('value');
            if ($saved) {
                $savedVars = json_decode($saved, true) ?: [];
            }
        } catch (\Throwable) {
        }

        $defaultVars = [
            '--i8j-primary'       => '#3b82f6',
            '--i8j-primary-hover' => '#2563eb',
            '--i8j-bg'            => '#ffffff',
            '--i8j-bg-secondary'  => '#f8fafc',
            '--i8j-text'          => '#1e293b',
            '--i8j-text-secondary'=> '#64748b',
            '--i8j-border'        => '#e2e8f0',
            '--i8j-radius'        => '8px',
            '--i8j-shadow'        => '0 1px 3px rgba(0,0,0,.1)',
        ];

        $currentVars = array_merge($defaultVars, $savedVars);

        $this->app->view->assign([
            'record'       => $record->toArray(),
            'current_vars' => $currentVars,
            'default_vars' => $defaultVars,
        ]);

        return $this->app->view->fetch('ai_theme/tweak');
    }

    /**
     * 保存CSS变量配置（AJAX）
     */
    public function save_tweak(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $themeName = $this->request->post('theme_name', '');
        $vars = $this->request->post('vars', []);

        if (empty($themeName)) {
            return $this->error('主题名不能为空');
        }

        try {
            $configKey = 'theme_vars_' . $themeName;
            ConfigModel::updateOrCreate(
                ['name' => $configKey],
                [
                    'value' => json_encode($vars, JSON_UNESCAPED_UNICODE),
                    'group' => 'theme',
                    'type'  => 'textarea',
                    'remark'=> $themeName . ' CSS变量覆盖值',
                ]
            );

            Cache::delete('site_configs');

            return $this->success('保存成功');
        } catch (\Throwable $e) {
            return $this->error('保存失败: ' . $e->getMessage());
        }
    }

    /**
     * 重置CSS变量配置（AJAX）
     */
    public function reset_tweak(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $themeName = $this->request->post('theme_name', '');
        if (empty($themeName)) {
            return $this->error('主题名不能为空');
        }

        try {
            $configKey = 'theme_vars_' . $themeName;
            ConfigModel::where('name', $configKey)->delete();
            Cache::delete('site_configs');
            return $this->success('已重置为默认值');
        } catch (\Throwable $e) {
            return $this->error('重置失败: ' . $e->getMessage());
        }
    }
}
