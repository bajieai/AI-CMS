<?php
declare(strict_types=1);

namespace app\common\service\i18n;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 多语言路由服务 - V2.9.40 I18N-V3-2
 *
 * 多语言URL策略：子目录(/en/)、子域名(en.)、参数(?lang=en)
 * 路由别名映射 + SEO友好URL
 */
class MultilingualRouteService
{
    private const CACHE_TAG = 'multilingual_route';
    private const CACHE_TTL = 3600;

    /** URL策略 */
    private const STRATEGIES = [
        'subdirectory' => '/{lang}/path',   // 子目录策略
        'subdomain'    => '{lang}.domain',   // 子域名策略
        'parameter'    => '/path?lang={lang}', // 参数策略
    ];

    /**
     * 获取当前URL策略
     */
    public function getStrategy(): string
    {
        return Cache::remember('route_strategy', function () {
            return Db::name('config')
                ->where('group', 'i18n')
                ->where('key', 'url_strategy')
                ->value('value') ?: 'subdirectory';
        }, self::CACHE_TTL);
    }

    /**
     * 设置URL策略
     */
    public function setStrategy(string $strategy): bool
    {
        if (!isset(self::STRATEGIES[$strategy])) return false;

        Db::name('config')->where('group', 'i18n')->where('key', 'url_strategy')->update([
            'value' => $strategy,
        ]);

        Cache::clear();
        return true;
    }

    /**
     * 生成多语言URL
     */
    public function generateUrl(string $path, string $lang, array $params = []): string
    {
        $strategy = $this->getStrategy();
        $defaultLang = $this->getDefaultLang();

        switch ($strategy) {
            case 'subdirectory':
                if ($lang === $defaultLang) {
                    return $path; // 默认语言不加前缀
                }
                return '/' . $lang . $path;

            case 'subdomain':
                if ($lang === $defaultLang) {
                    return $path;
                }
                $domain = request()->host();
                return '//' . $lang . '.' . $domain . $path;

            case 'parameter':
                $query = array_merge($params, ['lang' => $lang]);
                return $path . '?' . http_build_query($query);

            default:
                return '/' . $lang . $path;
        }
    }

    /**
     * 从URL中提取语言标识
     */
    public function detectLangFromUrl(string $url): string
    {
        $strategy = $this->getStrategy();
        $supportedLangs = $this->getSupportedLangs();

        switch ($strategy) {
            case 'subdirectory':
                $path = parse_url($url, PHP_URL_PATH) ?? '';
                $segments = explode('/', trim($path, '/'));
                if (!empty($segments) && in_array($segments[0], $supportedLangs)) {
                    return $segments[0];
                }
                break;

            case 'subdomain':
                $host = parse_url($url, PHP_URL_HOST) ?? '';
                $parts = explode('.', $host);
                if (count($parts) > 2 && in_array($parts[0], $supportedLangs)) {
                    return $parts[0];
                }
                break;

            case 'parameter':
                $query = parse_url($url, PHP_URL_QUERY) ?? '';
                parse_str($query, $params);
                if (isset($params['lang']) && in_array($params['lang'], $supportedLangs)) {
                    return $params['lang'];
                }
                break;
        }

        return $this->getDefaultLang();
    }

    /**
     * 创建路由别名映射
     */
    public function createAlias(int $contentId, string $lang, string $slug): int
    {
        $id = Db::name('multilingual_route')->insertGetId([
            'content_id'    => $contentId,
            'lang'          => $lang,
            'slug'          => $slug,
            'original_slug' => Db::name('content')->where('id', $contentId)->value('filename') ?? '',
            'created_at'    => time(),
            'updated_at'    => time(),
        ]);

        Cache::clear();
        return (int) $id;
    }

    /**
     * 获取路由别名
     */
    public function getAlias(int $contentId, string $lang): string
    {
        $cacheKey = 'route_alias_' . $contentId . '_' . $lang;

        return Cache::remember($cacheKey, function () use ($contentId, $lang) {
            $alias = Db::name('multilingual_route')
                ->where('content_id', $contentId)
                ->where('lang', $lang)
                ->value('slug');

            return $alias ?: '';
        }, self::CACHE_TTL);
    }

    /**
     * 通过别名查找内容
     */
    public function findContentByAlias(string $slug, string $lang): ?int
    {
        $cacheKey = 'route_content_' . md5($slug . $lang);

        return Cache::remember($cacheKey, function () use ($slug, $lang) {
            return Db::name('multilingual_route')
                ->where('slug', $slug)
                ->where('lang', $lang)
                ->value('content_id');
        }, self::CACHE_TTL);
    }

    /**
     * 生成hreflang标签
     */
    public function generateHreflang(int $contentId): array
    {
        $supportedLangs = $this->getSupportedLangs();
        $defaultLang = $this->getDefaultLang();
        $baseUrl = request()->host();

        $hreflang = [];
        foreach ($supportedLangs as $lang) {
            $alias = $this->getAlias($contentId, $lang);
            $url = $this->generateUrl('/' . ($alias ?: 'content/' . $contentId), $lang);
            $hreflang[$lang] = $url;
        }

        // x-default指向默认语言
        $hreflang['x-default'] = $hreflang[$defaultLang];

        return $hreflang;
    }

    /**
     * 获取默认语言
     */
    private function getDefaultLang(): string
    {
        return Cache::remember('default_lang', function () {
            return Db::name('config')->where('group', 'i18n')->where('key', 'default_lang')->value('value') ?: 'zh';
        }, 3600);
    }

    /**
     * 获取支持的语言列表
     */
    private function getSupportedLangs(): array
    {
        return Cache::remember('supported_langs', function () {
            $langs = Db::name('config')->where('group', 'i18n')->where('key', 'supported_langs')->value('value');
            return json_decode($langs ?? '["zh","en"]', true) ?: ['zh', 'en'];
        }, 3600);
    }

    /**
     * 获取策略选项
     */
    public function getStrategyOptions(): array
    {
        return self::STRATEGIES;
    }
}
