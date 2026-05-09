<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Content;
use app\common\model\Comment;
use think\facade\Config;
use think\facade\Request;

/**
 * Schema.org 结构化数据服务 - V2.9.2 M19b
 * 生成JSON-LD / Open Graph / BreadcrumbList
 */
class SchemaService
{
    /**
     * 生成文章/产品 JSON-LD
     */
    public static function article(Content $content): string
    {
        if (!Config::get('seo.schema_enabled', 1)) {
            return '';
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => self::getSchemaType((int) $content->type),
            'headline' => $content->title,
            'description' => $content->seo_description ?: mb_substr(strip_tags($content->content), 0, 200),
            'url'      => Request::domain() . $content->url,
            'datePublished' => date('c', $content->publish_time ?: $content->create_time),
            'dateModified'  => date('c', $content->update_time),
        ];

        if ($content->cover) {
            $schema['image'] = self::resolveUrl($content->cover);
        }

        if ($content->author) {
            $schema['author'] = [
                '@type' => 'Person',
                'name'  => $content->author,
            ];
        }

        // 关联分类
        if ($content->cate) {
            $schema['articleSection'] = $content->cate->name;
        }

        // 关联评价（AggregateRating）
        $rating = self::buildAggregateRating($content->id);
        if ($rating) {
            $schema['aggregateRating'] = $rating;
        }

        return self::wrapJsonLd($schema);
    }

    /**
     * 生成BreadcrumbList JSON-LD
     */
    public static function breadcrumb(array $items): string
    {
        if (!Config::get('seo.schema_enabled', 1)) {
            return '';
        }

        $listItems = [];
        $position = 1;
        foreach ($items as $name => $url) {
            $listItems[] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => $name,
                'item'     => $url,
            ];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'BreadcrumbList',
            'itemListElement' => $listItems,
        ];

        return self::wrapJsonLd($schema);
    }

    /**
     * 生成WebSite JSON-LD（首页SearchAction）
     */
    public static function website(): string
    {
        if (!Config::get('seo.schema_enabled', 1)) {
            return '';
        }

        $siteName = Config::get('site.site_name', 'AI-CMS');
        $siteUrl  = Request::domain();
        $searchUrl = $siteUrl . '/search?q={search_term_string}';

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'WebSite',
            'name'     => $siteName,
            'url'      => $siteUrl,
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $searchUrl,
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        return self::wrapJsonLd($schema);
    }

    /**
     * 生成Open Graph Meta标签数组
     */
    public static function openGraph(Content $content): array
    {
        if (!Config::get('seo.og_enabled', 1)) {
            return [];
        }

        $domain = Request::domain();
        $tags = [
            'og:type'        => self::getOgType((int) $content->type),
            'og:title'       => $content->seo_title ?: $content->title,
            'og:description' => $content->seo_description ?: mb_substr(strip_tags($content->content), 0, 200),
            'og:url'         => $domain . $content->url,
            'og:site_name'   => Config::get('site.site_name', 'AI-CMS'),
            'og:locale'      => self::getLocale($content->lang),
        ];

        if ($content->cover) {
            $tags['og:image'] = self::resolveUrl($content->cover);
        }

        // 多语言alternate
        $langs = LanguageService::getEnabledLanguages();
        $alternates = [];
        foreach ($langs as $lang) {
            if ($lang['code'] === $content->lang) continue;
            $alternates[] = [
                'href'   => $domain . $content->url . '?lang=' . $lang['code'],
                'locale' => self::getLocale($lang['code']),
            ];
        }
        $tags['og:locale:alternate'] = $alternates;

        return $tags;
    }

    /**
     * 生成Twitter Card Meta标签数组
     */
    public static function twitterCard(Content $content): array
    {
        $tags = [
            'twitter:card'        => 'summary_large_image',
            'twitter:title'       => $content->seo_title ?: $content->title,
            'twitter:description' => $content->seo_description ?: mb_substr(strip_tags($content->content), 0, 200),
        ];

        if ($content->cover) {
            $tags['twitter:image'] = self::resolveUrl($content->cover);
        }

        return $tags;
    }

    /**
     * 构建AggregateRating
     */
    protected static function buildAggregateRating(int $contentId): ?array
    {
        try {
            $avgRating = Comment::where('content_id', $contentId)
                ->where('status', 1)
                ->where('rating', '>', 0)
                ->avg('rating');

            $count = Comment::where('content_id', $contentId)
                ->where('status', 1)
                ->where('rating', '>', 0)
                ->count();

            if ($count == 0 || $avgRating == 0) {
                return null;
            }

            return [
                '@type'       => 'AggregateRating',
                'ratingValue' => round((float) $avgRating, 1),
                'reviewCount' => (int) $count,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 包装为JSON-LD script标签
     */
    protected static function wrapJsonLd(array $schema): string
    {
        $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return '<script type="application/ld+json">' . $json . '</script>' . "\n";
    }

    /**
     * 解析URL（相对路径转绝对路径）
     */
    protected static function resolveUrl(string $url): string
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }
        return Request::domain() . $url;
    }

    /**
     * 内容类型 → Schema.org类型
     */
    protected static function getSchemaType(int $type): string
    {
        $map = [
            1 => 'Product',
            2 => 'Article',
            3 => 'NewsArticle',
            4 => 'SoftwareApplication',
            5 => 'JobPosting',
            6 => 'Article',
        ];
        return $map[$type] ?? 'Article';
    }

    /**
     * 内容类型 → OG类型
     */
    protected static function getOgType(int $type): string
    {
        $map = [
            1 => 'product',
            2 => 'article',
            3 => 'article',
            4 => 'product',
            5 => 'article',
            6 => 'website',
        ];
        return $map[$type] ?? 'article';
    }

    /**
     * 语言代码 → OG locale
     */
    protected static function getLocale(string $langCode): string
    {
        $map = [
            'zh-CN' => 'zh_CN',
            'zh-TW' => 'zh_TW',
            'en'    => 'en_US',
            'ja'    => 'ja_JP',
            'ko'    => 'ko_KR',
            'fr'    => 'fr_FR',
            'de'    => 'de_DE',
            'es'    => 'es_ES',
        ];
        return $map[$langCode] ?? str_replace('-', '_', $langCode);
    }
}
