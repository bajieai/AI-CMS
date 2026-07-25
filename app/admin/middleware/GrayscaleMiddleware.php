<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.39 SYS-ROBUST-2: 灰度发布中间件
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\admin\middleware;

use think\facade\Config;
use app\common\service\system\GrayscaleReleaseService;

/**
 * 灰度发布中间件 - V2.9.39 SYS-ROBUST-2
 * 根据灰度策略决定请求路由
 */
class GrayscaleMiddleware
{
    protected GrayscaleReleaseService $service;

    public function __construct()
    {
        $this->service = new GrayscaleReleaseService();
    }

    /**
     * 中间件处理
     */
    public function handle($request, \Closure $next)
    {
        $featureKey = $this->extractFeatureKey($request);

        if ($featureKey) {
            $userId = (int) session('user_id');
            $ip = $request->ip();

            $inGrayscale = $this->service->isInGrayscale($featureKey, $userId, $ip);

            // 获取灰度计划用于日志记录
            $plan = $this->getActivePlan($featureKey);
            if ($plan) {
                $this->service->logAccess((int) $plan['id'], $userId, $ip, $inGrayscale);
            }

            // 注入灰度状态到请求
            $request->withInput(json_encode([
                '_grayscale' => [
                    'feature_key'   => $featureKey,
                    'in_grayscale'  => $inGrayscale,
                    'plan_id'       => $plan['id'] ?? 0,
                ],
            ]));

            // 设置响应头
            $response = $next($request);
            if (method_exists($response, 'header')) {
                $response->header([
                    'X-Grayscale-Feature' => $featureKey,
                    'X-Grayscale-Status'  => $inGrayscale ? 'on' : 'off',
                ]);
            }
            return $response;
        }

        return $next($request);
    }

    /**
     * 从请求中提取灰度特征标识
     */
    protected function extractFeatureKey($request): string
    {
        // 优先从Header获取
        $featureKey = $request->header('X-Grayscale-Feature');
        if (!empty($featureKey)) {
            return $featureKey;
        }

        // 从查询参数获取
        $featureKey = $request->get('grayscale_feature');
        if (!empty($featureKey)) {
            return $featureKey;
        }

        // 从路由配置匹配
        $path = $request->pathinfo();
        $grayscaleRoutes = Config::get('grayscale.routes', []);
        foreach ($grayscaleRoutes as $route => $key) {
            if (str_starts_with($path, $route)) {
                return $key;
            }
        }

        return '';
    }

    /**
     * 获取活跃的灰度计划
     */
    protected function getActivePlan(string $featureKey): ?array
    {
        try {
            return \think\facade\Db::name('grayscale_release')
                ->where('feature_key', $featureKey)
                ->where('status', GrayscaleReleaseService::STATUS_ACTIVE)
                ->find();
        } catch (\Throwable) {
            return null;
        }
    }
}
