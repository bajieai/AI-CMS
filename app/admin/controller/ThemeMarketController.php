<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\middleware\ThemePreviewMiddleware;
use app\common\service\ThemeMarketService;
use app\common\service\theme\RemoteTemplateSource;
use app\common\service\TemplateService;

/**
 * 模板市场管理后台控制器 - V3.1 Sprint 15 增强版
 *
 * 功能：透传API + 安装 + 回滚 + 切换 + 预览
 */
class ThemeMarketController extends AdminBaseController
{
    /**
     * 主题市场首页（V3.1 全新UI）
     */
    public function index(): string
    {
        // 透传模式：后端仅获取模板列表，前端JS做筛选/排序
        $result = ThemeMarketService::getMarketList('frontend');
        $categories = config('ai.theme_industry_categories', []);

        // 获取当前激活主题
        $currentTheme = TemplateService::getActiveTheme();

        $this->app->view->assign([
            'templates'      => $result['templates'],
            'source'         => $result['source'],
            'fetched_at'     => $result['fetched_at'],
            'current_theme'  => $currentTheme,
            'categories'     => $categories,
        ]);

        return $this->app->view->fetch('theme_market_index');
    }

    /**
     * V3.1 Sprint 15: 透传API — 获取模板列表（AJAX）
     */
    public function list(): \think\Response
    {
        try {
            $result = ThemeMarketService::getMarketList('frontend');
            return $this->success('ok', $result);
        } catch (\Throwable $e) {
            return $this->error('获取失败: ' . $e->getMessage());
        }
    }

    /**
     * V3.1 Sprint 15: 刷新远程列表（AJAX）
     */
    public function refresh(): \think\Response
    {
        try {
            $remoteSource = new RemoteTemplateSource();
            $remoteSource->refresh();
            $result = ThemeMarketService::getMarketList('frontend');
            return $this->success('刷新成功', $result);
        } catch (\Throwable $e) {
            return $this->error('刷新失败: ' . $e->getMessage());
        }
    }

    /**
     * V3.1 Sprint 15: 安装主题（AJAX）
     */
    public function install(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $code = trim($this->request->post('code', ''));
        $source = trim($this->request->post('source', 'prebuilt'));
        $downloadUrl = trim($this->request->post('download_url', ''));
        $type = trim($this->request->post('type', 'frontend'));
        $userId = (int) session('user_id');

        if (empty($code)) {
            return $this->error('请提供主题标识');
        }

        try {
            $result = ThemeMarketService::installTheme($code, $type, $source, $downloadUrl, $userId);

            $this->recordLog('安装主题', "code={$code}, source={$source}");
            return $this->success($result['message'], $result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * V3.1 Sprint 15: 切换主题（AJAX）
     */
    public function switch(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $code = trim($this->request->post('code', ''));
        $type = trim($this->request->post('type', 'frontend'));

        if (empty($code)) {
            return $this->error('请提供主题标识');
        }

        try {
            $result = ThemeMarketService::switchTheme($code, $type);
            $this->recordLog('切换主题', "code={$code}, type={$type}");
            return $this->success('切换成功', $result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * V3.1 Sprint 15: 卸载主题（AJAX）
     */
    public function uninstall(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $id = (int) $this->request->post('id', 0);
        try {
            ThemeMarketService::uninstallTheme($id);
            $this->recordLog('卸载主题', "id={$id}");
            return $this->success('卸载成功');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * V3.1 Sprint 15: 生成预览URL（AJAX）
     */
    public function previewUrl(): \think\Response
    {
        $code = trim($this->request->param('code', ''));
        if (empty($code)) {
            return $this->error('参数错误');
        }

        $themeDir = template_path() . 'themes' . DIRECTORY_SEPARATOR . $code;
        if (!is_dir($themeDir)) {
            return $this->error('主题目录不存在');
        }

        $hash = ThemePreviewMiddleware::generateHash($code);
        $previewUrl = url('/index/index') . '?preview=' . $hash;

        return $this->success('ok', [
            'preview_url' => $previewUrl,
            'hash'        => $hash,
            'expires_in'  => 86400,
        ]);
    }

    /**
     * V3.1 Sprint 15: 获取备份列表（AJAX）
     */
    public function backups(): \think\Response
    {
        $code = trim($this->request->param('code', ''));
        if (empty($code)) {
            return $this->error('参数错误');
        }

        try {
            $backups = ThemeMarketService::getBackups($code);
            return $this->success('ok', ['backups' => $backups]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * V3.1 Sprint 15: 回滚主题（AJAX）
     */
    public function rollback(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $code = trim($this->request->post('code', ''));
        $backupId = trim($this->request->post('backup_id', ''));
        $type = trim($this->request->post('type', 'frontend'));

        if (empty($code) || empty($backupId)) {
            return $this->error('参数错误');
        }

        try {
            $result = ThemeMarketService::rollbackTheme($code, $backupId, $type);
            $this->recordLog('回滚主题', "code={$code}, backup={$backupId}");
            return $this->success($result['message'], $result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 扫描同步（兼容旧版）
     */
    public function scan(): \think\Response
    {
        try {
            $result = ThemeMarketService::scanAndSync();
            return $this->success("扫描完成：新增{$result['added']}个，更新{$result['updated']}个", $result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 检查更新（增强版）
     */
    public function checkUpdate(): \think\Response
    {
        try {
            $updates = ThemeMarketService::checkUpdates();
            return $this->success('ok', ['updates' => $updates, 'count' => count($updates)]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
}
