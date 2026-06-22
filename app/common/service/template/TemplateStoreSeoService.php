<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateStore;
use think\facade\Cache;
use think\facade\Db;

/**
 * 模板商店SEO服务 — V2.9.28 M-8
 */
class TemplateStoreSeoService
{
    private const CACHE_TAG = 'template_store_seo';

    /**
     * 获取商店页面SEO配置
     */
    public function getStoreSeoConfig(): array
    {
        return Cache::tag(self::CACHE_TAG)->remember('store_seo_config', function() {
            return [
                'home_title' => Db::name('config')->where('name', 'template_store_seo_title')->value('value') ?: '模板商店 - 八界AI-CMS',
                'home_description' => Db::name('config')->where('name', 'template_store_seo_description')->value('value') ?: '专业CMS模板商店',
                'home_keywords' => Db::name('config')->where('name', 'template_store_seo_keywords')->value('value') ?: 'CMS模板,网站模板',
            ];
        }, 3600);
    }

    /**
     * 保存商店页面SEO配置
     */
    public function saveStoreSeoConfig(array $data): array
    {
        $configs = [
            'template_store_seo_title' => $data['home_title'] ?? '',
            'template_store_seo_description' => $data['home_description'] ?? '',
            'template_store_seo_keywords' => $data['home_keywords'] ?? '',
        ];

        foreach ($configs as $name => $value) {
            $existing = Db::name('config')->where('name', $name)->find();
            if ($existing) {
                Db::name('config')->where('name', $name)->update(['value' => $value]);
            } else {
                Db::name('config')->insert(['name' => $name, 'value' => $value, 'group' => 'template']);
            }
        }

        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => 'SEO配置已保存'];
    }

    /**
     * 获取模板详情页自动生成的SEO信息
     */
    public function getTemplateSeo(int $templateId): array
    {
        $cacheKey = 'template_seo_' . $templateId;
        return Cache::tag(self::CACHE_TAG)->remember($cacheKey, function() use ($templateId) {
            $template = TemplateStore::find($templateId);
            if (!$template) {
                return ['title' => '', 'description' => '', 'keywords' => ''];
            }

            // 优先使用手动配置的SEO字段
            $title = $template->seo_title ?: $template->name . ' - 模板商店';
            $description = $template->seo_description ?: mb_substr(strip_tags($template->description ?? ''), 0, 160);
            $keywords = $template->seo_keywords ?: $template->name;

            return [
                'title' => $title,
                'description' => $description,
                'keywords' => $keywords,
            ];
        }, 3600);
    }

    /**
     * 保存模板SEO信息
     */
    public function saveTemplateSeo(int $templateId, array $data): array
    {
        $template = TemplateStore::find($templateId);
        if (!$template) {
            return ['success' => false, 'message' => '模板不存在'];
        }

        $template->seo_title = $data['seo_title'] ?? '';
        $template->seo_description = $data['seo_description'] ?? '';
        $template->seo_keywords = $data['seo_keywords'] ?? '';
        $template->save();

        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => 'SEO信息已保存'];
    }

    /**
     * 生成Schema.org结构化数据
     */
    public function getStructuredData(int $templateId): array
    {
        $template = TemplateStore::find($templateId);
        if (!$template) {
            return [];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $template->name,
            'description' => mb_substr(strip_tags($template->description ?? ''), 0, 300),
            'image' => $template->cover ?: '',
            'offers' => [
                '@type' => 'Offer',
                'price' => $template->price,
                'priceCurrency' => 'CNY',
                'availability' => 'https://schema.org/InStock',
            ],
        ];
    }

    /**
     * 获取商店Sitemap URL列表
     */
    public function getSitemapUrls(): array
    {
        $cacheKey = 'template_sitemap_urls';
        return Cache::tag(self::CACHE_TAG)->remember($cacheKey, function() {
            $urls = [
                ['loc' => '/template_store/market', 'priority' => '0.9', 'changefreq' => 'daily'],
            ];

            // 所有上架模板
            $templates = TemplateStore::where('status', 1)
                ->field('id, slug, update_time')
                ->select()
                ->toArray();

            foreach ($templates as $tpl) {
                $urls[] = [
                    'loc' => '/template_store/detail/' . $tpl['id'],
                    'priority' => '0.8',
                    'changefreq' => 'weekly',
                    'lastmod' => date('Y-m-d', (int)$tpl['update_time']),
                ];
            }

            return $urls;
        }, 3600);
    }
}
