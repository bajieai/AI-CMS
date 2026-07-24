<?php
declare(strict_types=1);
namespace app\common\service\template;

use think\facade\Db;
use think\facade\Cache;

/**
 * 自定义CSS/JS板块Service - V2.9.32 CUS2-2
 */
class TemplateCustomCssJsService
{
    private const CACHE_TAG = 'custom_css_js';

    private const CSS_DANGEROUS = ['javascript:', 'expression(', '-moz-binding', 'url(javascript:', 'data:text/html', '<script', '</style', 'behavior:'];
    private const JS_DANGEROUS = ['eval(', 'document.write(', 'XMLHttpRequest', 'new Function(', 'setTimeout(string', 'setInterval(string'];

    public function saveCss(int $memberId, string $themeSlug, string $css, string $name = ''): array
    {
        $validated = $this->validateCss($css);
        if (!$validated['valid']) return ['success' => false, 'message' => 'CSS安全检测失败: ' . implode(', ', $validated['blocked'])];
        $this->saveCustom($memberId, $themeSlug, 'custom_css', $css, $name);
        return ['success' => true, 'message' => 'CSS保存成功'];
    }

    public function saveJs(int $memberId, string $themeSlug, string $js, string $name = ''): array
    {
        $validated = $this->validateJs($js);
        if (!$validated['valid']) return ['success' => false, 'message' => 'JS安全检测失败: ' . implode(', ', $validated['blocked'])];
        $this->saveCustom($memberId, $themeSlug, 'custom_js', $js, $name);
        return ['success' => true, 'message' => 'JS保存成功'];
    }

    public function loadCustom(int $memberId, string $themeSlug): array
    {
        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        $css = Db::table($prefix . 'template_custom_config')->where('member_id', $memberId)->where('theme_slug', $themeSlug)->where('config_key', 'custom_css')->value('config_value');
        $js = Db::table($prefix . 'template_custom_config')->where('member_id', $memberId)->where('theme_slug', $themeSlug)->where('config_key', 'custom_js')->value('config_value');
        return ['css' => $css ?: '', 'js' => $js ?: ''];
    }

    public function deleteCustom(int $memberId, string $themeSlug, string $type): array
    {
        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        Db::table($prefix . 'template_custom_config')->where('member_id', $memberId)->where('theme_slug', $themeSlug)->where('config_key', $type)->delete();
        Cache::clear();
        return ['success' => true, 'message' => '删除成功'];
    }

    public function validateCss(string $css): array
    {
        $blocked = [];
        foreach (self::CSS_DANGEROUS as $pattern) { if (stripos($css, $pattern) !== false) $blocked[] = $pattern; }
        return ['valid' => empty($blocked), 'blocked' => $blocked];
    }

    public function validateJs(string $js): array
    {
        $blocked = [];
        foreach (self::JS_DANGEROUS as $pattern) { if (stripos($js, $pattern) !== false) $blocked[] = $pattern; }
        return ['valid' => empty($blocked), 'blocked' => $blocked];
    }

    public function getPresets(): array
    {
        return [
            ['name' => '自定义字体加载', 'type' => 'css', 'code' => 'body { font-family: "Noto Sans SC", sans-serif; }'],
            ['name' => '自定义动画效果', 'type' => 'css', 'code' => '.fade-in { animation: fadeIn 0.5s ease-in; } @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }'],
            ['name' => '自定义统计代码', 'type' => 'js', 'code' => '// 在此粘贴统计代码\n// var _hmt = _hmt || [];'],
            ['name' => '自定义客服组件', 'type' => 'js', 'code' => '// 在此粘贴客服组件代码\n// document.createElement("script")'],
            ['name' => '自定义弹窗组件', 'type' => 'js', 'code' => '// 在此粘贴弹窗组件代码\n// function showModal() {}'],
        ];
    }

    private function saveCustom(int $memberId, string $themeSlug, string $key, string $value, string $name): void
    {
        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        $exists = Db::table($prefix . 'template_custom_config')->where('member_id', $memberId)->where('theme_slug', $themeSlug)->where('config_key', $key)->find();
        if ($exists) {
            Db::table($prefix . 'template_custom_config')->where('id', $exists['id'])->update(['config_value' => $value, 'update_time' => time()]);
        } else {
            Db::table($prefix . 'template_custom_config')->insert(['member_id' => $memberId, 'theme_slug' => $themeSlug, 'config_key' => $key, 'config_value' => $value, 'config_type' => 'custom', 'create_time' => time(), 'update_time' => time()]);
        }
        Cache::clear();
    }
}
