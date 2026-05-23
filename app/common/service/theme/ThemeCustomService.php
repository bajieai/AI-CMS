<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\theme;

use app\common\model\ThemeCustomization;
use app\common\middleware\ThemeCustomMiddleware;
use app\common\service\TemplateService;
use think\facade\Log;

/**
 * 主题定制核心服务 - V2.9.7 Phase 1
 *
 * 职责：
 * - 读取/保存/重置定制数据
 * - 生成CSS覆盖代码
 * - 获取theme.json中的design_tokens定义
 * - V2.9.11: CSS变量已统一为无前缀，I8J_MAPPING保留用于旧数据向后兼容
 */
class ThemeCustomService
{
    /**
     * V2.9.11: --i8j-* → 无前缀 映射表（保留用于读取旧数据向后兼容）
     * 新数据已统一使用无前缀变量（--primary, --bg, --text等）
     */
    protected const I8J_MAPPING = [
        '--i8j-primary'       => '--primary',
        '--i8j-primary-hover' => '--primary',
        '--i8j-bg'            => '--bg',
        '--i8j-bg-secondary'  => '--bg-secondary',
        '--i8j-text'          => '--text',
        '--i8j-text-secondary'=> '--text-secondary',
        '--i8j-border'        => '--border',
        '--i8j-radius'        => '--radius',
        '--i8j-shadow'        => '--shadow',
    ];

    /**
     * 获取主题的默认定制参数（从theme.json读取design_tokens）
     *
     * @param string $themeId 主题目录名
     * @return array 定制参数定义
     */
    public function getDefaults(string $themeId): array
    {
        $themeJson = $this->readThemeJson($themeId);

        // 如果有design_tokens，直接使用
        if (!empty($themeJson['design_tokens']['css_vars'])) {
            return $this->formatDesignTokens($themeJson['design_tokens']);
        }

        // 否则从colors字段推导（向后兼容）
        return $this->deriveFromColors($themeId, $themeJson);
    }

    /**
     * 获取当前激活的定制数据
     *
     * @param string $themeId
     * @return array
     */
    public function getActiveCustomization(string $themeId): array
    {
        $data = ThemeCustomization::getActiveCustomization($themeId);
        return $data ?? [];
    }

    /**
     * 保存定制数据并激活
     *
     * @param string $themeId 主题目录名
     * @param array  $data    CSS变量覆盖数据
     * @param string $variant 变体名称
     * @return array ['success'=>bool, 'message'=>string]
     */
    public function saveCustomization(string $themeId, array $data, string $variant = 'default'): array
    {
        // --i8j-* 映射为无前缀
        $data = $this->mapI8jVars($data);

        $result = ThemeCustomization::saveCustomization($themeId, $data, $variant);

        if ($result['success']) {
            // 激活该变体
            ThemeCustomization::activateVariant($themeId, $variant);

            // 清除缓存
            ThemeCustomMiddleware::clearCache($themeId);
        }

        return $result;
    }

    /**
     * 重置为默认值
     *
     * @param string $themeId
     * @return array
     */
    public function resetToDefault(string $themeId): array
    {
        $success = ThemeCustomization::resetToDefault($themeId);

        if ($success) {
            ThemeCustomMiddleware::clearCache($themeId);
        }

        return [
            'success' => $success,
            'message' => $success ? '已重置为默认' : '无定制数据可重置',
        ];
    }

    /**
     * 另存为新变体
     *
     * @param string $themeId
     * @param string $variantName
     * @return array
     */
    public function saveAsVariant(string $themeId, string $variantName): array
    {
        return ThemeCustomization::saveAsVariant($themeId, $variantName);
    }

    /**
     * 获取所有变体列表
     *
     * @param string $themeId
     * @return array
     */
    public function getVariants(string $themeId): array
    {
        return ThemeCustomization::getVariants($themeId);
    }

    /**
     * 获取字体预设列表
     *
     * @return array
     */
    public function getFontPresets(): array
    {
        return ThemeCustomization::FONT_PRESETS;
    }

    /**
     * 获取布局预设列表
     *
     * @return array
     */
    public function getLayoutPresets(): array
    {
        return ThemeCustomization::LAYOUT_PRESETS;
    }

    /**
     * 获取CSS变量白名单
     *
     * @return array
     */
    public function getCssVarWhitelist(): array
    {
        return ThemeCustomization::CSS_VAR_WHITELIST;
    }

    /**
     * V2.9.8 C-1: 获取可用预设（系统预设 + theme.json presets）
     *
     * @param string $themeId
     * @return array ['system'=>[], 'theme'=>[]]
     */
    public function getAvailablePresets(string $themeId): array
    {
        $themeJson = $this->readThemeJson($themeId);
        $themePresets = $themeJson['presets'] ?? [];

        return [
            'system' => ThemeCustomization::SYSTEM_PRESETS,
            'theme'  => $themePresets,
        ];
    }

