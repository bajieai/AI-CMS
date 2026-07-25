<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint UX2: 移动端模板适配
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

/**
 * 移动端模板适配服务 - V2.9.31 UX2-4
 * 提供移动端模板检测、切换、适配功能
 */
class MobileTemplateService
{
    /**
     * 移动端模板目录映射
     */
    private const MOBILE_DIRS = [
        'template/themes/default/mobile/',
        'template/themes/corporate/mobile/',
    ];

    /**
     * 检测移动端模板是否存在
     */
    public function hasMobileTemplate(string $theme = 'default'): bool
    {
        $path = root_path() . "template/themes/{$theme}/mobile/";
        return is_dir($path) && count(glob($path . '*.html')) > 0;
    }

    /**
     * 获取移动端模板路径
     */
    public function getMobileTemplatePath(string $theme = 'default'): string
    {
        return "template/themes/{$theme}/mobile/";
    }

    /**
     * 获取PC端模板路径
     */
    public function getPcTemplatePath(string $theme = 'default'): string
    {
        return "template/themes/{$theme}/pc/";
    }

    /**
     * 根据设备类型返回模板路径
     */
    public function resolveTemplatePath(string $theme = 'default', bool $isMobile = false): string
    {
        if ($isMobile && $this->hasMobileTemplate($theme)) {
            return $this->getMobileTemplatePath($theme);
        }
        return $this->getPcTemplatePath($theme);
    }

    /**
     * 获取移动端模板列表
     */
    public function getMobileTemplates(string $theme = 'default'): array
    {
        $path = root_path() . "template/themes/{$theme}/mobile/";
        if (!is_dir($path)) {
            return [];
        }

        $files = glob($path . '*.html');
        $templates = [];
        foreach ($files as $file) {
            $templates[] = basename($file, '.html');
        }
        return $templates;
    }

    /**
     * 检查移动端模板完整性
     */
    public function checkMobileTemplateCompleteness(string $theme = 'default'): array
    {
        $required = ['index', 'layout', 'detail', 'list'];
        $existing = $this->getMobileTemplates($theme);
        $missing = array_diff($required, $existing);

        return [
            'complete' => empty($missing),
            'existing' => $existing,
            'missing' => array_values($missing),
            'coverage' => count($existing) / count($required) * 100,
        ];
    }
}
