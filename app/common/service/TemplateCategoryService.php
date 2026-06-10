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

use app\common\model\TemplateCategory;
use think\facade\Cache;
use think\facade\Log;

/**
 * 模板分类服务 (V2.9.20 B-1)
 * 五级缓存体系：memory → request → cache → DB → fallback
 */
class TemplateCategoryService
{
    /** 内存缓存（单请求内） */
    protected static array $memoryCache = [];

    /** request级缓存标识 */
    protected static bool $requestLoaded = false;
    protected static array $requestCache = [];

    /** Cache标签 */
    protected const CACHE_TAG = 'template_category';
    protected const CACHE_KEY_TREE = 'template_category_tree';
    protected const CACHE_KEY_LIST = 'template_category_list';
    protected const CACHE_TTL = 3600;

    /**
     * 获取分类树（带五级缓存）
     *
     * @param string|null $type 维度过滤(content_model/industry/style)
     * @return array
     */
    public static function getTree(?string $type = null): array
    {
        $cacheKey = $type ? "tree_{$type}" : 'tree_all';

        // L1: Memory缓存（单次请求内重复调用）
        if (isset(self::$memoryCache[$cacheKey])) {
            return self::$memoryCache[$cacheKey];
        }

        // L2: Request级缓存（同请求内已加载全量数据）
        if (self::$requestLoaded && isset(self::$requestCache[$cacheKey])) {
            self::$memoryCache[$cacheKey] = self::$requestCache[$cacheKey];
            return self::$requestCache[$cacheKey];
        }

        // L3: Cache标签缓存（跨请求持久化）
        // ThinkPHP tag只支持set/clear，get需直接读取
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            self::$memoryCache[$cacheKey] = $cached;
            return $cached;
        }

        // L4: 数据库查询
        try {
            $data = self::fetchFromDb($type);
        } catch (\Exception $e) {
            Log::error('TemplateCategoryService DB查询失败: ' . $e->getMessage());
            $data = [];
        }

        // L5: Fallback（空数据兜底）
        if (empty($data)) {
            $data = self::getFallbackData($type);
        }

        // 写入L3缓存
        Cache::tag(self::CACHE_TAG)->set($cacheKey, $data, self::CACHE_TTL);

        // 写入L1/L2
        self::$memoryCache[$cacheKey] = $data;
        self::$requestCache[$cacheKey] = $data;

