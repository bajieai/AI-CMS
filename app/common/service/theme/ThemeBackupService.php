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

namespace app\common\service\theme;

use think\facade\Log;

/**
 * 模板备份与回滚服务 - V3.1 Sprint 15
 *
 * 功能：
 * 1. 安装前自动备份主题目录
 * 2. 一键回滚到备份版本
 * 3. 备份清理（保留最近N个）
 */
class ThemeBackupService
{
    /** 备份根目录 */
    protected string $backupRoot;

    /** 最大保留备份数 */
    protected int $maxBackups = 5;

    public function __construct()
    {
        $this->backupRoot = root_path() . 'runtime' . DIRECTORY_SEPARATOR . 'theme_backups';
        if (!is_dir($this->backupRoot)) {
            mkdir($this->backupRoot, 0755, true);
        }
    }

    /**
     * 备份主题目录
     *
     * @param string $themeCode 主题标识
     * @param string $themePath 主题目录绝对路径
     * @return array ['success' => bool, 'backup_id' => string, 'path' => string]
     */
    public function backup(string $themeCode, string $themePath): array
    {
        if (!is_dir($themePath)) {
            return ['success' => false, 'backup_id' => '', 'path' => '', 'message' => '主题目录不存在'];
        }

        $backupId = $themeCode . '_' . date('YmdHis') . '_' . substr(uniqid(), -4);
        $backupPath = $this->backupRoot . DIRECTORY_SEPARATOR . $backupId;

        try {
            $this->copyDir($themePath, $backupPath);
            $this->cleanupOldBackups($themeCode);

            Log::info("ThemeBackup: 备份完成 {$themeCode} -> {$backupId}");

            return [
                'success'    => true,
                'backup_id'  => $backupId,
                'path'       => $backupPath,
                'message'    => '备份成功',
            ];
        } catch (\Throwable $e) {
            Log::error("ThemeBackup: 备份失败 {$themeCode}: " . $e->getMessage());
            return [
                'success'    => false,
                'backup_id'  => '',
                'path'       => '',
                'message'    => '备份失败: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 回滚主题到指定备份
     *
     * @param string $backupId 备份ID
     * @param string $targetPath 目标主题目录
     * @return array ['success' => bool, 'message' => string]
     */
    public function rollback(string $backupId, string $targetPath): array
    {
        $backupPath = $this->backupRoot . DIRECTORY_SEPARATOR . $backupId;
        if (!is_dir($backupPath)) {
            return ['success' => false, 'message' => '备份不存在: ' . $backupId];
        }

        try {
            // 先备份当前（防止回滚后无法恢复）
            if (is_dir($targetPath)) {
                $currentCode = basename($targetPath);
                $currentBackup = $this->backupRoot . DIRECTORY_SEPARATOR . $currentCode . '_prerollback_' . date('YmdHis');
                $this->copyDir($targetPath, $currentBackup);
            }

            // 删除当前主题目录
            $this->rrmdir($targetPath);

            // 恢复备份
            $this->copyDir($backupPath, $targetPath);

            Log::info("ThemeBackup: 回滚完成 {$backupId} -> {$targetPath}");

            return ['success' => true, 'message' => '回滚成功'];
        } catch (\Throwable $e) {
            Log::error("ThemeBackup: 回滚失败 {$backupId}: " . $e->getMessage());
            return ['success' => false, 'message' => '回滚失败: ' . $e->getMessage()];
        }
    }

    /**
     * 获取主题的备份列表
     */
    public function getBackups(string $themeCode): array
    {
        $pattern = $this->backupRoot . DIRECTORY_SEPARATOR . $themeCode . '_*';
        $dirs = glob($pattern, GLOB_ONLYDIR);
        $backups = [];

        foreach ($dirs as $dir) {
            $backups[] = [
                'backup_id'  => basename($dir),
                'created_at' => date('Y-m-d H:i:s', filemtime($dir)),
                'size'       => $this->dirSize($dir),
            ];
        }

        usort($backups, fn ($a, $b) => strcmp($b['created_at'], $a['created_at']));
        return $backups;
    }

    /**
     * 清理旧备份（保留最近 maxBackups 个）
     */
    protected function cleanupOldBackups(string $themeCode): void
    {
        $backups = $this->getBackups($themeCode);
        if (count($backups) <= $this->maxBackups) {
            return;
        }

        $toDelete = array_slice($backups, $this->maxBackups);
        foreach ($toDelete as $b) {
            $this->rrmdir($this->backupRoot . DIRECTORY_SEPARATOR . $b['backup_id']);
            Log::info("ThemeBackup: 清理旧备份 {$b['backup_id']}");
        }
    }

    /**
     * 递归复制目录
     */
    protected function copyDir(string $src, string $dst): void
    {
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        foreach (scandir($src) as $file) {
            if ($file === '.' || $file === '..') continue;
            $srcPath = $src . DIRECTORY_SEPARATOR . $file;
            $dstPath = $dst . DIRECTORY_SEPARATOR . $file;
            if (is_dir($srcPath)) {
                $this->copyDir($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
    }

    /**
     * 递归删除目录
     */
    protected function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (glob($dir . '/*') as $file) {
            is_dir($file) ? $this->rrmdir($file) : unlink($file);
        }
        rmdir($dir);
    }

    /**
     * 计算目录大小
     */
    protected function dirSize(string $dir): int
    {
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }
}
