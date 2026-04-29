<?php
declare(strict_types=1);

namespace app\admin\middleware;

/**
 * PJAX 中间件
 * 当请求携带 X-PJAX 头时，将完整 HTML 响应提取为 JSON 片段返回
 * 前端据此局部刷新内容区，避免整页重绘
 */
class PjaxMiddleware
{
    public function handle($request, \Closure $next)
    {
        $response = $next($request);

        // 仅处理携带 X-PJAX 头的 HTML 响应
        if (!$request->header('X-PJAX') || strpos($response->getContentType() ?: '', 'html') === false) {
            return $response;
        }

        $content = $response->getContent();

        // 提取 <title>
        preg_match('/<title[^>]*>(.*?)<\/title>/is', $content, $titleMatch);
        $title = isset($titleMatch[1]) ? trim($titleMatch[1]) : '';

        // 提取 <main class="main-content"> 内部 HTML
        preg_match('/<main\s+class=["\']main-content["\'][^>]*>(.*)<\/main>/is', $content, $mainMatch);
        $html = isset($mainMatch[1]) ? $mainMatch[1] : '';

        // 提取底部内联脚本（不含 src 属性，长度>20，排除公共脚本）
        preg_match_all('/<script[^>]*>(.*?)<\/script>/is', $content, $scriptMatches, PREG_SET_ORDER);
        $js = '';
        foreach ($scriptMatches as $match) {
            if (strpos($match[0], 'src=') === false && strlen($match[1]) > 20) {
                $js .= $match[1] . "\n";
            }
        }

        return json([
            'title'      => $title,
            'html'       => $html,
            'js'         => $js,
            'csrf_token' => session('__token__'),
        ]);
    }
}
