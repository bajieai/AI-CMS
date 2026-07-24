<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint CUS: 模板配色方案服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\template;

use think\facade\Cache;

/**
 * 模板配色方案服务 - V2.9.31 CUS-2
 * 提供官方配色方案，支持一键应用
 */
class TemplateColorSchemeService
{
    private const string CACHE_TAG = 'template_color_scheme';

    /**
     * 官方配色方案
     */
    private const SCHEMES = [
        'default' => [
            'name' => '默认蓝',
            'description' => '经典蓝色主题，适合大多数场景',
            'colors' => [
                'primary_color' => '#0d6efd',
                'secondary_color' => '#6c757d',
                'bg_color' => '#ffffff',
                'text_color' => '#212529',
                'heading_color' => '#1a1a1a',
                'link_color' => '#0d6efd',
                'accent_color' => '#dc3545',
            ],
        ],
        'forest' => [
            'name' => '森林绿',
            'description' => '自然绿色主题，适合环保/健康类网站',
            'colors' => [
                'primary_color' => '#198754',
                'secondary_color' => '#6c757d',
                'bg_color' => '#f8f9fa',
                'text_color' => '#212529',
                'heading_color' => '#1a1a1a',
                'link_color' => '#198754',
                'accent_color' => '#fd7e14',
            ],
        ],
        'sunset' => [
            'name' => '落日橙',
            'description' => '温暖橙色主题，适合餐饮/生活方式',
            'colors' => [
                'primary_color' => '#fd7e14',
                'secondary_color' => '#6c757d',
                'bg_color' => '#fff8f0',
                'text_color' => '#333333',
                'heading_color' => '#1a1a1a',
                'link_color' => '#fd7e14',
                'accent_color' => '#dc3545',
            ],
        ],
        'ocean' => [
            'name' => '深海蓝',
            'description' => '深邃蓝色主题，适合科技/金融类网站',
            'colors' => [
                'primary_color' => '#0dcaf0',
                'secondary_color' => '#495057',
                'bg_color' => '#f0f8ff',
                'text_color' => '#212529',
                'heading_color' => '#0a192f',
                'link_color' => '#0dcaf0',
                'accent_color' => '#6610f2',
            ],
        ],
        'rose' => [
            'name' => '玫瑰粉',
            'description' => '柔和粉色主题，适合女性/时尚类网站',
            'colors' => [
                'primary_color' => '#d63384',
                'secondary_color' => '#6f42c1',
                'bg_color' => '#fff0f5',
                'text_color' => '#333333',
                'heading_color' => '#1a1a1a',
                'link_color' => '#d63384',
                'accent_color' => '#ff6b6b',
            ],
        ],
        'dark' => [
            'name' => '暗夜黑',
            'description' => '深色模式主题，适合创意/科技类网站',
            'colors' => [
                'primary_color' => '#0d6efd',
                'secondary_color' => '#adb5bd',
                'bg_color' => '#1a1a2e',
                'text_color' => '#e9ecef',
                'heading_color' => '#ffffff',
                'link_color' => '#0d6efd',
                'accent_color' => '#20c997',
            ],
        ],
        'purple' => [
            'name' => '高贵紫',
            'description' => '神秘紫色主题，适合艺术/设计类网站',
            'colors' => [
                'primary_color' => '#6f42c1',
                'secondary_color' => '#6c757d',
                'bg_color' => '#faf5ff',
                'text_color' => '#333333',
                'heading_color' => '#2d1b4e',
                'link_color' => '#6f42c1',
                'accent_color' => '#e83e8c',
            ],
        ],
        'gold' => [
            'name' => '奢华金',
            'description' => '金色奢华主题，适合珠宝/高端品牌',
            'colors' => [
                'primary_color' => '#d4af37',
                'secondary_color' => '#8b6914',
                'bg_color' => '#fffef5',
                'text_color' => '#3d3d3d',
                'heading_color' => '#1a1a1a',
                'link_color' => '#d4af37',
                'accent_color' => '#8b0000',
            ],
        ],
        'mint' => [
            'name' => '清新薄荷',
            'description' => '清凉薄荷色主题，适合医疗/健康类网站',
            'colors' => [
                'primary_color' => '#20c997',
                'secondary_color' => '#6c757d',
                'bg_color' => '#f0fff4',
                'text_color' => '#333333',
                'heading_color' => '#1a1a1a',
                'link_color' => '#20c997',
                'accent_color' => '#fd7e14',
            ],
        ],
        'coral' => [
            'name' => '珊瑚红',
            'description' => '活力珊瑚色主题，适合旅游/生活类网站',
            'colors' => [
                'primary_color' => '#ff6b6b',
                'secondary_color' => '#6c757d',
                'bg_color' => '#fff5f5',
                'text_color' => '#333333',
                'heading_color' => '#1a1a1a',
                'link_color' => '#ff6b6b',
                'accent_color' => '#4ecdc4',
            ],
        ],
        'lavender' => [
            'name' => '薰衣草',
            'description' => '柔和薰衣草色，适合SPA/美容类网站',
            'colors' => [
                'primary_color' => '#9b8cf2',
                'secondary_color' => '#6c757d',
                'bg_color' => '#f8f5ff',
                'text_color' => '#333333',
                'heading_color' => '#1a1a1a',
                'link_color' => '#9b8cf2',
                'accent_color' => '#e91e63',
            ],
        ],
        'slate' => [
            'name' => '商务灰',
            'description' => '沉稳灰色主题，适合企业/法律类网站',
            'colors' => [
                'primary_color' => '#495057',
                'secondary_color' => '#adb5bd',
                'bg_color' => '#f8f9fa',
                'text_color' => '#212529',
                'heading_color' => '#1a1a1a',
                'link_color' => '#495057',
                'accent_color' => '#dc3545',
            ],
        ],
        'teal' => [
            'name' => '青绿色',
            'description' => '清新青绿色主题，适合教育/科技类网站',
            'colors' => [
                'primary_color' => '#0dcaf0',
                'secondary_color' => '#6c757d',
                'bg_color' => '#f0fdff',
                'text_color' => '#333333',
                'heading_color' => '#1a1a1a',
                'link_color' => '#0dcaf0',
                'accent_color' => '#fd7e14',
            ],
        ],
        'indigo' => [
            'name' => '靛蓝色',
            'description' => '深邃靛蓝主题，适合金融/咨询类网站',
            'colors' => [
                'primary_color' => '#6610f2',
                'secondary_color' => '#6c757d',
                'bg_color' => '#f8f6ff',
                'text_color' => '#333333',
                'heading_color' => '#1a1a1a',
                'link_color' => '#6610f2',
                'accent_color' => '#fd7e14',
            ],
        ],
        'crimson' => [
            'name' => '深红色',
            'description' => '热情深红色主题，适合餐饮/节庆类网站',
            'colors' => [
                'primary_color' => '#dc3545',
                'secondary_color' => '#6c757d',
                'bg_color' => '#fff5f5',
                'text_color' => '#333333',
                'heading_color' => '#1a1a1a',
                'link_color' => '#dc3545',
                'accent_color' => '#fd7e14',
            ],
        ],
        'emerald' => [
            'name' => '翡翠绿',
            'description' => '宝石翡翠绿主题，适合环保/农业类网站',
            'colors' => [
                'primary_color' => '#10b981',
                'secondary_color' => '#6c757d',
                'bg_color' => '#f0fdf4',
                'text_color' => '#333333',
                'heading_color' => '#1a1a1a',
                'link_color' => '#10b981',
                'accent_color' => '#f59e0b',
            ],
        ],
        'amber' => [
            'name' => '琥珀黄',
            'description' => '温暖琥珀色主题，适合咖啡/烘焙类网站',
            'colors' => [
                'primary_color' => '#f59e0b',
                'secondary_color' => '#6c757d',
                'bg_color' => '#fffbeb',
                'text_color' => '#333333',
                'heading_color' => '#1a1a1a',
                'link_color' => '#f59e0b',
                'accent_color' => '#dc3545',
            ],
        ],
        'cyan' => [
            'name' => '青色',
            'description' => '清爽青色主题，适合水产/清洁类网站',
            'colors' => [
                'primary_color' => '#06b6d4',
                'secondary_color' => '#6c757d',
                'bg_color' => '#f0fdff',
                'text_color' => '#333333',
                'heading_color' => '#1a1a1a',
                'link_color' => '#06b6d4',
                'accent_color' => '#f59e0b',
            ],
        ],
        'graphite' => [
            'name' => '石墨黑',
            'description' => '专业石墨色深色主题，适合开发者/技术类网站',
            'colors' => [
                'primary_color' => '#3b82f6',
                'secondary_color' => '#9ca3af',
                'bg_color' => '#18181b',
                'text_color' => '#e4e4e7',
                'heading_color' => '#ffffff',
                'link_color' => '#3b82f6',
                'accent_color' => '#10b981',
            ],
        ],
        'cherry' => [
            'name' => '樱花粉',
            'description' => '温柔樱花粉色，适合婚庆/花艺类网站',
            'colors' => [
                'primary_color' => '#ec4899',
                'secondary_color' => '#6c757d',
                'bg_color' => '#fdf2f8',
                'text_color' => '#333333',
                'heading_color' => '#1a1a1a',
                'link_color' => '#ec4899',
                'accent_color' => '#8b5cf6',
            ],
        ],
        'navy' => [
            'name' => '海军蓝',
            'description' => '经典海军蓝主题，适合物流/制造类网站',
            'colors' => [
                'primary_color' => '#1e3a8a',
                'secondary_color' => '#6c757d',
                'bg_color' => '#f0f4ff',
                'text_color' => '#333333',
                'heading_color' => '#1a1a1a',
                'link_color' => '#1e3a8a',
                'accent_color' => '#f59e0b',
            ],
        ],
    ];

