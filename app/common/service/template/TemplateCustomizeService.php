<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateCustomConfig;

/**
 * 模板自定义服务 - V2.9.12
 *
 * 提供样式配置（颜色/字体/Logo）和布局配置的读写、应用、预览功能
 */
class TemplateCustomizeService
{
    /**
     * 预置字体方案
     */
    protected static array $fontPresets = [
        'default'    => ['name' => '默认', 'family' => "'Helvetica Neue', Arial, sans-serif"],
        'songti'     => ['name' => '宋体', 'family' => "'SimSun', 'Songti SC', serif"],
        'heiti'      => ['name' => '黑体', 'family' => "'SimHei', 'Heiti SC', sans-serif"],
        'yahei'      => ['name' => '微软雅黑', 'family' => "'Microsoft YaHei', 'PingFang SC', sans-serif"],
        'sans'       => ['name' => '无衬线', 'family' => "system-ui, -apple-system, sans-serif"],
    ];

    /**
     * 可配置的CSS变量映射
     */
    protected static array $styleVariableMap = [
        'primary_color'   => '--color-primary',
        'secondary_color' => '--color-secondary',
        'bg_color'        => '--color-bg',
        'text_color'      => '--color-text',
        'heading_color'   => '--color-heading',
        'link_color'      => '--color-link',
        'accent_color'    => '--color-accent',
    ];

    /**
     * 获取样式配置
     */
    public function getStyleConfig(int $memberId, string $themeSlug): array
    {
        $config = TemplateCustomConfig::getThemeConfig($memberId, $themeSlug);
        $defaults = [
            'primary_color'   => '#0d6efd',
            'secondary_color' => '#6c757d',
            'bg_color'        => '#ffffff',
            'text_color'      => '#212529',
            'heading_color'   => '#1a1a1a',
            'link_color'      => '#0d6efd',
            'accent_color'    => '#dc3545',
            'font_preset'     => 'default',
            'logo_url'        => '',
            'custom_css'      => '',
        ];
        return array_merge($defaults, $config);
    }

    /**
     * 保存样式配置
     */
    public function saveStyleConfig(int $memberId, string $themeSlug, array $data): void
    {
        $styleKeys = array_keys(self::$styleVariableMap) + ['font_preset', 'logo_url', 'custom_css'];
        foreach ($data as $key => $value) {
            if (in_array($key, $styleKeys, true)) {
                TemplateCustomConfig::setConfig($memberId, $themeSlug, $key, $value, 'style');
            }
        }
    }

    /**
     * 获取布局配置
     */
    public function getLayoutConfig(int $memberId, string $themeSlug): array
    {
        $config = TemplateCustomConfig::getThemeConfig($memberId, $themeSlug);
        $defaults = [
            'sections' => [
                ['id' => 'hero',      'name' => '首屏大图', 'visible' => true, 'sort' => 1],
                ['id' => 'features',  'name' => '功能特色', 'visible' => true, 'sort' => 2],
                ['id' => 'about',     'name' => '关于我们', 'visible' => true, 'sort' => 3],
                ['id' => 'news',      'name' => '最新动态', 'visible' => true, 'sort' => 4],
                ['id' => 'gallery',   'name' => '图库展示', 'visible' => false, 'sort' => 5],
                ['id' => 'pricing',   'name' => '价格方案', 'visible' => false, 'sort' => 6],
                ['id' => 'faq',       'name' => '常见问题', 'visible' => false, 'sort' => 7],
                ['id' => 'contact',   'name' => '联系我们', 'visible' => true, 'sort' => 8],
            ],
        ];
        if (!empty($config['sections'])) {
            $defaults['sections'] = $config['sections'];
        }
        return $defaults;
    }

    /**
     * 保存布局配置
     */
    public function saveLayoutConfig(int $memberId, string $themeSlug, array $sections): void
    {
        TemplateCustomConfig::setConfig($memberId, $themeSlug, 'sections', $sections, 'layout');
    }

    /**
     * 应用样式生成CSS
     */
    public function applyStyle(int $memberId, string $themeSlug): string
    {
        $config = $this->getStyleConfig($memberId, $themeSlug);
        $css = ":root {\n";

        foreach (self::$styleVariableMap as $configKey => $cssVar) {
            $value = $config[$configKey] ?? '';
            if ($value) {
                $css .= "  {$cssVar}: {$value};\n";
            }
        }
        $css .= "}\n";

        // 字体覆盖
        $font = self::$fontPresets[$config['font_preset'] ?? 'default'] ?? self::$fontPresets['default'];
        $css .= "body { font-family: {$font['family']}; }\n";

        // Logo
        if (!empty($config['logo_url'])) {
            $css .= ".site-logo { background-image: url('{$config['logo_url']}'); background-size: contain; background-repeat: no-repeat; }\n";
        }

        // 自定义CSS
        if (!empty($config['custom_css'])) {
            $css .= "/* Custom CSS */\n" . $config['custom_css'] . "\n";
        }

        return $css;
    }

    /**
     * 获取可用字体列表
     */
    public function getAvailableFonts(): array
    {
        return self::$fontPresets;
    }

    /**
     * 获取布局Schema定义
     */
    public function getLayoutSchema(): array
    {
        return [
            ['id' => 'hero',      'name' => '首屏大图', 'desc' => '首页顶部大图/欢迎区域', 'icon' => 'bi bi-image-alt'],
            ['id' => 'features',  'name' => '功能特色', 'desc' => '核心功能或服务亮点展示', 'icon' => 'bi bi-stars'],
            ['id' => 'about',     'name' => '关于我们', 'desc' => '公司/团队介绍区域', 'icon' => 'bi bi-building'],
            ['id' => 'news',      'name' => '最新动态', 'desc' => '新闻/文章列表区域', 'icon' => 'bi bi-newspaper'],
            ['id' => 'gallery',   'name' => '图库展示', 'desc' => '图片/作品展示区域', 'icon' => 'bi bi-images'],
            ['id' => 'pricing',   'name' => '价格方案', 'desc' => '产品/服务定价展示', 'icon' => 'bi bi-tags'],
            ['id' => 'faq',       'name' => '常见问题', 'desc' => 'FAQ问答区域', 'icon' => 'bi bi-question-circle'],
            ['id' => 'contact',   'name' => '联系我们', 'desc' => '联系表单/信息区域', 'icon' => 'bi bi-envelope'],
        ];
    }

    /**
     * 重置为官方默认（清除自定义配置）
     */
    public function resetToDefault(int $memberId, string $themeSlug): bool
    {
        return TemplateCustomConfig::clearThemeConfig($memberId, $themeSlug) > 0;
    }

    /**
     * 预编辑自动备份触发
     */
    public function preEditBackup(int $memberId, string $themeSlug): void
    {
        $config = TemplateCustomConfig::getThemeConfig($memberId, $themeSlug);
        if (empty($config)) {
            return;
        }

        // 仅保留最近5个自动备份
        $backupService = new TemplateBackupRestoreService();
        $backupService->createAutoBackup($memberId, $themeSlug, $config);
    }
}
