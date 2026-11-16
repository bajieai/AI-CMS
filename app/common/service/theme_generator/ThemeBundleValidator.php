<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\theme_generator;

/**
 * 主题包体量校验（文件数 / 总大小 / 扩展名 / 路径）
 *
 * 阈值单源：运行期优先读 config/theme_generator.php.limits，配置缺失才退回本类常量。
 * 单测 ThemeBundleLimitsTest 锁住"配置值与类常量不漂移"。
 */
class ThemeBundleValidator
{
    /** 兜底：最大文件数 */
    public const MAX_FILES = 500;

    /** 兜底：解压后总大小上限（MB） */
    public const MAX_SIZE_MB = 20;

    /** 兜底：允许的扩展名（小写，与配置逐项比对） */
    public const ALLOWED_EXTENSIONS = [
        'html', 'htm', 'css', 'js', 'json', 'map', 'txt',
        'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico',
        'woff', 'woff2', 'ttf', 'eot',
    ];

    /**
     * 校验已打开的 zip，返回文件清单 [{name,size}, ...]
     *
     * @return array<int,array{name:string,size:int}>
     */
    public function validateZip(\ZipArchive $zip): array
    {
        $files = [];
        $totalSize = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) {
                continue;
            }

            $name = (string) $stat['name'];
            if (substr($name, -1) === '/') {
                continue;
            }

            $this->assertSafePath($name);

            $size = (int) $stat['size'];
            $files[] = ['name' => $name, 'size' => $size];
            $totalSize += $size;
        }

        $this->assertWithinLimits(count($files), $totalSize);

        foreach ($files as $file) {
            $this->assertAllowedExtension($file['name']);
        }

        return $files;
    }

    /**
     * 校验磁盘上的目录（回滚/预览前复用同一套限制）
     *
     * @return array<int,array{name:string,size:int}>
     */
    public function validateDirectory(string $dir): array
    {
        $root = rtrim($dir, '/\\');
        if (!is_dir($root)) {
            throw new ThemeGeneratorException('E-TG-016', '主题目录不存在：' . $root);
        }

        $files = [];
        $totalSize = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if (!$file->isFile()) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            $this->assertSafePath($relative);

            $size = (int) $file->getSize();
            $files[] = ['name' => $relative, 'size' => $size];
            $totalSize += $size;
        }

        $this->assertWithinLimits(count($files), $totalSize);

        foreach ($files as $file) {
            $this->assertAllowedExtension($file['name']);
        }

        return $files;
    }

    /**
     * 文件数与总大小
     */
    private function assertWithinLimits(int $fileCount, int $totalSize): void
    {
        if ($fileCount > $this->maxFiles()) {
            throw new ThemeGeneratorException(
                'E-TG-012',
                '包内文件数超限：' . $fileCount . ' > ' . $this->maxFiles()
            );
        }

        $maxBytes = $this->maxSizeMb() * 1024 * 1024;
        if ($totalSize > $maxBytes) {
            throw new ThemeGeneratorException(
                'E-TG-013',
                '包解压后体积超限：' . $this->formatSize($totalSize) . ' > ' . $this->maxSizeMb() . 'MB'
            );
        }
    }

    /**
     * 扩展名白名单
     */
    private function assertAllowedExtension(string $name): void
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, $this->allowedExtensions(), true)) {
            throw new ThemeGeneratorException('E-TG-014', '包内含不允许的文件类型：' . $name);
        }
    }

    /**
     * 路径越界防护
     */
    private function assertSafePath(string $path): void
    {
        $normalized = str_replace('\\', '/', $path);
        if (str_starts_with($normalized, '/') || preg_match('#(^|/)\.\.(/|$)#', $normalized)) {
            throw new ThemeGeneratorException('E-TG-014', '包内含越界路径：' . $path);
        }
    }

    private function maxFiles(): int
    {
        $value = config('theme_generator.limits.max_files');
        return $value === null ? self::MAX_FILES : (int) $value;
    }

    private function maxSizeMb(): int
    {
        $value = config('theme_generator.limits.max_size_mb');
        return $value === null ? self::MAX_SIZE_MB : (int) $value;
    }

    /**
     * @return string[]
     */
    private function allowedExtensions(): array
    {
        $value = config('theme_generator.limits.allowed_extensions');
        if ($value === null) {
            return self::ALLOWED_EXTENSIONS;
        }

        return array_values(array_map('strtolower', (array) $value));
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2) . 'MB';
        }

        return round($bytes / 1024, 1) . 'KB';
    }
}
