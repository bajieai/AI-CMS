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

namespace app\common\service\template;

use app\common\model\TemplateStore;
use app\common\model\TemplateCategory;
use app\common\model\TemplateCategoryMap;
use think\facade\Cache;

/**
 * V2.9.20 B-2: 模板搜索服务（多维度搜索 + 热门标签）
 */
class TemplateSearchService
{
    private const CACHE_TAG = 'template_search';
    private const HOT_TAGS_KEY = 'template_hot_tags';
    private const HOT_TAGS_TTL = 3600;

    /**
     * 多维度模板搜索
     *
     * @param array $params 搜索参数
     *   - keyword: 关键词
     *   - model_type: 适用模型类型 (model分类的value)
     *   - industry: 行业分类
     *   - style: 风格分类
     *   - price_type: free|paid
     *   - sort: id|price|install_count|rating_avg|create_time
     *   - order: asc|desc
     *   - page: 页码
     *   - limit: 每页数量
     */
    public function search(array $params = []): array
    {
        $query = TemplateStore::with('category');

        // 1. 关键词搜索（name/description/slug/author）
        if (!empty($params['keyword'])) {
            $keyword = trim($params['keyword']);
            $query->where(function ($q) use ($keyword) {
                $q->whereLike('name', "%{$keyword}%")
                  ->whereOrLike('slug', "%{$keyword}%")
                  ->whereOrLike('description', "%{$keyword}%")
                  ->whereOrLike('author_name', "%{$keyword}%");
            });
        }

        // 2. 新分类体系筛选（通过TemplateCategoryMap关联）
        $categoryFilters = [];
        if (!empty($params['model_type'])) {
            $categoryFilters[] = ['type' => 'model', 'value' => $params['model_type']];
        }
        if (!empty($params['industry'])) {
            $categoryFilters[] = ['type' => 'industry', 'value' => $params['industry']];
        }
        if (!empty($params['style'])) {
            $categoryFilters[] = ['type' => 'style', 'value' => $params['style']];
        }

        if (!empty($categoryFilters)) {
            $storeIds = $this->getStoreIdsByCategories($categoryFilters);
            if (empty($storeIds)) {
                // 无匹配结果，返回空
                return [
                    'list' => [],
                    'total' => 0,
                    'page' => (int) ($params['page'] ?? 1),
                    'limit' => (int) ($params['limit'] ?? 15),
                    'pages' => 0,
                ];
            }
            $query->whereIn('id', $storeIds);
        }

        // 3. 价格筛选
        if (!empty($params['price_type'])) {
            if ($params['price_type'] === 'free') {
                $query->where('price', 0);
            } elseif ($params['price_type'] === 'paid') {
                $query->where('price', '>', 0);
            }
        }

        // 4. 状态（默认只搜索已上架）
        $status = isset($params['status']) && $params['status'] !== ''
            ? (int) $params['status']
            : TemplateStore::STATUS_ONLINE;
        $query->where('status', $status);

        // 5. 排序
        $sortField = $params['sort'] ?? 'id';
        $sortOrder = $params['order'] ?? 'desc';
        $allowSort = ['id', 'price', 'install_count', 'rating_avg', 'create_time', 'quality_score'];
        if (in_array($sortField, $allowSort, true)) {
            $query->order($sortField, $sortOrder);
        }

        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 15);

        $paginator = $query->paginate($limit, false, ['page' => $page]);

        return [
            'list' => $paginator->items(),
            'total' => $paginator->total(),
            'page' => $page,
            'limit' => $limit,
            'pages' => $paginator->lastPage(),
        ];
    }

    /**
     * 获取热门标签（按搜索频次/安装次数统计）
     */
    public function getHotTags(int $limit = 10): array
    {
        return Cache::remember(self::HOT_TAGS_KEY, function () use ($limit) {
            // 策略：取安装次数最多的模板关联的分类标签
            $topStores = TemplateStore::where('status', TemplateStore::STATUS_ONLINE)
                ->order('install_count', 'desc')
                ->limit(50)
                ->column('id');

            if (empty($topStores)) {
                return [];
            }

            // 统计这些模板关联的分类出现频次
            $categoryCounts = TemplateCategoryMap::whereIn('template_id', $topStores)
                ->field('category_id, COUNT(*) as count')
                ->group('category_id')
                ->order('count', 'desc')
                ->limit($limit)
                ->select()
                ->toArray();

            if (empty($categoryCounts)) {
                return [];
            }

            $categoryIds = array_column($categoryCounts, 'category_id');
            $categories = TemplateCategory::whereIn('id', $categoryIds)
                ->column('name, type, value', 'id');

            $result = [];
            foreach ($categoryCounts as $item) {
                $cat = $categories[$item['category_id']] ?? null;
                if ($cat) {
                    $result[] = [
                        'name' => $cat['name'],
                        'type' => $cat['type'],
                        'value' => $cat['value'],
                        'count' => (int) $item['count'],
                    ];
                }
            }

            return $result;
        }, self::HOT_TAGS_TTL);
    }

    /**
     * 获取筛选条件聚合（用于搜索页面侧边栏）
     */
    public function getFilterAggregations(): array
    {
        $cacheKey = 'template_filter_aggregations';
        return Cache::remember($cacheKey, function () {
            $service = new \app\common\service\TemplateCategoryService();
            return [
                'model' => $service->getByType('model'),
                'industry' => $service->getByType('industry'),
                'style' => $service->getByType('style'),
            ];
        }, 3600);
    }

    /**
     * 记录搜索关键词（用于后续热门关键词统计）
     */
    public function logSearch(string $keyword): void
    {
        if (empty($keyword)) {
            return;
        }
        // 写入搜索日志表（如需要可后续扩展）
        // 当前版本仅作接口预留
    }

    /**
     * 根据分类条件获取模板ID列表（交集逻辑：多分类条件取交集）
     */
    private function getStoreIdsByCategories(array $filters): array
    {
        $storeIdSets = [];

        foreach ($filters as $filter) {
            $cat = TemplateCategory::where('type', $filter['type'])
                ->where('value', $filter['value'])
                ->where('status', 1)
                ->find();

            if (empty($cat)) {
                return [];
            }

            $ids = TemplateCategoryMap::where('category_id', $cat->id)
                ->column('template_id');

            if (empty($ids)) {
                return [];
            }

            $storeIdSets[] = $ids;
        }

        if (empty($storeIdSets)) {
            return [];
        }

        // 取交集
        $result = array_shift($storeIdSets);
        foreach ($storeIdSets as $set) {
            $result = array_intersect($result, $set);
            if (empty($result)) {
                return [];
            }
        }

        return array_values($result);
    }
}
