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

use app\common\model\TemplateInstallLog;
use app\common\model\TemplateStore;
use app\common\model\TemplateStoreCategory;
use app\common\model\TemplateInstall;
use app\common\model\TemplateReview;
use app\common\service\theme\ThemeRepairPipeline;
use think\facade\Cache;

/**
 * 模板商店核心服务 - V2.9.12新增
 */
class TemplateStoreService
{
    /**
     * 获取模板列表（分页）
     */
    public function getList(array $params = []): array
    {
        $query = TemplateStore::with('category');

        // 分类筛选
        if (!empty($params['category_id'])) {
            $query->where('category_id', (int) $params['category_id']);
        }

        // 状态筛选
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', (int) $params['status']);
        }

        // 关键词搜索
        if (!empty($params['keyword'])) {
            $keyword = trim($params['keyword']);
            $query->where(function ($q) use ($keyword) {
                $q->whereLike('name', "%{$keyword}%")
                  ->whereOrLike('slug', "%{$keyword}%")
                  ->whereOrLike('description', "%{$keyword}%");
            });
        }

        // 价格筛选
        if (isset($params['price_type']) && $params['price_type'] !== '') {
            if ($params['price_type'] === 'free') {
                $query->where('price', 0);
            } elseif ($params['price_type'] === 'paid') {
                $query->where('price', '>', 0);
            }
        }

        // 排序
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
     * 获取模板详情
     */
    public function getDetail(int $id): ?TemplateStore
    {
        return TemplateStore::with('category,reviews')->find($id);
    }

    /**
     * 按slug获取详情
     */
    public function getBySlug(string $slug): ?TemplateStore
    {
        return TemplateStore::where('slug', $slug)->find();
    }

