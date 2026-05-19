<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\theme;

use think\facade\Log;

/**
 * 主题版本管理服务 - V3.0 Phase 3
 *
 * 职责：
 * - 主题修改前的备份（优先 git，降级文件拷贝）
 * - 版本回退（git checkout / 文件拷贝还原）
 * - 版本差异对比（git diff / 文件比对）
 * - 版本历史查询
 *
 * Git 不可用时的降级方案：文件拷贝到 runtime/backup/
 */
class ThemeVersionManager
{
    /** 备份根目录（文件拷贝降级时使用） */
    protected string $backupRoot;
    /** 主题根目录 */
    protected string $themeRoot;
    /** Git 是否可用 */
    protected ?bool $gitAvailable = null;

    public function __construct()
    {
        $this->backupRoot = runtime_path() . 'backup' . DIRECTORY_SEPARATOR . 'themes';
        $this->themeRoot  = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes';
    }

    /**
     * 检测 Git 是否可用
     */
    public function isGitAvailable(): bool
    {
        if ($this->gitAvailable !== null) {
            return $this->gitAvailable;
        }

        $output = [];
        $exitCode = -1;
        @exec('git --version 2>&1', $output, $exitCode);
        $this->gitAvailable = ($exitCode === 0 && !empty($output) && str_contains($output[0], 'git version'));

        Log::info("[ThemeVersionManager] Git detection: " . ($this->gitAvailable ? 'available' : 'unavailable'));
        return $this->gitAvailable;
    }

    /**
     * 备份主题（修改前调用）
     *
     * @param string $themeName 主题名称
     * @param int $version 当前版本号
     * @param string $summary 修改摘要
     * @return array ['method' => 'git'|'file', 'identifier' => string, 'success' => bool]
     */
    public function backupBeforeChange(string $themeName, int $version, string $summary = ''): array
    {
        if ($this->isGitAvailable()) {
            return $this->gitBackup($themeName, $version, $summary);
        }
        return $this->fileBackup($themeName, $version);
    }

    /**
     * 回退到指定版本
     *
     * @param string $themeName 主题名称
     * @param string $identifier git commit hash 或文件备份路径
     * @return array ['success' => bool, 'message' => string]
     */
    public function rollback(string $themeName, string $identifier): array
    {
        if ($this->isGitAvailable() && strlen($identifier) === 40) {
            // 40位是 git commit hash
            return $this->gitRollback($themeName, $identifier);
        }
        // 否则按文件备份路径处理
        return $this->fileRollback($themeName, $identifier);
    }

    /**
     * 获取版本差异
     *
     * @param string $themeName 主题名称
     * @param string $fromIdentifier 起始版本标识
     * @param string $toIdentifier 目标版本标识
     * @return array ['success' => bool, 'diff' => string, 'message' => string]
     */
    public function diff(string $themeName, string $fromIdentifier, string $toIdentifier): array
    {
        if ($this->isGitAvailable() && strlen($fromIdentifier) === 40 && strlen($toIdentifier) === 40) {
            return $this->gitDiff($themeName, $fromIdentifier, $toIdentifier);
        }
        return $this->fileDiff($themeName, $fromIdentifier, $toIdentifier);
    }

    /**
     * 获取版本历史列表
     *
     * @param string $themeName 主题名称
     * @return array 版本列表
     */
    public function getVersionHistory(string $themeName): array
    {
        if ($this->isGitAvailable()) {
            return $this->gitHistory($themeName);
        }
        return $this->fileHistory($themeName);
    }

    // ==================== Git 方案 ====================

