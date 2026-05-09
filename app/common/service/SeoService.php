<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Content as ContentModel;
use app\common\model\Cate as CateModel;
use app\common\model\Tag as TagModel;
use think\facade\Cache;
use think\facade\Config;

/**
 * SEO服务 - V2.9.2 M19b增强
 * Sitemap索引拆分+增量缓存+robots动态生成
 */
class SeoService
{
    /** Sitemap单文件最大URL数 */
    protected int $maxUrlsPerSitemap = 50000;

    /** Sitemap分页查询每页数量 */
    protected int $chunkSize = 5000;

    /**
     * 生成主Sitemap（含索引拆分）
     */
    public function generateSitemap(): string
    {
        return Cache::tag(CacheService::TAG_SEO)->remember('sitemap_xml', function () {
            $totalUrls = $this->countTotalUrls();

            // 小站点直接生成单文件
            if ($totalUrls <= $this->maxUrlsPerSitemap) {
                return $this->generateSingleSitemap();
            }

            // 大站点生成索引文件
            return $this->generateSitemapIndex();
        }, 86400);
    }

    /**
     * 生成单文件Sitemap
     */
    protected function generateSingleSitemap(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= $this->buildAllUrlEntries();
        $xml .= '</urlset>';
        return $xml;
    }

    /**
     * 生成Sitemap索引文件（大站点拆分）
     */
    protected function generateSitemapIndex(): string
    {
        $totalContent = ContentModel::where('status', 2)->count();
        $totalCate = CateModel::where('status', 1)->count();
        $totalTag = Config::get('seo.sitemap_includes_tag', 1) ? TagModel::count() : 0;
        $staticUrls = 1; // 首页

        $totalUrls = $totalContent + $totalCate + $totalTag + $staticUrls;
        $chunks = (int) ceil($totalUrls / $this->maxUrlsPerSitemap);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        for ($i = 1; $i <= $chunks; $i++) {
            $xml .= "  <sitemap>\n";
            $xml .= "    <loc>" . url('/sitemap/' . $i . '.xml') . "</loc>\n";
            $xml .= "    <lastmod>" . date('c') . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';
        return $xml;
    }

    /**
     * 生成分页Sitemap子文件
     */
    public function generateSitemapChunk(int $page): string
    {
        $cacheKey = 'sitemap_chunk_' . $page;
        return Cache::tag(CacheService::TAG_SEO)->remember($cacheKey, function () use ($page) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            $offset = ($page - 1) * $this->maxUrlsPerSitemap;
            $limit = $this->maxUrlsPerSitemap;
            $added = 0;

            // 首页（仅在第一页）
            if ($page === 1) {
                $xml .= $this->buildUrlEntry(url('/'), '1.0', 'daily');
                $added++;
            }

            // 分类页
            $cateOffset = max(0, $offset - $added);
            $cates = CateModel::where('status', 1)
                ->limit($cateOffset, $limit - $added)
                ->select();
            foreach ($cates as $cate) {
                $xml .= $this->buildUrlEntry(url('/cate/' . $cate->id), '0.8', 'weekly');
                $added++;
            }

            // 标签页
            if ($added < $limit && Config::get('seo.sitemap_includes_tag', 1)) {
                $tagOffset = max(0, $offset - $added - $this->countCateUrls());
                $tags = TagModel::limit($tagOffset, $limit - $added)->select();
                foreach ($tags as $tag) {
                    $xml .= $this->buildUrlEntry(url('/tag/' . $tag->id), '0.6', 'weekly');
                    $added++;
                }
            }

            // 内容页
            if ($added < $limit) {
                $contentOffset = max(0, $offset - $added - $this->countCateUrls() - $this->countTagUrls());
                $contents = ContentModel::where('status', 2)
                    ->where('translation_of', 0) // 只收录原始内容
                    ->order('id', 'desc')
                    ->limit($contentOffset, $limit - $added)
                    ->select();
                foreach ($contents as $content) {
                    $xml .= $this->buildUrlEntry(
                        url($content->url),
                        '0.6',
                        'monthly',
                        date('c', $content->update_time)
                    );
                    $added++;
                }
            }

            $xml .= '</urlset>';
            return $xml;
        }, 86400);
    }

    /**
     * 统计总URL数
     */
    protected function countTotalUrls(): int
    {
        return 1 // 首页
            + $this->countCateUrls()
            + $this->countTagUrls()
            + ContentModel::where('status', 2)->where('translation_of', 0)->count();
    }

