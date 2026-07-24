<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint PERF: 图片懒加载服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

/**
 * 图片懒加载服务 - V2.9.31 PERF-1
 * 提供图片懒加载HTML生成和优化
 */
class LazyLoadService
{
    /**
     * 生成懒加载图片HTML
     */
    public static function image(string $src, string $alt = '', array $attrs = []): string
    {
        $defaultAttrs = [
            'data-src' => $src,
            'alt' => $alt,
            'class' => 'lazyload',
        ];

        $merged = array_merge($defaultAttrs, $attrs);
        $attrStr = '';
        foreach ($merged as $key => $val) {
            $attrStr .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
        }

        // 使用1x1透明像素作为占位图
        $placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

        return '<img src="' . $placeholder . '"' . $attrStr . ' loading="lazy">';
    }

    /**
     * 处理HTML内容中的所有图片为懒加载
     */
    public static function processHtml(string $html): string
    {
        // 替换已有图片添加lazyload类
        $html = preg_replace_callback('/<img([^>]+)src=["\']([^"\']+)["\']([^>]*)>/i', function ($matches) {
            $before = $matches[1];
            $src = $matches[2];
            $after = $matches[3];

            // 如果已经是data-src或已有lazyload类，跳过
            if (str_contains($before, 'data-src') || str_contains($after, 'data-src')) {
                return $matches[0];
            }

            $placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
            $newTag = '<img' . $before . 'src="' . $placeholder . '" data-src="' . $src . '"' . $after;

            if (!str_contains($newTag, 'lazyload')) {
                $newTag = str_replace('<img', '<img class="lazyload"', $newTag);
            }

            if (!str_contains($newTag, 'loading=')) {
                $newTag = str_replace('<img', '<img loading="lazy"', $newTag);
            }

            return $newTag;
        }, $html);

        return $html;
    }

    /**
     * 获取懒加载JS代码
     */
    public static function getScript(): string
    {
        return <<<'SCRIPT'
<script>
// 原生懒加载（IntersectionObserver）
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                }
                img.classList.remove('lazyload');
                img.classList.add('lazyloaded');
                observer.unobserve(img);
            }
        });
    });
    document.querySelectorAll('img.lazyload').forEach(img => imageObserver.observe(img));
} else {
    // 降级：直接加载所有图片
    document.querySelectorAll('img[data-src]').forEach(img => {
        img.src = img.dataset.src;
    });
}
</script>
SCRIPT;
    }
}
