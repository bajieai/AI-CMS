<?php

declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;
use think\Response;
use think\facade\Config;
use app\common\service\SqlInjectionDetectService;

/**
 * V2.9.35 SEC-2: SQL注入检测中间件
 * 检测GET/POST参数中的SQL注入特征
 * 模式: block(阻断请求) / log(仅记录日志)
 */
class SqlInjectionDetectMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $config = Config::get('security.sql_injection', []);
        if (empty($config['enabled'])) {
            return $next($request);
        }

        $whitelist = $config['whitelist'] ?? [];
        $currentPath = $request->pathinfo();
        foreach ($whitelist as $pattern) {
            if (str_contains($currentPath, $pattern)) {
                return $next($request);
            }
        }

        $detectService = new SqlInjectionDetectService();
        $params = array_merge($request->get(), $request->post());
        $threat = $detectService->detect($params);

        if ($threat !== null) {
            // 记录安全日志
            $detectService->logThreat($threat, $request);

            $mode = $config['mode'] ?? 'block';
            if ($mode === 'block') {
                return Response::create([
                    'code' => 403,
                    'msg'  => '请求包含不安全的内容，已被安全防护拦截。',
                    'data' => null,
                ])->contentType('application/json')->code(403);
            }
        }

        return $next($request);
    }
}