    protected function countCateUrls(): int
    {
        return CateModel::where('status', 1)->count();
    }

    protected function countTagUrls(): int
    {
        return Config::get('seo.sitemap_includes_tag', 1) ? TagModel::count() : 0;
    }

    /**
     * 生成所有URL条目（用于单文件Sitemap）
     */
    protected function buildAllUrlEntries(): string
    {
        $xml = '';

        // 首页
        $xml .= $this->buildUrlEntry(url('/'), '1.0', 'daily');

        // 分类页
        $includesCate = Config::get('seo.sitemap_includes_cate', 1);
        if ($includesCate) {
            $cates = CateModel::where('status', 1)->select();
            foreach ($cates as $cate) {
                $xml .= $this->buildUrlEntry(url('/cate/' . $cate->id), '0.8', 'weekly');
            }
        }

        // 标签页
        $includesTag = Config::get('seo.sitemap_includes_tag', 1);
        if ($includesTag) {
            $tags = TagModel::select();
            foreach ($tags as $tag) {
                $xml .= $this->buildUrlEntry(url('/tag/' . $tag->id), '0.6', 'weekly');
            }
        }

        // 内容页（只收录原始内容，翻译内容在hreflang中处理）
        $contents = ContentModel::where('status', 2)
            ->where('translation_of', 0)
            ->order('id', 'desc')
            ->select();
        foreach ($contents as $content) {
            $xml .= $this->buildUrlEntry(
                url($content->url),
                '0.6',
                'monthly',
                date('c', $content->update_time)
            );
        }

        return $xml;
    }

    protected function buildUrlEntry(string $loc, string $priority, string $changefreq, string $lastmod = ''): string
    {
        $entry = "  <url>\n";
        $entry .= "    <loc>" . htmlspecialchars($loc, ENT_XML1, 'UTF-8') . "</loc>\n";
        if ($lastmod) {
            $entry .= "    <lastmod>{$lastmod}</lastmod>\n";
        }
        $entry .= "    <changefreq>{$changefreq}</changefreq>\n";
        $entry .= "    <priority>{$priority}</priority>\n";
        $entry .= "  </url>\n";
        return $entry;
    }

    /**
     * 保存Sitemap到文件
     */
    public function saveSitemap(): bool
    {
        $xml = $this->generateSitemap();
        $path = public_path() . 'sitemap.xml';
        return file_put_contents($path, $xml) !== false;
    }

    /**
     * 生成robots.txt（动态）
     */
    public function generateRobots(): string
    {
        $lines = [];
        $lines[] = 'User-agent: *';
        $lines[] = 'Allow: /';
        $lines[] = 'Disallow: /admin/';
        $lines[] = 'Disallow: /member/';
        $lines[] = 'Disallow: /api/';
        $lines[] = '';
        $lines[] = 'Sitemap: ' . url('/sitemap.xml');
        $lines[] = '';
        $lines[] = '# AI-CMS v2.9.2';

        $custom = Config::get('seo.seo_robots_txt', '');
        if ($custom) {
            $lines[] = '';
            $lines[] = '# 自定义规则';
            $lines[] = $custom;
        }

        return implode("\n", $lines);
    }

    /**
     * 保存robots.txt
     */
    public function saveRobots(): bool
    {
        $content = $this->generateRobots();
        $path = public_path() . 'robots.txt';
        return file_put_contents($path, $content) !== false;
    }

