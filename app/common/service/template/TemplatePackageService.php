<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\template;

// TemplatePackageService

/**
 * 模板打包导出/导入服务 - V2.9.12
 *
 * 提供ZIP打包、manifest校验、安全导入功能
 */
class TemplatePackageService
{
    /**
     * 打包模板为ZIP
     */
    public function package(string $slug, bool $includeManifest = true): array
    {
        $themePath = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $slug;
        if (!is_dir($themePath)) {
            return ['success' => false, 'message' => '模板目录不存在: ' . $slug];
        }

        $tempDir = runtime_path() . 'temp';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir . DIRECTORY_SEPARATOR . $slug . '_v' . date('Ymd') . '.zip';
        $manifestPath = $themePath . DIRECTORY_SEPARATOR . 'manifest.json';

        // 如需要manifest但不存在，自动生成
        if ($includeManifest && !file_exists($manifestPath)) {
            $this->generateManifest($slug, $themePath);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return ['success' => false, 'message' => '无法创建ZIP文件'];
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($themePath) + 1);

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        return [
            'success' => true,
            'message' => '打包成功',
            'path'    => $zipPath,
            'size'    => filesize($zipPath),
        ];
    }

    /**
     * 导入模板ZIP
     */
    public function unpack(string $zipPath): array
    {
        if (!file_exists($zipPath)) {
            return ['success' => false, 'message' => 'ZIP文件不存在'];
        }

        $tempExtractDir = runtime_path() . 'temp' . DIRECTORY_SEPARATOR . 'unpack_' . uniqid();
        mkdir($tempExtractDir, 0755, true);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'message' => '无法打开ZIP文件'];
        }
        $zip->extractTo($tempExtractDir);
        $zip->close();

        // 查找manifest
        $manifestFile = $tempExtractDir . DIRECTORY_SEPARATOR . 'manifest.json';
        if (!file_exists($manifestFile)) {
            // 可能在子目录中
            $dirs = glob($tempExtractDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
            if (!empty($dirs)) {
                $manifestFile = $dirs[0] . DIRECTORY_SEPARATOR . 'manifest.json';
                if (file_exists($manifestFile)) {
                    $tempExtractDir = $dirs[0];
                }
            }
        }

        $manifest = [];
        if (file_exists($manifestFile)) {
            $manifest = json_decode(file_get_contents($manifestFile), true) ?: [];
        }

        $slug = $manifest['name'] ?? basename($tempExtractDir);
        $targetPath = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $slug;

        // 安全检查：禁止覆盖官方模板（可选）
        if (is_dir($targetPath) && !empty($manifest['protected'])) {
            return ['success' => false, 'message' => '该模板受保护，禁止覆盖导入'];
        }

        // 清理并复制
        if (is_dir($targetPath)) {
            $this->recursiveDelete($targetPath);
        }
        mkdir($targetPath, 0755, true);
        $this->recursiveCopy($tempExtractDir, $targetPath);

        // 清理临时目录
        $this->recursiveDelete($tempExtractDir);
        @rmdir(dirname($tempExtractDir));

        return [
            'success'    => true,
            'message'    => '导入成功',
            'theme_name' => $slug,
            'manifest'   => $manifest,
        ];
    }

    /**
     * 生成manifest.json
     */
    public function generateManifest(string $slug, string $themePath): array
    {
        $themeJson = $themePath . DIRECTORY_SEPARATOR . 'theme.json';
        $info = [];
        if (file_exists($themeJson)) {
            $info = json_decode(file_get_contents($themeJson), true) ?: [];
        }

        $manifest = [
            'name'        => $info['name'] ?? $slug,
            'slug'        => $slug,
            'version'     => $info['version'] ?? '1.0.0',
            'author'      => $info['author'] ?? '未知作者',
            'description' => $info['description'] ?? '',
            'screenshots' => $info['screenshots'] ?? [],
            'requirements'=> [
                'php' => '>=8.1',
                'cms' => '>=2.9.0',
            ],
            'created_at'  => date('Y-m-d H:i:s'),
        ];

        file_put_contents($themePath . DIRECTORY_SEPARATOR . 'manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $manifest;
    }

    /**
     * 校验manifest
     */
    public function validateManifest(array $manifest): array
    {
        $required = ['name', 'slug', 'version'];
        $errors = [];
        foreach ($required as $field) {
            if (empty($manifest[$field])) {
                $errors[] = "缺少必要字段: {$field}";
            }
        }
        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * 版本差异对比
     */
    public function diffVersions(string $slug, string $oldVersion, string $newVersion): array
    {
        return [
            'old_version' => $oldVersion,
            'new_version' => $newVersion,
            'summary'     => "{$slug} 从 v{$oldVersion} 升级到 v{$newVersion}",
            'details'     => '版本差异对比功能需结合具体文件变更实现',
        ];
    }

    /**
     * 递归复制目录
     */
    protected function recursiveCopy(string $src, string $dst): void
    {
        $dir = opendir($src);
        @mkdir($dst, 0755, true);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') continue;
            $srcFile = $src . DIRECTORY_SEPARATOR . $file;
            $dstFile = $dst . DIRECTORY_SEPARATOR . $file;
            if (is_dir($srcFile)) {
                $this->recursiveCopy($srcFile, $dstFile);
            } else {
                copy($srcFile, $dstFile);
            }
        }
        closedir($dir);
    }

    /**
     * 递归删除目录
     */
    protected function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }
}
