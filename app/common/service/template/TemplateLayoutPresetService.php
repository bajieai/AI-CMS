<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint CUS: 模板布局预设服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\template;

use think\facade\Cache;

/**
 * 模板布局预设服务 - V2.9.31 CUS-1
 * 提供官方布局预设方案，支持一键应用
 */
class TemplateLayoutPresetService
{
    private const string CACHE_TAG = 'template_layout_preset';

    /**
     * 官方布局预设方案
     */
    private const PRESETS = [
        'corporate' => [
            'name' => '企业官网',
            'description' => '适合企业展示的标准布局',
            'sections' => [
                ['id' => 'hero', 'name' => '首屏大图', 'visible' => true, 'sort' => 1],
                ['id' => 'features', 'name' => '功能特色', 'visible' => true, 'sort' => 2],
                ['id' => 'about', 'name' => '关于我们', 'visible' => true, 'sort' => 3],
                ['id' => 'news', 'name' => '最新动态', 'visible' => true, 'sort' => 4],
                ['id' => 'contact', 'name' => '联系我们', 'visible' => true, 'sort' => 5],
            ],
        ],
        'landing' => [
            'name' => '落地页',
            'description' => '高转化率的单页营销布局',
            'sections' => [
                ['id' => 'hero', 'name' => '首屏大图', 'visible' => true, 'sort' => 1],
                ['id' => 'features', 'name' => '功能特色', 'visible' => true, 'sort' => 2],
                ['id' => 'pricing', 'name' => '价格方案', 'visible' => true, 'sort' => 3],
                ['id' => 'faq', 'name' => '常见问题', 'visible' => true, 'sort' => 4],
                ['id' => 'contact', 'name' => '联系我们', 'visible' => true, 'sort' => 5],
            ],
        ],
        'portfolio' => [
            'name' => '作品展示',
            'description' => '适合设计师/摄影师的作品集布局',
            'sections' => [
                ['id' => 'hero', 'name' => '首屏大图', 'visible' => true, 'sort' => 1],
                ['id' => 'gallery', 'name' => '图库展示', 'visible' => true, 'sort' => 2],
                ['id' => 'about', 'name' => '关于我们', 'visible' => true, 'sort' => 3],
                ['id' => 'contact', 'name' => '联系我们', 'visible' => true, 'sort' => 4],
            ],
        ],
        'blog' => [
            'name' => '博客资讯',
            'description' => '内容导向的博客/资讯布局',
            'sections' => [
                ['id' => 'hero', 'name' => '首屏大图', 'visible' => true, 'sort' => 1],
                ['id' => 'news', 'name' => '最新动态', 'visible' => true, 'sort' => 2],
                ['id' => 'features', 'name' => '功能特色', 'visible' => true, 'sort' => 3],
                ['id' => 'about', 'name' => '关于我们', 'visible' => true, 'sort' => 4],
            ],
        ],
        'minimal' => [
            'name' => '极简风格',
            'description' => '简洁干净的极简布局',
            'sections' => [
                ['id' => 'hero', 'name' => '首屏大图', 'visible' => true, 'sort' => 1],
                ['id' => 'about', 'name' => '关于我们', 'visible' => true, 'sort' => 2],
                ['id' => 'contact', 'name' => '联系我们', 'visible' => true, 'sort' => 3],
            ],
        ],
    ];

    /**
     * 获取所有预设方案
     */
    public function getAll(): array
    {
        return self::PRESETS;
    }

    /**
     * 获取单个预设
     */
    public function get(string $key): ?array
    {
        return self::PRESETS[$key] ?? null;
    }

    /**
     * 应用预设方案到用户配置
     */
    public function apply(int $memberId, string $themeSlug, string $presetKey): bool
    {
        $preset = $this->get($presetKey);
        if (empty($preset)) {
            return false;
        }

        $customizeService = new TemplateCustomizeService();
        $customizeService->saveLayoutConfig($memberId, $themeSlug, $preset['sections']);

        Cache::clear();
        return true;
    }

    /**
     * 获取预设预览信息
     */
    public function getPreview(string $presetKey): array
    {
        $preset = $this->get($presetKey);
        if (empty($preset)) {
            return [];
        }

        return [
            'name' => $preset['name'],
            'description' => $preset['description'],
            'section_count' => count($preset['sections']),
            'sections' => array_column($preset['sections'], 'name'),
        ];
    }
}
