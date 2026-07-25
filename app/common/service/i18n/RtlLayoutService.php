<?php
declare(strict_types=1);

namespace app\common\service\i18n;

use app\common\service\ml\LangSwitchService;
use think\facade\Cache;

/**
 * RTL布局服务
 * V2.9.39 I18N-V2-4
 *
 * 自动检测语言方向(LTR/RTL) + RTL样式管理
 * RTL语言: 阿拉伯语(ar)、希伯来语(he)、波斯语(fa)、乌尔都语(ur)、意第绪语(yi)、普什图语(ps)、信德语(sd)
 */
class RtlLayoutService
{
    private const CACHE_TAG = 'rtl_layout';
    private const CACHE_TTL = 3600;

    /** RTL语言列表 */
    public const RTL_LANGUAGES = ['ar', 'he', 'fa', 'ur', 'yi', 'ps', 'sd'];

    /** @var LangSwitchService */
    private LangSwitchService $langSwitchService;

    public function __construct()
    {
        $this->langSwitchService = new LangSwitchService();
    }

    /**
     * 检测语言是否RTL
     *
     * @param string $langCode 语言代码
     * @return bool
     */
    public function isRtl(string $langCode): bool
    {
        $short = substr($langCode, 0, 2);
        $short = strtolower($short);
        return in_array($short, self::RTL_LANGUAGES, true);
    }

    /**
     * 获取语言方向
     *
     * @param string $langCode 语言代码
     * @return string 'rtl' | 'ltr'
     */
    public function getDirection(string $langCode): string
    {
        return $this->isRtl($langCode) ? 'rtl' : 'ltr';
    }

    /**
     * 获取当前页面的语言方向
     *
     * @return string 'rtl' | 'ltr'
     */
    public function getCurrentDirection(): string
    {
        $currentLang = $this->langSwitchService->getDefaultLang();
        try {
            $currentLang = $this->langSwitchService->detectLanguage(request());
        } catch (\Throwable $e) {
            // 降级使用默认语言
        }
        return $this->getDirection($currentLang);
    }

    /**
     * 获取RTL CSS文件路径
     *
     * @param string $theme 皮肤名(default/corporate)
     * @return string CSS文件相对路径
     */
    public function getRtlCssPath(string $theme = 'default'): string
    {
        return '/template/admin/' . $theme . '/css/rtl.css';
    }

    /**
     * 获取需要注入的HTML属性
     *
     * @param string|null $langCode
     * @return array [dir, lang, css_class]
     */
    public function getHtmlAttributes(?string $langCode = null): array
    {
        $langCode = $langCode ?? $this->langSwitchService->getDefaultLang();
        $isRtl = $this->isRtl($langCode);

        return [
            'dir'       => $isRtl ? 'rtl' : 'ltr',
            'lang'      => $langCode,
            'css_class' => $isRtl ? 'rtl-layout' : 'ltr-layout',
        ];
    }

    /**
     * 生成<link>标签引入RTL CSS（仅在RTL语言时输出）
     *
     * @param string $theme 皮肤名
     * @param string|null $langCode
     * @return string HTML link标签或空字符串
     */
    public function renderRtlCssLink(string $theme = 'default', ?string $langCode = null): string
    {
        $langCode = $langCode ?? $this->langSwitchService->getDefaultLang();
        if (!$this->isRtl($langCode)) {
            return '';
        }
        $cssPath = $this->getRtlCssPath($theme);
        return '<link rel="stylesheet" href="' . $cssPath . '" type="text/css" />' . "\n";
    }

