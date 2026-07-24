<?php
declare(strict_types=1);

namespace app\home\middleware;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 语言检测中间件 - V2.9.40 I18N-V3-2
 *
 * 三级回退策略：Cookie → 浏览器Accept-Language → 系统默认语言
 * 检测后写入Cookie并注入Request属性
 */
class LanguageDetectMiddleware
{
    private const COOKIE_NAME = 'ai_cms_lang';
    private const COOKIE_TTL  = 86400 * 30; // 30天

    /** 支持的语言列表 */
    private static array $supportedLangs = [];

    /** 默认语言 */
    private static string $defaultLang = 'zh';

    /**
     * 处理请求
     */
    public function handle($request, \Closure $next)
    {
        $this->initLangConfig();

        $lang = $this->detectLanguage($request);

        // 写入Cookie（首次检测时）
        if ($request->cookie(self::COOKIE_NAME) !== $lang) {
            cookie(self::COOKIE_NAME, $lang, self::COOKIE_TTL);
        }

        // 注入Request属性
        $request->lang = $lang;

        return $next($request);
    }

    /**
     * 初始化语言配置（缓存1小时）
     */
    private function initLangConfig(): void
    {
        $config = Cache::remember('lang_detect_config', function () {
            return [
                'default'   => Db::name('config')->where('group', 'i18n')->where('key', 'default_lang')->value('value') ?: 'zh',
                'supported' => json_decode(
                    Db::name('config')->where('group', 'i18n')->where('key', 'supported_langs')->value('value') ?: '["zh","en"]',
                    true
                ) ?: ['zh', 'en'],
            ];
        }, 3600);

        self::$defaultLang = $config['default'];
        self::$supportedLangs = $config['supported'];
    }

    /**
     * 三级回退语言检测
     *
     * Level1: Cookie → Level2: Accept-Language → Level3: 系统默认
     */
    private function detectLanguage($request): string
    {
        // Level1: Cookie
        $cookieLang = $request->cookie(self::COOKIE_NAME);
        if ($cookieLang && in_array($cookieLang, self::$supportedLangs)) {
            return $cookieLang;
        }

        // Level2: 浏览器Accept-Language
        $acceptLang = $request->header('accept-language', '');
        if (!empty($acceptLang)) {
            $detected = $this->parseAcceptLanguage($acceptLang);
            if ($detected && in_array($detected, self::$supportedLangs)) {
                return $detected;
            }
        }

        // Level3: 系统默认语言
        return self::$defaultLang;
    }

    /**
     * 解析Accept-Language头
     *
     * 格式: zh-CN,zh;q=0.9,en;q=0.8
     * 策略：按权重排序，匹配主语言代码
     */
    private function parseAcceptLanguage(string $header): ?string
    {
        $langs = [];
        $parts = explode(',', $header);
        foreach ($parts as $part) {
            $part = trim($part);
            $lang = $part;
            $q = 1.0;

            // 解析q值
            if (strpos($part, ';q=') !== false) {
                $segments = explode(';q=', $part);
                $lang = trim($segments[0]);
                $q = (float) trim($segments[1]);
            }

            // 取主语言代码（zh-CN → zh, en-US → en）
            $mainLang = strtolower(explode('-', $lang)[0]);
            $langs[$mainLang] = max($langs[$mainLang] ?? 0, $q);
        }

        // 按权重排序
        arsort($langs);

        // 逐个匹配支持的语言
        foreach ($langs as $code => $q) {
            if (in_array($code, self::$supportedLangs)) {
                return $code;
            }
        }

        return null;
    }
}