    /**
     * 生成预览CSS（不保存，仅用于预览）
     *
     * @param array $customData
     * @return string CSS代码
     */
    public function generatePreviewCss(array $customData): string
    {
        $customData = $this->mapI8jVars($customData);
        return ThemeCustomization::generateOverrideCss($customData);
    }

    /**
     * 读取theme.json
     */
    protected function readThemeJson(string $themeId): array
    {
        $path = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes'
            . DIRECTORY_SEPARATOR . $themeId . DIRECTORY_SEPARATOR . 'theme.json';

        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        return json_decode($content, true) ?: [];
    }

    /**
     * 格式化design_tokens为前端可用的结构
     */
    protected function formatDesignTokens(array $designTokens): array
    {
        $result = [
            'css_vars'  => [],
            'fonts'     => $designTokens['fonts'] ?? [],
            'groups'    => $designTokens['groups'] ?? [],
        ];

        foreach ($designTokens['css_vars'] ?? [] as $varName => $definition) {
            $result['css_vars'][$varName] = [
                'default' => $definition['default'] ?? '',
                'label'   => $definition['label'] ?? $varName,
                'type'    => $definition['type'] ?? 'text',
                'group'   => $definition['group'] ?? '颜色',
            ];
        }

        return $result;
    }

    /**
     * 从colors字段推导design_tokens（向后兼容）
     */
    protected function deriveFromColors(string $themeId, array $themeJson): array
    {
        $colors = $themeJson['colors'] ?? [];
        $defaults = [];

        // 从FrontBaseController获取默认CSS变量值
        $controller = new \app\common\controller\FrontBaseController(app());
        $reflection = new \ReflectionMethod($controller, 'getThemeCssVars');
        $reflection->setAccessible(true);
        $allDefaults = $reflection->invoke($controller, $themeId);

        // 构建css_vars定义
        $varMeta = [
            '--primary'          => ['label' => '主色', 'type' => 'color', 'group' => '颜色'],
            '--secondary'       => ['label' => '辅色', 'type' => 'color', 'group' => '颜色'],
            '--accent'          => ['label' => '强调色', 'type' => 'color', 'group' => '颜色'],
            '--bg'              => ['label' => '背景色', 'type' => 'color', 'group' => '颜色'],
            '--bg-secondary'    => ['label' => '次背景色', 'type' => 'color', 'group' => '颜色'],
            '--text'            => ['label' => '文字色', 'type' => 'color', 'group' => '颜色'],
            '--text-secondary'  => ['label' => '次文字色', 'type' => 'color', 'group' => '颜色'],
            '--border'          => ['label' => '边框色', 'type' => 'color', 'group' => '颜色'],
            '--radius'          => ['label' => '圆角', 'type' => 'text', 'group' => '样式'],
            '--shadow'          => ['label' => '阴影', 'type' => 'text', 'group' => '样式'],
            '--font-heading'    => ['label' => '标题字体', 'type' => 'font', 'group' => '字体'],
            '--font-body'       => ['label' => '正文字体', 'type' => 'font', 'group' => '字体'],
            '--sidebar-pos'     => ['label' => '侧栏位置', 'type' => 'select', 'group' => '布局', 'options' => ['left', 'right', 'none']],
            '--content-width'   => ['label' => '内容宽度', 'type' => 'select', 'group' => '布局', 'options' => ['1200px', '960px', '100%']],
            '--header-style'    => ['label' => '页头风格', 'type' => 'select', 'group' => '布局', 'options' => ['full', 'minimal']],
            '--logo-max-height' => ['label' => 'Logo高度', 'type' => 'text', 'group' => 'Logo'],
            '--btn-primary-bg'     => ['label' => '按钮主色', 'type' => 'color', 'group' => '按钮'],
            '--btn-primary-hover'  => ['label' => '按钮悬停色', 'type' => 'color', 'group' => '按钮'],
        ];

        $cssVars = [];
        foreach ($varMeta as $var => $meta) {
            $default = $allDefaults[$var] ?? '';
            // 优先使用theme.json中colors的值覆盖默认值
            if (!empty($colors) && $meta['type'] === 'color' && $var === '--primary') {
                $default = $colors['primary'] ?? $default;
            }
            $cssVars[$var] = array_merge($meta, ['default' => $default]);
        }

        return [
            'css_vars'  => $cssVars,
            'fonts'     => ThemeCustomization::FONT_PRESETS,
            'groups'    => ['颜色', '样式', '字体', '布局', 'Logo', '按钮'],
        ];
    }

