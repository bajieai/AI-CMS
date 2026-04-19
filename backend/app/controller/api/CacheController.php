<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Cache;
use think\facade\Db;

/**
 * 缓存管理控制器
 */
class CacheController extends BaseController
{
    /**
     * 清除所有缓存
     */
    public function clear(): \think\Response
    {
        $type = $this->request->param('type', 'all');
        $cleared = [];

        try {
            switch ($type) {
                case 'all':
                    $cleared = $this->clearAllCache();
                    break;
                case 'system':
                    $cleared = $this->clearSystemCache();
                    break;
                case 'config':
                    $cleared = $this->clearConfigCache();
                    break;
                case 'template':
                    $cleared = $this->clearTemplateCache();
                    break;
                default:
                    return $this->error('未知的缓存类型');
            }

            return $this->success([
                'cleared_types' => $cleared,
                'timestamp' => date('Y-m-d H:i:s'),
            ], '缓存清除成功');

        } catch (\Exception $e) {
            return $this->error('缓存清除失败: ' . $e->getMessage());
        }
    }

    /**
     * 清除所有缓存
     */
    protected function clearAllCache(): array
    {
        $cleared = [];

        // 1. 清除ThinkPHP应用缓存
        $this->clearSystemCache();
        $cleared[] = 'system';

        // 2. 清除配置缓存
        $this->clearConfigCache();
        $cleared[] = 'config';

        // 3. 清除模板缓存
        $this->clearTemplateCache();
        $cleared[] = 'template';

        // 4. 清除数据库查询缓存（使用缓存标签）
        try {
            Cache::tag('db_query')->clear();
            $cleared[] = 'db_query';
        } catch (\Exception $e) {
            // 如果缓存驱动不支持标签，则跳过
            $cleared[] = 'db_query (skipped)';
        }

        return $cleared;
    }

    /**
     * 清除系统缓存
     */
    protected function clearSystemCache(): array
    {
        $cleared = [];
        
        // 清除ThinkPHP缓存
        Cache::clear();
        $cleared[] = 'thinkphp_cache';

        // 清除运行时缓存目录
        $runtimePath = app()->getRuntimePath();
        $this->clearDirectory($runtimePath . 'cache');
        $cleared[] = 'runtime_cache';

        return $cleared;
    }

    /**
     * 清除配置缓存
     */
    protected function clearConfigCache(): array
    {
        $cleared = [];
        
        // 清除配置缓存文件
        $configPath = app()->getRuntimePath() . 'config';
        $this->clearDirectory($configPath);
        $cleared[] = 'config_cache';

        return $cleared;
    }

    /**
     * 清除模板缓存
     */
    protected function clearTemplateCache(): array
    {
        $cleared = [];
        
        // 清除模板缓存
        $tempPath = app()->getRuntimePath() . 'temp';
        $this->clearDirectory($tempPath);
        $cleared[] = 'template_cache';

        // 清除视图缓存
        $viewPath = app()->getRuntimePath() . 'view';
        $this->clearDirectory($viewPath);
        $cleared[] = 'view_cache';

        return $cleared;
    }

    /**
     * 清空目录（保留目录本身）
     */
    protected function clearDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }
    }

    /**
     * 获取缓存状态
     */
    public function status(): \think\Response
    {
        $runtimePath = app()->getRuntimePath();
        
        $status = [
            'system' => [
                'name' => '系统缓存',
                'size' => $this->getDirectorySize($runtimePath . 'cache'),
            ],
            'config' => [
                'name' => '配置缓存',
                'size' => $this->getDirectorySize($runtimePath . 'config'),
            ],
            'template' => [
                'name' => '模板缓存',
                'size' => $this->getDirectorySize($runtimePath . 'temp'),
            ],
            'view' => [
                'name' => '视图缓存',
                'size' => $this->getDirectorySize($runtimePath . 'view'),
            ],
            'cache_driver' => config('cache.default', 'file'),
        ];

        return $this->success($status);
    }

    /**
     * 获取目录大小
     */
    protected function getDirectorySize(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $size = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }
}
