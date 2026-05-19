<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service;

use think\facade\Cache;
use think\facade\Log;

/**
 * V2.9.5 缓存预热服务
 * 内容发布/更新/删除时触发相关缓存清除与重建
 * 使用同步方式实现（Redis可用后可改为异步队列）
 */
class CacheWarmService
{
    /**
     * 清除并重建内容相关缓存
     */
    public static function warmContentCache(int $contentId, ?int $cateId = null): void
    {
        try {
            // 1. 清除内容详情缓存
            Cache::tag(CacheService::TAG_CONTENT)->clear();

            // 2. 清除分类缓存（如果提供了分类ID）
            if ($cateId) {
                Cache::tag(CacheService::TAG_CATE)->clear();
            }

            // 3. 清除页面缓存（整页缓存包含内容列表和详情）
            Cache::tag(CacheService::TAG_PAGE_CACHE)->clear();

            // 4. 清除SEO缓存
            Cache::tag(CacheService::TAG_SEO)->clear();

            Log::info("[CacheWarm] 内容{$contentId} 缓存已清除并标记重建");
        } catch (\Throwable $e) {
            Log::warning('[CacheWarm] 缓存预热失败: ' . $e->getMessage());
        }
    }

    /**
     * 清除并重建配置缓存
     */
    public static function warmConfigCache(): void
    {
        try {
            Cache::tag(CacheService::TAG_CONFIG)->clear();
            Log::info('[CacheWarm] 配置缓存已清除');
        } catch (\Throwable $e) {
            Log::warning('[CacheWarm] 配置缓存清除失败: ' . $e->getMessage());
        }
    }

    /**
     * 清除会员相关缓存
     */
    public static function warmMemberCache(int $memberId): void
    {
        try {
            Cache::tag(CacheService::TAG_MEMBER)->clear();
            Log::info("[CacheWarm] 会员{$memberId} 相关缓存已清除");
        } catch (\Throwable $e) {
            Log::warning('[CacheWarm] 会员缓存清除失败: ' . $e->getMessage());
        }
    }

    /**
     * 全站缓存清除（主题切换、大规模数据变更时）
     */
    public static function warmAllCache(): void
    {
        try {
            CacheService::clearAll();
            Log::info('[CacheWarm] 全站缓存已清除');
        } catch (\Throwable $e) {
            Log::warning('[CacheWarm] 全站缓存清除失败: ' . $e->getMessage());
        }
    }
}