    /**
     * 行业分类映射
     */
    private const INDUSTRY_MAP = [
        'default'  => 'general',   'forest'   => 'medical',
        'sunset'   => 'food',      'ocean'    => 'tech',
        'rose'     => 'fashion',   'dark'     => 'tech',
        'purple'   => 'art',       'gold'     => 'finance',
        'mint'     => 'medical',   'coral'    => 'travel',
        'lavender' => 'beauty',    'slate'    => 'business',
        'teal'     => 'education', 'indigo'   => 'finance',
        'crimson'  => 'food',      'emerald'  => 'agriculture',
        'amber'    => 'food',      'cyan'     => 'tech',
        'graphite' => 'tech',      'cherry'   => 'wedding',
        'navy'     => 'logistics',
    ];

    /**
     * 色系分类映射
     */
    private const COLOR_SYSTEM_MAP = [
        'default' => 'cool', 'forest' => 'cool', 'sunset' => 'warm',
        'ocean' => 'cool', 'rose' => 'warm', 'dark' => 'neutral',
        'purple' => 'cool', 'gold' => 'warm', 'mint' => 'cool',
        'coral' => 'warm', 'lavender' => 'cool', 'slate' => 'neutral',
        'teal' => 'cool', 'indigo' => 'cool', 'crimson' => 'warm',
        'emerald' => 'cool', 'amber' => 'warm', 'cyan' => 'cool',
        'graphite' => 'neutral', 'cherry' => 'warm', 'navy' => 'cool',
    ];

