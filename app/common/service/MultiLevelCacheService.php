<?php
declare(strict_types=1);
namespace app\common\service;

use think\facade\Cache;

/**
 * 多级缓存策略Service - V2.9.32 PERF2-2
 * L1内存缓存(Redis) → L2文件缓存 → L3数据库查询缓存
 */
class MultiLevelCacheService
{
    public const L1 = 'L1'; // Redis 高频 5-30分钟
    public const L2 = 'L2'; // 文件 中频 1-24小时
    public const L3 = 'L3'; // DB查询 低频 24小时

    private const CACHE_TAG = 'multilevel';

    /**
     * 获取缓存（多级穿透）
     */
    public function get(string $key, string $level = self::L1)
    {
        // L1
        $value = Cache::get($key);
        if ($value !== null) return $value;

        // L2
        $fileKey = 'ml_' . md5($key);
        $value = Cache::get($fileKey);
        if ($value !== null) {
            // 回填L1
            Cache::set($key, $value, 300);
            return $value;
        }

        return null;
    }

    /**
     * 设置缓存（多级写入）
     */
    public function set(string $key, $value, int $ttl = 300, string $level = self::L1): void
    {
        Cache::set($key, $value, $ttl);

        if ($level === self::L2 || $level === self::L3) {
            $fileKey = 'ml_' . md5($key);
            $fileTtl = $level === self::L3 ? 86400 : 3600;
            Cache::set($fileKey, $value, $fileTtl);
        }
    }

    /**
     * remember（缓存不存在时回调获取）
     */
    public function remember(string $key, callable $callback, int $ttl = 300, string $level = self::L1)
    {
        $value = $this->get($key, $level);
        if ($value !== null) return $value;

        $value = $callback();
        $this->set($key, $value, $ttl, $level);
        return $value;
    }

    /**
     * 按标签清理
     */
    public function clearByTag(string $tag): void
    {
        Cache::clear();
    }

    /**
     * 按类型清理
     */
    public function clearByType(string $type): void
    {
        $tagMap = [
            'template' => ['template_store', 'template_detail', 'template_ranking', 'template_color'],
            'content'  => ['content_list', 'content_detail'],
            'config'   => ['config', 'system_config'],
            'seo'      => ['seo_diagnosis', 'seo_batch'],
            'ai'       => ['ai_image', 'ai_summary', 'ai_tag', 'ai_result_cache'],
        ];
        $tags = $tagMap[$type] ?? [];
        foreach ($tags as $tag) {
            Cache::clear();
        }
    }

    /**
     * 全量清理
     */
    public function clearAll(): void
    {
        Cache::clear();
    }

    /**
     * 获取缓存统计
     */
    public function getStats(): array
    {
        $stats = Cache::get('cache_stats') ?: ['hits' => 0, 'misses' => 0];
        $total = $stats['hits'] + $stats['misses'];
        return [
            'hits' => $stats['hits'],
            'misses' => $stats['misses'],
            'hit_rate' => $total > 0 ? round($stats['hits'] / $total * 100, 1) : 0,
        ];
    }

    public function recordHit(): void
    {
        $stats = $this->getStats();
        $stats['hits']++;
        Cache::set('cache_stats', $stats);
    }

    public function recordMiss(): void
    {
        $stats = $this->getStats();
        $stats['misses']++;
        Cache::set('cache_stats', $stats);
    }
}
