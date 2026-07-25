<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint PERF: CDN辅助函数
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

/**
 * CDN辅助服务 - V2.9.31 PERF-2
 * 提供CDN URL生成和静态资源优化
 */
class CdnHelperService
{
    /**
     * CDN配置
     */
    private static array $config = [];

    /**
     * 初始化配置
     */
    public static function init(): void
    {
        if (empty(self::$config)) {
            self::$config = config('cdn') ?: [
                'enabled' => false,
                'domain' => '',
                'static_version' => 'v1',
            ];
        }
    }

    /**
     * 获取CDN URL
     */
    public static function url(string $path): string
    {
        self::init();

        if (empty(self::$config['enabled']) || empty(self::$config['domain'])) {
            return $path;
        }

        // 如果已经是完整URL，直接返回
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $domain = rtrim(self::$config['domain'], '/');
        $path = ltrim($path, '/');

        return $domain . '/' . $path;
    }

    /**
     * 获取带版本号的静态资源URL
     */
    public static function asset(string $path): string
    {
        self::init();

        $version = self::$config['static_version'] ?? 'v1';
        $separator = str_contains($path, '?') ? '&' : '?';

        return self::url($path) . $separator . '_v=' . $version;
    }

    /**
     * 生成CSS链接
     */
    public static function css(string $path): string
    {
        return '<link rel="stylesheet" href="' . self::asset($path) . '">';
    }

    /**
     * 生成JS脚本链接
     */
    public static function js(string $path): string
    {
        return '<script src="' . self::asset($path) . '"></script>';
    }

    /**
     * 是否启用CDN
     */
    public static function isEnabled(): bool
    {
        self::init();
        return !empty(self::$config['enabled']) && !empty(self::$config['domain']);
    }
}