    /**
     * 风格分类映射
     */
    private const STYLE_MAP = [
        'default' => 'professional', 'forest' => 'professional', 'sunset' => 'creative',
        'ocean' => 'professional', 'rose' => 'creative', 'dark' => 'minimal',
        'purple' => 'creative', 'gold' => 'professional', 'mint' => 'professional',
        'coral' => 'creative', 'lavender' => 'creative', 'slate' => 'professional',
        'teal' => 'professional', 'indigo' => 'professional', 'crimson' => 'creative',
        'emerald' => 'professional', 'amber' => 'creative', 'cyan' => 'professional',
        'graphite' => 'minimal', 'cherry' => 'creative', 'navy' => 'professional',
    ];

    /**
     * 获取所有配色方案（含7维颜色补全）
     */
    public function getAll(): array
    {
        $schemes = self::SCHEMES;
        foreach ($schemes as $key => &$scheme) {
            $scheme['colors'] = $this->ensureFullColors($scheme['colors']);
            $scheme['industry'] = self::INDUSTRY_MAP[$key] ?? 'general';
            $scheme['color_system'] = self::COLOR_SYSTEM_MAP[$key] ?? 'neutral';
            $scheme['style'] = self::STYLE_MAP[$key] ?? 'professional';
        }
        return $schemes;
    }

