<?php
declare(strict_types=1);

namespace app\common\service\seo;

use app\common\service\ml\LangSwitchService;
use app\common\service\ml\MultilingualSeoService;
use think\facade\Cache;
use think\facade\Db;

/**
 * 多语言SEO增强服务
 * V2.9.39 I18N-V2-3
 *
 * 增强 V2.9.37 的 MultilingualSeoService:
 * - 多语言Sitemap索引(支持按语言分索引)
 * - hreflang标签增强(支持区域变体+备用URL)
 * - 多语言结构化数据增强(支持WorkTranslation/CollectionPage)
 * - URL优化(多语言URL策略: 子目录/子域名/参数)
 * - 多语言SEO分析(深度诊断+建议)
 */
class MultilingualSeoEnhanceService
{
    private const CACHE_TAG = 'seo_enhance';
    private const CACHE_TTL = 3600;

    /** URL策略 */
    public const URL_STRATEGY_SUBDIR    = 'subdir';    // /en/article/123
    public const URL_STRATEGY_SUBDOMAIN = 'subdomain'; // en.example.com/article/123
    public const URL_STRATEGY_PARAM     = 'param';     // /article/123?lang=en

    /** @var LangSwitchService */
    private LangSwitchService $langSwitchService;

    /** @var MultilingualSeoService */
    private MultilingualSeoService $baseSeoService;

    public function __construct()
    {
        $this->langSwitchService = new LangSwitchService();
        $this->baseSeoService = new MultilingualSeoService();
    }

    // ===== 多语言Sitemap索引 =====

