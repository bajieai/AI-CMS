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
use app\common\middleware\ThemePreviewMiddleware;
use app\common\model\AiThemeChatLog;
use app\common\model\AiThemeRecord;
use app\common\model\Config as ConfigModel;
use app\common\service\theme\AiThemeGenerateService;
use app\common\service\theme\BatchThemeGenerateService;
use app\common\service\theme\ThemeFileService;
use app\common\service\theme\ThemeQualityService;
use app\common\service\theme\ThemeVersionManager;
use app\common\service\TemplateService;
use think\facade\Cache;

/**
 * AI主题管理后台控制器 - V3.0 Phase 2
 *
 * 功能：生成/审核/发布/预览/CSS变量配置
 */
class AiThemeController extends AdminBaseController
{
    protected array $noNeedPermission = ['progress', 'preview_url', 'chat'];

    /** @var \app\common\service\theme\AiThemeGenerateService */
    protected AiThemeGenerateService $themeService;
    /** @var \app\common\service\theme\ThemeVersionManager */
    protected ThemeVersionManager $versionManager;

    /**
     * AI主题列表页（审核列表）
     */
    public function index()
    {
        $page = (int) $this->request->param('page', 1);
        $status = $this->request->param('status', '');
        $batchId = trim($this->request->param('batch_id', ''));
        $limit = 15;

        $query = AiThemeRecord::order('created_at', 'desc');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }
        if ($batchId !== '') {
            $query->where('batch_id', $batchId);
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
    public function generate()
    {
        $service = new AiThemeGenerateService();
        $remaining = $service->getRemainingQuota();
        $dailyLimit = (int) config('ai.theme_generate.daily_limit', 50);

        // V2.9.11: 骨架模板和行业列表
        $skeletonThemes = config('theme_styles.skeleton_themes', []);
        $industries = config('theme_styles.industries', []);
        $generateModes = config('theme_styles.generate_modes', []);

        $this->app->view->assign([
            'remaining'     => $remaining,
            'daily_limit'   => $dailyLimit,
            'can_generate'  => $remaining > 0,
            'skeleton_themes'=> $skeletonThemes,
            'industries'    => $industries,
            'generate_modes'=> $generateModes,
        ]);

        return $this->app->view->fetch('ai_theme/generate');
    }

    /**
     * V3.1-下一阶段 Sprint 14: 批量生成页面
     */
    public function batchGenerate()
    {
        $batchService = new BatchThemeGenerateService();
        $industries = $batchService->getAllIndustries();
        $dailyLimit = (int) config('ai.theme_generate.daily_limit', 50);

        // 获取最近的批次列表
        $recentBatches = AiThemeRecord::whereNotNull('batch_id')
            ->group('batch_id')
            ->order('created_at', 'desc')
            ->limit(10)
            ->column('batch_id');

        $batchProgress = [];
        foreach ($recentBatches as $bid) {
            $batchProgress[] = $batchService->getBatchProgress($bid);
        }

        $this->app->view->assign([
            'industries'     => $industries,
            'daily_limit'    => $dailyLimit,
            'batch_progress' => $batchProgress,
        ]);

        return $this->app->view->fetch('ai_theme/batch_generate');
    }

    /**
     * V3.1-下一阶段 Sprint 14: 提交批量生成请求（AJAX）
     */
    public function doBatchGenerate(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $userId = (int) session('user_id');
        $industry = trim($this->request->post('industry', ''));
        $count = (int) $this->request->post('count', 10);
        $options = $this->request->post('options', []);

        if (empty($industry)) {
            return $this->error('请选择行业类型');
        }

        $count = min(30, max(1, $count));

        $batchService = new BatchThemeGenerateService();

        try {
            $result = $batchService->createBatch($userId, $industry, $count, $options);
            if ($result['success']) {
                return $this->success($result['message'], [
                    'batch_id' => $result['batch_id'],
                    'tasks'    => $result['tasks'],
                ]);
            }
            return $this->error($result['message']);
        } catch (\Throwable $e) {
            return $this->error('创建批量任务失败: ' . $e->getMessage());
        }
    }

    /**
     * V3.1-下一阶段 Sprint 14: 批量生成进度查询（AJAX）
     */
    public function batchProgress(): \think\Response
    {
        $batchId = trim($this->request->param('batch_id', ''));
        if (empty($batchId)) {
            return $this->error('参数错误');
        }

        $batchService = new BatchThemeGenerateService();
        $progress = $batchService->getBatchProgress($batchId);

        if (!$progress['exists']) {
            return $this->error('批次不存在');
        }

        return $this->success('ok', $progress);
    }

    /**
     * V3.1-下一阶段 Sprint 14: 质量评分（AJAX）
     */
    public function qualityScore(): \think\Response
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

        try {
            $options = json_decode($record->options, true) ?: [];
            $industry = $options['industry'] ?? '';
            $qualityService = new ThemeQualityService();
            $result = $qualityService->score($themePath, $industry);

            // 缓存评分结果
            $record->quality_score = $result['total'];
            $record->quality_detail = $result;
            $record->save();

            return $this->success('评分完成', $result);
        } catch (\Throwable $e) {
            return $this->error('评分失败: ' . $e->getMessage());
        }
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

        // V2.9.11: 接收生成模式参数
        $generateMode = trim($this->request->post('generate_mode', 'full'));
        $skeletonTheme = trim($this->request->post('skeleton_theme', 'ai-base-showcase'));
        $layoutType = trim($this->request->post('layout_type', 'showcase'));
        $industryType = trim($this->request->post('industry_type', 'corporate'));

        if (empty($description)) {
            return $this->error('请输入主题描述');
        }

        // 合并选项
        $options['generate_mode'] = in_array($generateMode, ['full', 'skeleton']) ? $generateMode : 'full';
        $options['skeleton_theme'] = $skeletonTheme;
        $options['layout_type'] = $layoutType;
        $options['industry_type'] = $industryType;

        $service = new AiThemeGenerateService();

        if (!$service->checkDailyLimit()) {
            return $this->error('今日生成次数已达上限');
        }

        try {
            $recordId = $service->createTask($userId, $description, $options);
            return $this->success('生成任务已创建', [
                'record_id' => $recordId,
                'mode'      => $options['generate_mode'],
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
    public function detail()
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
            'quality_detail' => $record->quality_detail ?: [],
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
    public function tweak()
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

        // V2.9.11: 统一25个CSS变量（与FrontBaseController/getThemeCssVars完全对齐）
        $defaultVars = [
            '--primary'        => '#2563EB',
            '--primary-light'  => '#DBEAFE',
            '--primary-dark'   => '#1E40AF',
            '--secondary'      => '#64748B',
            '--accent'         => '#F59E0B',
            '--bg'             => '#FFFFFF',
            '--bg-secondary'   => '#F8FAFC',
            '--bg-section'     => '#F1F5F9',
            '--text'           => '#1E293B',
            '--text-secondary' => '#64748B',
            '--text-inverse'   => '#FFFFFF',
            '--border'         => '#E2E8F0',
            '--radius'         => '8px',
            '--radius-lg'      => '12px',
            '--radius-sm'      => '4px',
            '--shadow'         => '0 1px 3px rgba(0,0,0,0.1)',
            '--shadow-hover'   => '0 4px 12px rgba(0,0,0,0.15)',
            '--shadow-lg'      => '0 10px 25px rgba(0,0,0,0.1)',
            '--font-heading'   => "'Noto Sans SC', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
            '--font-body'      => "'Noto Sans SC', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
            '--transition'     => 'all 0.2s ease',
            '--transition-slow'=> 'all 0.3s ease',
            '--max-width'      => '1200px',
            '--sidebar-pos'    => 'left',
            '--header-style'   => 'full',
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

    // ==================== V3.0 Phase 3: AI模板增强 ====================

    /**
     * 多轮对话增量修改（AJAX，同步模式）
     */
    public function chat(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $id = (int) $this->request->post('id', 0);
        $instruction = trim($this->request->post('instruction', ''));
        $userId = (int) session('user_id');

        if ($id <= 0) {
            return $this->error('参数错误');
        }
        if (empty($instruction)) {
            return $this->error('请输入修改指令');
        }

        // 设置较长的执行时间（同步模式需要等待LLM返回）
        set_time_limit(120);

        $service = new AiThemeGenerateService();
        $result = $service->generateIncremental($id, $userId, $instruction);

        if ($result['success']) {
            return $this->success($result['message'], [
                'changed_files'   => $result['changed_files'] ?? [],
                'version'         => $result['version'] ?? 0,
                'validate_errors' => $result['validate_errors'] ?? [],
            ]);
        }
        return $this->error($result['message']);
    }

    /**
     * 单文件重生成（AJAX，同步模式）
     */
    public function regenerateFile(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $id = (int) $this->request->post('id', 0);
        $filePath = trim($this->request->post('file_path', ''));
        $instruction = trim($this->request->post('instruction', ''));
        $userId = (int) session('user_id');

        if ($id <= 0 || empty($filePath)) {
            return $this->error('参数错误');
        }
        if (empty($instruction)) {
            return $this->error('请输入修改指令');
        }

        set_time_limit(120);

        $service = new AiThemeGenerateService();
        $result = $service->regenerateFile($id, $userId, $filePath, $instruction);

        if ($result['success']) {
            return $this->success($result['message'], [
                'content'         => $result['content'] ?? '',
                'version'         => $result['version'] ?? 0,
                'validate_result' => $result['validate_result'] ?? [],
            ]);
        }
        return $this->error($result['message']);
    }

    /**
     * 版本回退（AJAX）
     */
    public function rollback(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $id = (int) $this->request->post('id', 0);
        $identifier = $this->request->post('identifier', '');

        if ($id <= 0 || empty($identifier)) {
            return $this->error('参数错误');
        }

        $record = AiThemeRecord::find($id);
        if (!$record) {
            return $this->error('记录不存在');
        }

        $versionManager = new ThemeVersionManager();
        $result = $versionManager->rollback($record->theme_name, $identifier);

        if ($result['success']) {
            // 更新文件树
            $fileService = new ThemeFileService();
            $themePath = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $record->theme_name;
            $record->files_tree = $fileService->scanFilesTree($themePath);
            $record->save();

            return $this->success('回退成功');
        }
        return $this->error($result['message']);
    }

    /**
     * 版本历史查询（AJAX）
     */
    public function versionHistory(): \think\Response
    {
        $id = (int) $this->request->param('id', 0);
        $record = AiThemeRecord::find($id);

        if (!$record) {
            return $this->error('记录不存在');
        }

        $versionManager = new ThemeVersionManager();
        $history = $versionManager->getVersionHistory($record->theme_name);

        // 合并对话日志版本信息
        $chatStats = AiThemeChatLog::getTokenStats($id);

        return $this->success('ok', [
            'versions'     => $history,
            'current_version' => $record->getCurrentVersion(),
            'chat_stats'   => $chatStats,
        ]);
    }

    /**
     * 版本差异对比（AJAX）
     */
    public function versionDiff(): \think\Response
    {
        $id = (int) $this->request->param('id', 0);
        $from = $this->request->param('from', '');
        $to = $this->request->param('to', '');

        if ($id <= 0 || empty($from) || empty($to)) {
            return $this->error('参数错误');
        }

        $record = AiThemeRecord::find($id);
        if (!$record) {
            return $this->error('记录不存在');
        }

        $versionManager = new ThemeVersionManager();
        $result = $versionManager->diff($record->theme_name, $from, $to);

        if ($result['success']) {
            return $this->success('ok', [
                'diff' => $result['diff'],
            ]);
        }
        return $this->error($result['message']);
    }

    /**
     * 主题管理页面
     */
    public function manage()
    {
        $page = (int) $this->request->param('page', 1);
        $limit = 15;

        $query = AiThemeRecord::order('created_at', 'desc');
        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        // 扫描本地主题目录（非AI生成的）
        $themeRoot = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes';
        $localThemes = [];
        if (is_dir($themeRoot)) {
            $dirs = glob($themeRoot . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $name = basename($dir);
                if (str_starts_with($name, '.')) continue;
                $localThemes[] = [
                    'name' => $name,
                    'path' => $dir,
                    'is_ai' => str_starts_with($name, 'ai-theme-'),
                    'modified' => date('Y-m-d H:i:s', filemtime($dir)),
                ];
            }
        }

        $this->app->view->assign([
            'list'        => $list,
            'local_themes'=> $localThemes,
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
        ]);

        return $this->app->view->fetch('ai_theme/manage');
    }

    /**
     * 导出主题 ZIP
     *
     * 支持两种调用方式：
     * - /admin/ai_theme/exportTheme/:id      (AI主题，通过记录ID)
     * - /admin/ai_theme/exportTheme?id=0&theme_name=xxx (本地主题，直接名称)
     */
    public function exportTheme(): \think\Response
    {
        $id = (int) $this->request->param('id', 0);
        $themeName = '';

        if ($id > 0) {
            $record = AiThemeRecord::find($id);
            if (!$record) {
                return $this->error('记录不存在');
            }
            $themeName = $record->theme_name;
        } else {
            $themeName = trim($this->request->param('theme_name', ''));
            if (empty($themeName)) {
                return $this->error('参数错误，请指定主题名称');
            }
            // 安全校验：防止路径穿越
            if (str_contains($themeName, '..') || str_contains($themeName, '/') || str_contains($themeName, '\\')) {
                return $this->error('无效的主题名称');
            }
        }

        $packageService = new \app\common\service\theme\ThemePackageService();
        $result = $packageService->exportTheme($themeName);

        if (!$result['success']) {
            return $this->error($result['message']);
        }

        $zipPath = $result['path'];

        return download($zipPath, $themeName . '.zip')->force(true);
    }

    /**
     * 导入主题 ZIP
     */
    public function importTheme(): \think\Response
    {
        $file = $this->request->file('theme_zip');
        if (!$file) {
            return $this->error('请上传ZIP文件');
        }

        $ext = strtolower(pathinfo($file->getOriginalName(), PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            return $this->error('仅支持ZIP格式');
        }

        $tempPath = $file->getPathname();
        $packageService = new \app\common\service\theme\ThemePackageService();
        $result = $packageService->importTheme($tempPath);

        if ($result['success']) {
            return $this->success($result['message'], ['theme_name' => $result['theme_name']]);
        }
        return $this->error($result['message']);
    }
}
