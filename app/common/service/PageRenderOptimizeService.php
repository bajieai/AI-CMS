<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;

/**
 * V2.9.35 PERF-4: 页面渲染优化服务
 */
class PageRenderOptimizeService
{
    /**
     * 生成静态HTML页面
     */
    public function generateStaticPage(string $url, string $html): string
    {
        $staticDir = public_path() . 'static_html';
        if (!is_dir($staticDir)) {
            @mkdir($staticDir, 0755, true);
        }

        $filename = $this->urlToFilename($url);
        $filepath = $staticDir . '/' . $filename;

        file_put_contents($filepath, $html);

        return $filepath;
    }

    /**
     * 检查静态页面是否存在且有效
     */
    public function getStaticPage(string $url): ?string
    {
        $filename = $this->urlToFilename($url);
        $filepath = public_path() . 'static_html/' . $filename;

        if (!file_exists($filepath)) {
            return null;
        }

        // 检查过期时间（默认1小时）
        if (time() - filemtime($filepath) > 3600) {
            @unlink($filepath);
            return null;
        }

        return file_get_contents($filepath);
    }

    /**
     * 清除静态页面缓存
     */
    public function clearStaticPages(string $pattern = ''): int
    {
        $staticDir = public_path() . 'static_html';
        if (!is_dir($staticDir)) {
            return 0;
        }

        $count = 0;
        $files = glob($staticDir . '/*.html');
        foreach ($files as $file) {
            if ($pattern && !str_contains(basename($file), $pattern)) {
                continue;
            }
            @unlink($file);
            $count++;
        }

        return $count;
    }

    /**
     * URL转文件名
     */
    protected function urlToFilename(string $url): string
    {
        $url = trim($url, '/');
        $url = str_replace(['/', '?', '&', '='], ['_', '_', '_', '_'], $url);
        return ($url ?: 'index') . '.html';
    }
}
