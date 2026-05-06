<?php
declare(strict_types=1);

namespace app\admin\middleware;

use think\response\Json as JsonResponse;

class PjaxMiddleware
{
    public function handle($request, \Closure $next)
    {
        $response = $next($request);
        $isPjax = !!$request->header('X-PJAX');
        if (!$isPjax) {
            return $response;
        }

        $content = $response->getContent();
        if (!is_string($content) || strlen($content) < 20) {
            return json([
                'title' => '',
                'html'  => '',
                'css'   => '',
                'js'    => '',
                'csrf_token' => session('__token__'),
            ]);
        }

        // =====================================================
        // 关键修复：ThinkPHP autoResponse() 会根据请求的 Accept
        // 头判断 isJson()，当 jQuery AJAX 设置 dataType:'json'
        // 时，Accept 头包含 "application/json"，导致框架将
        // 控制器返回的 HTML 字符串自动包装为 Json 响应。
        // 此时 $content 已是 json_encode 后的字符串（如
        // "\"\\r\\n<div class=\\\"card\\\">..." ），需要先
        // json_decode 还原为原始 HTML，否则后续再次
        // json_encode 会导致双重编码。
        // =====================================================
        if ($response instanceof JsonResponse) {
            $decoded = json_decode($content, true);
            if (is_string($decoded)) {
                $content = $decoded;
            }
        }

        $title = '';
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $content, $tm)) {
            $title = trim(html_entity_decode($tm[1], ENT_QUOTES, 'UTF-8'));
        }

        $html = $this->extractMainContent($content);
        $css  = $this->extractPageCss($content);
        $js   = $this->extractPageJs($content);
        $jsSrc = $this->extractExternalJs($content);

        $payload = [
            'title' => $title,
            'html'  => $html,
            'css'   => $css,
            'js'    => $js,
            'js_src' => $jsSrc,
            'csrf_token' => session('__token__'),
        ];

        $jsonResponse = \think\Response::create($payload, 'json', 200);
        $jsonResponse->header([
            'Content-Type'  => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ]);
        $jsonResponse->options(['json_encode_param' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE]);

        return $jsonResponse;
    }

    /**
     * 提取主内容区（PJAX-CONTAINER 标记之间）
     */
    private function extractMainContent(string $content): string
    {
        // 方案1：注释边界标记（优先级最高）
        $startMark = '<!-- PJAX-CONTAINER-START -->';
        $endMark   = '<!-- PJAX-CONTAINER-END -->';
        $startPos  = stripos($content, $startMark);
        if ($startPos !== false) {
            $endPos = stripos($content, $endMark, $startPos + strlen($startMark));
            if ($endPos !== false && $endPos > $startPos) {
                $inner = substr($content, $startPos + strlen($startMark), $endPos - $startPos - strlen($startMark));
                return trim($inner, "\r\n\t ");
            }
        }

        // 方案2：回退到 <main> 标签提取
        $mainStart = stripos($content, '<main');
        if ($mainStart !== false) {
            $mainTagEnd = strpos($content, '>', $mainStart);
            if ($mainTagEnd !== false) {
                $bodyStart = $mainTagEnd + 1;
                $mainEnd   = stripos($content, '</main>', $bodyStart);
                if ($mainEnd !== false) {
                    return trim(substr($content, $bodyStart, $mainEnd - $bodyStart), "\r\n\t ");
                }
            }
        }

        // 都找不到，返回空字符串触发前端降级
        return '';
    }

    /**
     * 提取页面级CSS
     * 来源1: <head>中的<style>块（{block name="css"} 区域）
     * 来源2: <body>中PJAX容器外的<style>块
     * 排除layout.html的全局样式
     */
    private function extractPageCss(string $content): string
    {
        $cssBlocks = [];

        // ---- 来源1: <head> 中的 <style> 块 ----
        $headEnd = stripos($content, '</head>');
        if ($headEnd !== false) {
            $headContent = substr($content, 0, $headEnd);
            if (preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $headContent, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $cssText = trim($match[1]);
                    if (empty($cssText)) continue;
                    if ($this->isLayoutGlobalCss($cssText)) continue;
                    $cssBlocks[] = $cssText;
                }
            }
        }

        // ---- 来源2: <body> 中 PJAX 容器外的 <style> 块 ----
        $bodyStart = stripos($content, '<body');
        if ($bodyStart !== false) {
            $bodyContent = substr($content, $bodyStart);
            $pjaxEnd = stripos($bodyContent, '<!-- PJAX-CONTAINER-END -->');
            if ($pjaxEnd !== false) {
                $afterPjax = substr($bodyContent, $pjaxEnd + strlen('<!-- PJAX-CONTAINER-END -->'));
                if (preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $afterPjax, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $cssText = trim($match[1]);
                        if (empty($cssText)) continue;
                        if ($this->isLayoutGlobalCss($cssText)) continue;
                        $cssBlocks[] = $cssText;
                    }
                }
            }
        }

        return implode("\n\n", $cssBlocks);
    }

    /**
     * 提取页面级JS
     * 来源: <body>中PJAX容器之后的内联<script>块
     * 排除外部引用脚本和layout全局脚本
     */
    private function extractPageJs(string $content): string
    {
        $jsBlocks = [];

        $bodyStart = stripos($content, '<body');
        if ($bodyStart === false) {
            return '';
        }
        $bodyContent = substr($content, $bodyStart);

        $pjaxEnd = stripos($bodyContent, '<!-- PJAX-CONTAINER-END -->');
        if ($pjaxEnd === false) {
            return '';
        }

        // PJAX 容器之后的全部内容（即 {block name="js"} 所在区域）
        $afterPjax = substr($bodyContent, $pjaxEnd + strlen('<!-- PJAX-CONTAINER-END -->'));

        // 匹配所有内联 <script> 块（排除带 src 属性的外部引用）
        $regexResult = preg_match_all('/<script(?![^>]*\ssrc\s*=)([^>]*)>(.*?)<\/script>/is', $afterPjax, $matches, PREG_SET_ORDER);

        if ($regexResult) {
            foreach ($matches as $match) {
                $attrs  = $match[1];
                $jsText = trim($match[2]);
                if (empty($jsText)) continue;
                // 排除 type="application/ld+json" 等非JS类型
                if (preg_match('/type\s*=\s*["\'](?!text\/javascript|application\/javascript)["\']/i', $attrs)) continue;
                // 排除layout全局脚本
                if ($this->isLayoutGlobalScript($jsText)) continue;
                $jsBlocks[] = $jsText;
            }
        }

        return implode("\n\n", $jsBlocks);
    }

    /**
     * 提取页面级外部JS（<script src="...">）
     * 来源: <body>中PJAX容器之后的带src属性的<script>标签
     * 这些外部脚本需要在PJAX切换时动态加载，且必须在内联脚本执行前完成
     */
    private function extractExternalJs(string $content): array
    {
        $urls = [];

        $bodyStart = stripos($content, '<body');
        if ($bodyStart === false) {
            return $urls;
        }
        $bodyContent = substr($content, $bodyStart);

        $pjaxEnd = stripos($bodyContent, '<!-- PJAX-CONTAINER-END -->');
        if ($pjaxEnd === false) {
            return $urls;
        }

        // PJAX 容器之后的全部内容（即 {block name="js"} 所在区域）
        $afterPjax = substr($bodyContent, $pjaxEnd + strlen('<!-- PJAX-CONTAINER-END -->'));

        // 匹配带 src 属性的 <script> 标签
        if (preg_match_all('/<script[^>]*\ssrc\s*=\s*["\']([^"\']+)["\'][^>]*>/is', $afterPjax, $matches)) {
            foreach ($matches[1] as $url) {
                $urls[] = $url;
            }
        }

        // 也要检查 PJAX 容器内部的外部脚本（如 dashboard 的 ECharts 引用在 content block 中）
        $containerStart = stripos($bodyContent, '<!-- PJAX-CONTAINER-START -->');
        if ($containerStart !== false) {
            $containerEnd = stripos($bodyContent, '<!-- PJAX-CONTAINER-END -->');
            if ($containerEnd !== false) {
                $innerContent = substr($bodyContent, $containerStart, $containerEnd - $containerStart);
                if (preg_match_all('/<script[^>]*\ssrc\s*=\s*["\']([^"\']+)["\'][^>]*>/is', $innerContent, $matches)) {
                    foreach ($matches[1] as $url) {
                        if (!in_array($url, $urls)) {
                            $urls[] = $url;
                        }
                    }
                }
            }
        }

        return $urls;
    }

    /**
     * 判断CSS是否为layout.html全局样式（不应重复注入）
     */
    private function isLayoutGlobalCss(string $css): bool
    {
        $markers = [
            'V2.6 双栏菜单',
            'sidebar-wrapper',
            'sidebar-l1',
            'sidebar-l2',
        ];
        foreach ($markers as $marker) {
            if (strpos($css, $marker) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 判断脚本是否为layout.html中的全局脚本（不应在PJAX中重复执行）
     */
    private function isLayoutGlobalScript(string $js): bool
    {
        $layoutMarkers = [
            'window.MENU_DATA',            // 菜单数据注入
            'function doPjax',             // PJAX核心函数（已在pjax.js中定义）
            'function updateSidebarActive', // 侧栏高亮
            'function showToast',          // 全局toast
            'function ajaxPost',           // 全局ajax封装
            'function confirmDelete',      // 全局删除确认
            'function showConfirm',        // 全局确认框
            'fetchUnread',                 // 未读消息轮询
            'showPageLoader',              // 页面加载进度条
            'hidePageLoader',              // 页面加载进度条
        ];

        foreach ($layoutMarkers as $marker) {
            if (strpos($js, $marker) !== false) {
                return true;
            }
        }

        return false;
    }
}