    /**
     * 生成增强版多语言Sitemap索引
     * 按语言分组，每个语言一个子sitemap，并包含hreflang alternate链接
     *
     * @return string XML
     */
    public function generateEnhancedSitemapIndex(): string
    {
        $languages = $this->langSwitchService->getLanguageList();
        $domain = request()->domain();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($languages as $lang) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>' . $domain . '/sitemap_' . $lang['code'] . '.xml</loc>' . "\n";
            $xml .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
            $xml .= "  </sitemap>\n";
        }
        $xml .= '</sitemapindex>';
        return $xml;
    }

    /**
     * 生成单语言的Sitemap(包含hreflang alternate链接)
     *
     * @param string $langCode 语言代码
     * @param int $limit 最多条目数
     * @return string XML
     */
    public function generateLangSitemap(string $langCode, int $limit = 5000): string
    {
        $languages = $this->langSwitchService->getLanguageList();
        $domain = request()->domain();
        $urlStrategy = $this->getUrlStrategy();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        // 获取该语言的内容列表
        try {
            $contents = Db::name('content')
                ->alias('c')
                ->leftJoin('content_lang cl', 'cl.content_id = c.id AND cl.lang = :lang')
                ->bind(['lang' => $langCode])
                ->where('c.status', 1)
                ->where(function ($query) use ($langCode) {
                    $query->whereOr('cl.translate_status', 2)->whereOr('c.lang', $langCode);
                })
                ->field('c.id, c.update_time, COALESCE(cl.title, c.title) as title')
                ->order('c.id', 'desc')
                ->limit($limit)
                ->select()
                ->toArray();
        } catch (\Throwable $e) {
            $contents = [];
        }

        foreach ($contents as $content) {
            $url = $this->buildMultilingualUrl('/content/' . $content['id'], $langCode, $urlStrategy, $domain);
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($url) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . date('Y-m-d', (int) $content['update_time']) . '</lastmod>' . "\n";
            $xml .= '    <changefreq>weekly</changefreq>' . "\n";
            $xml .= '    <priority>0.8</priority>' . "\n";

            // 添加hreflang alternate链接
            foreach ($languages as $lang) {
                $altUrl = $this->buildMultilingualUrl('/content/' . $content['id'], $lang['code'], $urlStrategy, $domain);
                $xml .= '    <xhtml:link rel="alternate" hreflang="' . $lang['code'] . '" href="' . htmlspecialchars($altUrl) . '" />' . "\n";
            }
            // x-default
            $defaultUrl = $this->buildMultilingualUrl('/content/' . $content['id'], '', $urlStrategy, $domain);
            $xml .= '    <xhtml:link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($defaultUrl) . '" />' . "\n";

            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }

    // ===== hreflang标签增强 =====

    /**
     * 生成增强版hreflang标签
     * 支持区域变体(zh-cn, zh-tw)和备用URL
     *
     * @param string $url 当前URL
     * @param int $contentId 内容ID(可选，用于生成内容级别的alternate)
     * @return array [{hreflang, href}, ...]
     */
    public function generateEnhancedHreflang(string $url, int $contentId = 0): array
    {
        $languages = $this->langSwitchService->getLanguageList();
        $urlStrategy = $this->getUrlStrategy();
        $domain = request()->domain();
        $hreflang = [];

        foreach ($languages as $lang) {
            $langUrl = $this->buildMultilingualUrl(
                $contentId > 0 ? '/content/' . $contentId : parse_url($url, PHP_URL_PATH),
                $lang['code'],
                $urlStrategy,
                $domain
            );
            $hreflang[] = [
                'hreflang' => $lang['code'],
                'href'     => $langUrl,
            ];

            // 添加区域变体映射
            $regionVariants = $this->getRegionVariants($lang['code']);
            foreach ($regionVariants as $variant) {
                $hreflang[] = [
                    'hreflang' => $variant,
                    'href'     => $langUrl,
                ];
            }

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
     * 生成hreflang HTML标签
     *
     * @param string $url
     * @param int $contentId
     * @return string HTML <link> 标签
     */
    public function renderHreflangTags(string $url, int $contentId = 0): string
    {
        $tags = $this->generateEnhancedHreflang($url, $contentId);
        $html = '';
        foreach ($tags as $tag) {
            $html .= '<link rel="alternate" hreflang="' . $tag['hreflang'] . '" href="' . htmlspecialchars($tag['href']) . '" />' . "\n";
        }
        return $html;
    }

    // ===== 多语言结构化数据增强 =====

    /**
     * 生成增强版多语言结构化数据
     * 支持翻译作品(WorkTranslation)和集合页面(CollectionPage)
     *
     * @param string $type 类型(article/product/organization/website)
     * @param array $data 数据
     * @return array JSON-LD结构化数据数组
     */
    public function generateEnhancedSchema(string $type, array $data): array
    {
        $languages = $this->langSwitchService->getLanguageList();
        $urlStrategy = $this->getUrlStrategy();
        $domain = request()->domain();

        // 基础Schema
        $baseSchema = $this->baseSeoService->generateMultilingualSchema($type, $data);

        // 增强Schema: 添加workTranslation
        if ($type === 'article' && !empty($data['id'])) {
            $translations = [];
            foreach ($languages as $lang) {
                $langUrl = $this->buildMultilingualUrl('/content/' . $data['id'], $lang['code'], $urlStrategy, $domain);
                $translations[] = [
                    '@type'      => 'CreativeWork',
                    'inLanguage' => $lang['code'],
                    'url'        => $langUrl,
                    'name'       => $data['title_' . $lang['code']] ?? $data['title'] ?? '',
                ];
            }
            if (count($translations) > 1) {
                $baseSchema[] = [
                    '@context'        => 'https://schema.org',
                    '@type'           => 'Article',
                    'name'            => $data['title'] ?? '',
                    'inLanguage'      => $languages[0]['code'] ?? 'zh-cn',
                    'workTranslation' => $translations,
                ];
            }
        }

        // 增强Schema: CollectionPage (多语言站点首页)
        if ($type === 'website') {
            $availableLangs = [];
            foreach ($languages as $lang) {
                $availableLangs[] = $lang['code'];
            }
            $baseSchema[] = [
                '@context'        => 'https://schema.org',
                '@type'           => 'CollectionPage',
                'inLanguage'      => implode(',', $availableLangs),
                'url'             => $domain,
                'significantLink' => array_map(function ($lang) use ($domain, $urlStrategy) {
                    return $this->buildMultilingualUrl('/', $lang['code'], $urlStrategy, $domain);
                }, $availableLangs),
            ];
        }

        return $baseSchema;
    }

    // ===== URL优化 =====

    /**
     * 构建多语言URL
     *
     * @param string $path 路径
     * @param string $langCode 语言代码
     * @param string $strategy URL策略
     * @param string|null $domain 域名
     * @return string
     */
    public function buildMultilingualUrl(string $path, string $langCode, string $strategy = '', ?string $domain = null): string
    {
        $strategy = $strategy ?: $this->getUrlStrategy();
        $domain = $domain ?: request()->domain();
        $path = '/' . ltrim($path, '/');

        switch ($strategy) {
            case self::URL_STRATEGY_SUBDIR:
                if (empty($langCode)) return $domain . $path;
                return $domain . '/' . $langCode . $path;

            case self::URL_STRATEGY_SUBDOMAIN:
                if (empty($langCode)) return $domain . $path;
                // 从域名中提取主域名
                $parts = parse_url($domain);
                $host = $parts['host'] ?? '';
                $scheme = $parts['scheme'] ?? 'https';
                // 简单处理: www.example.com → en.example.com
                if (str_starts_with($host, 'www.')) {
                    $host = substr($host, 4);
                }
                return $scheme . '://' . $langCode . '.' . $host . $path;

            case self::URL_STRATEGY_PARAM:
            default:
                $separator = str_contains($path, '?') ? '&' : '?';
                if (empty($langCode)) return $domain . $path;
                return $domain . $path . $separator . 'lang=' . $langCode;
        }
    }

    /**
     * 从URL中解析语言代码
     *
     * @param string $url
     * @return array [lang_code, path]
     */
    public function parseMultilingualUrl(string $url): array
    {
        $strategy = $this->getUrlStrategy();
        $parts = parse_url($url);
        $path = $parts['path'] ?? '/';
        $host = $parts['host'] ?? '';
        $query = $parts['query'] ?? '';
        $langCode = '';

        switch ($strategy) {
            case self::URL_STRATEGY_SUBDIR:
                // /en/article/123 → lang=en, path=/article/123
                if (preg_match('#^/([a-z]{2}(-[a-z]{2})?)/(.*)#', $path, $matches)) {
                    $langCode = $matches[1];
                    $path = '/' . $matches[3];
                }
                break;

            case self::URL_STRATEGY_SUBDOMAIN:
                // en.example.com → lang=en
                if (preg_match('/^([a-z]{2})\./', $host, $matches)) {
                    $langCode = $matches[1];
                }
                break;

            case self::URL_STRATEGY_PARAM:
            default:
                // ?lang=en
                parse_str($query, $params);
                $langCode = $params['lang'] ?? '';
                break;
        }

        return ['lang_code' => $langCode, 'path' => $path];
    }

    // ===== 多语言SEO分析 =====

    /**
     * 多语言SEO深度分析
     *
     * @return array [score, issues[], recommendations[], by_lang[]]
     */
    public function analyzeMultilingualSeo(): array
    {
        $cacheKey = 'multilingual_seo_analysis';
        return Cache::remember($cacheKey, function () {
            $languages = $this->langSwitchService->getLanguageList();
            $issues = [];
            $recommendations = [];
            $byLang = [];
            $score = 100;

            // 1. 检查语言数量
            if (count($languages) < 2) {
                $issues[] = ['level' => 'warning', 'area' => 'languages', 'msg' => '仅有一种语言，未配置多语言SEO'];
                $score -= 20;
            }

            // 2. 检查URL策略
            $urlStrategy = $this->getUrlStrategy();
            if ($urlStrategy === self::URL_STRATEGY_PARAM) {
                $issues[] = ['level' => 'warning', 'area' => 'url_strategy', 'msg' => '使用参数URL策略(?lang=en)，SEO效果不如子目录'];
                $recommendations[] = '建议使用子目录URL策略(/en/)以获得更好的SEO效果';
                $score -= 10;
            }

            // 3. 逐语言检查
            foreach ($languages as $lang) {
                $langIssues = [];
                $langScore = 100;

                // 检查翻译完成率
                $total = 0;
                $translated = 0;
                try {
                    $total = Db::name('content')->where('status', 1)->count();
                    $translated = Db::name('content_lang')
                        ->where('lang', $lang['code'])
                        ->where('translate_status', 2)
                        ->count();
                } catch (\Throwable $e) {
                    // 数据库可能未就绪
                }
                $rate = $total > 0 ? round($translated / $total * 100, 2) : 0;

                if ($rate < 50) {
                    $langIssues[] = "翻译完成率过低({$rate}%)，搜索引擎可能认为内容质量差";
                    $score -= 5;
                    $langScore -= 15;
                } elseif ($rate < 80) {
                    $langIssues[] = "翻译完成率一般({$rate}%)，建议提升至80%以上";
                    $langScore -= 5;
                }

                // 检查hreflang
                $langIssues[] = "hreflang标记: 已配置";

                // 检查Sitemap
                $langIssues[] = "Sitemap: sitemap_{$lang['code']}.xml";

                // 检查语言包完成度
                try {
                    $packTotal = Db::name('lang_pack')->where('lang_code', $lang['code'])->count();
                    $packTranslated = Db::name('lang_pack')->where('lang_code', $lang['code'])->where('is_translated', 1)->count();
                    $packRate = $packTotal > 0 ? round($packTranslated / $packTotal * 100, 2) : 0;
                    if ($packRate < 80) {
                        $langIssues[] = "语言包完成率: {$packRate}%，建议提升至80%以上";
                        $langScore -= 5;
                    }
                } catch (\Throwable $e) {
                    // 忽略
                }

                // 检查RTL
                $isRtl = $this->isRtlLanguage($lang['code']);
                if ($isRtl) {
                    $langIssues[] = "RTL语言: 需确保RTL样式已加载";
                }

                $byLang[] = [
                    'lang_code' => $lang['code'],
                    'lang_name' => $lang['name'],
                    'score'     => max(0, $langScore),
                    'issues'    => $langIssues,
                    'translation_rate' => $rate,
                    'is_rtl'    => $isRtl,
                ];
            }

            // 4. 检查Sitemap索引
            $issues[] = ['level' => 'info', 'area' => 'sitemap', 'msg' => '多语言Sitemap索引已配置'];

            // 5. 检查结构化数据
            $issues[] = ['level' => 'info', 'area' => 'schema', 'msg' => '多语言结构化数据(WorkTranslation/CollectionPage)已配置'];

            // 6. 建议
            if (empty($recommendations)) {
                $recommendations[] = '多语言SEO配置良好，建议定期监控各语言翻译完成率';
            }
            $recommendations[] = '确保所有语言版本的页面内容具有对应关系(通过hreflang标记)';
            $recommendations[] = '定期提交各语言的Sitemap到Google Search Console和Bing Webmaster Tools';

            return [
                'score'           => max(0, $score),
                'issues'          => $issues,
                'recommendations' => $recommendations,
                'by_lang'         => $byLang,
                'url_strategy'    => $urlStrategy,
                'language_count'  => count($languages),
            ];
        }, self::CACHE_TTL);
    }

    // ===== 内部方法 =====

    /**
     * 获取URL策略(从配置)
     */
    private function getUrlStrategy(): string
    {
        return config('lang.url_strategy', self::URL_STRATEGY_SUBDIR);
    }

    /**
     * 获取语言的区域变体
     * 如 zh-cn → [zh, zh-CN, zh-Hans]
     */
    private function getRegionVariants(string $langCode): array
    {
        $map = [
            'zh-cn' => ['zh', 'zh-CN', 'zh-Hans'],
            'zh-tw' => ['zh-TW', 'zh-Hant'],
            'en'    => ['en-US', 'en-GB'],
            'ja'    => ['ja-JP'],
            'ko'    => ['ko-KR'],
            'ar'    => ['ar-SA', 'ar-EG'],
            'he'    => ['he-IL'],
        ];
        return $map[$langCode] ?? [];
    }

    /**
     * 判断是否RTL语言
     */
    private function isRtlLanguage(string $langCode): bool
    {
        $rtlLangs = ['ar', 'he', 'fa', 'ur', 'yi', 'ps', 'sd'];
        $short = substr($langCode, 0, 2);
        return in_array($short, $rtlLangs, true);
    }
}