    /**
     * 获取单个配色方案（含7维颜色补全）
     */
    public function get(string $key): ?array
    {
        $scheme = self::SCHEMES[$key] ?? null;
        if ($scheme === null) return null;
        $scheme['colors'] = $this->ensureFullColors($scheme['colors']);
        $scheme['industry'] = self::INDUSTRY_MAP[$key] ?? 'general';
        $scheme['color_system'] = self::COLOR_SYSTEM_MAP[$key] ?? 'neutral';
        $scheme['style'] = self::STYLE_MAP[$key] ?? 'professional';
        return $scheme;
    }

    /**
     * 确保配色方案包含7维颜色定义
     * 主色/辅色/文字色/背景色/链接色/按钮色/悬停色
     */
    private function ensureFullColors(array $colors): array
    {
        // 按钮色默认=主色
        if (empty($colors['button_color'])) {
            $colors['button_color'] = $colors['primary_color'] ?? '#0d6efd';
        }
        // 悬停色默认=主色加深15%
        if (empty($colors['hover_color'])) {
            $colors['hover_color'] = $this->darkenColor($colors['primary_color'] ?? '#0d6efd', 15);
        }
        return $colors;
    }

    /**
     * 颜色加深
     */
    private function darkenColor(string $hex, int $percent): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) return $hex;
        $r = (int) hexdec(substr($hex, 0, 2));
        $g = (int) hexdec(substr($hex, 2, 2));
        $b = (int) hexdec(substr($hex, 4, 2));
        $factor = 1 - $percent / 100;
        $r = max(0, (int) ($r * $factor));
        $g = max(0, (int) ($g * $factor));
        $b = max(0, (int) ($b * $factor));
        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }

    /**
     * 搜索配色方案
     */
    public function search(string $industry = '', string $colorSystem = '', string $style = ''): array
    {
        $all = $this->getAll();
        $results = [];

        foreach ($all as $key => $scheme) {
            if (!empty($industry) && ($scheme['industry'] ?? '') !== $industry) continue;
            if (!empty($colorSystem) && ($scheme['color_system'] ?? '') !== $colorSystem) continue;
            if (!empty($style) && ($scheme['style'] ?? '') !== $style) continue;
            $results[$key] = $scheme;
        }

        return $results;
    }

    /**
     * 根据行业推荐配色方案
     */
    public function getRecommendByIndustry(string $industry, int $count = 3): array
    {
        $all = $this->getAll();
        $matched = [];

        foreach ($all as $key => $scheme) {
            if (($scheme['industry'] ?? '') === $industry) {
                $matched[] = ['key' => $key] + $scheme;
            }
        }

        // 如果匹配不足，补充通用配色
        if (count($matched) < $count) {
            foreach ($all as $key => $scheme) {
                if (($scheme['industry'] ?? '') === 'general') {
                    $matched[] = ['key' => $key] + $scheme;
                    if (count($matched) >= $count) break;
                }
            }
        }

        return array_slice($matched, 0, $count);
    }

    /**
     * 应用配色方案到用户配置
     */
    public function apply(int $memberId, string $themeSlug, string $schemeKey): bool
    {
        $scheme = $this->get($schemeKey);
        if (empty($scheme)) {
            return false;
        }

        $customizeService = new TemplateCustomizeService();
        $customizeService->saveStyleConfig($memberId, $themeSlug, $scheme['colors']);

        Cache::clear();
        return true;
    }

    /**
     * 获取配色预览色块
     */
    public function getPreview(string $schemeKey): array
    {
        $scheme = $this->get($schemeKey);
        if (empty($scheme)) {
            return [];
        }

        return [
            'name' => $scheme['name'],
            'description' => $scheme['description'],
            'primary' => $scheme['colors']['primary_color'],
            'accent' => $scheme['colors']['accent_color'],
            'bg' => $scheme['colors']['bg_color'],
        ];
    }
}
