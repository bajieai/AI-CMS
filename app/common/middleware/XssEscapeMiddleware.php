<?php
declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;
use think\Response;

/**
 * V2.9.5 XSS输出过滤中间件
 * 1. 添加 Content-Security-Policy-Report-Only 响应头（观察模式）
 * 2. 添加其他安全响应头（X-Content-Type-Options, X-Frame-Options, Referrer-Policy）
 * 3. 对 text/html 响应进行轻量XSS payload检测与日志记录（不阻断合法内容）
 * 4. 自动跳过 JSON/文件下载/API响应
 */
class XssEscapeMiddleware
{
    /**
     * 常见反射型XSS payload特征（用于检测日志，非阻断规则）
     */
    protected array $xssSignatures = [
        '/<script[^>]*>\s*alert\s*\(/i',
        '/<script[^>]*>\s*eval\s*\(/i',
        '/<script[^>]*>\s*document\.location\s*=/i',
        '/<script[^>]*>\s*window\.location\s*=/i',
        '/javascript:\s*alert\s*\(/i',
        '/on\w+\s*=\s*["\']?\s*javascript:/i',
        '/<img[^>]+onerror\s*=\s*["\']?\s*alert\s*\(/i',
        '/<svg[^>]*onload\s*=/i',
        '/<body[^>]*onload\s*=/i',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 仅对HTML响应处理
        $contentType = $response->getHeader('Content-Type');
        if (is_array($contentType)) {
            $contentType = implode(';', $contentType);
        }
        $isHtml = is_string($contentType) && stripos($contentType, 'text/html') !== false;

        // 统一添加安全响应头
        $this->addSecurityHeaders($response, $isHtml);

        if ($isHtml) {
            $html = $response->getContent();
            if (!empty($html)) {
                $this->detectXssPayload($html, $request);
            }
        }

        return $response;
    }

    /**
     * 添加安全响应头
     */
    protected function addSecurityHeaders(Response $response, bool $isHtml): void
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options'        => 'SAMEORIGIN',
            'Referrer-Policy'        => 'strict-origin-when-cross-origin',
        ];

        if ($isHtml) {
            // CSP 报告模式（先观察，确认无第三方资源误报后切换为强制模式）
            $csp = "default-src 'self'; "
                . "script-src 'self' 'unsafe-inline' 'unsafe-eval' *.googleapis.com *.gstatic.com; "
                . "style-src 'self' 'unsafe-inline' *.googleapis.com; "
                . "img-src 'self' data: blob: *.gravatar.com *.googleusercontent.com; "
                . "font-src 'self' *.gstatic.com; "
                . "connect-src 'self'; "
                . "frame-ancestors 'self'; "
                . "base-uri 'self'; "
                . "form-action 'self';";
            $headers['Content-Security-Policy-Report-Only'] = $csp;
        }

        $response->header($headers);
    }

    /**
     * 检测HTML中是否包含常见XSS payload并记录日志
     * 注意：此方法仅记录日志，不修改响应内容，避免误伤合法代码片段
     */
    protected function detectXssPayload(string $html, Request $request): void
    {
        foreach ($this->xssSignatures as $pattern) {
            if (preg_match($pattern, $html)) {
                \think\facade\Log::warning('[XSS_DETECT] 响应中包含潜在XSS特征: ' . $pattern . ' | URL=' . $request->url(true) . ' | IP=' . $request->ip());
                // 仅记录第一条匹配，避免日志爆炸
                break;
            }
        }
    }
}
