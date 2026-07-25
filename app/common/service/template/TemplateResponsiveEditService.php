<?php
declare(strict_types=1);

namespace app\common\service\template;

use think\facade\Cache;

/**
 * 模板响应式编辑 — V2.9.33 CUS3-2
 * 设备断点管理 + 响应式CSS编辑
 */
class TemplateResponsiveEditService
{
    private const CACHE_TAG = 'template_responsive';

    /** 预置断点 */
    public const BREAKPOINTS = [
        'mobile'  => ['name' => '手机', 'max_width' => 767, 'media' => '@media (max-width: 767px)'],
        'tablet'  => ['name' => '平板', 'min_width' => 768, 'max_width' => 1023, 'media' => '@media (min-width: 768px) and (max-width: 1023px)'],
        'desktop' => ['name' => '桌面', 'min_width' => 1024, 'media' => '@media (min-width: 1024px)'],
    ];

    /**
     * 获取响应式CSS
     */
    public function getResponsiveCss(int $templateId): array
    {
        $config = \app\common\model\TemplateCustomConfig::where('template_id', $templateId)->find();
        $css = $config ? ($config->custom_css ?? '') : '';

        // 解析媒体查询块
        $breakpoints = [];
        foreach (self::BREAKPOINTS as $key => $bp) {
            $breakpoints[$key] = [
                'name' => $bp['name'],
                'media' => $bp['media'],
                'css' => $this->extractMediaQuery($css, $bp['media']),
            ];
        }

        return [
            'base_css' => $this->extractBaseCss($css),
            'breakpoints' => $breakpoints,
        ];
    }

    /**
     * 保存响应式CSS
     */
    public function saveResponsiveCss(int $templateId, array $data): array
    {
        $baseCss = $data['base_css'] ?? '';
        $breakpoints = $data['breakpoints'] ?? [];

        $css = $baseCss . "\n\n";
        foreach ($breakpoints as $key => $bpCss) {
            if (!empty($bpCss) && isset(self::BREAKPOINTS[$key])) {
                $media = self::BREAKPOINTS[$key]['media'];
                $css .= "{$media} {\n{$bpCss}\n}\n\n";
            }
        }

        $config = \app\common\model\TemplateCustomConfig::where('template_id', $templateId)->find();
        if ($config) {
            $config->custom_css = $css;
            $config->save();
        } else {
            \app\common\model\TemplateCustomConfig::create([
                'template_id' => $templateId,
                'custom_css' => $css,
            ]);
        }

        Cache::clear();
        return ['success' => true];
    }

    /**
     * 提取媒体查询内的CSS
     */
    private function extractMediaQuery(string $css, string $mediaQuery): string
    {
        $pattern = '/' . preg_quote($mediaQuery, '/') . '\s*\{([^}]*)\}/s';
        if (preg_match($pattern, $css, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    /**
     * 提取基础CSS（非媒体查询部分）
     */
    private function extractBaseCss(string $css): string
    {
        // 移除所有媒体查询块
        $base = preg_replace('/@media[^{]*\{[^{}]*\}/s', '', $css);
        return trim($base);
    }
}
