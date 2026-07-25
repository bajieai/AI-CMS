<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint PERF: 缓存预热服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use think\facade\Cache;

/**
 * 缓存预热服务 - V2.9.31 PERF-3
 * 提供缓存预热、刷新、统计功能
 */
class CacheWarmupService
{
    private const string CACHE_TAG = 'warmup';

    /**
     * 预热系统配置缓存
     */
    public function warmupConfig(): array
    {
        $count = 0;
        try {
            $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
            $configs = \think\facade\Db::table($prefix . 'config')->column('value', 'name');
            foreach ($configs as $name => $value) {
                Cache::set('config_' . $name, $value, 3600);
                $count++;
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        return ['success' => true, 'type' => 'config', 'count' => $count];
    }

    /**
     * 预热分类缓存
     */
    public function warmupCategories(): array
    {
        $count = 0;
        try {
            $categories = \app\common\model\Cate::select()->toArray();
            Cache::set('all_categories', $categories, 3600);
            $count = count($categories);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        return ['success' => true, 'type' => 'category', 'count' => $count];
    }

    /**
     * 预热模板商店缓存
     */
    public function warmupTemplateStore(): array
    {
        $count = 0;
        try {
            $service = new \app\common\service\template\TemplateStoreService();
            $featured = $service->getFeatured(10);
            $categories = $service->getCategories();
            $count = count($featured) + count($categories);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        return ['success' => true, 'type' => 'template_store', 'count' => $count];
    }

    /**
     * 全量预热
     */
    public function warmupAll(): array
    {
        $results = [];
        $results[] = $this->warmupConfig();
        $results[] = $this->warmupCategories();
        $results[] = $this->warmupTemplateStore();

        $total = array_sum(array_column(array_filter($results, fn($r) => $r['success'] ?? false), 'count'));

        return [
            'success' => true,
            'results' => $results,
            'total' => $total,
        ];
    }

    /**
     * 获取缓存统计
     */
    public function getStats(): array
    {
        try {
            $stats = Cache::get('cache_stats') ?: [
                'hits' => 0,
                'misses' => 0,
                'warmup_time' => 0,
            ];
            return $stats;
        } catch (\Throwable $e) {
            return ['hits' => 0, 'misses' => 0, 'warmup_time' => 0];
        }
    }

    /**
     * 记录缓存命中
     */
    public function recordHit(): void
    {
        $stats = $this->getStats();
        $stats['hits']++;
        Cache::set('cache_stats', $stats);
    }

    /**
     * 记录缓存未命中
     */
    public function recordMiss(): void
    {
        $stats = $this->getStats();
        $stats['misses']++;
        Cache::set('cache_stats', $stats);
    }
}
