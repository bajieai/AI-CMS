<?php
declare(strict_types=1);

namespace app\middleware;

use think\App;
use think\Response;

/**
 * CORS跨域中间件
 */
class CorsMiddleware
{
    /**
     * 应用实例
     */
    protected App $app;

    /**
     * 配置文件
     */
    protected array $config;

    /**
     * 构造函数
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->config = config('cors');
    }

    /**
     * 中间件处理
     */
    public function handle($request, \Closure $next): Response
    {
        // 处理OPTIONS预检请求
        if ($request->method(true) === 'OPTIONS') {
            return $this->handlePreflightRequest();
        }
        
        // 处理实际请求
        $response = $next($request);
        
        // 添加CORS头
        return $this->addCorsHeaders($response);
    }

    /**
     * 处理预检请求
     */
    protected function handlePreflightRequest(): Response
    {
        $response = response('', 204);
        return $this->addCorsHeaders($response);
    }

    /**
     * 添加CORS头
     */
    protected function addCorsHeaders(Response $response): Response
    {
        $origin = $this->getOrigin();
        
        // 检查是否允许该来源
        if ($this->isOriginAllowed($origin)) {
            $headers = [
                'Access-Control-Allow-Origin' => $origin,
                'Access-Control-Allow-Methods' => implode(', ', $this->config['allow_methods']),
                'Access-Control-Allow-Headers' => implode(', ', $this->config['allow_headers']),
                'Access-Control-Expose-Headers' => implode(', ', $this->config['expose_headers']),
                'Access-Control-Max-Age' => (string) $this->config['max_age'],
            ];

            if ($this->config['allow_credentials']) {
                $headers['Access-Control-Allow-Credentials'] = 'true';
            }

            $response->header($headers);
        }
        
        return $response;
    }

    /**
     * 获取请求来源
     */
    protected function getOrigin(): ?string
    {
        return $this->app->request->header('origin', '');
    }

    /**
     * 检查来源是否允许
     */
    protected function isOriginAllowed(?string $origin): bool
    {
        if (empty($origin)) {
            return false;
        }
        
        $allowedOrigins = $this->config['allow_origin'];
        
        // 检查通配符
        if (in_array('*', $allowedOrigins)) {
            return true;
        }
        
        // 精确匹配
        if (in_array($origin, $allowedOrigins)) {
            return true;
        }
        
        // 支持通配符匹配
        foreach ($allowedOrigins as $allowed) {
            if (str_contains($allowed, '*')) {
                $pattern = '/^' . str_replace(['*', '.'], ['.*', '\.'], $allowed) . '$/';
                if (preg_match($pattern, $origin)) {
                    return true;
                }
            }
        }
        
        return false;
    }
}
