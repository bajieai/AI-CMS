<?php
declare(strict_types=1);

namespace app\common\service\storage;

/**
 * 本地存储驱动 - V2.6
 * 文件存储在 public/uploads 目录下
 */
class LocalStorageDriver implements StorageDriverInterface
{
    /**
     * 上传文件到本地
     */
    public function upload(string $localPath, string $savePath, array $options = []): array
    {
        $baseDir = public_path() . 'uploads' . DIRECTORY_SEPARATOR;
        $targetPath = $baseDir . ltrim($savePath, '/\\');
        $targetDir = dirname($targetPath);

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if (!rename($localPath, $targetPath)) {
            // rename失败时尝试copy+delete
            if (!copy($localPath, $targetPath)) {
                return ['success' => false, 'url' => '', 'path' => '', 'error' => '本地文件移动失败'];
            }
            @unlink($localPath);
        }

        $url = '/uploads/' . str_replace('\\', '/', ltrim($savePath, '/\\'));
        return ['success' => true, 'url' => $url, 'path' => $savePath, 'error' => ''];
    }

    /**
     * 删除本地文件
     */
    public function delete(string $path): bool
    {
        $filePath = public_path() . 'uploads' . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
        if (file_exists($filePath)) {
            return @unlink($filePath);
        }
        return true;
    }

    /**
     * 获取本地文件访问URL
     */
    public function getUrl(string $path): string
    {
        return '/uploads/' . ltrim(str_replace('\\', '/', $path), '/');
    }

    /**
     * 检查本地文件是否存在
     */
    public function exists(string $path): bool
    {
        $filePath = public_path() . 'uploads' . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
        return file_exists($filePath);
    }

    public function getName(): string
    {
        return 'local';
    }

    public function getDisplayName(): string
    {
        return '本地存储';
    }

    public function getConfigFields(): array
    {
        return [];
    }

    public function validateConfig(array $config): bool
    {
        return true;
    }
}