    /**
     * 搜索模板
     */
    public function search(string $keyword, int $limit = 10): array
    {
        return TemplateStore::online()
            ->whereLike('name', "%{$keyword}%")
            ->whereOrLike('description', "%{$keyword}%")
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取推荐模板
     */
    public function getFeatured(int $limit = 6): array
    {
        $cacheKey = 'template_store_featured_' . $limit;
        $result = Cache::get($cacheKey);
        if ($result !== null) {
            return $result;
        }

        $result = TemplateStore::online()
            ->featured()
            ->order('install_count', 'desc')
            ->order('id', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();

        Cache::set($cacheKey, $result, 3600);
        return $result;
    }

    /**
     * 获取分类列表
     */
    public function getCategories(): array
    {
        $cacheKey = 'template_store_categories';
        $result = Cache::get($cacheKey);
        if ($result !== null) {
            return $result;
        }

        $result = TemplateStoreCategory::enabled()
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        Cache::set($cacheKey, $result, 3600);
        return $result;
    }

    /**
     * 按分类获取模板
     */
    public function getByCategory(int $categoryId, int $limit = 15): array
    {
        return TemplateStore::online()
            ->where('category_id', $categoryId)
            ->order('install_count', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 安装模板
     */
    public function installTheme(int $storeId, int $memberId): array
    {
        $store = TemplateStore::find($storeId);
        if (empty($store)) {
            throw new \RuntimeException('模板不存在');
        }

        // V2.9.13 I-2: 安装前自动质量校验 + 记录质量分
        $pipeline = new ThemeRepairPipeline();
        $checkResult = $pipeline->validate($store);
        $qualityScore = (int) ($checkResult['quality_score'] ?? 0);
        if (!$checkResult['pass']) {
            throw new \RuntimeException('模板质量校验未通过（评分' . $qualityScore . '），无法安装');
        }

        // 检查是否已安装
        $exists = TemplateInstall::where('store_id', $storeId)
            ->where('member_id', $memberId)
            ->find();
        if ($exists) {
            throw new \RuntimeException('该模板已安装');
        }

        // 创建安装记录（含质量分）
        $install = new TemplateInstall();
        $install->store_id = $storeId;
        $install->member_id = $memberId;
        $install->slug = $store->slug;
        $install->theme_name = $store->name;
        $install->is_active = 0;
        $install->install_path = 'themes/' . $store->slug;
        $install->quality_on_install = $qualityScore;
        $install->save();

        // 更新安装次数
        $store->inc('install_count')->save();

        // V2.9.31 T3-2: 记录安装日志
        try {
            $logService = new TemplateInstallLogService();
            $logService->log($storeId, $memberId, TemplateInstallLog::ACTION_INSTALL, [
                'result' => 1,
            ]);
        } catch (\Throwable $e) {
            // 日志记录失败不影响主流程
        }

        return ['install_id' => $install->id, 'message' => '安装成功'];
    }

    /**
     * 激活模板
     */
    public function activateTheme(int $installId, int $memberId): array
    {
        $install = TemplateInstall::where('id', $installId)
            ->where('member_id', $memberId)
            ->find();

        if (empty($install)) {
            throw new \RuntimeException('安装记录不存在');
        }

        // 取消该用户其他激活模板
        TemplateInstall::where('member_id', $memberId)
            ->where('is_active', 1)
            ->update(['is_active' => 0]);

        // 激活当前模板
        $install->is_active = 1;
        $install->save();

        // V2.9.31 T3-2: 记录激活日志
        try {
            $logService = new TemplateInstallLogService();
            $logService->log((int) $install->store_id, $memberId, TemplateInstallLog::ACTION_ACTIVATE, [
                'result' => 1,
            ]);
        } catch (\Throwable $e) {
            // 日志记录失败不影响主流程
        }

        return ['message' => '切换成功'];
    }

    /**
     * 更新评分统计
     */
    public function updateRatingStats(int $storeId): void
    {
        $stats = TemplateReview::where('store_id', $storeId)
            ->where('is_audited', TemplateReview::AUDIT_PASS)
            ->field([
                'AVG(rating) as avg_rating',
                'COUNT(*) as count',
            ])
            ->find();

        $avg = round((float) ($stats['avg_rating'] ?? 0), 1);
        $count = (int) ($stats['count'] ?? 0);

        TemplateStore::where('id', $storeId)->update([
            'rating_avg' => $avg,
            'rating_count' => $count,
        ]);

        Cache::delete('template_store_featured_*');
    }

    /**
     * 清除缓存
     */
    public function clearCache(): void
    {
        Cache::delete('template_store_featured_*');
        Cache::delete('template_store_categories');
    }

    /**
     * 高级搜索 — V2.9.30 T2-4
     * 支持全文搜索、多条件筛选、智能排序
     */
    public function advancedSearch(array $conditions): array
    {
        $query = TemplateStore::where('status', 1);

        // 关键词搜索（LIKE降级方案，FULLTEXT INDEX可选）
        if (!empty($conditions['keyword'])) {
            $keyword = $conditions['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->whereOr('name', 'like', "%{$keyword}%")
                  ->whereOr('description', 'like', "%{$keyword}%")
                  ->whereOr('tags', 'like', "%{$keyword}%");
            });
        }

        // 标签筛选（通过 i8j_template_tag_relation 关联）
        if (!empty($conditions['tag_ids'])) {
            $query->whereIn('id', function ($q) use ($conditions) {
                $q->name('template_tag_relation')
                  ->whereIn('tag_id', $conditions['tag_ids'])
                  ->field('template_id');
            });
        }

        // 价格筛选
        if (($conditions['price_min'] ?? 0) > 0) {
            $query->where('price', '>=', $conditions['price_min']);
        }
        if (($conditions['price_max'] ?? 0) > 0) {
            $query->where('price', '<=', $conditions['price_max']);
        }

        // 评分筛选
        if (($conditions['rating_min'] ?? 0) > 0) {
            $query->where('rating_avg', '>=', $conditions['rating_min']);
        }

        // 行业筛选
        if (!empty($conditions['industry'])) {
            $query->where('industry', $conditions['industry']);
        }

        // 智能排序
        $sort = $conditions['sort'] ?? 'recommend';
        switch ($sort) {
            case 'updated':
                $query->order('update_time', 'desc');
                break;
            case 'rating':
                $query->order('rating_avg', 'desc');
                break;
            case 'downloads':
                $query->order('install_count', 'desc');
                break;
            default: // recommend
                $query->order('is_featured', 'desc')->order('install_count', 'desc');
                break;
        }

        $page = $conditions['page'] ?? 1;
        $pageSize = $conditions['page_size'] ?? 20;
        $result = $query->paginate($pageSize, false, ['page' => $page]);

        return [
            'list' => $result->items(),
            'total' => $result->total(),
            'page' => $result->currentPage(),
            'page_size' => $result->listRows(),
        ];
    }
}
