<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\template\TemplateBackupRestoreService;
use app\common\service\template\TemplateCustomizeService;
use think\Response;

/**
 * 模板自定义控制器 - V2.9.12
 *
 * 网站主角色：样式定制、布局配置、备份还原
 */
class TemplateCustomizeController extends AdminBaseController
{
    /**
     * 自定义编辑页（左侧配置面板+右侧实时预览）
     */
    public function index(string $slug = ''): string
    {
        if (empty($slug)) {
            $slug = $this->request->get('slug', '');
        }
        if (empty($slug)) {
            return $this->error('缺少模板标识');
        }

        $memberId = (int) session('user_id');
        $service = new TemplateCustomizeService();

        $styleConfig = $service->getStyleConfig($memberId, $slug);
        $layoutConfig = $service->getLayoutConfig($memberId, $slug);
        $fonts = $service->getAvailableFonts();
        $layoutSchema = $service->getLayoutSchema();

        $this->assign([
            'slug'          => $slug,
            'style_config'  => $styleConfig,
            'layout_config' => $layoutConfig,
            'fonts'         => $fonts,
            'layout_schema' => $layoutSchema,
        ]);

        return $this->view('/template_customize/index');
    }

    /**
     * 保存样式配置（AJAX）
     */
    public function save(): Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $memberId = (int) session('user_id');
        $slug = $this->request->post('slug', '');
        $data = $this->request->post('style', []);

        if (empty($slug)) {
            return $this->error('缺少模板标识');
        }

        $service = new TemplateCustomizeService();

        // 保存前自动备份
        $service->preEditBackup($memberId, $slug);

        $service->saveStyleConfig($memberId, $slug, $data);

        return $this->success('保存成功');
    }

    /**
     * 保存布局配置（AJAX）
     */
    public function saveLayout(): Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $memberId = (int) session('user_id');
        $slug = $this->request->post('slug', '');
        $sections = $this->request->post('sections', []);

        if (empty($slug)) {
            return $this->error('缺少模板标识');
        }

        $service = new TemplateCustomizeService();
        $service->saveLayoutConfig($memberId, $slug, $sections);

        return $this->success('布局保存成功');
    }

    /**
     * 实时预览CSS（AJAX）
     */
    public function livePreview(): Response
    {
        $memberId = (int) session('user_id');
        $slug = $this->request->get('slug', '');

        if (empty($slug)) {
            return $this->error('缺少模板标识');
        }

        $service = new TemplateCustomizeService();
        $css = $service->applyStyle($memberId, $slug);

        return json(['success' => true, 'css' => $css]);
    }

    /**
     * Logo上传
     */
    public function uploadLogo(): Response
    {
        $file = $this->request->file('logo');
        if (empty($file)) {
            return $this->error('未上传文件');
        }

        $validate = validate(['logo' => 'fileSize:2097152|fileExt:jpg,png,gif,webp']);
        if (!$validate->check(['logo' => $file])) {
            return $this->error($validate->getError());
        }

        $saveName = \think\facade\Filesystem::disk('public')->putFile('theme_logo', $file);
        // V2.9.12: 使用think\Filesystem上传Logo
        $url = '/storage/' . str_replace('\\', '/', $saveName);

        return $this->success('上传成功', ['url' => $url]);
    }

    /**
     * 备份列表
     */
    public function backupList(string $slug = ''): Response
    {
        if (empty($slug)) {
            $slug = $this->request->get('slug', '');
        }
        $memberId = (int) session('user_id');

        $service = new TemplateBackupRestoreService();
        $list = $service->listBackups($memberId, $slug);

        return json(['success' => true, 'data' => $list]);
    }

    /**
     * 创建备份（AJAX）
     */
    public function createBackup(): Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $memberId = (int) session('user_id');
        $slug = $this->request->post('slug', '');
        $name = $this->request->post('name', '');

        if (empty($slug)) {
            return $this->error('缺少模板标识');
        }

        $service = new TemplateBackupRestoreService();
        $result = $service->createBackup($memberId, $slug, $name);

        if ($result['success']) {
            return $this->success($result['message'], $result['data']);
        }
        return $this->error($result['message']);
    }

    /**
     * 还原备份（AJAX）
     */
    public function restore(): Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $memberId = (int) session('user_id');
        $backupId = (int) $this->request->post('backup_id', 0);

        if ($backupId <= 0) {
            return $this->error('缺少备份ID');
        }

        $service = new TemplateBackupRestoreService();
        $result = $service->restoreBackup($backupId, $memberId);

        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * 删除备份（AJAX）
     */
    public function deleteBackup(): Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $memberId = (int) session('user_id');
        $backupId = (int) $this->request->post('backup_id', 0);

        $service = new TemplateBackupRestoreService();
        if ($service->deleteBackup($backupId, $memberId)) {
            return $this->success('删除成功');
        }
        return $this->error('删除失败');
    }

    /**
     * 重置为官方默认（AJAX）
     */
    public function reset(): Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $memberId = (int) session('user_id');
        $slug = $this->request->post('slug', '');

        if (empty($slug)) {
            return $this->error('缺少模板标识');
        }

        $service = new TemplateBackupRestoreService();
        $result = $service->resetToDefault($memberId, $slug);

        return $this->success($result['message']);
    }
}
