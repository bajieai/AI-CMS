<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Language as LanguageModel;
use app\common\model\Translation;
use think\facade\Cache;
use think\facade\Cookie;

/**
 * 多语言服务 - V2.5新增
 * 语言管理 + 翻译管理 + 当前语言切换
 */
class LanguageService
{
    protected static string $cacheTag = 'i8j_language';

    /**
     * 获取当前语言
     */
    public static function getCurrentLang(): string
    {
        // 1. Cookie中的语言偏好
        $cookieLang = Cookie::get('lang');
        if ($cookieLang && self::isLanguageEnabled($cookieLang)) {
            return $cookieLang;
        }

        // 2. 默认语言
        return self::getDefaultLang();
    }

    /**
     * 设置当前语言
     */
    public static function setCurrentLang(string $langCode): bool
    {
        if (!self::isLanguageEnabled($langCode)) return false;
        Cookie::set('lang', $langCode, 86400 * 30);
        return true;
    }

    /**
     * 获取默认语言
     */
    public static function getDefaultLang(): string
    {
        $default = LanguageModel::where('is_default', 1)->find();
        return $default ? $default->code : 'zh-CN';
    }

    /**
     * 获取所有启用语言
     */
    public static function getEnabledLanguages(): array
    {
        return Cache::tag(self::$cacheTag)->remember('enabled_languages', function () {
            return LanguageModel::where('is_enabled', 1)->order('sort', 'asc')->select()->toArray();
        }, 3600);
    }

    /**
     * 检查语言是否启用
     */
    public static function isLanguageEnabled(string $code): bool
    {
        $languages = self::getEnabledLanguages();
        foreach ($languages as $lang) {
            if ($lang['code'] === $code) return true;
        }
        return false;
    }

    /**
     * 翻译词条
     * @param string $key 原文/翻译键
     * @param string $group 分组
     * @param array $params 替换参数
     * @param string|null $langCode 指定语言代码（null=使用当前语言）
     * @return string 翻译后的文本
     */
    public static function translate(string $key, string $group = 'common', array $params = [], ?string $langCode = null): string
    {
        $lang = $langCode ?: self::getCurrentLang();

        // 默认语言不需要翻译
        if ($lang === self::getDefaultLang()) {
            return self::replaceParams($key, $params);
        }

        // 从缓存获取翻译
        $cacheKey = "lang_{$lang}_{$group}_" . md5($key);
        $translated = Cache::tag(self::$cacheTag)->get($cacheKey);

        if ($translated === null) {
            $record = Translation::where('lang_code', $lang)
                ->where('group', $group)
                ->where('key', $key)
                ->find();

            $translated = $record ? $record->translation : $key;
            Cache::tag(self::$cacheTag)->set($cacheKey, $translated, 3600);
        }

        return self::replaceParams($translated, $params);
    }

    /**
     * 批量获取某组翻译
     */
    public static function getGroupTranslations(string $group = 'common', ?string $langCode = null): array
    {
        $lang = $langCode ?: self::getCurrentLang();
        $cacheKey = "lang_group_{$lang}_{$group}";

        return Cache::tag(self::$cacheTag)->remember($cacheKey, function () use ($lang, $group) {
            $records = Translation::where('lang_code', $lang)
                ->where('group', $group)
                ->select();
            $result = [];
            foreach ($records as $record) {
                $result[$record->key] = $record->translation;
            }
            return $result;
        }, 3600);
    }

    /**
     * 保存翻译
     */
    public static function saveTranslation(string $langCode, string $group, string $key, string $translation): bool
    {
        $record = Translation::where('lang_code', $langCode)
            ->where('group', $group)
            ->where('key', $key)
            ->find();

        if ($record) {
            $record->translation = $translation;
            $record->save();
        } else {
            Translation::create([
                'lang_code' => $langCode,
                'group' => $group,
                'key' => $key,
                'translation' => $translation,
            ]);
        }

        Cache::tag(self::$cacheTag)->clear();
        return true;
    }

    /**
     * 替换参数 :name → value
     */
    protected static function replaceParams(string $text, array $params): string
    {
        foreach ($params as $key => $value) {
            $text = str_replace(':' . $key, (string) $value, $text);
        }
        return $text;
    }
}
