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

/**
 * V2.9.16: 翻译语言统一配置类
 *
 * 集中管理所有支持的语言列表，消除硬编码。
 * 支持从 config/ai.php 扩展自定义语言。
 */
class TranslateLanguageConfig
{
    /**
     * 内置语言列表 [code => displayName]
     */
    protected static array $defaultLanguages = [
        // 东亚
        'zh' => ['name' => '中文', 'en_name' => 'Chinese'],
        'ja' => ['name' => '日语', 'en_name' => 'Japanese'],
        'ko' => ['name' => '韩语', 'en_name' => 'Korean'],
        // 欧洲
        'en' => ['name' => '英语', 'en_name' => 'English'],
        'fr' => ['name' => '法语', 'en_name' => 'French'],
        'de' => ['name' => '德语', 'en_name' => 'German'],
        'es' => ['name' => '西班牙语', 'en_name' => 'Spanish'],
        'it' => ['name' => '意大利语', 'en_name' => 'Italian'],
        'pt' => ['name' => '葡萄牙语', 'en_name' => 'Portuguese'],
        'ru' => ['name' => '俄语', 'en_name' => 'Russian'],
        // 其他
        'ar' => ['name' => '阿拉伯语', 'en_name' => 'Arabic'],
        'hi' => ['name' => '印地语', 'en_name' => 'Hindi'],
        'th' => ['name' => '泰语', 'en_name' => 'Thai'],
        'vi' => ['name' => '越南语', 'en_name' => 'Vietnamese'],
        'id' => ['name' => '印尼语', 'en_name' => 'Indonesian'],
        'tr' => ['name' => '土耳其语', 'en_name' => 'Turkish'],
    ];

    /**
     * 获取合并后的语言列表（内置 + 自定义扩展）
     * @return array [code => ['name' => '中文', 'en_name' => 'Chinese']]
     */
    public static function getAllLanguages(): array
    {
        $custom = Config::get('ai.translate.languages', []);
        return array_merge(self::$defaultLanguages, $custom);
    }

    /**
     * 获取所有支持的语言代码
     * @return array ['zh', 'en', 'ja', ...]
     */
    public static function getSupportedCodes(): array
    {
        return array_keys(self::getAllLanguages());
    }

    /**
     * 获取语言显示名称（中文）
     */
    public static function getLangName(string $code): string
    {
        $langs = self::getAllLanguages();
        return $langs[$code]['name'] ?? $code;
    }

    /**
     * 获取语言英文名称（用于Prompt）
     */
    public static function getLangEnName(string $code): string
    {
        $langs = self::getAllLanguages();
        return $langs[$code]['en_name'] ?? $code;
    }

    /**
     * 获取供前端下拉框使用的 [code => name] 映射
     * @return array ['zh' => '中文', 'en' => '英语', ...]
     */
    public static function getDropdownOptions(): array
    {
        $options = [];
        foreach (self::getAllLanguages() as $code => $info) {
            $options[$code] = $info['name'];
        }
        return $options;
    }

    /**
     * 检查语言是否支持
     */
    public static function isSupported(string $code): bool
    {
        return isset(self::getAllLanguages()[$code]);
    }
}