        return $data;
    }

    /**
     * 获取按维度分组的分类
     */
    public static function getGrouped(): array
    {
        $cacheKey = 'grouped';

        if (isset(self::$memoryCache[$cacheKey])) {
            return self::$memoryCache[$cacheKey];
        }

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            self::$memoryCache[$cacheKey] = $cached;
            return $cached;
        }

        $result = [];
        foreach (TemplateCategory::$typeMap as $type => $label) {
            $result[$type] = [
                'label' => $label,
                'items' => self::getTree($type),
            ];
        }

        Cache::tag(self::CACHE_TAG)->set($cacheKey, $result, self::CACHE_TTL);
        self::$memoryCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * 获取扁平列表（用于下拉选择）
     */
    public static function getFlatList(?string $type = null): array
    {
        $tree = self::getTree($type);
        return self::flattenTree($tree);
    }

    /**
     * 从数据库查询分类数据
     */
    protected static function fetchFromDb(?string $type = null): array
    {
        $query = TemplateCategory::where('status', TemplateCategory::STATUS_ENABLED)
            ->order('sort', 'asc');

        if ($type) {
            $query->where('type', $type);
        }

        $list = $query->select()->toArray();
        return self::buildTree($list, 0);
    }

    /**
     * 构建树形结构
     */
    protected static function buildTree(array $data, int $parentId): array
    {
        $tree = [];
        foreach ($data as $item) {
            if ((int) $item['parent_id'] === $parentId) {
                $children = self::buildTree($data, (int) $item['id']);
                if (!empty($children)) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }

    /**
     * 扁平化树
     */
    protected static function flattenTree(array $tree, int $depth = 0): array
    {
        $result = [];
        foreach ($tree as $node) {
            $node['depth'] = $depth;
            $children = $node['children'] ?? [];
            unset($node['children']);
            $result[] = $node;
            if (!empty($children)) {
                $result = array_merge($result, self::flattenTree($children, $depth + 1));
            }
        }
        return $result;
    }

    /**
     * 兜底数据（当数据库为空时使用）
     */
    protected static function getFallbackData(?string $type = null): array
    {
        $fallback = [
            'content_model' => [
                ['id' => 1, 'name' => '通用型', 'code' => 'cat_model_general', 'children' => []],
                ['id' => 2, 'name' => '文章型', 'code' => 'cat_model_article', 'children' => []],
                ['id' => 3, 'name' => '产品型', 'code' => 'cat_model_product', 'children' => []],
            ],
            'industry' => [
                ['id' => 7, 'name' => '企业官网', 'code' => 'cat_ind_enterprise', 'children' => []],
                ['id' => 8, 'name' => '电商', 'code' => 'cat_ind_ecommerce', 'children' => []],
            ],
            'style' => [
                ['id' => 15, 'name' => '简约现代', 'code' => 'cat_style_minimal', 'children' => []],
                ['id' => 16, 'name' => '科技时尚', 'code' => 'cat_style_tech', 'children' => []],
            ],
        ];

        if ($type && isset($fallback[$type])) {
            return $fallback[$type];
        }

        if (!$type) {
            $all = [];
            foreach ($fallback as $items) {
                $all = array_merge($all, $items);
            }
            return $all;
        }

        return [];
    }

    /**
     * 清除缓存（数据变更后调用）
     */
    public static function clearCache(): void
    {
        self::$memoryCache = [];
        self::$requestCache = [];
        self::$requestLoaded = false;
        Cache::tag(self::CACHE_TAG)->clear();
    }

    /**
     * 预加载全量数据到request缓存（减少多次查询）
     */
    public static function preload(): void
    {
        if (self::$requestLoaded) {
            return;
        }

        // 一次性加载所有维度数据
        foreach (array_keys(TemplateCategory::$typeMap) as $type) {
            $tree = self::fetchFromDb($type);
            self::$requestCache["tree_{$type}"] = $tree;
            Cache::tag(self::CACHE_TAG)->set("tree_{$type}", $tree, self::CACHE_TTL);
        }

        self::$requestLoaded = true;
    }

    /**
     * 按类型获取分类列表
     */
    public static function getByType(string $type): array
    {
        return self::getFlatList($type);
    }

    /**
     * 获取全部分组数据
     */
    public static function getAllGrouped(): array
    {
        return self::getGrouped();
    }

    /**
     * 创建分类
     */
    public static function createCategory(array $data): TemplateCategory
    {
        $category = TemplateCategory::create($data);
        self::clearCache();
        return $category;
    }

    /**
     * 更新分类
     */
    public static function updateCategory(int $id, array $data): bool
    {
        $category = TemplateCategory::find($id);
        if (empty($category)) {
            throw new \RuntimeException('分类不存在');
        }
        $category->save($data);
        self::clearCache();
        return true;
    }

    /**
     * 删除分类
     */
    public static function deleteCategory(int $id): bool
    {
        $category = TemplateCategory::find($id);
        if (empty($category)) {
            throw new \RuntimeException('分类不存在');
        }

        // 检查是否有模板关联
        $count = \app\common\model\TemplateCategoryMap::where('category_id', $id)->count();
        if ($count > 0) {
            throw new \RuntimeException('该分类下存在模板，无法删除');
        }

        $category->delete();
        self::clearCache();
        return true;
    }

    /**
     * V2.9.21 D-3: 获取模板的主分类信息
     */
    public static function getPrimaryCategory(int $templateId): ?array
    {
        $categoryId = \app\common\model\TemplateCategoryMap::getPrimaryCategoryId($templateId);
        if (!$categoryId) {
            return null;
        }
        $category = TemplateCategory::find($categoryId);
        return $category ? $category->toArray() : null;
    }

    /**
     * V2.9.21 D-3: 获取模板的分类列表（含 is_primary/confidence 元数据）
     */
    public static function getTemplateCategories(int $templateId): array
    {
        $maps = \app\common\model\TemplateCategoryMap::getCategoriesWithMeta($templateId);
        if (empty($maps)) {
            return [];
        }

        $categoryIds = array_column($maps, 'category_id');
        $categories = TemplateCategory::whereIn('id', $categoryIds)
            ->column('name', 'id');

        $result = [];
        foreach ($maps as $map) {
            $result[] = [
                'category_id' => $map['category_id'],
                'name'        => $categories[$map['category_id']] ?? '未知分类',
                'is_primary'  => $map['is_primary'],
                'confidence'  => $map['confidence'],
                'created_by'  => $map['created_by'],
            ];
        }
        return $result;
    }

    /**
     * V2.9.21 D-3: 设置模板分类（增强版，支持 is_primary/confidence）
     */
    public static function setTemplateCategories(int $templateId, array $categories, int $createdBy = 1): void
    {
        \app\common\model\TemplateCategoryMap::setTemplateCategories($templateId, $categories, $createdBy);
    }

    /**
     * V2.9.21 D-3: 获取同分类推荐模板（按置信度排序）
     */
    public static function getRelatedTemplates(int $templateId, int $limit = 5): array
    {
        $categoryId = \app\common\model\TemplateCategoryMap::getPrimaryCategoryId($templateId);
        if (!$categoryId) {
            return [];
        }

        $relatedIds = \app\common\model\TemplateCategoryMap::where('category_id', $categoryId)
            ->where('template_id', '<>', $templateId)
            ->order('is_primary', 'desc')
            ->order('confidence', 'desc')
            ->limit($limit)
            ->column('template_id');

        if (empty($relatedIds)) {
            return [];
        }

        return \app\common\model\TemplateStore::whereIn('id', $relatedIds)
            ->where('status', 1)
            ->select()
            ->toArray();
    }
}
