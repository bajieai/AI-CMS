<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\seo;

/**
 * V2.9.15: Schema.org 结构化标记服务
 *
 * 生成5种页面类型的JSON-LD结构化数据：
 * 1. Article / NewsArticle — 文章详情页
 * 2. BreadcrumbList — 面包屑导航
 * 3. Organization — 企业/网站信息
 * 4. WebSite (含SitelinksSearchBox) — 首页
 * 5. WebPage — 通用页面兜底
 * 6. Product — 预留（V2.9.16实现）
 */
class SchemaMarkupService
{
    protected string $siteName;
    protected string $siteUrl;
    protected string $logoUrl;

    public function __construct()
    {
        $this->siteName = config('site.name', 'AI-CMS');
        $this->siteUrl  = rtrim(config('site.url', request()->domain()), '/');
        $this->logoUrl  = config('site.logo', $this->siteUrl . '/static/logo.png');
    }

    // ============================================================
    //  5种Schema生成方法
    // ============================================================

    /**
     * 生成Article Schema（文章详情页）
     */
    public function generateArticle(array $article): array
    {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Article',
            'headline'    => $article['title'] ?? '',
            'description' => $article['description'] ?? '',
            'image'       => $article['image'] ?? '',
            'url'         => $article['url'] ?? '',
            'datePublished'=> $this->toIso8601($article['create_time'] ?? time()),
            'dateModified' => $this->toIso8601($article['update_time'] ?? time()),
            'author'      => [
                '@type' => 'Organization',
                'name'  => $this->siteName,
                'url'   => $this->siteUrl,
            ],
            'publisher'   => [
                '@type' => 'Organization',
                'name'  => $this->siteName,
                'logo'  => [
                    '@type' => 'ImageObject',
                    'url'   => $this->logoUrl,
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => $article['url'] ?? '',
            ],
        ];

        // 如果文章是新闻类，使用NewsArticle
        if (!empty($article['is_news'])) {
            $schema['@type'] = 'NewsArticle';
        }

        return $schema;
    }

    /**
     * 生成BreadcrumbList Schema（面包屑）
     */
    public function generateBreadcrumb(array $items): array
    {
        $itemList = [];
        $position = 1;

        foreach ($items as $item) {
            $itemList[] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => $item['name'] ?? '',
                'item'     => $item['url'] ?? '',
            ];
        }

        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'BreadcrumbList',
            'itemListElement' => $itemList,
        ];
    }

    /**
     * 生成Organization Schema（企业信息）
     */
    public function generateOrganization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => $this->siteName,
            'url'      => $this->siteUrl,
            'logo'     => $this->logoUrl,
        ];
    }

    /**
     * 生成WebSite Schema（含SitelinksSearchBox）
     */
    public function generateWebSite(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type'    => 'WebSite',
            'name'     => $this->siteName,
            'url'      => $this->siteUrl,
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => $this->siteUrl . '/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * 生成WebPage Schema（通用页面兜底）
     */
    public function generateWebPage(array $page = []): array
    {
        return [
            '@context'    => 'https://schema.org',
            '@type'       => 'WebPage',
            'name'        => $page['title'] ?? $this->siteName,
            'description' => $page['description'] ?? '',
            'url'         => $page['url'] ?? $this->siteUrl,
        ];
    }

    /**
     * 生成Product Schema（预留V2.9.16）
     *
     * @todo V2.9.16 实现Product类型Schema.org结构化标记
     */
    public function generateProduct(array $product): ?array
    {
        // 预留：V2.9.16 实现Product类型
        // 当前返回null，不在页面注入
        return null;
    }

    // ============================================================
    //  组合与输出
    // ============================================================

    /**
     * 合并多个Schema为一个数组（用于页面注入多个JSON-LD）
     */
    public function concatSchemas(array ...$schemas): array
    {
        $result = [];
        foreach ($schemas as $schema) {
            if ($schema === null) {
                continue;
            }
            if (isset($schema['@context'])) {
                $result[] = $schema;
            }
        }
        return $result;
    }

    /**
     * 将Schema数组转换为JSON-LD脚本标签
     */
    public function toJsonLd(array $schemas): string
    {
        if (empty($schemas)) {
            return '';
        }

        // 单条Schema直接输出，多条用数组包裹
        if (count($schemas) === 1) {
            $json = json_encode($schemas[0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $json = json_encode($schemas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return '<script type="application/ld+json">' . $json . '</script>' . "\n";
    }

    // ============================================================
    //  辅助方法
    // ============================================================

    /**
     * 时间戳转ISO 8601格式
     */
    protected function toIso8601($time): string
    {
        if (is_numeric($time)) {
            return date('c', (int) $time);
        }
        return (string) $time;
    }
}
