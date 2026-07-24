<?php

declare(strict_types=1);

namespace app\common\service\plugin;

use think\facade\Db;
use think\facade\Cache;

/**
 * PLUG-SHOP-1: 插件商店前端服务 — V2.9.36
 */
class PluginStoreFrontService
{
    private const CACHE_TAG = 'plugin_store';
    private const CACHE_TTL = 1800; // 30分钟

    /**
     * 商店首页数据
     */
    public function getHomePage(): array
    {
        return Cache::remember('plugin_store_home', function () {
            // 轮播推荐（精选）
            $featured = Db::name('plugin')
                ->where('is_featured', 1)
                ->where('is_enabled', 1)
                ->order('download_count', 'desc')
                ->limit(6)
                ->select()->toArray();

            // 热门
            $hot = Db::name('plugin')
                ->where('is_enabled', 1)
                ->order('download_count', 'desc')
                ->limit(10)
                ->select()->toArray();

            // 最新
            $latest = Db::name('plugin')
                ->where('is_enabled', 1)
                ->order('id', 'desc')
                ->limit(10)
                ->select()->toArray();

            // 推荐
            $recommended = Db::name('plugin')
                ->where('is_recommended', 1)
                ->where('is_enabled', 1)
                ->order('rating', 'desc')
                ->limit(10)
                ->select()->toArray();

            // 统计
            $stats = [
                'total_plugins'   => Db::name('plugin')->where('is_enabled', 1)->count(),
                'total_downloads' => Db::name('plugin')->sum('download_count'),
                'total_installs'  => Db::name('plugin')->sum('install_count'),
            ];

            return [
                'featured'    => $featured,
                'hot'         => $hot,
                'latest'      => $latest,
                'recommended' => $recommended,
                'stats'       => $stats,
            ];
        }, self::CACHE_TTL);
    }

    /**
     * 插件列表（分类/价格/版本筛选 + 排序）
     */
    public function getPluginList(int $page = 1, int $pageSize = 20, array $filter = []): array
    {
        $query = Db::name('plugin')->where('is_enabled', 1);

        // 分类筛选
        if (!empty($filter['category_id'])) {
            $query->where('category_id', (int) $filter['category_id']);
        }

        // 价格筛选
        if (!empty($filter['price_type'])) {
            if ($filter['price_type'] === 'free') {
                $query->where('price', 0);
            } elseif ($filter['price_type'] === 'paid') {
                $query->where('price', '>', 0);
            }
        }

        // 关键词
        if (!empty($filter['keyword'])) {
            $kw = trim($filter['keyword']);
            $query->where(function ($q) use ($kw) {
                $q->whereLike('name', "%{$kw}%")
                  ->whereOr('code', 'like', "%{$kw}%")
                  ->whereOr('tags', 'like', "%{$kw}%");
            });
        }

        // 排序
        $sort = $filter['sort'] ?? 'download';
        $sortMap = [
            'download' => ['download_count', 'desc'],
            'rating'   => ['rating', 'desc'],
            'latest'   => ['id', 'desc'],
            'price_asc'=> ['price', 'asc'],
            'price_desc'=>['price', 'desc'],
        ];
        [$sortField, $sortOrder] = $sortMap[$sort] ?? $sortMap['download'];
        $query->order($sortField, $sortOrder);

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        return [
            'list'     => $list,
            'total'    => $total,
            'page'     => $page,
            'pageSize' => $pageSize,
        ];
    }

    /**
     * 插件详情
     */
    public function getPluginDetail(int $id): array
    {
        $plugin = Db::name('plugin')->find($id);
        if (!$plugin) {
            return ['code' => 1, 'msg' => '插件不存在'];
        }

        // 截图
        $screenshots = [];
        if (!empty($plugin['screenshots'])) {
            $screenshots = is_array($plugin['screenshots'])
                ? $plugin['screenshots']
                : (json_decode($plugin['screenshots'], true) ?? []);
        }

        // 版本列表
        $versions = Db::name('plugin_version')
            ->where('plugin_id', $id)
            ->order('id', 'desc')
            ->limit(10)
            ->select()->toArray();

        // 评分统计
        $ratingStats = $this->getRatingStatsInline($id);

        // 兼容性
        $compatibility = [
            'php_min' => $plugin['php_version'] ?? '8.0',
            'cms_min' => $plugin['cms_version'] ?? '2.9.0',
        ];

        return [
            'code' => 0,
            'msg'  => 'ok',
            'data' => [
                'plugin'       => $plugin,
                'screenshots'  => $screenshots,
                'versions'     => $versions,
                'rating_stats' => $ratingStats,
                'compatibility'=> $compatibility,
            ],
        ];
    }

    /**
     * 搜索插件
     */
    public function searchPlugins(string $keyword, int $page = 1): array
    {
        $kw = trim($keyword);
        if ($kw === '') {
            return ['list' => [], 'total' => 0, 'page' => $page];
        }

        $query = Db::name('plugin')
            ->where('is_enabled', 1)
            ->where(function ($q) use ($kw) {
                $q->whereLike('name', "%{$kw}%")
                  ->whereOr('code', 'like', "%{$kw}%")
                  ->whereOr('description', 'like', "%{$kw}%")
                  ->whereOr('tags', 'like', "%{$kw}%");
            });

        $total = $query->count();
        $list = $query->order('download_count', 'desc')
            ->page($page, 20)
            ->select()->toArray();

        return [
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
        ];
    }

    /**
     * 分类列表
     */
    public function getCategories(): array
    {
        return Cache::remember('plugin_categories', function () {
            return Db::name('plugin_category')
                ->where('status', 1)
                ->order('sort', 'asc')
                ->select()->toArray();
        }, self::CACHE_TTL);
    }

    /**
     * 内联评分统计
     */
    private function getRatingStatsInline(int $pluginId): array
    {
        $row = Db::name('plugin_rating')
            ->where('plugin_id', $pluginId)
            ->where('status', 1)
            ->field('AVG(rating) as avg_rating, COUNT(*) as total')
            ->find();

        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $distribution[$i] = Db::name('plugin_rating')
                ->where('plugin_id', $pluginId)
                ->where('status', 1)
                ->where('rating', $i)
                ->count();
        }

        return [
            'avg'          => $row && $row['avg_rating'] ? round((float) $row['avg_rating'], 1) : 0,
            'total'        => $row ? (int) $row['total'] : 0,
            'distribution' => $distribution,
        ];
    }
}