    /**
     * Git 备份：add + commit
     */
    protected function gitBackup(string $themeName, int $version, string $summary): array
    {
        $themePath = 'template/themes/' . $themeName;
        $commitMsg = "theme:{$themeName}:v{$version}";
        if (!empty($summary)) {
            $commitMsg .= " - {$summary}";
        }

        $rootPath = root_path();

        // git add
        $output1 = [];
        $exitCode1 = -1;
        @exec("cd \"{$rootPath}\" && git add \"{$themePath}\" 2>&1", $output1, $exitCode1);

        if ($exitCode1 !== 0) {
            Log::error("[ThemeVersionManager] git add failed: " . implode("\n", $output1));
            // 降级到文件备份
            return $this->fileBackup($themeName, $version);
        }

        // git commit
        $output2 = [];
        $exitCode2 = -1;
        $escapedMsg = escapeshellarg($commitMsg);
        @exec("cd \"{$rootPath}\" && git commit -m {$escapedMsg} --no-verify 2>&1", $output2, $exitCode2);

        if ($exitCode2 !== 0) {
            // 可能是无变更（nothing to commit）
            if (str_contains(implode("\n", $output2), 'nothing to commit')) {
                Log::info("[ThemeVersionManager] git backup: nothing to commit, using HEAD");
                // 获取当前 HEAD
                $headOutput = [];
                @exec("cd \"{$rootPath}\" && git rev-parse HEAD 2>&1", $headOutput);
                $hash = trim($headOutput[0] ?? '');
                return ['method' => 'git', 'identifier' => $hash, 'success' => true];
            }
            Log::error("[ThemeVersionManager] git commit failed: " . implode("\n", $output2));
            return $this->fileBackup($themeName, $version);
        }

        // 获取 commit hash
        $output3 = [];
        @exec("cd \"{$rootPath}\" && git rev-parse HEAD 2>&1", $output3);
        $hash = trim($output3[0] ?? '');

        Log::info("[ThemeVersionManager] git backup success: {$themeName} v{$version} -> {$hash}");

        return [
            'method'     => 'git',
            'identifier' => $hash,
            'success'    => true,
            'message'    => $commitMsg,
        ];
    }

    /**
     * Git 回退：checkout
     */
    protected function gitRollback(string $themeName, string $commitHash): array
    {
        $themePath = 'template/themes/' . $themeName;
        $rootPath = root_path();

        $output = [];
        $exitCode = -1;
        @exec("cd \"{$rootPath}\" && git checkout {$commitHash} -- \"{$themePath}\" 2>&1", $output, $exitCode);

        if ($exitCode !== 0) {
            $msg = implode("\n", $output);
            Log::error("[ThemeVersionManager] git rollback failed: {$msg}");
            return ['success' => false, 'message' => 'Git 回退失败: ' . $msg];
        }

        Log::info("[ThemeVersionManager] git rollback success: {$themeName} -> {$commitHash}");
        return ['success' => true, 'message' => '已回退到指定版本'];
    }

    /**
     * Git 差异对比
     */
    protected function gitDiff(string $themeName, string $fromHash, string $toHash): array
    {
        $themePath = 'template/themes/' . $themeName;
        $rootPath = root_path();

        $output = [];
        $exitCode = -1;
        @exec("cd \"{$rootPath}\" && git diff {$fromHash} {$toHash} -- \"{$themePath}\" 2>&1", $output, $exitCode);

        $diff = implode("\n", $output);

        if ($exitCode !== 0 && empty($diff)) {
            return ['success' => false, 'diff' => '', 'message' => 'Git diff 失败'];
        }

        return ['success' => true, 'diff' => $diff, 'message' => ''];
    }

    /**
     * Git 历史查询
     */
    protected function gitHistory(string $themeName): array
    {
        $themePath = 'template/themes/' . $themeName;
        $rootPath = root_path();

        $output = [];
        $exitCode = -1;
        // 格式: hash|author|date|subject
        $format = '--pretty=format:%H|%an|%ai|%s';
        @exec("cd \"{$rootPath}\" && git log {$format} -- \"{$themePath}\" 2>&1", $output, $exitCode);

        if ($exitCode !== 0) {
            return [];
        }

        $versions = [];
        foreach ($output as $line) {
            $parts = explode('|', $line, 4);
            if (count($parts) >= 4) {
                $versions[] = [
                    'hash'    => $parts[0],
                    'author'  => $parts[1],
                    'date'    => $parts[2],
                    'subject' => $parts[3],
                ];
            }
        }

        return $versions;
    }

    // ==================== 文件拷贝降级方案 ====================

    /**
     * 文件备份：recursiveCopy
     */
    protected function fileBackup(string $themeName, int $version): array
    {
        $srcPath = $this->themeRoot . DIRECTORY_SEPARATOR . $themeName;
        $backupPath = $this->backupRoot . DIRECTORY_SEPARATOR . $themeName . '_v' . $version . '_' . date('YmdHis');

        if (!is_dir($srcPath)) {
            Log::error("[ThemeVersionManager] file backup failed: source dir not found {$srcPath}");
            return ['method' => 'file', 'identifier' => '', 'success' => false];
        }

        try {
            $this->recursiveCopy($srcPath, $backupPath);
            Log::info("[ThemeVersionManager] file backup success: {$themeName} v{$version} -> {$backupPath}");
            return [
                'method'     => 'file',
                'identifier' => $backupPath,
                'success'    => true,
            ];
        } catch (\Throwable $e) {
            Log::error("[ThemeVersionManager] file backup failed: " . $e->getMessage());
            return ['method' => 'file', 'identifier' => '', 'success' => false];
        }
    }

