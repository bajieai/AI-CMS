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
 * 集中管理所有支持的语言列表。
 * 优先级：config/ai.php → 内置默认值（兜底）
 *
 * 管理员在 config/ai.php translate.languages 中增删语言即可控制可用语言。
 */
class TranslateLanguageConfig
{
    /**
     * 内置语言兜底列表（仅在 config/ai.php 无 languages 配置时使用）
     */
    protected static array $fallbackLanguages = [
        'zh' => ['name' => '中文',     'en_name' => 'Chinese',      'direction' => 'ltr', 'enabled' => true],
        'en' => ['name' => '英语',     'en_name' => 'English',      'direction' => 'ltr', 'enabled' => true],
        'ja' => ['name' => '日语',     'en_name' => 'Japanese',     'direction' => 'ltr', 'enabled' => true],
        'ko' => ['name' => '韩语',     'en_name' => 'Korean',       'direction' => 'ltr', 'enabled' => true],
        'fr' => ['name' => '法语',     'en_name' => 'French',       'direction' => 'ltr', 'enabled' => true],
        'de' => ['name' => '德语',     'en_name' => 'German',       'direction' => 'ltr', 'enabled' => true],
        'es' => ['name' => '西班牙语', 'en_name' => 'Spanish',      'direction' => 'ltr', 'enabled' => true],
        'pt' => ['name' => '葡萄牙语', 'en_name' => 'Portuguese',   'direction' => 'ltr', 'enabled' => false],
        'ru' => ['name' => '俄语',     'en_name' => 'Russian',      'direction' => 'ltr', 'enabled' => false],
        'ar' => ['name' => '阿拉伯语', 'en_name' => 'Arabic',       'direction' => 'rtl', 'enabled' => false],
        'th' => ['name' => '泰语',     'en_name' => 'Thai',         'direction' => 'ltr', 'enabled' => false],
        'vi' => ['name' => '越南语',   'en_name' => 'Vietnamese',   'direction' => 'ltr', 'enabled' => false],
        'id' => ['name' => '印尼语',   'en_name' => 'Indonesian',   'direction' => 'ltr', 'enabled' => false],
        'tr' => ['name' => '土耳其语', 'en_name' => 'Turkish',      'direction' => 'ltr', 'enabled' => false],
        'it' => ['name' => '意大利语', 'en_name' => 'Italian',      'direction' => 'ltr', 'enabled' => false],
        'hi' => ['name' => '印地语',   'en_name' => 'Hindi',        'direction' => 'ltr', 'enabled' => false],
    ];

    /**
     * 获取完整语言列表（优先 config，回退内置）
     * @return array [code => ['name'=>..., 'en_name'=>..., 'direction'=>..., 'enabled'=>...]]
     */
    public static function getAllLanguages(): array
    {
        $config = Config::get('ai.translate.languages', []);
        return !empty($config) ? $config : self::$fallbackLanguages;
    }

    /**
     * 获取已启用的语言代码列表
     * @return array ['zh', 'en', 'ja', ...]
     */
    public static function getSupportedCodes(): array
    {
        $codes = [];
        foreach (self::getAllLanguages() as $code => $info) {
            if ($info['enabled'] ?? true) {
                $codes[] = $code;
            }
        }
        return $codes;
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
     * 获取语言国旗Emoji
     */
    public static function getFlag(string $code): string
    {
        $langs = self::getAllLanguages();
        return $langs[$code]['flag'] ?? '';
    }

    /**
     * 获取文字方向（ltr/rtl）
     */
    public static function getDirection(string $code): string
    {
        $langs = self::getAllLanguages();
        return $langs[$code]['direction'] ?? 'ltr';
    }

    /**
     * 获取供前端下拉框使用的 [code => name] 映射
     * @return array ['zh' => '中文', 'en' => '英语', ...]
     */
    public static function getDropdownOptions(): array
    {
        $options = [];
        foreach (self::getAllLanguages() as $code => $info) {
            $options[$code] = ($info['flag'] ?? '') . ' ' . ($info['name'] ?? $code);
        }
        return $options;
    }

    /**
     * 检查语言是否支持且已启用
     */
    public static function isSupported(string $code): bool
    {
        return in_array($code, self::getSupportedCodes(), true);
    }
}
