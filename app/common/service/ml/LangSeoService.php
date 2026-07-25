<?php
declare(strict_types=1);

namespace app\common\service\ml;

use app\common\model\LangSite;
use app\common\model\Content;
use app\common\service\SeoService;
use think\facade\Cache;

/**
 * 多语言SEO — V2.9.34 ML-2
 * 各语言独立SEO字段 + Sitemap + hreflang + URL优化
 */
class LangSeoService
{
    private const CACHE_TAG = 'lang_seo';

    public function getTemplateSeo(int $contentId, int $langSiteId): array
    {
        return Cache::remember("seo_{$contentId}_{$langSiteId}", function() use ($contentId, $langSiteId) {
            $content = Content::find($contentId);
            if (!$content) return [];
            $lang = LangSite::find($langSiteId);
            $langCode = $lang ? $lang->lang_code : 'zh-CN';
            return [
                'seo_title' => $content->seo_title ?: $content->title,
                'seo_description' => $content->seo_description ?: mb_substr(strip_tags($content->content), 0, 160),
                'seo_keywords' => $content->seo_keywords ?: '',
                'canonical' => $this->buildCanonical($contentId, $langSiteId),
                'hreflang' => $this->generateHreflang($contentId),
                'og_tags' => $this->generateOgTags($content, $langCode),
            ];
        }, 3600);
    }

    public function generateHreflang(int $contentId): array
    {
        $sites = LangSite::where('status', 1)->select();
        $hreflang = [];
        foreach ($sites as $site) {
            $hreflang[$site->lang_code] = $this->buildCanonical($contentId, (int)$site->id);
        }
        $defaultSite = LangSite::where('is_default', 1)->find();
        if ($defaultSite) $hreflang['x-default'] = $this->buildCanonical($contentId, (int)$defaultSite->id);
        return $hreflang;
    }

    public function buildCanonical(int $contentId, int $langSiteId): string
    {
        $site = LangSite::find($langSiteId);
        if (!$site) return '';
        $slugService = new LangSlugService();
        $slug = $slugService->getSlug($contentId, $langSiteId) ?: "content-{$contentId}";
        $domain = $site->site_domain ?: ($_SERVER['HTTP_HOST'] ?? '');
        $prefix = $site->url_mode === 'prefix' ? $site->url_prefix : '';
        return "https://{$domain}{$prefix}/{$slug}";
    }

    public function generateSitemap(int $langSiteId): array
    {
        return Cache::remember("sitemap_{$langSiteId}", function() use ($langSiteId) {
            $contents = Content::where('lang_site_id', $langSiteId)->where('status', 1)->field('id,slug,update_time')->select();
            $urls = [];
            foreach ($contents as $content) {
                $urls[] = ['loc' => $this->buildCanonical((int)$content->id, $langSiteId), 'lastmod' => date('Y-m-d', (int)$content->update_time)];
            }
            return $urls;
        }, 3600);
    }

    public function getSeoStats(int $langSiteId): array
    {
        return Cache::remember("seo_stats_{$langSiteId}", function() use ($langSiteId) {
            $total = Content::where('lang_site_id', $langSiteId)->count();
            $hasSeoTitle = Content::where('lang_site_id', $langSiteId)->where('seo_title', '<>', '')->count();
            $hasSeoDesc = Content::where('lang_site_id', $langSiteId)->where('seo_description', '<>', '')->count();
            return ['total' => $total, 'seo_title_coverage' => $total > 0 ? round($hasSeoTitle / $total * 100, 1) : 0, 'seo_desc_coverage' => $total > 0 ? round($hasSeoDesc / $total * 100, 1) : 0];
        }, 3600);
    }

    private function generateOgTags($content, string $langCode): array
    {
        return ['og:title' => $content->title, 'og:description' => $content->seo_description ?: mb_substr(strip_tags($content->content), 0, 200), 'og:locale' => str_replace('-', '_', $langCode), 'og:type' => 'article'];
    }
}
