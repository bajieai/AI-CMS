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
 * 主题预览渲染器（沙箱 iframe 用）
 *
 * 输入是包内真实 HTML：解析 `{% extends %}` / `{% block %}`，把子模板内容注入基模板，
 * 再把相对 assets 路径改写到 /skin/themes/<slug>/assets/，供 sandbox="allow-scripts" 的 srcdoc 使用。
 * 不写 template/themes/，不改 frontend_theme。
 */
class ThemePreviewRenderer
{
    /** 预览入口模板 */
    private const ENTRY = 'templates/index.html';

    /** 继承链最大深度，防循环继承 */
    private const MAX_DEPTH = 8;

    /**
     * 渲染预览 HTML
     */
    public function render(string $themeDir, string $slug, array $manifest): string
    {
        $root = rtrim($themeDir, '/\\');
        $entry = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::ENTRY);

        if (!is_file($entry)) {
            throw new ThemeGeneratorException('E-TG-017', '主题缺少 ' . self::ENTRY . '，无法预览');
        }

        $html = $this->renderTemplateFile($entry, $root, 0);

        // 尾部兜底：清掉未处理的模板标签，避免原样漏到 iframe
        $html = preg_replace('/{%\s*(?:extends|block|endblock)[^%]*%}/', '', $html) ?? $html;

        return $this->rewriteAssetPaths($html, $slug);
    }

    /**
     * 递归解析继承与 block
     */
    private function renderTemplateFile(string $file, string $root, int $depth): string
    {
        if ($depth > self::MAX_DEPTH) {
            throw new ThemeGeneratorException('E-TG-017', '模板继承层级过深或存在循环继承');
        }

        if (!is_file($file)) {
            throw new ThemeGeneratorException('E-TG-017', '模板文件不存在：' . $file);
        }

        $raw = (string) file_get_contents($file);
        $blocks = $this->collectBlocks($raw);

        if (preg_match('/{%\s*extends\s+["\']([^"\']+)["\']\s*%}/', $raw, $matches)) {
            $parent = $root . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, ltrim($matches[1], '/\\'));
            $parentHtml = $this->renderTemplateFile($parent, $root, $depth + 1);

            return $this->applyBlocks($parentHtml, $blocks);
        }

        return $this->applyBlocks($raw, $blocks);
    }

    /**
     * 收集本文件定义的 block 内容
     *
     * @return array<string,string>
     */
    private function collectBlocks(string $html): array
    {
        $blocks = [];
        if (preg_match_all('/{%\s*block\s+([A-Za-z0-9_]+)\s*%}(.*?){%\s*endblock\s*%}/s', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $blocks[$match[1]] = $match[2];
            }
        }

        return $blocks;
    }

    /**
     * 用给定内容替换 HTML 中的 block（未覆盖的保留默认内容），并剥离 block 标记
     *
     * @param array<string,string> $blocks
     */
    private function applyBlocks(string $html, array $blocks): string
    {
        $result = preg_replace_callback(
            '/{%\s*block\s+([A-Za-z0-9_]+)\s*%}(.*?){%\s*endblock\s*%}/s',
            static function (array $match) use ($blocks): string {
                return $blocks[$match[1]] ?? $match[2];
            },
            $html
        );

        return $result ?? $html;
    }

    /**
     * 相对 assets 路径 → 可被浏览器直出的静态路径
     */
    private function rewriteAssetPaths(string $html, string $slug): string
    {
        $prefix = '/skin/themes/' . $slug . '/assets/';

        $result = preg_replace(
            '/((?:href|src)\s*=\s*["\'])(?:\.\/)?assets\//i',
            '${1}' . $prefix,
            $html
        );

        return $result ?? $html;
    }
}
