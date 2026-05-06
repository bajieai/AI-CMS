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
    // V2.5 新增缓存标签
    const TAG_PAYMENT    = 'i8j_payment';
    const TAG_PLUGIN     = 'i8j_plugin';
    const TAG_EMAIL      = 'i8j_email';
    const TAG_COLLECT    = 'i8j_collect';
    const TAG_PUBLISH    = 'i8j_publish';
    const TAG_LANGUAGE   = 'i8j_language';
    // V2.6 新增缓存标签
    const TAG_QUEUE      = 'i8j_queue';
    const TAG_MESSAGE    = 'i8j_message';
    const TAG_REVIEW     = 'i8j_review';
    const TAG_SEARCH     = 'i8j_search';
    const TAG_POINTS     = 'i8j_points';

    /**
     * 清除所有业务缓存
     */
    public static function clearAll(): bool
    {
        try {
            $tags = [self::TAG_CATE, self::TAG_TAG, self::TAG_CONFIG, self::TAG_CONTENT, self::TAG_SEO, self::TAG_MEMBER, self::TAG_AD, self::TAG_COMMENT, self::TAG_MODULE, self::TAG_PAGE_CACHE, self::TAG_PAYMENT, self::TAG_PLUGIN, self::TAG_EMAIL, self::TAG_COLLECT, self::TAG_PUBLISH, self::TAG_LANGUAGE, self::TAG_QUEUE, self::TAG_MESSAGE, self::TAG_REVIEW, self::TAG_SEARCH, self::TAG_POINTS];
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
    public static function clearByTag(string $tag): bool
    {
        try {
            Cache::tag($tag)->clear();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
