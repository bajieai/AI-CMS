<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\middleware;

use app\common\model\ThemeCustomization;
use app\common\service\TemplateService;
use think\facade\Log;

/**
 * 主题定制CSS注入中间件 - V2.9.7 Phase 1
 *
 * 工作原理：
 * 1. 检测当前主题是否有激活的定制数据
 * 2. 有定制 → 在响应HTML的</head>前注入<style>覆盖层
 * 3. 零侵入：不修改任何模板文件，定制数据完全在DB中
 *
 * 优势：
 * - 切换/重置定制无需文件IO
 * - 模板升级时定制自动保留
 * - 性能：仅一次DB查询，结果可缓存
 */
class ThemeCustomMiddleware
{
    /**
     * 缓存Key前缀
     */
    protected const CACHE_PREFIX = 'theme_custom_';

    /**
     * 缓存有效期（秒）
     */
    protected const CACHE_TTL = 300; // 5分钟

    public function handle($request, \Closure $next)
    {
        $response = $next($request);

        // 仅处理HTML响应
        $contentType = $response->getHeader('Content-Type') ?? '';
        if (!str_contains($contentType, 'text/html')) {
            return $response;
        }

        // 获取当前主题
        $themeId = TemplateService::getActiveTheme();
        if (empty($themeId)) {
            return $response;
        }

        // 尝试从缓存读取定制数据
        $cacheKey = self::CACHE_PREFIX . $themeId;
        $customData = cache($cacheKey);

        if ($customData === false || $customData === null) {
            // 从DB读取
            $customData = ThemeCustomization::getActiveCustomization($themeId);
            cache($cacheKey, $customData ?: [], self::CACHE_TTL);
        }

        // 无定制数据，直接返回
        if (empty($customData)) {
            return $response;
        }

        // 生成CSS覆盖代码
        $overrideCss = ThemeCustomization::generateOverrideCss($customData);
        if (empty($overrideCss)) {
            return $response;
        }

        // 在</head>前注入字体CSS + 定制CSS覆盖层
        $content = $response->getContent();
        $fontLink = '<link rel="stylesheet" href="/assets/css/theme-fonts.css">';
        $injectTag = $fontLink . "\n<style class=\"theme-custom-override\">\n/* V2.9.8 Theme Customization */\n{$overrideCss}\n</style>";

        if (str_contains($content, '</head>')) {
            $content = str_replace('</head>', $injectTag . "\n</head>", $content);
            $response->content($content);

            Log::debug("[ThemeCustomMiddleware] 注入字体CSS+定制CSS: theme={$themeId}");
        }

        return $response;
    }

    /**
     * 清除指定主题的定制缓存
     *
     * @param string $themeId
     */
    public static function clearCache(string $themeId): void
    {
        cache(self::CACHE_PREFIX . $themeId, null);
    }

    /**
     * 清除所有主题的定制缓存
     */
    public static function clearAllCache(): void
    {
        // 扫描所有主题清除缓存
        $themes = TemplateService::scanThemes();
        foreach ($themes as $theme) {
            $name = $theme['dirname'] ?? $theme['name'] ?? '';
            if ($name) {
                cache(self::CACHE_PREFIX . $name, null);
            }
        }
    }
}
