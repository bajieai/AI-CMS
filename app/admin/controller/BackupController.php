<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\BackupService;
use think\facade\Request;

/**
 * 数据库备份控制器
 * 仅超级管理员可访问
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
        $allowedTypes = ['all', 'structure', 'data'];
        if (!in_array($type, $allowedTypes)) {
            $type = 'all';
        }

        try {
            $backupService = new BackupService();
            $result = $backupService->create($type);

            $this->recordLog('backup', '创建数据库备份：' . $result['filename'] . ' (' . $result['size_text'] . ')');
            return $this->success('备份成功', $result);
        } catch (\Exception $e) {
            return $this->error('备份失败：' . $e->getMessage());
        }
    }

    /**
     * 恢复备份
     */
    public function restore()
    {
        $filename = Request::post('filename', '');
        if (empty($filename)) {
            return $this->error('请选择备份文件');
        }

        try {
            $backupService = new BackupService();
            $backupService->restore($filename);

            $this->recordLog('restore', '恢复数据库备份：' . $filename);
            return $this->success('恢复成功');
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
            $this->recordLog('delete', '删除数据库备份：' . $filename);
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
}
