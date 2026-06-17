<?php
/**
 * V2.9.23 C-1: 模板自定义服务
 * 配色/布局读写、应用、预览、持久化
 */

namespace app\common\service;

use app\common\model\TemplateCustomConfig;
use app\common\model\TemplatePresetColor;
use think\facade\Cache;

class TemplateCustomizeService
{
    /**
     * 获取用户主题配置（带缓存）
     */
    public function getUserConfig(int $memberId, string $themeSlug): array
    {
        $cacheKey = 'theme_config_' . $memberId . '_' . $themeSlug;
        return Cache::remember($cacheKey, function () use ($memberId, $themeSlug) {
            return TemplateCustomConfig::getThemeConfig($memberId, $themeSlug);
        }, 3600);
    }

    /**
     * 保存用户主题配置
     */
    public function saveUserConfig(int $memberId, string $themeSlug, array $config): bool
    {
        $result = TemplateCustomConfig::setConfigs($memberId, $themeSlug, $config);
        // 清除缓存
        Cache::delete('theme_config_' . $memberId . '_' . $themeSlug);
        return $result;
    }

    /**
     * 获取CSS变量数组（用于模板注入）
     */
    public function getCssVars(int $memberId, string $themeSlug): array
    {
        $config = $this->getUserConfig($memberId, $themeSlug);
        $defaults = $this->getDefaultCssVars();

        // 合并用户配置和默认值
        $vars = $defaults;
        foreach ($config as $key => $value) {
            if (isset($vars[$key])) {
                $vars[$key] = $value;
            }
        }

        return $vars;
    }

    /**
     * 应用配色方案
     */
    public function applyColorScheme(int $memberId, string $themeSlug, array $colors): bool
    {
        $config = [];
        $mapping = [
            'primary' => '--primary',
            'secondary' => '--secondary',
            'bg' => '--bg',
            'text' => '--text',
            'heading' => '--font-heading',
            'link' => '--primary',
            'accent' => '--accent',
        ];

        foreach ($mapping as $colorKey => $cssVar) {
            if (isset($colors[$colorKey])) {
                $config[$cssVar] = $colors[$colorKey];
            }
        }

        return $this->saveUserConfig($memberId, $themeSlug, $config);
    }

    /**
     * 获取预设配色方案列表
     */
    public function getPresetColors(?string $industry = null): array
    {
        $query = TemplatePresetColor::where('is_system', 1)->order('sort ASC');

        if (!empty($industry)) {
            $query->whereFindInSet('industry_tags', $industry);
        }

        $presets = $query->limit(8)->select();

        // 如果行业匹配不够，补充默认推荐
        if (count($presets) < 5 && !empty($industry)) {
            $extraIds = $presets->column('id');
            $defaults = TemplatePresetColor::where('is_system', 1)
                ->whereNotIn('id', $extraIds)
                ->order('sort ASC')
                ->limit(5 - count($presets))
                ->select();
            $presets = array_merge($presets->toArray(), $defaults->toArray());
        }

        return $presets;
    }

    /**
     * 获取布局方案列表
     */
    public function getLayoutPresets(): array
    {
        return [
            ['id' => 'classic', 'name' => '经典布局', 'description' => '侧边栏+内容区经典结构', 'vars' => ['--sidebar-pos' => 'left', '--content-width' => '1140px']],
            ['id' => 'wide', 'name' => '宽屏布局', 'description' => '全宽内容，无侧边栏', 'vars' => ['--sidebar-pos' => 'none', '--content-width' => '100%']],
            ['id' => 'center', 'name' => '居中布局', 'description' => '内容居中，最大宽度限制', 'vars' => ['--sidebar-pos' => 'none', '--content-width' => '960px']],
            ['id' => 'card', 'name' => '卡片布局', 'description' => '卡片式内容展示', 'vars' => ['--sidebar-pos' => 'right', '--content-width' => '1200px', '--radius' => '12px', '--shadow' => '0 4px 12px rgba(0,0,0,0.1)']],
        ];
    }

    /**
     * 默认CSS变量
     */
    public function getDefaultCssVars(): array
    {
        return [
            '--primary' => '#0d6efd',
            '--secondary' => '#6c757d',
            '--accent' => '#dc3545',
            '--bg' => '#ffffff',
            '--bg-secondary' => '#f8f9fa',
            '--text' => '#212529',
            '--text-secondary' => '#6c757d',
            '--border' => '#dee2e6',
            '--radius' => '6px',
            '--shadow' => '0 2px 8px rgba(0,0,0,0.08)',
            '--font-heading' => 'system-ui, -apple-system, sans-serif',
            '--font-body' => 'system-ui, -apple-system, sans-serif',
            '--sidebar-pos' => 'left',
            '--content-width' => '1140px',
            '--header-style' => 'default',
            '--logo-max-height' => '60px',
            '--btn-primary-bg' => '#0d6efd',
            '--btn-primary-hover' => '#0b5ed7',
        ];
    }

    /**
     * AI生成配色方案（调用DeepSeek API）
     */
    public function generateAIColors(string $description): ?array
    {
        try {
            $aiService = new AiTemplateService();
            $prompt = $this->buildColorPrompt($description);
            $response = $aiService->callDeepSeek($prompt);

            if (empty($response)) {
                return null;
            }

            // 尝试解析JSON响应
            $colors = $this->parseColorResponse($response);
            if ($colors && $this->validateColors($colors)) {
                return $colors;
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 构建AI配色提示词
     */
    private function buildColorPrompt(string $description): string
    {
        return "请为网站生成一组配色方案。要求：\n"
            . "1. 风格描述：{$description}\n"
            . "2. 输出格式：JSON对象，包含以下7个字段\n"
            . "3. 字段要求：\n"
            . "   - primary: 主色调（按钮、链接、重点元素）\n"
            . "   - secondary: 辅助色（次要按钮、标签）\n"
            . "   - bg: 背景色（页面主背景）\n"
            . "   - text: 文字色（正文文字）\n"
            . "   - heading: 标题色（标题文字）\n"
            . "   - link: 链接色（超链接）\n"
            . "   - accent: 强调色（错误提示、重要标记）\n"
            . "4. 仅输出JSON，不要其他文字\n"
            . "示例：{\"primary\":\"#0d6efd\",\"secondary\":\"#6c757d\",\"bg\":\"#ffffff\",\"text\":\"#212529\",\"heading\":\"#1a1a1a\",\"link\":\"#0d6efd\",\"accent\":\"#dc3545\"}";
    }

    /**
     * 解析AI响应中的颜色JSON
     */
    private function parseColorResponse(string $response): ?array
    {
        // 尝试提取JSON
        if (preg_match('/\{[^}]+\}/', $response, $matches)) {
            $json = json_decode($matches[0], true);
            if (is_array($json)) {
                return $json;
            }
        }

        // 直接尝试解析整个响应
        $json = json_decode($response, true);
        if (is_array($json)) {
            return $json;
        }

        return null;
    }

    /**
     * 验证颜色格式
     */
    private function validateColors(array $colors): bool
    {
        $required = ['primary', 'secondary', 'bg', 'text', 'heading', 'link', 'accent'];
        foreach ($required as $key) {
            if (empty($colors[$key]) || !preg_match('/^#[0-9a-fA-F]{6}$/', $colors[$key])) {
                return false;
            }
        }
        return true;
    }
}
