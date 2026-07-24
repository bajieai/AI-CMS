<?php
declare(strict_types=1);

namespace app\api\middleware;

use app\common\service\plugin\ApiOpenPlatformService;
use think\Request;
use think\Response;

/**
 * 开放API认证中间件
 * V2.9.37 PLUG-ECO-4
 * HMAC-SHA256签名验证 + 频率限制 + IP白名单
 */
class OpenApiAuthMiddleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        $apiKey = $request->header('X-Api-Key', '');
        $signature = $request->header('X-Api-Signature', '');
        $timestamp = (int) $request->header('X-Api-Timestamp', 0);
        if (empty($apiKey) || empty($signature) || $timestamp <= 0) {
            return json(['code' => 401, 'msg' => 'API认证参数缺失'])->code(401);
        }
        $service = new ApiOpenPlatformService();
        // 验证签名
        if (!$service->verifyApiKey($apiKey, $signature, $timestamp)) {
            return json(['code' => 401, 'msg' => 'API签名验证失败'])->code(401);
        }
        // 频率限制
        if (!$service->checkRateLimit($apiKey)) {
            return json(['code' => 429, 'msg' => 'API调用频率超限'])->code(429);
        }
        // 记录调用日志
        $service->logCall($apiKey, $request->path(), $request->param());
        return $next($request);
    }
}
