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

namespace app\common\service;

use think\facade\Cache;
use app\common\model\Config as ConfigModel;

/**
 * 模板路径解析服务
 * 统一管理前后台模板路径计算逻辑、主题扫描、切换
 */
class TemplateService
{
    // 后台模板根目录
    const ADMIN_ROOT = 'template' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR;

    // 前台模板根目录
    const THEME_ROOT = 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;

    // 默认主题名
    const DEFAULT_THEME = 'default';

    // 缓存键
    const CACHE_KEY_ADMIN_THEME = 'template_admin_theme';
    const CACHE_KEY_THEME = 'template_active_theme';

    /** @var string|null 预览主题名（中间件设置，优先于数据库配置） */
    protected static ?string $previewTheme = null;

    // ═══════════════════════════════════════════════
    //  后台模板方法
    // ═══════════════════════════════════════════════

    /**
     * 获取当前激活的后台主题名
     */
    public static function getAdminTheme(): string
    {
        return Cache::remember(self::CACHE_KEY_ADMIN_THEME, function () {
            try {
                $theme = ConfigModel::where('name', 'admin_theme')->value('value');
                $valid = !empty($theme) ? $theme : self::DEFAULT_THEME;

                // 安全校验：主题目录是否存在
                $path = root_path() . self::ADMIN_ROOT . $valid . DIRECTORY_SEPARATOR;
                if (!is_dir($path)) {
                    return self::DEFAULT_THEME;
                }
                return $valid;
            } catch (\Throwable) {
                return self::DEFAULT_THEME;
            }
        }, 600);
    }

    /**
     * 获取后台模板路径
     */
    public static function getAdminPath(?string $theme = null): string
    {
        $name = $theme ?? self::getAdminTheme();
        $path = root_path() . self::ADMIN_ROOT . $name . DIRECTORY_SEPARATOR;

        // 降级：目录不存在时回退到 default
        if (!is_dir($path)) {
            $path = root_path() . self::ADMIN_ROOT . self::DEFAULT_THEME . DIRECTORY_SEPARATOR;
        }
        return $path;
    }

    /**
     * 扫描所有可用后台模板
     */
    public static function scanAdminThemes(): array
    {
        $adminDir = root_path() . self::ADMIN_ROOT;
        if (!is_dir($adminDir)) {
            return [];
        }

        $themes = [];
        $dirs = glob($adminDir . '*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $name = basename($dir);

            // 检测截图
            $screenshot = '';
            foreach (['screenshot.png', 'screenshot.jpg', 'screenshot.webp'] as $ext) {
                if (is_file($dir . DIRECTORY_SEPARATOR . $ext)) {
                    $screenshot = $ext;
                    break;
                }
            }

            // 读取 admin_theme.json
            $configFile = $dir . DIRECTORY_SEPARATOR . 'admin_theme.json';
            $config = [];
            if (is_file($configFile)) {
                $config = json_decode(file_get_contents($configFile), true) ?: [];
            }

            // 检测核心页面
            $hasCore = is_file($dir . DIRECTORY_SEPARATOR . 'layout.html')
                    && is_file($dir . DIRECTORY_SEPARATOR . 'index.html');

            $themes[] = [
                'name'         => $name,
                'label'        => $config['name'] ?? $name,
                'description'  => $config['description'] ?? '',
                'version'      => $config['version'] ?? '1.0.0',
                'author'       => $config['author'] ?? '',
                'screenshot'   => $screenshot,
                'has_core'     => $hasCore,
            ];
        }

        return $themes;
    }

    /**
     * 清除后台主题缓存
     */
    public static function clearAdminCache(): void
    {
        Cache::delete(self::CACHE_KEY_ADMIN_THEME);
    }

    // ═══════════════════════════════════════════════
    //  前台模板方法
    // ═══════════════════════════════════════════════

    /**
     * 获取当前设备类型（基于 UA）
     */

    /**
     * 获取当前设备类型（基于 UA）
     */
    public static function getDeviceType(): string
    {
        $ua = request()->server('HTTP_USER_AGENT', '');

        $mobileKeywords = ['Mobile', 'Android', 'iPhone', 'iPad', 'iPod'];
        foreach ($mobileKeywords as $keyword) {
            if (stripos($ua, $keyword) !== false) {
                return 'mobile';
            }
        }
        return 'pc';
    }

