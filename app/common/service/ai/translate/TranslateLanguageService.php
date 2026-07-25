<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\ai\translate;

use think\facade\Config;
use app\common\model\Setting;

/**
 * V2.9.17 M-2: 翻译语言服务（统一语言管理入口）
 *
 * 读取优先级（Settings > Config）：
 *   1. settings 表 translate.enabled_languages（后台UI设置）
 *   2. config/ai.php enabled 字段（兜底）
 *   3. settings 表 translate.language_order（排序）
 *   4. settings 表 translate.custom_languages（自定义语言）
 */
class TranslateLanguageService
{
    /**
     * 获取已启用的语言代码列表
     */
    public static function getEnabledCodes(): array
    {
        $saved = Setting::get('translate.enabled_languages');
        if (!empty($saved)) {
            return json_decode($saved, true) ?: [];
        }

        $allLanguages = Config::get('ai.translate.languages', []);
        return array_keys(array_filter($allLanguages, function ($m) {
            return !isset($m['enabled']) || $m['enabled'] !== false;
        }));
    }

    /**
     * 获取所有已启用语言的完整元数据
     */
    public static function getEnabledLanguages(): array
    {
        $enabledCodes = static::getEnabledCodes();
        $allLanguages = static::getAllLanguages();
        $result = [];
        foreach ($enabledCodes as $code) {
            if (isset($allLanguages[$code])) {
                $result[$code] = $allLanguages[$code];
            }
        }
        return $result;
    }

    /**
     * 获取所有已注册的语言（含内置+自定义，含已禁用）
     */
    public static function getAllLanguages(): array
    {
        $allLanguages = Config::get('ai.translate.languages', []);
        $customSaved = Setting::get('translate.custom_languages');
        if (!empty($customSaved)) {
            $custom = json_decode($customSaved, true) ?: [];
            $allLanguages = array_merge($allLanguages, $custom);
        }
        return $allLanguages;
    }

    /**
     * 检查语言是否已启用
     */
    public static function isLanguageEnabled(string $code): bool
    {
        return in_array($code, static::getEnabledCodes(), true);
    }

    /**
     * 获取排序后的语言代码列表
     */
    public static function getSortedCodes(): array
    {
        $enabledCodes = static::getEnabledCodes();
        $savedOrder = Setting::get('translate.language_order');
        if (empty($savedOrder)) {
            return $enabledCodes;
        }
        $order = json_decode($savedOrder, true) ?: [];
        if (empty($order)) {
            return $enabledCodes;
        }
        $sorted = [];
        foreach ($order as $code) {
            if (in_array($code, $enabledCodes, true)) {
                $sorted[] = $code;
            }
        }
        foreach ($enabledCodes as $code) {
            if (!in_array($code, $sorted, true)) {
                $sorted[] = $code;
            }
        }
        return $sorted;
    }

    /**
     * 生成前端下拉选项HTML
     */
    public static function renderOptions(string $selected = ''): string
    {
        $sortedCodes = static::getSortedCodes();
        $allLanguages = static::getAllLanguages();
        $html = '';
        foreach ($sortedCodes as $code) {
            if (!isset($allLanguages[$code])) continue;
            $meta = $allLanguages[$code];
            $flag = $meta['flag'] ?? '';
            $native = $meta['native'] ?? $code;
            $name = $meta['name'] ?? '';
            $sel = $code === $selected ? 'selected' : '';
            $html .= "<option value=\"{$code}\" {$sel}>{$flag} {$native} ({$name})</option>";
        }
        return $html;
    }
}
