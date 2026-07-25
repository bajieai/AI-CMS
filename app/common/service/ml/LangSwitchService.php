<?php
declare(strict_types=1);

namespace app\common\service\ml;

use think\facade\Cache;

/**
 * 前台多语言切换服务
 * V2.9.37 I18N-1
 */
class LangSwitchService
{
    private const CACHE_TAG = 'config';
    private const COOKIE_KEY = 'aicms_lang';
    private const COOKIE_TTL = 2592000; // 30天

    /**
     * 检测语言(优先级: 用户选择 > 浏览器语言 > 系统默认)
     */
    public function detectLanguage($request): string
    {
        // 1. URL参数 ?lang=en
        $urlLang = $request->get('lang');
        if ($urlLang && $this->isValidLang($urlLang)) {
            return $urlLang;
        }
        // 2. Cookie
        $cookieLang = $request->cookie(self::COOKIE_KEY);
        if ($cookieLang && $this->isValidLang($cookieLang)) {
            return $cookieLang;
        }
        // 3. 浏览器Accept-Language
        $acceptLang = $request->header('accept-language', '');
        if ($acceptLang) {
            $detected = $this->parseAcceptLanguage($acceptLang);
            if ($detected && $this->isValidLang($detected)) {
                return $detected;
            }
        }
        // 4. 系统默认
        return $this->getDefaultLang();
    }

    /**
     * 获取语言列表(1小时缓存)
     */
    public function getLanguageList(): array
    {
        return Cache::remember(
            'lang_switch_list',
            function () {
                // 从数据库或配置获取语言列表
                $config = config('lang.languages', []);
                if (empty($config)) {
                    $config = [
                        ['code' => 'zh-cn', 'name' => '简体中文', 'flag' => '🇨🇳', 'is_default' => 1],
                        ['code' => 'en', 'name' => 'English', 'flag' => '🇬🇧', 'is_default' => 0],
                    ];
                }
                return $config;
            },
            3600
        );
    }

    /**
     * 切换语言
     */
    public function switchLanguage(string $lang): string
    {
        if (!$this->isValidLang($lang)) {
            return $this->getDefaultLang();
        }
        cookie(self::COOKIE_KEY, $lang, self::COOKIE_TTL);
        return $lang;
    }

    /**
     * 获取指定语言的URL
     */
    public function getLanguageUrl(string $lang, string $currentUrl = ''): string
    {
        if (empty($currentUrl)) {
            $currentUrl = request()->url(true);
        }
        $separator = strpos($currentUrl, '?') !== false ? '&' : '?';
        return $currentUrl . $separator . 'lang=' . $lang;
    }

    /**
     * 获取默认语言
     */
    public function getDefaultLang(): string
    {
        $list = $this->getLanguageList();
        foreach ($list as $lang) {
            if (!empty($lang['is_default'])) {
                return $lang['code'];
            }
        }
        return 'zh-cn';
    }

    /**
     * 获取当前语言信息
     */
    public function getCurrentLanguage(string $langCode): array
    {
        $list = $this->getLanguageList();
        foreach ($list as $lang) {
            if ($lang['code'] === $langCode) {
                return $lang;
            }
        }
        return ['code' => $langCode, 'name' => $langCode, 'flag' => '', 'is_default' => 0];
    }

    private function isValidLang(string $code): bool
    {
        $list = $this->getLanguageList();
        foreach ($list as $lang) {
            if ($lang['code'] === $code) {
                return true;
            }
        }
        return false;
    }

    private function parseAcceptLanguage(string $header): ?string
    {
        // 解析 Accept-Language: zh-CN,zh;q=0.9,en;q=0.8
        $parts = explode(',', $header);
        foreach ($parts as $part) {
            $code = trim(explode(';', $part)[0]);
            $code = strtolower($code);
            // 标准化: zh-CN → zh-cn, en-US → en
            if (strlen($code) > 2 && strpos($code, '-') !== false) {
                $short = substr($code, 0, 2);
                if ($short === 'zh') return 'zh-cn';
                if ($short === 'en') return 'en';
                if ($short === 'ja') return 'jp';
                if ($short === 'ko') return 'ko';
            } elseif (strlen($code) === 2) {
                if (in_array($code, ['zh', 'en', 'jp', 'ko'])) {
                    return $code === 'zh' ? 'zh-cn' : $code;
                }
            }
        }
        return null;
    }
}