    /**
     * 获取前台模板路径（核心方法）
     *
     * 降级链：
     *   1. themes/{theme}/{device}/
     *   2. themes/{theme}/pc/              （主题不支持此设备 → 降级 PC）
     *   3. themes/default/{device}/        （主题目录不存在 → 降级默认主题）
     */
    public static function getFrontendPath(): string
    {
        $theme = self::getActiveTheme();
        $device = self::getDeviceType();
        $root = root_path();

        // 1. 当前主题 + 当前设备
        $path = $root . self::THEME_ROOT . $theme . DIRECTORY_SEPARATOR . $device . DIRECTORY_SEPARATOR;
        if (is_dir($path)) {
            return $path;
        }

        // 2. 当前主题 + PC
        $pcPath = $root . self::THEME_ROOT . $theme . DIRECTORY_SEPARATOR . 'pc' . DIRECTORY_SEPARATOR;
        if (is_dir($pcPath)) {
            return $pcPath;
        }

        // 3. 默认主题 + 当前设备
        $defaultPath = $root . self::THEME_ROOT . self::DEFAULT_THEME . DIRECTORY_SEPARATOR . $device . DIRECTORY_SEPARATOR;
        if (is_dir($defaultPath)) {
            return $defaultPath;
        }

        // 最终回退：默认主题 + PC
        return $root . self::THEME_ROOT . self::DEFAULT_THEME . DIRECTORY_SEPARATOR . 'pc' . DIRECTORY_SEPARATOR;
    }

    /**
     * 扫描所有可用前台主题
     */
    public static function scanThemes(): array
    {
        $themesDir = root_path() . self::THEME_ROOT;
        if (!is_dir($themesDir)) {
            return [];
        }

        // 需要排除的非主题目录
        $excludeDirs = ['shared'];

        $themes = [];
        $dirs = glob($themesDir . '*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $name = basename($dir);

            // 跳过非主题目录
            if (in_array($name, $excludeDirs, true)) {
                continue;
            }

            $configFile = $dir . DIRECTORY_SEPARATOR . 'theme.json';
            $configFile = $dir . DIRECTORY_SEPARATOR . 'theme.json';
            $config = [];
            if (is_file($configFile)) {
                $config = json_decode(file_get_contents($configFile), true) ?: [];
            }

            $screenshot = '';
            foreach (['screenshot.png', 'screenshot.jpg', 'screenshot.webp'] as $ext) {
                if (is_file($dir . DIRECTORY_SEPARATOR . $ext)) {
                    $screenshot = $ext;
                    break;
                }
            }

            $supports = [];
            foreach (['pc', 'mobile'] as $device) {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $device)) {
                    $supports[] = $device;
                }
            }

            $themes[] = [
                'name'        => $name,
                'label'       => $config['name'] ?? $name,
                'description' => $config['description'] ?? '',
                'version'     => $config['version'] ?? '1.0.0',
                'author'      => $config['author'] ?? '',
                'screenshot'  => $screenshot,
                'supports'    => $supports,
            ];
        }

        return $themes;
    }

    /**
     * 设置预览主题（由ThemePreviewMiddleware调用）
     */
    public static function setPreviewTheme(string $themeName): void
    {
        self::$previewTheme = $themeName;
    }

    /**
     * 清除预览主题
     */
    public static function clearPreviewTheme(): void
    {
        self::$previewTheme = null;
    }

    /**
     * 获取当前激活的前台主题名（支持预览覆写）
     */
    public static function getActiveTheme(): string
    {
        // 预览模式优先
        if (self::$previewTheme !== null) {
            return self::$previewTheme;
        }

        return Cache::remember(self::CACHE_KEY_THEME, function () {
            try {
                $theme = ConfigModel::where('name', 'frontend_theme')->value('value');
                return !empty($theme) ? $theme : self::DEFAULT_THEME;
            } catch (\Throwable) {
                return self::DEFAULT_THEME;
            }
        }, 3600);
    }

    /**
     * 清除前台主题缓存
     */
    public static function clearCache(): void
    {
        Cache::delete(self::CACHE_KEY_THEME);
        // 清除整页缓存（切换主题后旧缓存页面失效）
        \think\facade\Cache::tag(CacheService::TAG_PAGE_CACHE)->clear();
    }
}
