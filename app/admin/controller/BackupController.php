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
use app\common\service\BackupService;
use think\facade\Request;

/**
 * 数据库备份控制器 - V2.9.3 增强版
 * 支持文件备份、完整备份、gzip压缩、恢复安全保护
 */
class BackupController extends AdminBaseController
{
    /**
     * 备份列表
     */
    public function index()
    {
        $backupService = new BackupService();
        $list = $backupService->getList();

        $this->app->view->assign('list', $list);
        return $this->app->view->fetch('/backup_list');
    }

    /**
     * 创建备份
     */
    public function create()
    {
        $type = Request::post('type', 'all');
        $gzip = (bool) Request::post('gzip', false);
        $allowedTypes = ['all', 'structure', 'data', 'files', 'full'];
        if (!in_array($type, $allowedTypes)) {
            $type = 'all';
        }

        try {
            $backupService = new BackupService();

            if ($type === 'files') {
                $result = $backupService->createFileBackup();
                $this->recordLog('backup', '创建文件备份：' . $result['filename'] . ' (' . $result['size_text'] . ')');
            } elseif ($type === 'full') {
                $result = $backupService->createFullBackup($gzip);
                $this->recordLog('backup', '创建完整备份：DB=' . $result['db']['filename'] . ', Files=' . $result['files']['filename']);
            } else {
                $result = $backupService->create($type, $gzip);
                $this->recordLog('backup', '创建数据库备份：' . $result['filename'] . ' (' . $result['size_text'] . ')' . ($gzip ? ' [gzip]' : ''));
            }

            return $this->success('备份成功', $result);
        } catch (\Exception $e) {
            return $this->error('备份失败：' . $e->getMessage());
        }
    }

    /**
     * 恢复备份（带安全保护）
     */
    public function restore()
    {
        $filename = Request::post('filename', '');

        if (empty($filename)) {
            return $this->error('请选择备份文件');
        }

        // 如果是.zip文件，不支持恢复
        if (str_ends_with($filename, '.zip')) {
            return $this->error('文件备份（.zip）不支持通过此处恢复，请手动解压');
        }

        try {
            $backupService = new BackupService();

            // 安全保护：恢复前强制自动快照
            $snapshot = $backupService->create('all', true);

            $backupService->restore($filename, false);

            $this->recordLog('restore', '恢复数据库备份：' . $filename . '（恢复前快照：' . $snapshot['filename'] . '）');
            return $this->success('恢复成功（已自动创建快照: ' . $snapshot['filename'] . '）');
        } catch (\Exception $e) {
            return $this->error('恢复失败：' . $e->getMessage());
        }
    }

    /**
     * 删除备份
     */
    public function delete()
    {
        $filename = Request::post('filename', '');
        if (empty($filename)) {
            return $this->error('请选择备份文件');
        }

        $backupService = new BackupService();
        if ($backupService->delete($filename)) {
            $this->recordLog('delete', '删除备份：' . $filename);
            return $this->success('删除成功');
        }

        return $this->error('删除失败');
    }

    /**
     * 下载备份
     */
    public function download()
    {
        $filename = Request::get('filename', '');
        if (empty($filename)) {
            return $this->error('请选择备份文件');
        }

        try {
            $backupService = new BackupService();
            $filepath = $backupService->download($filename);

            return download($filepath, $filename);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 清理旧备份
     */
    public function cleanup()
    {
        $keep = (int) Request::post('keep', 10);
        $keep = max(1, min(50, $keep));

        try {
            $backupService = new BackupService();
            $deleted = $backupService->cleanup($keep);
            $this->recordLog('backup', "清理旧备份：删除 {$deleted} 个，保留最近 {$keep} 个");
            return $this->success("已清理 {$deleted} 个旧备份");
        } catch (\Exception $e) {
            return $this->error('清理失败：' . $e->getMessage());
        }
    }

    /**
     * V2.9.27 V-5: 定时备份配置页面
     */
    public function schedule()
    {
        $config = \app\common\service\ConfigService::get('backup_schedule', []);
        $this->app->view->assign('config', $config);
        return $this->app->view->fetch('/backup_schedule');
    }

    /**
     * V2.9.27 V-5: 保存定时备份配置
     */
    public function saveSchedule()
    {
        $data = Request::post();
        $schedule = [
            'enabled' => (bool) ($data['enabled'] ?? false),
            'frequency' => $data['frequency'] ?? 'daily',
            'hour' => (int) ($data['hour'] ?? 2),
            'minute' => (int) ($data['minute'] ?? 0),
            'type' => $data['type'] ?? 'all',
            'gzip' => (bool) ($data['gzip'] ?? true),
            'keep_count' => (int) ($data['keep_count'] ?? 7),
        ];
        \app\common\service\ConfigService::set('backup_schedule', $schedule);
        $this->recordLog('backup', '更新定时备份配置');
        return $this->success('配置已保存');
    }

    /**
     * V2.9.27 V-5: 执行定时备份（手动触发）
     */
    public function runScheduled()
    {
        try {
            $backupService = new BackupService();
            $result = $backupService->runScheduledBackup();
            $this->recordLog('backup', '手动执行定时备份：' . $result['filename']);
            return $this->success('定时备份执行成功', $result);
        } catch (\Exception $e) {
            return $this->error('执行失败：' . $e->getMessage());
        }
    }
}
