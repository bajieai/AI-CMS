<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Content;
use think\facade\Config;
use think\facade\Request;

/**
 * 社交分享服务 - V2.9.9 轻量版
 * 能力：分享链接生成 + OG卡片元数据 + URL追踪参数
 * 不涉及：各平台SDK接入、前端分享按钮UI
 */
class SocialShareService
{
    /** 支持的分享平台 */
    public const PLATFORMS = ['wechat', 'weibo', 'qq', 'twitter', 'facebook', 'linkedin'];

    /** 追踪参数名 */
    public const UTM_SOURCE = 'utm_source';
    public const UTM_MEDIUM = 'utm_medium';
    public const UTM_CAMPAIGN = 'utm_campaign';

    /**
     * 生成带追踪参数的分享链接
     *
     * @param string $url 原始链接（相对或绝对）
     * @param string $platform 平台标识
     * @param string $campaign 活动标识（可选）
     * @return string
     */
    public static function generateShareUrl(string $url, string $platform, string $campaign = ''): string
    {
        if (!in_array($platform, self::PLATFORMS, true)) {
            $platform = 'unknown';
        }

        $domain = Config::get('site.site_url', Request::domain());
        if (!str_starts_with($url, 'http')) {
            $url = rtrim($domain, '/') . '/' . ltrim($url, '/');
        }

        $params = [
            self::UTM_SOURCE   => $platform,
            self::UTM_MEDIUM   => 'social_share',
            self::UTM_CAMPAIGN => $campaign ?: 'default',
        ];

        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . http_build_query($params);
    }

    /**
     * 生成内容分享链接（全平台）
     *
     * @param Content|array $content 内容对象或数组
     * @param string $campaign 活动标识
     * @return array
     */
    public static function generateContentShareLinks(Content|array $content, string $campaign = ''): array
    {
        $url = is_array($content) ? ($content['url'] ?? '') : ($content->url ?? '');
        if (empty($url)) {
            return [];
        }

        $links = [];
        foreach (self::PLATFORMS as $platform) {
            $links[$platform] = self::generateShareUrl($url, $platform, $campaign);
        }
        return $links;
    }

    /**
     * 生成OG卡片元数据数组
     *
     * @param Content|array $content 内容对象或数组
     * @return array
     */
    public static function generateOgMeta(Content|array $content): array
    {
        $isArray = is_array($content);
        $title       = $isArray ? ($content['seo_title'] ?? $content['title'] ?? '') : ($content->seo_title ?: $content->title);
        $description = $isArray
            ? ($content['seo_description'] ?? mb_substr(strip_tags($content['content'] ?? ''), 0, 200))
            : ($content->seo_description ?: mb_substr(strip_tags($content->content), 0, 200));
        $cover       = $isArray ? ($content['cover'] ?? '') : ($content->cover ?? '');
        $url         = $isArray ? ($content['url'] ?? '') : ($content->url ?? '');
        $lang        = $isArray ? ($content['lang'] ?? 'zh-CN') : ($content->lang ?? 'zh-CN');

        $domain = Config::get('site.site_url', Request::domain());
        if (!empty($url) && !str_starts_with($url, 'http')) {
            $url = rtrim($domain, '/') . '/' . ltrim($url, '/');
        }
        if (!empty($cover) && !str_starts_with($cover, 'http')) {
            $cover = rtrim($domain, '/') . '/' . ltrim($cover, '/');
        }

        $meta = [
            'og:title'       => $title,
            'og:description' => $description,
            'og:url'         => $url,
            'og:type'        => 'article',
            'og:site_name'   => Config::get('site.site_name', 'AI-CMS'),
            'og:locale'      => self::mapLocale($lang),
        ];

        if ($cover) {
            $meta['og:image'] = $cover;
            $meta['og:image:width']  = '1200';
            $meta['og:image:height'] = '630';
        }

        // Twitter Card
        $meta['twitter:card']        = 'summary_large_image';
        $meta['twitter:title']       = $title;
        $meta['twitter:description'] = $description;
        if ($cover) {
            $meta['twitter:image'] = $cover;
        }

        return $meta;
    }

    /**
     * 渲染OG Meta HTML
     *
     * @param Content|array $content
     * @return string
     */
    public static function renderOgHtml(Content|array $content): string
    {
        $meta = self::generateOgMeta($content);
        $html = [];
        foreach ($meta as $property => $value) {
            if (str_starts_with($property, 'twitter:')) {
                $html[] = sprintf('<meta name="%s" content="%s">', $property, htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'));
            } else {
                $html[] = sprintf('<meta property="%s" content="%s">', $property, htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'));
            }
        }
        return implode("\n", $html);
    }

    /**
     * 获取分享统计（轻量版：基于访问日志聚合）
     *
     * @param int|null $contentId 内容ID，null表示全局
     * @param string $period 周期 today|week|month|all
     * @return array
     */
    public static function getStats(?int $contentId = null, string $period = 'month'): array
    {
        $query = \think\facade\Db::table('i8j_visit_log')
            ->whereLike('referer', '%utm_medium=social_share%');

        if ($contentId !== null) {
            $query->where('content_id', $contentId);
        }

        switch ($period) {
            case 'today':
                $query->whereTime('visit_time', 'today');
                break;
            case 'week':
                $query->whereTime('visit_time', 'week');
                break;
            case 'month':
                $query->whereTime('visit_time', 'month');
                break;
        }

        $total = (int) $query->count();

        // 按平台分组
        $platformStats = [];
        foreach (self::PLATFORMS as $platform) {
            $platformStats[$platform] = (int) (clone $query)->whereLike('referer', "%utm_source={$platform}%")->count();
        }

        return [
            'total'     => $total,
            'platforms' => $platformStats,
            'period'    => $period,
        ];
    }

    /**
     * 获取后台分享配置
     *
     * @return array
     */
    public static function getConfig(): array
    {
        return [
            'enabled'       => (int) Config::get('social_share.enabled', 1),
            'show_og'       => (int) Config::get('social_share.show_og', 1),
            'platforms'     => Config::get('social_share.platforms', self::PLATFORMS),
            'utm_campaign'  => Config::get('social_share.utm_campaign', 'default'),
            'default_image' => Config::get('social_share.default_image', ''),
        ];
    }

    /**
     * 保存后台分享配置
     *
     * @param array $data
     * @return bool
     */
    public static function saveConfig(array $data): bool
    {
        $configService = new ConfigService();
        $configs = [
            'social_share.enabled'       => $data['enabled'] ?? 1,
            'social_share.show_og'       => $data['show_og'] ?? 1,
            'social_share.platforms'     => is_array($data['platforms'] ?? null) ? json_encode($data['platforms']) : json_encode(self::PLATFORMS),
            'social_share.utm_campaign'  => $data['utm_campaign'] ?? 'default',
            'social_share.default_image' => $data['default_image'] ?? '',
        ];
        foreach ($configs as $key => $value) {
            $configService->set($key, $value);
        }
        return true;
    }

    /**
     * 语言代码映射到OG locale
     */
    protected static function mapLocale(string $lang): string
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
        return $map[$lang] ?? str_replace('-', '_', $lang);
    }
}
