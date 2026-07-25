<?php

declare(strict_types=1);

namespace app\common\service;

/**
 * V2.9.35 PERF-3: 静态资源优化服务
 * JS/CSS压缩合并 + 图片WebP
 */
class StaticResourceService
{
    /**
     * 压缩JS
     */
    public function minifyJs(string $content): string
    {
        // 移除注释
        $content = preg_replace('/\/\/.*$/m', '', $content);
        $content = preg_replace('/\/\*[\s\S]*?\*\//', '', $content);
        // 移除多余空白
        $content = preg_replace('/\s+/', ' ', $content);
        $content = preg_replace('/\s*([{};,:])\s*/', '$1', $content);
        return trim($content);
    }

    /**
     * 压缩CSS
     */
    public function minifyCss(string $content): string
    {
        // 移除注释
        $content = preg_replace('/\/\*[\s\S]*?\*\//', '', $content);
        // 移除多余空白
        $content = preg_replace('/\s+/', ' ', $content);
        $content = preg_replace('/\s*([{};:,>])\s*/', '$1', $content);
        $content = preg_replace('/;}/', '}', $content);
        return trim($content);
    }

    /**
     * 合并多个文件
     */
    public function mergeFiles(array $files, string $outputFile): bool
    {
        $content = '';
        foreach ($files as $file) {
            if (file_exists($file)) {
                $content .= file_get_contents($file) . "\n";
            }
        }

        // 根据扩展名决定压缩方式
        $ext = pathinfo($outputFile, PATHINFO_EXTENSION);
        if ($ext === 'js') {
            $content = $this->minifyJs($content);
        } elseif ($ext === 'css') {
            $content = $this->minifyCss($content);
        }

        return file_put_contents($outputFile, $content) !== false;
    }

    /**
     * 生成资源加载标签（带DNS预解析/预加载）
     */
    public function generateResourceTags(array $resources): string
    {
        $html = '';
        $cdnDomain = config('cdn.domain', '');

        // DNS预解析
        if (!empty($cdnDomain)) {
            $html .= "<link rel=\"dns-prefetch\" href=\"//{$cdnDomain}\">\n";
            $html .= "<link rel=\"preconnect\" href=\"https://{$cdnDomain}\">\n";
        }

        foreach ($resources as $res) {
            $url = !empty($cdnDomain) ? "https://{$cdnDomain}{$res['path']}" : $res['path'];
            if ($res['type'] === 'css') {
                $html .= "<link rel=\"stylesheet\" href=\"{$url}";
                if (!empty($res['version'])) $html .= "?v={$res['version']}";
                $html .= "\">\n";
            } elseif ($res['type'] === 'js') {
                $load = $res['defer'] ?? false ? ' defer' : ($res['async'] ?? false ? ' async' : '');
                $html .= "<script src=\"{$url}";
                if (!empty($res['version'])) $html .= "?v={$res['version']}";
                $html .= "\"{$load}></script>\n";
            }
        }

        return $html;
    }
}