    /**
     * 将--i8j-*变量映射为无前缀变量
     */
    protected function mapI8jVars(array $data): array
    {
        $mapped = [];
        foreach ($data as $key => $value) {
            if (isset(self::I8J_MAPPING[$key])) {
                $mapped[self::I8J_MAPPING[$key]] = $value;
            } else {
                $mapped[$key] = $value;
            }
        }
        return $mapped;
    }

    /**
     * V2.9.8 B-1: 智能推荐预设（文本匹配）
     * 从theme.json读取color_schema信息，匹配最接近的预设
     */
    public function recommendPreset(string $themeId): ?string
    {
        $themeJson = $this->readThemeJson($themeId);
        $schema = $themeJson['color_schema'] ?? ($themeJson['color'] ?? '');

        if (empty($schema)) {
            return null;
        }

        // 色彩倾向 → 预设key匹配
        $schemaMap = [
            'blue'    => 'cool',
            'corporate_blue' => 'deep_blue',
            'dark'    => 'calm',
            'black'   => 'midnight_black',
            'warm'    => 'warm',
            'orange'  => 'sunset_orange',
            'green'   => 'nature_green',
            'fresh'   => 'fresh',
            'purple'  => 'tech_purple',
            'pink'    => 'sakura_pink',
            'red'     => 'rose_gold',
            'rose'    => 'rose_gold',
            'vibrant' => 'vibrant',
            'tech'    => 'tech_purple',
            'deep'    => 'deep_blue',
        ];

        foreach ($schemaMap as $keyword => $presetKey) {
            if (stripos($schema, $keyword) !== false) {
                return $presetKey;
            }
        }

        return null;
    }

    /**
     * V2.9.8 C-2/B-1: 获取模板默认CSS变量值（恢复默认用）
     */
    public function getDefaultVars(string $themeId): array
    {
        return $this->getDefaults($themeId);
    }

    /**
     * V2.9.9 A-3: 基于色值距离的预设推荐（HSL+Euclidean+权重融合）
     *
     * @param string $hexColor 用户输入的HEX色值，如 #3B82F6
     * @return array 按距离排序的预设推荐列表
     */
    public function recommendPresetByColor(string $hexColor): array
    {
        $inputRgb = self::hexToRgb($hexColor);
        if (!$inputRgb) {
            return [];
        }
        $inputHsl = self::rgbToHsl($inputRgb['r'], $inputRgb['g'], $inputRgb['b']);

        $recommendations = [];
        foreach (ThemeCustomization::SYSTEM_PRESETS as $preset) {
            $presetHex = $preset['preview_color'] ?? ($preset['css_vars']['--primary'] ?? '#000000');
            $presetRgb = self::hexToRgb($presetHex);
            if (!$presetRgb) continue;
            $presetHsl = self::rgbToHsl($presetRgb['r'], $presetRgb['g'], $presetRgb['b']);

            $distance = self::hslDistance($inputHsl, $presetHsl);
            $recommendations[] = [
                'key'         => $preset['key'],
                'name'        => $preset['name'],
                'distance'    => round($distance, 2),
                'score'       => max(0, round(100 - $distance, 2)),
                'preview'     => $preset['preview_gradient'] ?? $presetHex,
                'hex'         => $presetHex,
            ];
        }

        // 按距离升序排列（距离越小越接近）
        usort($recommendations, fn ($a, $b) => $a['distance'] <=> $b['distance']);
        return $recommendations;
    }

    /**
     * HEX → RGB 转换
     */
    public static function hexToRgb(string $hex): ?array
    {
        $hex = ltrim($hex, '#');
        if (!preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            return null;
        }
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * RGB → HSL 转换
     */
    public static function rgbToHsl(int $r, int $g, int $b): array
    {
        $r /= 255; $g /= 255; $b /= 255;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            switch ($max) {
                case $r: $h = ($g - $b) / $d + ($g < $b ? 6 : 0); break;
                case $g: $h = ($b - $r) / $d + 2; break;
                case $b: $h = ($r - $g) / $d + 4; break;
            }
            $h *= 60;
        }

        return ['h' => $h, 's' => $s * 100, 'l' => $l * 100];
    }

    /**
     * HSL 加权 Euclidean 距离
     * 权重：H=0.5, S=1.0, L=1.0（色相环形差异需归一化）
     */
    public static function hslDistance(array $a, array $b): float
    {
        // 色相环形最短距离（0-360度）
        $dh = abs($a['h'] - $b['h']);
        $dh = min($dh, 360 - $dh);

        $ds = $a['s'] - $b['s'];
        $dl = $a['l'] - $b['l'];

        // 加权：H权重0.5（缩小色相差异敏感度），S和L权重1.0
        return sqrt(0.5 * $dh * $dh + 1.0 * $ds * $ds + 1.0 * $dl * $dl);
    }
}
