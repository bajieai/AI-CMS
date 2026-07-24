<?php
declare(strict_types=1);
namespace app\common\service\template;

use app\common\model\TemplateStore;
use think\facade\Cache;

/**
 * 模板排行Service - V2.9.32 T4-3
 */
class TemplateRankingService
{
    private const CACHE_TAG = 'template_ranking';

    public function getRanking(string $type = 'install', string $period = 'total'): array
    {
        $cacheKey = "ranking_{$type}_{$period}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) return $cached;

        $query = TemplateStore::where('status', 1);
        switch ($type) {
            case 'rating':
                $query->order('avg_rating DESC, review_count DESC'); break;
            case 'favorite':
                $query->order('favorite_count DESC'); break;
            case 'new':
                $query->order('create_time DESC'); break;
            default:
                $query->order('install_count DESC, rating_avg DESC'); break;
        }

        $list = $query->limit(10)->field('id, name, slug, price, install_count, avg_rating, review_count, current_version, is_featured')->select()->toArray();
        $result = ['type' => $type, 'period' => $period, 'list' => $list];
        Cache::set($cacheKey, $result, 3600);
        return $result;
    }

    public function getRankingLabels(int $templateId): array
    {
        $store = TemplateStore::find($templateId);
        if (!$store) return [];
        $labels = [];
        $ranking = $this->getRanking('install');
        if (!empty($ranking['list']) && $ranking['list'][0]['id'] === $templateId) $labels[] = 'TOP1';
        if ($store->install_count > 100) $labels[] = '热';
        if (time() - ($store->create_time ?? 0) < 7 * 86400) $labels[] = '新';
        if ($store->is_featured) $labels[] = '推荐';
        return $labels;
    }

    public function refreshCache(): void
    {
        Cache::clear();
    }
}
