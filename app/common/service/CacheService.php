<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
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
    // V2.9 新增缓存标签
    const TAG_COUPON     = 'i8j_coupon';
    const TAG_RATING     = 'i8j_rating';
    const TAG_THEME      = 'i8j_theme';
    // V2.9.4 PJAX响应缓存标签
    const TAG_PJAX_CACHE = 'i8j_pjax_cache';

    /**
     * 缓存清除分组映射（V2.9.10 重组为5项）
     * 每个分组对应多个缓存标签，分组名称对应前端下拉菜单项
     */
    public const GROUP_ALL      = 'all';
    public const GROUP_CONTENT  = 'content';
    public const GROUP_TEMPLATE = 'template';
    public const GROUP_PLUGIN   = 'plugin';

    /**
     * 分组 → 缓存标签映射表（不含all/browser，all走clearAll，browser纯前端）
     */
    private const GROUP_TAGS = [
        self::GROUP_CONTENT  => [self::TAG_CONTENT, self::TAG_CATE, self::TAG_TAG, self::TAG_AD, self::TAG_COMMENT, self::TAG_COLLECT, self::TAG_PUBLISH, self::TAG_REVIEW, self::TAG_RATING, self::TAG_PAGE_CACHE, self::TAG_PJAX_CACHE, self::TAG_SEARCH, self::TAG_SEO],
        self::GROUP_TEMPLATE => [self::TAG_THEME],
        self::GROUP_PLUGIN   => [self::TAG_PLUGIN],
    ];

    /**
     * 清除所有业务缓存
     */
    public static function clearAll(): bool
    {
        try {
            $tags = [self::TAG_CATE, self::TAG_TAG, self::TAG_CONFIG, self::TAG_CONTENT, self::TAG_SEO, self::TAG_MEMBER, self::TAG_AD, self::TAG_COMMENT, self::TAG_MODULE, self::TAG_PAGE_CACHE, self::TAG_PAYMENT, self::TAG_PLUGIN, self::TAG_EMAIL, self::TAG_COLLECT, self::TAG_PUBLISH, self::TAG_LANGUAGE, self::TAG_QUEUE, self::TAG_MESSAGE, self::TAG_REVIEW, self::TAG_SEARCH, self::TAG_POINTS, self::TAG_COUPON, self::TAG_RATING, self::TAG_THEME, self::TAG_PJAX_CACHE];
            foreach ($tags as $tag) {
                Cache::tag($tag)->clear();
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 按分组清除缓存
     * @param string $group 分组名称
     * @return bool
     */
    public static function clearByGroup(string $group): bool
    {
        if (!isset(self::GROUP_TAGS[$group])) {
            return false;
        }
        try {
            foreach (self::GROUP_TAGS[$group] as $tag) {
                Cache::tag($tag)->clear();
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 清除模板缓存（TAG_THEME + runtime目录下各应用temp编译缓存）
     */
    public static function clearTemplate(): bool
    {
        try {
            Cache::tag(self::TAG_THEME)->clear();
            $runtimePath = root_path() . 'runtime' . DIRECTORY_SEPARATOR;
            $apps = ['admin', 'api', 'home'];
            foreach ($apps as $app) {
                $tempPath = $runtimePath . $app . DIRECTORY_SEPARATOR . 'temp';
                if (is_dir($tempPath)) {
                    self::clearDir($tempPath);
                }
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 清除插件缓存
     */
    public static function clearPlugin(): bool
    {
        try {
            Cache::tag(self::TAG_PLUGIN)->clear();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 递归清空目录（保留目录本身）
     */
    private static function clearDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
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
