<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Cache;

/**
 * 缓存服务
 */
class CacheService
{
    const TAG_CATE       = 'i8j_cate';
    const TAG_TAG        = 'i8j_tag';
    const TAG_CONFIG     = 'i8j_config';
    const TAG_CONTENT    = 'i8j_content';
    const TAG_SEO        = 'i8j_seo';
    const TAG_MEMBER     = 'i8j_member';
    const TAG_AD         = 'i8j_ad';
    const TAG_COMMENT    = 'i8j_comment';
    const TAG_MODULE     = 'i8j_module';
    const TAG_PAGE_CACHE = 'i8j_page_cache';

    /**
     * 清除所有业务缓存
     */
    public function clearAll(): bool
    {
        try {
            $tags = [self::TAG_CATE, self::TAG_TAG, self::TAG_CONFIG, self::TAG_CONTENT, self::TAG_SEO, self::TAG_MEMBER, self::TAG_AD, self::TAG_COMMENT, self::TAG_MODULE, self::TAG_PAGE_CACHE];
            foreach ($tags as $tag) {
                Cache::tag($tag)->clear();
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 清除指定标签缓存
     */
    public function clearByTag(string $tag): bool
    {
        try {
            Cache::tag($tag)->clear();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