    /**
     * 获取所有RTL语言列表（用于语言管理界面展示）
     *
     * @return array [{code, name, direction}]
     */
    public function getRtlLanguageList(): array
    {
        return [
            ['code' => 'ar', 'name' => 'العربية (Arabic)', 'direction' => 'rtl'],
            ['code' => 'he', 'name' => 'עברית (Hebrew)', 'direction' => 'rtl'],
            ['code' => 'fa', 'name' => 'فارسی (Persian)', 'direction' => 'rtl'],
            ['code' => 'ur', 'name' => 'اردو (Urdu)', 'direction' => 'rtl'],
            ['code' => 'yi', 'name' => 'ייִדיש (Yiddish)', 'direction' => 'rtl'],
            ['code' => 'ps', 'name' => 'پښتو (Pashto)', 'direction' => 'rtl'],
            ['code' => 'sd', 'name' => 'سنڌي (Sindhi)', 'direction' => 'rtl'],
        ];
    }

    /**
     * 更新语言的RTL标志到数据库
     *
     * @param string $langCode 语言代码
     * @param bool $isRtl 是否RTL
     * @return bool
     */
    public function updateLangRtlFlag(string $langCode, bool $isRtl): bool
    {
        try {
            \think\facade\Db::name('lang')
                ->where('lang_code', $langCode)
                ->update(['is_rtl' => $isRtl ? 1 : 0]);
            Cache::clear();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 自动检测并更新所有语言的RTL标志
     *
     * @return array [updated => int, checked => int]
     */
    public function autoDetectRtlFlags(): array
    {
        $languages = $this->langSwitchService->getLanguageList();
        $updated = 0;
        foreach ($languages as $lang) {
            $isRtl = $this->isRtl($lang['code']);
            try {
                $current = \think\facade\Db::name('lang')
                    ->where('lang_code', $lang['code'])
                    ->value('is_rtl');
                if ((int) $current !== ($isRtl ? 1 : 0)) {
                    $this->updateLangRtlFlag($lang['code'], $isRtl);
                    $updated++;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
        return ['updated' => $updated, 'checked' => count($languages)];
    }

    /**
     * 获取RTL布局配置
     * 用于前端注入，包含方向、CSS路径、翻转规则等
     *
     * @param string $theme 皮肤名
     * @param string|null $langCode
     * @return array
     */
    public function getLayoutConfig(string $theme = 'default', ?string $langCode = null): array
    {
        $langCode = $langCode ?? $this->langSwitchService->getDefaultLang();
        $isRtl = $this->isRtl($langCode);

        return [
            'is_rtl'       => $isRtl,
            'direction'    => $isRtl ? 'rtl' : 'ltr',
            'lang_code'    => $langCode,
            'css_path'     => $isRtl ? $this->getRtlCssPath($theme) : '',
            'html_class'   => $isRtl ? 'rtl-layout' : 'ltr-layout',
            'css_link'     => $this->renderRtlCssLink($theme, $langCode),
        ];
    }

    /**
     * CSS逻辑属性映射表(LTR → 逻辑属性)
     * 供模板中使用CSS逻辑属性参考
     *
     * @return array [physical_property => logical_property]
     */
    public function getCssLogicalPropertyMap(): array
    {
        return [
            'margin-left'            => 'margin-inline-start',
            'margin-right'           => 'margin-inline-end',
            'padding-left'           => 'padding-inline-start',
            'padding-right'          => 'padding-inline-end',
            'border-left'            => 'border-inline-start',
            'border-right'           => 'border-inline-end',
            'border-left-width'      => 'border-inline-start-width',
            'border-right-width'     => 'border-inline-end-width',
            'border-left-color'      => 'border-inline-start-color',
            'border-right-color'     => 'border-inline-end-color',
            'border-left-style'      => 'border-inline-start-style',
            'border-right-style'     => 'border-inline-end-style',
            'left'                   => 'inset-inline-start',
            'right'                  => 'inset-inline-end',
            'text-align: left'       => 'text-align: start',
            'text-align: right'      => 'text-align: end',
            'float: left'            => 'float: inline-start',
            'float: right'           => 'float: inline-end',
            'clear: left'            => 'clear: inline-start',
            'clear: right'           => 'clear: inline-end',
        ];
    }
}
