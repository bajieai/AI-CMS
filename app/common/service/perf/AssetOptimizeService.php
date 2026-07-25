<?php
declare(strict_types=1);

namespace app\common\service\perf;

use think\facade\Config;

/**
 * 静态资源优化服务
 * V2.9.38 PERF-II-4
 * CSS/JS/HTML压缩+合并+CDN+加载优化+版本管理
 * 
 * 开发/生产环境配置分离: 开发环境不启用压缩和CDN，避免调试困难
 */
class AssetOptimizeService
{
    /**
     * 压缩CSS
     */
    public function compressCss(string $css): string
    {
        // 移除注释
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // 移除空白
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        // 压缩冒号和分号周围空格
        $css = str_replace([' {', '{ ', ' }', '} ', ': ', ' :', '; ', ' ;'], ['{', '{', '}', '}', ':', ':', ';', ';'], $css);
        // 移除末尾分号
        $css = rtrim($css, ';');
        return trim($css);
    }

    /**
     * 压缩JS
     */
    public function compressJs(string $js): string
    {
        // 移除单行注释(不移除URL中的//)
        $js = preg_replace('/(?<!:)//.*$/', '', $js);
        // 移除多行注释
        $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js);
        // 移除多余空白
        $js = preg_replace('/\s+/', ' ', $js);
        return trim($js);
    }

    /**
     * 压缩HTML
     */
    public function compressHtml(string $html): string
    {
        // 移除HTML注释(保留条件注释)
        $html = preg_replace('/<!--(?!\[if).*?-->/', '', $html);
        // 移除标签间空白
        $html = preg_replace('/>\s+</', '><', $html);
        // 压缩连续空白
        $html = preg_replace('/\s+/', ' ', $html);
        return trim($html);
    }

    /**
     * 合并资源文件
     */
    public function mergeAssets(array $files, string $type): string
    {
        $content = '';
        foreach ($files as $file) {
            $path = public_path() . ltrim($file, '/');
            if (file_exists($path)) {
                $content .= file_get_contents($path) . "\n";
            }
        }
        
        // 压缩合并后的内容
        return match($type) {
            'css' => $this->compressCss($content),
            'js' => $this->compressJs($content),
            default => $content,
        };
    }

    /**
     * 上传到CDN
     */
    public function uploadToCdn(string $filePath): string
    {
        // 简化: 返回CDN URL(实际应调用CDN API)
        $cdnDomain = Config::get('cdn.domain', '');
        $relativePath = str_replace(public_path(), '', $filePath);
        return $cdnDomain . $relativePath;
    }

    /**
     * 替换为CDN URL
     */
    public function replaceWithCdnUrl(string $url): string
    {
        $cdnDomain = Config::get('cdn.domain', '');
        if (empty($cdnDomain)) return $url;
        return str_replace(request()->root(true), $cdnDomain, $url);
    }

    /**
     * 生成资源版本号
     */
    public function generateVersion(string $filePath): string
    {
        $fullPath = public_path() . ltrim($filePath, '/');
        if (file_exists($fullPath)) {
            return md5_file($fullPath);
        }
        return date('Ymd');
    }

    /**
     * 内联关键CSS
     */
    public function inlineCriticalCss(string $html, string $css): string
    {
        return str_replace('</head>', "<style>{$css}</style></head>", $html);
    }

    /**
     * 图片懒加载
     */
    public function lazyLoadImages(string $html): string
    {
        return preg_replace_callback('/<img([^>]*)src=/', function($matches) {
            $attrs = $matches[1];
            // 如果已有loading属性则跳过
            if (strpos($attrs, 'loading=') !== false) return $matches[0];
            return '<img' . $attrs . 'loading="lazy" data-src=';
        }, $html);
    }

    /**
     * 获取优化配置(开发/生产环境分离)
     */
    public function getOptimizeConfig(): array
    {
        $env = env('APP_ENV', 'production');
        return [
            'environment' => $env,
            'compress_css' => $env === 'production',
            'compress_js' => $env === 'production',
            'compress_html' => $env === 'production',
            'cdn_enabled' => $env === 'production' && !empty(Config::get('cdn.domain')),
            'lazy_load_images' => true,
            'inline_critical_css' => $env === 'production',
            'version_cache_busting' => true,
        ];
    }
}
