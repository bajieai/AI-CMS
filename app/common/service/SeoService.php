<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Content as ContentModel;
use app\common\model\Cate as CateModel;
use think\facade\Cache;

/**
 * SEO服务
 */
class SeoService
{
    /**
     * 生成Sitemap XML
     */
    public function generateSitemap(): string
    {
        return Cache::tag(CacheService::TAG_SEO)->remember('sitemap_xml', function () {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            // 首页
            $xml .= $this->buildUrlEntry(url('/'), '1.0', 'daily');

            // 分类页
            $cates = CateModel::where('status', 1)->select();
            foreach ($cates as $cate) {
                $xml .= $this->buildUrlEntry(url('/cate/' . $cate->id), '0.8', 'weekly');
            }

            // 内容页
            $contents = ContentModel::where('status', 2)->select();
            foreach ($contents as $content) {
                $xml .= $this->buildUrlEntry(url($content->url), '0.6', 'monthly', date('c', $content->update_time));
            }

            $xml .= '</urlset>';
            return $xml;
        });
    }

    protected function buildUrlEntry(string $loc, string $priority, string $changefreq, string $lastmod = ''): string
    {
        $entry = "  <url>\n";
        $entry .= "    <loc>{$loc}</loc>\n";
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
     * 生成robots.txt
     */
    public function generateRobots(): string
    {
        return config('seo.seo_robots_txt') ?: "User-agent: *\nAllow: /\nDisallow: /admin/";
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
        } catch (\Throwable $e) {
            return 0;
        }
    }
}