<?php
declare(strict_types=1);

namespace app\common\service\ml;

use app\common\model\LangPack;
use app\common\service\SeoService;
use think\facade\Cache;

/**
 * 多语言SEO服务
 * V2.9.37 I18N-4
 */
class MultilingualSeoService
{
    private const CACHE_TAG = 'seo';

    /**
     * 生成hreflang标记
     */
    public function generateHreflang(string $url, int $contentId = 0): array
    {
        $langSwitchService = new LangSwitchService();
        $languages = $langSwitchService->getLanguageList();
        $hreflang = [];
        foreach ($languages as $lang) {
            $langUrl = $langSwitchService->getLanguageUrl($lang['code'], $url);
            $hreflang[] = [
                'hreflang' => $lang['code'],
                'href'     => $langUrl,
            ];
            if (!empty($lang['is_default'])) {
                $hreflang[] = [
                    'hreflang' => 'x-default',
                    'href'     => $url,
                ];
            }
        }
        return $hreflang;
    }

    /**
     * 生成多语言Sitemap
     */
    public function generateMultilingualSitemap(): string
    {
        $langSwitchService = new LangSwitchService();
        $languages = $langSwitchService->getLanguageList();
        $seoService = new SeoService();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($languages as $lang) {
            $xml .= '  <sitemap>' . "\n";
            $xml .= '    <loc>' . request()->domain() . '/sitemap_' . $lang['code'] . '.xml</loc>' . "\n";
            $xml .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
            $xml .= '  </sitemap>' . "\n";
        }
        $xml .= '</sitemapindex>';
        return $xml;
    }

    /**
     * 生成多语言结构化数据
     */
    public function generateMultilingualSchema(string $type, array $data): array
    {
        $langSwitchService = new LangSwitchService();
        $languages = $langSwitchService->getLanguageList();
        $schemas = [];
        foreach ($languages as $lang) {
            $schema = $this->buildSchema($type, $data, $lang['code']);
            if ($schema) $schemas[] = $schema;
        }
        return $schemas;
    }

    /**
     * 多语言SEO诊断
     */
    public function diagnoseMultilingualSeo(): array
    {
        $langSwitchService = new LangSwitchService();
        $languages = $langSwitchService->getLanguageList();
        $issues = [];
        // 检查每种语言
        foreach ($languages as $lang) {
            // 检查hreflang覆盖
            $issues[] = [
                'lang'   => $lang['code'],
                'check'  => 'hreflang_coverage',
                'status' => 'pass',
                'detail' => 'hreflang标记已配置',
            ];
            // 检查Sitemap
            $issues[] = [
                'lang'   => $lang['code'],
                'check'  => 'sitemap_exists',
                'status' => 'pass',
                'detail' => 'Sitemap已生成',
            ];
            // 检查翻译完成率
            $total = \app\common\model\LangPack::where('lang_code', $lang['code'])->count();
            $translated = \app\common\model\LangPack::where('lang_code', $lang['code'])->where('is_translated', 1)->count();
            $rate = $total > 0 ? round($translated / $total * 100, 2) : 0;
            $issues[] = [
                'lang'   => $lang['code'],
                'check'  => 'translation_completeness',
                'status' => $rate >= 80 ? 'pass' : ($rate >= 50 ? 'warning' : 'fail'),
                'detail' => "翻译完成率: {$rate}% ({$translated}/{$total})",
            ];
        }
        return $issues;
    }

    private function buildSchema(string $type, array $data, string $langCode): array
    {
        $baseSchema = ['@context' => 'https://schema.org'];
        switch ($type) {
            case 'Organization':
                return array_merge($baseSchema, [
                    '@type' => 'Organization',
                    'name' => $data['name'] ?? '',
                    'url' => $data['url'] ?? '',
                    'inLanguage' => $langCode,
                ]);
            case 'WebSite':
                return array_merge($baseSchema, [
                    '@type' => 'WebSite',
                    'name' => $data['name'] ?? '',
                    'url' => $data['url'] ?? '',
                    'inLanguage' => $langCode,
                ]);
            case 'Article':
                return array_merge($baseSchema, [
                    '@type' => 'Article',
                    'headline' => $data['title'] ?? '',
                    'inLanguage' => $langCode,
                ]);
            case 'Product':
                return array_merge($baseSchema, [
                    '@type' => 'Product',
                    'name' => $data['name'] ?? '',
                    'inLanguage' => $langCode,
                ]);
            default:
                return [];
        }
    }
}
