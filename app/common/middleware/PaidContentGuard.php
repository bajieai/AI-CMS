<?php
declare(strict_types=1);

namespace app\common\middleware;

use app\common\service\PaidService;
use think\Response;

/**
 * 付费内容二级防护中间件 - V2.7 P0-3
 * 对标记为付费的内容路由追加权限检查
 * 注意：此中间件只做检查并注入权限信息，不直接阻断（让控制器灵活处理预览/拦截逻辑）
 * 位置：从api/middleware移至common/middleware（V2.8修复）
 */
class PaidContentGuard
{
    public function handle($request, \Closure $next)
    {
        $contentId = (int) $request->route('id', 0);
        $memberId  = $request->apiMemberId ?? null;

        if ($contentId > 0) {
            // 注入付费权限检查结果，供后续控制器使用
            $request->paidAccessResult = self::checkAccess($contentId, $memberId);
        }

        $response = $next($request);

        // 如果响应是JSON且包含付费内容数据，追加权限信息
        if ($response instanceof Response && $response->getCode() === 200) {
            $data = json_decode($response->getContent(), true);
            if (is_array($data) && $data['code'] === 0 && isset($data['data']['is_paid_content'])) {
                $data['data']['is_unlocked'] = $data['data']['is_unlocked'] ?? ($request->paidAccessResult['has_access'] ?? false);
                $response->content(json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        }

        return $response;
    }

    /**
     * 检查内容访问权限（静态方法，供API和前台控制器共用）
     * @return array ['has_access' => bool, 'price' => float, 'type' => string]
     */
    public static function checkAccess(int $contentId, ?int $memberId): array
    {
        $hasAccess = false;
        $price     = 0;
        $type      = 'none';

        if ($memberId !== null && $memberId > 0) {
            $hasAccess = PaidService::canAccess($memberId, $contentId);
        }

        return [
            'has_access' => $hasAccess,
            'price'      => $price,
            'type'       => $type,
        ];
    }
}
