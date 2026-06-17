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

namespace app\common\service;

use think\facade\Cache;

/**
 * V2.9.23 A-4: 模板缓存服务
 * 提供模板文件MD5校验、自动清除、日志记录功能
 */
class TemplateCacheService
{
    /**
     * 缓存标签
     */
    private const string CACHE_TAG = 'template_cache';

    /**
     * 模板文件MD5缓存键前缀
     */
    private const string MD5_PREFIX = 'template_md5_';

    /**
     * 需要监控的模板目录
     */
    private array $watchDirs = [
        'template/themes/default/pc/',
        'template/themes/default/mobile/',
        'template/themes/corporate/pc/',
        'template/themes/corporate/mobile/',
        'template/admin/default/',
        'template/admin/corporate/',
    ];

    /**
     * 模板编译缓存目录
     */
    private array $compileDirs = [
        'runtime/admin/temp/',
        'runtime/home/temp/',
        'runtime/api/temp/',
    ];

    /**
     * 检查模板文件变更并自动清除对应缓存
     *
     * @return array 变更记录 ['changed'=>[], 'cleared'=>[], 'errors'=>[]]
     */
    public function checkAndClear(): array
    {
        $result = ['changed' => [], 'cleared' => [], 'errors' => []];
        $rootPath = root_path();

        foreach ($this->watchDirs as $dir) {
            $fullDir = $rootPath . $dir;
            if (!is_dir($fullDir)) {
                continue;
            }

            $files = $this->scanTemplateFiles($fullDir);
            foreach ($files as $file) {
                $relativePath = str_replace($rootPath, '', $file);
                $cacheKey = self::MD5_PREFIX . md5($relativePath);
                $currentMd5 = md5_file($file);

                // 获取缓存的MD5
                $cachedMd5 = Cache::get($cacheKey);

                if ($cachedMd5 !== null && $cachedMd5 !== $currentMd5) {
                    // 文件已变更，清除对应编译缓存
                    $cleared = $this->clearCompileCache($relativePath);
                    $result['changed'][] = $relativePath;
                    if ($cleared) {
                        $result['cleared'][] = $relativePath;
                    }
                }

                // 更新MD5缓存（无论是否变更，都刷新缓存时间）
                Cache::set($cacheKey, $currentMd5, 86400);
            }
        }

        return $result;
    }

    /**
     * 扫描模板文件（仅 .html）
     */
    private function scanTemplateFiles(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'html') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * 清除指定模板文件的编译缓存
     */
    private function clearCompileCache(string $templatePath): bool
    {
        $rootPath = root_path();
        $cleared = false;

        // 根据模板路径推断编译缓存文件名
        $compileFileName = $this->getCompileFileName($templatePath);

        foreach ($this->compileDirs as $compileDir) {
            $fullCompileDir = $rootPath . $compileDir;
            if (!is_dir($fullCompileDir)) {
                continue;
            }

            // 清除匹配的编译缓存文件
            $pattern = $fullCompileDir . '*' . $compileFileName . '*.php';
            $cachedFiles = glob($pattern);
            if ($cachedFiles === false) {
                continue;
            }

            foreach ($cachedFiles as $cachedFile) {
                if (is_file($cachedFile)) {
                    @unlink($cachedFile);
                    $cleared = true;
                }
            }
        }

        return $cleared;
    }

    /**
     * 根据模板路径生成编译缓存文件名匹配模式
     */
    private function getCompileFileName(string $templatePath): string
    {
        // 模板路径如: template/themes/default/pc/index.html
        // 编译缓存文件名通常包含路径哈希或文件名
        $fileName = basename($templatePath, '.html');
        return $fileName;
    }

    /**
     * 一键清除所有模板编译缓存
     *
     * @return array ['success'=>bool, 'count'=>int, 'errors'=>array]
     */
    public function clearAll(): array
    {
        $result = ['success' => true, 'count' => 0, 'errors' => []];
        $rootPath = root_path();

        foreach ($this->compileDirs as $dir) {
            $fullDir = $rootPath . $dir;
            if (!is_dir($fullDir)) {
                continue;
            }

            $files = glob($fullDir . '*.php');
            if ($files === false) {
                continue;
            }

            foreach ($files as $file) {
                if (is_file($file)) {
                    if (@unlink($file)) {
                        $result['count']++;
                    } else {
                        $result['errors'][] = str_replace($rootPath, '', $file);
                    }
                }
            }
        }

        // 同时清除MD5缓存
        Cache::tag(self::CACHE_TAG)->clear();

        return $result;
    }

    /**
     * 获取缓存统计信息
     */
    public function getStats(): array
    {
        $rootPath = root_path();
        $stats = [
            'template_files' => 0,
            'compile_cache_files' => 0,
            'compile_cache_size' => 0,
            'watch_dirs' => [],
        ];

        // 统计模板文件数
        foreach ($this->watchDirs as $dir) {
            $fullDir = $rootPath . $dir;
            if (!is_dir($fullDir)) {
                continue;
            }
            $files = $this->scanTemplateFiles($fullDir);
            $count = count($files);
            $stats['template_files'] += $count;
            $stats['watch_dirs'][] = [
                'path' => $dir,
                'file_count' => $count,
            ];
        }

        // 统计编译缓存文件数和大小
        foreach ($this->compileDirs as $dir) {
            $fullDir = $rootPath . $dir;
            if (!is_dir($fullDir)) {
                continue;
            }
            $files = glob($fullDir . '*.php');
            if ($files === false) {
                continue;
            }
            foreach ($files as $file) {
                if (is_file($file)) {
                    $stats['compile_cache_files']++;
                    $stats['compile_cache_size'] += filesize($file);
                }
            }
        }

        $stats['compile_cache_size_human'] = $this->formatBytes($stats['compile_cache_size']);

        return $stats;
    }

    /**
     * 格式化字节大小
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / (1024 ** $i), 2) . ' ' . $units[(int)$i];
    }
}
