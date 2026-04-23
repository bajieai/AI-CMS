<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Cache;

/**
 * 缓存服务
 */
class CacheService
{
    /**
     * 清除所有业务缓存
     */
    public function clearAll(): bool
    {
        try {
            $tags = ['i8j_cate', 'i8j_tag', 'i8j_config', 'i8j_content'];
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