    /**
     * 文件回退
     */
    protected function fileRollback(string $themeName, string $backupPath): array
    {
        $destPath = $this->themeRoot . DIRECTORY_SEPARATOR . $themeName;

        if (!is_dir($backupPath)) {
            return ['success' => false, 'message' => '备份目录不存在: ' . $backupPath];
        }

        try {
            // 先删除当前目录
            $this->recursiveDelete($destPath);
            // 复制备份
            $this->recursiveCopy($backupPath, $destPath);
            Log::info("[ThemeVersionManager] file rollback success: {$backupPath} -> {$destPath}");
            return ['success' => true, 'message' => '已回退到指定版本'];
        } catch (\Throwable $e) {
            Log::error("[ThemeVersionManager] file rollback failed: " . $e->getMessage());
            return ['success' => false, 'message' => '回退失败: ' . $e->getMessage()];
        }
    }

    /**
     * 文件差异对比（简化版：列出变更文件）
     */
    protected function fileDiff(string $themeName, string $fromPath, string $toPath): array
    {
        if (!is_dir($fromPath) || !is_dir($toPath)) {
            return ['success' => false, 'diff' => '', 'message' => '备份目录不存在'];
        }

        $diffLines = [];
        $diffLines[] = "=== 文件差异: {$themeName} ===";
        $diffLines[] = "From: {$fromPath}";
        $diffLines[] = "To: {$toPath}";
        $diffLines[] = "";

        // 简单对比：列出所有差异文件
        $fromFiles = $this->scanFiles($fromPath);
        $toFiles = $this->scanFiles($toPath);

        $allFiles = array_unique(array_merge(array_keys($fromFiles), array_keys($toFiles)));
        sort($allFiles);

        foreach ($allFiles as $relPath) {
            $inFrom = isset($fromFiles[$relPath]);
            $inTo = isset($toFiles[$relPath]);

            if (!$inFrom && $inTo) {
                $diffLines[] = "+ {$relPath} (新增)";
            } elseif ($inFrom && !$inTo) {
                $diffLines[] = "- {$relPath} (删除)";
            } elseif ($fromFiles[$relPath] !== $toFiles[$relPath]) {
                $diffLines[] = "M {$relPath} (修改)";
            }
        }

        return [
            'success' => true,
            'diff'    => implode("\n", $diffLines),
            'message' => '',
        ];
    }

    /**
     * 文件历史查询
     */
    protected function fileHistory(string $themeName): array
    {
        $pattern = $this->backupRoot . DIRECTORY_SEPARATOR . $themeName . '_v*';
        $dirs = glob($pattern, GLOB_ONLYDIR);

        if (empty($dirs)) {
            return [];
        }

        // 按修改时间倒序
        usort($dirs, fn($a, $b) => filemtime($b) <=> filemtime($a));

        $versions = [];
        foreach ($dirs as $dir) {
            $basename = basename($dir);
            // 解析 themeName_v{version}_YYYYMMDDHis
            if (preg_match('/_v(\d+)_(\d{14})$/', $basename, $matches)) {
                $versions[] = [
                    'hash'    => $dir, // 文件备份用路径作为标识
                    'author'  => 'system',
                    'date'    => date('Y-m-d H:i:s', filemtime($dir)),
                    'subject' => "备份 {$themeName} v{$matches[1]}",
                ];
            }
        }

        return $versions;
    }

    // ==================== 工具方法 ====================

    /**
     * 递归复制目录
     */
    protected function recursiveCopy(string $src, string $dst): void
    {
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $destPath = $dst . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0755, true);
                }
            } else {
                copy($item->getPathname(), $destPath);
            }
        }
    }

    /**
     * 递归删除目录
     */
    protected function recursiveDelete(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }

    /**
     * 扫描目录文件（返回相对路径->内容映射）
     */
    protected function scanFiles(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relPath = str_replace($dir . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $files[$relPath] = file_get_contents($file->getPathname());
            }
        }

        return $files;
    }
}