    /**
     * V2.9.2 M19b-hreflang: 生成指定语言的Sitemap
     */
    public function generateLangSitemap(string $langCode): string
    {
        $cacheKey = 'sitemap_lang_' . $langCode;
        return Cache::tag(CacheService::TAG_SEO)->remember($cacheKey, function () use ($langCode) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
            $xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

            $contents = ContentModel::where('status', 2)
                ->where(function ($query) use ($langCode) {
                    $query->where('lang', $langCode)->whereOr('translation_of', 0);
                })
                ->order('id', 'desc')
                ->select();

            $languages = LanguageService::getEnabledLanguages();

            foreach ($contents as $content) {
                if ($content->translation_of > 0 && $content->lang !== $langCode) {
                    continue;
                }

                $loc = url($content->url) . '?lang=' . $langCode;
                $entry = "  <url>\n";
                $entry .= "    <loc>" . htmlspecialchars($loc, ENT_XML1, 'UTF-8') . "</loc>\n";
                $entry .= "    <lastmod>" . date('c', $content->update_time) . "</lastmod>\n";
                $entry .= "    <changefreq>monthly</changefreq>\n";
                $entry .= "    <priority>0.6</priority>\n";

                foreach ($languages as $lang) {
                    $altLang = $lang['code'];
                    $altUrl = url($content->url) . '?lang=' . $altLang;
                    $entry .= "    <xhtml:link rel=\"alternate\" hreflang=\"" . $this->formatHreflang($altLang) . "\" href=\"" . htmlspecialchars($altUrl, ENT_XML1, 'UTF-8') . "\" />\n";
                }
                $defaultUrl = url($content->url);
                $entry .= "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"" . htmlspecialchars($defaultUrl, ENT_XML1, 'UTF-8') . "\" />\n";

                $entry .= "  </url>\n";
                $xml .= $entry;
            }

            $xml .= '</urlset>';
            return $xml;
        }, 86400);
    }

    protected function formatHreflang(string $code): string
    {
        return str_replace('_', '-', $code);
    }

    /**
     * 生成首页hreflang标签（用于layout.html head区）
     */
    public static function generateHreflangTags(string $currentUrl, ?string $currentLang = null): string
    {
        try {
            $languages = LanguageService::getEnabledLanguages();
            $currentLang = $currentLang ?: LanguageService::getCurrentLang();
            $tags = [];

            foreach ($languages as $lang) {
                $code = $lang['code'];
                $href = $currentUrl . (strpos($currentUrl, '?') !== false ? '&' : '?') . 'lang=' . $code;
                $tags[] = '<link rel="alternate" hreflang="' . str_replace('_', '-', $code) . '" href="' . $href . '" />';
            }

            $tags[] = '<link rel="alternate" hreflang="x-default" href="' . $currentUrl . '" />';
            return implode("\n", $tags);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * 搜索引擎Ping
     */
    public static function pingSearchEngines(string $sitemapUrl): array
    {
        $engines = [
            'google' => 'https://www.google.com/ping?sitemap=' . urlencode($sitemapUrl),
            'bing'   => 'https://www.bing.com/ping?sitemap=' . urlencode($sitemapUrl),
        ];

        $results = [];
        foreach ($engines as $name => $url) {
            try {
                $client = new \GuzzleHttp\Client(['timeout' => 10]);
                $response = $client->get($url);
                $results[$name] = ['success' => $response->getStatusCode() < 400, 'status' => $response->getStatusCode()];
            } catch (\Throwable $e) {
                $results[$name] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * V2.9.2: 清除Sitemap缓存（内容发布/更新时调用）
     */
    public static function clearSitemapCache(): void
    {
        try {
            Cache::tag(CacheService::TAG_SEO)->clear();
        } catch (\Throwable $e) {
            \think\facade\Log::warning('[SeoService] 清除Sitemap缓存失败: ' . $e->getMessage());
        }
    }

    /**
     * 生成JSON-LD结构化数据
     */
    public function buildJsonLd(array $data): string
    {
        $json = [
            '@context' => 'https://schema.org',
            '@type'    => $data['type'] ?? 'Article',
            'headline' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'url' => $data['url'] ?? '',
            'datePublished' => isset($data['create_time']) ? date('c', $data['create_time']) : '',
            'dateModified' => isset($data['update_time']) ? date('c', $data['update_time']) : '',
        ];

        if (!empty($data['cover'])) {
            $json['image'] = $data['cover'];
        }

        return json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * 检查死链
     */
    public function checkDeadLinks(): array
    {
        $results = [];
        $contents = ContentModel::where('status', 2)->field('id,title,content')->select();

        foreach ($contents as $content) {
            preg_match_all('/href=["\'](https?:\/\/[^"\']+)["\']/i', $content->content ?? '', $matches);
            $urls = array_unique($matches[1] ?? []);

            foreach ($urls as $url) {
                $statusCode = $this->checkUrl($url);
                if ($statusCode >= 400 || $statusCode === 0) {
                    $results[] = [
                        'url' => $url,
                        'status_code' => $statusCode,
                        'source' => '/content/' . $content->id,
                        'content_id' => $content->id,
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * 检查单个URL的HTTP状态码
     */
    protected function checkUrl(string $url): int
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_NOBODY         => true,
                CURLOPT_USERAGENT      => 'AI-CMS-SEOBot/2.3',
            ]);
            curl_exec($ch);
            $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $statusCode;
        } catch (\Throwable) {
            return 0;
        }
    }
}