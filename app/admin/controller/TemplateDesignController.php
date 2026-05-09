<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Config as ConfigModel;

/**
 * 模板设计器控制器 - V2.9 前台模板可视化配置（预研）
 */
class TemplateDesignController extends AdminBaseController
{
    /**
     * 模板设计器首页
     */
    public function index()
    {
        $this->app->view->assign('menuActive', 'template_design');

        // 读取当前前台主题
        $theme = ConfigModel::where('name', 'frontend_theme')->value('value') ?: 'default';

        // 读取该主题的CSS变量配置
        $themeConfig = $this->getThemeVariables($theme);

        $this->assign([
            'theme'       => $theme,
            'themeConfig' => $themeConfig,
        ]);
        return $this->view('/template_design_index');
    }

    /**
     * 保存主题配置
     */
    public function save()
    {
        $data = $this->request->post();
        $theme = $data['theme'] ?? 'default';
        $variables = $data['variables'] ?? [];

        // 保存到i8j_theme_config表（简化实现：存为JSON到config表）
        $configKey = 'theme_vars_' . $theme;
        $config = ConfigModel::where('name', $configKey)->find();
        if ($config) {
            $config->value = is_array($variables) ? json_encode($variables) : $variables;
            $config->save();
        } else {
            ConfigModel::create([
                'name'   => $configKey,
                'value'  => is_array($variables) ? json_encode($variables) : $variables,
                'group'  => 'site',
                'type'   => 'text',
                'remark' => $theme . '主题变量配置',
                'sort'   => 100,
            ]);
        }

        \app\common\service\CacheService::clearByTag(\app\common\service\CacheService::TAG_THEME);

        return $this->success('保存成功');
    }

    /**
     * AI配色推荐 - V2.9.1 M16b 增强版
     * 支持行业/风格偏好，优先调用AI大模型，降级为HSL推导
     */
    public function aiSuggest()
    {
        $baseColor = $this->request->post('base_color', '#3b82f6');
        $industry  = $this->request->post('industry', '');
        $style     = $this->request->post('style', '');

        // 优先使用AI大模型配色
        if ($industry || $style) {
            try {
                $aiService = new \app\common\service\AiService();
                $aiResult = $aiService->colorSuggest($industry, $style, $baseColor);
                if (!empty($aiResult) && empty($aiResult['error'])) {
                    return $this->success('AI配色推荐成功', $aiResult);
                }
            } catch (\Throwable $e) {
                // AI调用失败，降级为HSL推导
                \think\facade\Log::warning('[TemplateDesign] AI配色降级: ' . $e->getMessage());
            }
        }

        // 降级：基于主色的HSL色彩推导
        $scheme = $this->generateColorScheme($baseColor);

        return $this->success('获取成功', $scheme);
    }

    /**
     * 预览主题
     */
    public function preview()
    {
        $theme = $this->request->get('theme', 'default');
        $variables = $this->request->get('variables', '');

        $this->assign([
            'theme'     => $theme,
            'variables' => $variables ? json_decode($variables, true) : [],
        ]);
        return $this->view('/template_design_preview');
    }

    /**
     * 获取主题CSS变量
     */
    protected function getThemeVariables(string $theme): array
    {
        $configKey = 'theme_vars_' . $theme;
        $saved = ConfigModel::where('name', $configKey)->value('value');
        if ($saved) {
            $decoded = json_decode($saved, true);
            if (is_array($decoded)) return $decoded;
        }

        // 默认CSS变量
        return [
            '--primary'    => '#3b82f6',
            '--secondary'  => '#64748b',
            '--accent'     => '#f59e0b',
            '--bg'         => '#ffffff',
            '--bg-secondary' => '#f8fafc',
            '--text'       => '#1e293b',
            '--text-secondary' => '#64748b',
            '--border'     => '#e2e8f0',
            '--radius'     => '8px',
            '--shadow'     => '0 1px 3px rgba(0,0,0,.1)',
        ];
    }

    /**
     * 基于主色生成配色方案
     */
    protected function generateColorScheme(string $baseColor): array
    {
        // 将hex转HSL后推导
        $rgb = $this->hexToRgb($baseColor);
        if (!$rgb) {
            return ['error' => '无效的颜色值'];
        }

        $hsl = $this->rgbToHsl($rgb[0], $rgb[1], $rgb[2]);

        return [
            'primary'         => $baseColor,
            'primary-light'   => $this->hslToHex($hsl[0], min($hsl[1], 0.8), min($hsl[2] + 0.15, 0.9)),
            'primary-dark'    => $this->hslToHex($hsl[0], min($hsl[1], 0.8), max($hsl[2] - 0.12, 0.15)),
            'accent'          => $this->hslToHex(fmod($hsl[0] + 0.08, 1), 0.75, 0.55),
            'bg-secondary'    => $this->hslToHex($hsl[0], 0.15, 0.97),
            'border'         => $this->hslToHex($hsl[0], 0.2, 0.88),
            'text-secondary' => $this->hslToHex($hsl[0], 0.15, 0.45),
        ];
    }

    protected function hexToRgb(string $hex): ?array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        if (strlen($hex) !== 6) return null;
        return [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
    }

    protected function rgbToHsl(int $r, int $g, int $b): array
    {
        $r /= 255; $g /= 255; $b /= 255;
        $max = max($r, $g, $b); $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        if ($max === $min) { $h = $s = 0; }
        else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            if ($max === $r) $h = (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6;
            elseif ($max === $g) $h = (($b - $r) / $d + 2) / 6;
            else $h = (($r - $g) / $d + 4) / 6;
        }
        return [$h, $s, $l];
    }

    protected function hslToHex(float $h, float $s, float $l): string
    {
        if ($s === 0) { $r = $g = $b = $l; }
        else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = $this->hue2rgb($p, $q, $h + 1/3);
            $g = $this->hue2rgb($p, $q, $h);
            $b = $this->hue2rgb($p, $q, $h - 1/3);
        }
        return '#' . sprintf('%02x%02x%02x', (int)round($r*255), (int)round($g*255), (int)round($b*255));
    }

    protected function hue2rgb(float $p, float $q, float $t): float
    {
        if ($t < 0) $t += 1; if ($t > 1) $t -= 1;
        if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1/2) return $q;
        if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
        return $p;
    }
}
