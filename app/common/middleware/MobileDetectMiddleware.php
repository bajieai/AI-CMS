<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint UX2: 移动端检测中间件
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\middleware;

use think\Request;
use think\Response;

/**
 * 移动端检测中间件 - V2.9.31 UX2-1
 * 基于User-Agent正则匹配，不依赖第三方库
 */
class MobileDetectMiddleware
{
    /**
     * 移动端User-Agent正则模式
     */
    private const MOBILE_PATTERNS = [
        'phone' => [
            'iPhone', 'iPod', 'Android.*Mobile', 'Windows Phone',
            'BlackBerry', 'BB10', 'Opera Mini', 'IEMobile',
            'Mobile.*Firefox', 'Mobile.*Chrome', 'Mobile.*Safari',
        ],
        'tablet' => [
            'iPad', 'Android(?!.*Mobile)', 'Tablet', 'Kindle',
            'Silk', 'PlayBook', 'Transformer',
        ],
    ];

    /**
     * 处理请求
     */
    public function handle($request, \Closure $next)
    {
        $userAgent = $request->header('User-Agent') ?? '';
        $isMobile = $this->isMobile($userAgent);
        $isTablet = $this->isTablet($userAgent);

        // 设置请求属性供后续使用
        $request->isMobile = $isMobile;
        $request->isTablet = $isTablet;
        $request->isDesktop = !$isMobile && !$isTablet;

        // 注入模板变量
        if (method_exists($request, 'app')) {
            $app = $request->app();
            if (method_exists($app, 'config')) {
                $viewConfig = $app->config->get('view');
                if (is_array($viewConfig)) {
                    $viewConfig['is_mobile'] = $isMobile;
                    $viewConfig['is_tablet'] = $isTablet;
                    $app->config->set($viewConfig, 'view');
                }
            }
        }

        return $next($request);
    }

    /**
     * 检测是否为手机
     */
    public function isMobile(string $userAgent): bool
    {
        foreach (self::MOBILE_PATTERNS['phone'] as $pattern) {
            if (preg_match('/' . $pattern . '/i', $userAgent)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 检测是否为平板
     */
    public function isTablet(string $userAgent): bool
    {
        foreach (self::MOBILE_PATTERNS['tablet'] as $pattern) {
            if (preg_match('/' . $pattern . '/i', $userAgent)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取设备类型文本
     */
    public function getDeviceType(string $userAgent): string
    {
        if ($this->isTablet($userAgent)) {
            return 'tablet';
        }
        if ($this->isMobile($userAgent)) {
            return 'mobile';
        }
        return 'desktop';
    }
}
