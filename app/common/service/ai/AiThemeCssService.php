<?php
declare(strict_types=1);

namespace app\common\service\ai;

use think\facade\Cache;

/**
 * AI主题CSS定制服务 — V2.9.26 R-4
 *
 * 功能：CSS变量编辑 + 实时预览 + AI预设方案生成
 */
class AiThemeCssService
{
    /**
     * 获取CSS变量预设方案
     */
    public function getPresetSchemes(): array
    {
        return [
            'modern_dark' => [
                'name' => '现代深色',
                'vars' => ['--primary' => '#6366f1', '--bg' => '#1a1a2e', '--text' => '#e0e0e0'],
            ],
            'business_blue' => [
                'name' => '商务蓝',
                'vars' => ['--primary' => '#2563eb', '--bg' => '#ffffff', '--text' => '#1e293b'],
            ],
            'warm_coral' => [
                'name' => '暖珊瑚',
                'vars' => ['--primary' => '#f97316', '--bg' => '#fffbeb', '--text' => '#451a03'],
            ],
            'tech_green' => [
                'name' => '科技绿',
                'vars' => ['--primary' => '#10b981', '--bg' => '#f0fdf4', '--text' => '#064e3b'],
            ],
        ];
    }

    /**
     * AI生成CSS预设方案
     */
    public function generatePreset(string $description, string $style = 'modern'): array
    {
        $providerFactory = new AiProviderFactory();
        try {
            $provider = $providerFactory->getDefaultProvider();
            $result = $provider->chat([
                ['role' => 'system', 'content' => 'You are a CSS designer. Generate CSS variables based on the description. Output only JSON.'],
                ['role' => 'user', 'content' => "Generate CSS custom properties for a theme with: {$description}, style: {$style}. Format: {\"--primary\":\"#hex\",\"--bg\":\"#hex\",\"--text\":\"#hex\",\"--accent\":\"#hex\",\"--border\":\"#hex\"}"],
            ]);
            $cssVars = json_decode($result['content'] ?? '{}', true);
            return ['success' => true, 'vars' => $cssVars ?: []];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 生成CSS预览代码
     */
    public function generatePreviewCss(array $vars): string
    {
        $css = ":root {\n";
        foreach ($vars as $name => $value) {
            $css .= "  {$name}: {$value};\n";
        }
        $css .= "}\n";
        return $css;
    }

    /**
     * 应用CSS变量到主题
     */
    public function applyToTheme(int $themeId, array $vars): array
    {
        $css = $this->generatePreviewCss($vars);
        $themePath = root_path() . 'template/themes/' . $themeId . '/assets/css/custom_vars.css';
        $dir = dirname($themePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($themePath, $css);
        return ['success' => true, 'message' => 'CSS变量已应用'];
    }
}
