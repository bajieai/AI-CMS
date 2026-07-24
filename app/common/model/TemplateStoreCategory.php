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

namespace app\common\model;

use think\Model;
use think\facade\Cache;

/**
 * 模板商店分类模型 - V2.9.12新增
 * V2.9.26 P-2: 增加多级分类支持（parent_id/level）+ SEO元数据
 */
class TemplateStoreCategory extends Model
{
    protected $name = 'template_store_category';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'sort'       => 'integer',
        'is_enabled' => 'integer',
        'is_visible' => 'integer',
        'parent_id'  => 'integer',
        'level'      => 'integer',
    ];

    public const CACHE_TAG = 'template_category';

    /**
     * 获取是否可见文本
     */
    public function getIsVisibleTextAttr($value, $data): string
    {
        return ($data['is_visible'] ?? 1) ? '显示' : '隐藏';
    }

    /**
     * 关联父分类
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * 关联子分类
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->order('sort', 'asc');
    }

    /**
     * 关联模板
     */
    public function templates()
    {
        return $this->hasMany(TemplateStore::class, 'category_id');
    }

    /**
     * 查询作用域 — 只查询启用的分类
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', 1);
    }

    /**
     * 查询作用域 — 只查询前台可见的分类
     */
    public function scopeVisible($query)
    {
        return $query->where('is_enabled', 1)->where('is_visible', 1);
    }

    /**
     * 查询作用域 — 按排序字段升序
     */
    public function scopeSorted($query)
    {
        return $query->order('sort', 'asc')->order('id', 'asc');
    }

    /**
     * V2.9.26 P-2: 获取分类树形结构
     *
     * @param int $parentId 根节点ID
     * @param bool $onlyVisible 是否只查可见
     * @return array 树形数组
     */
    public static function getTree(int $parentId = 0, bool $onlyVisible = false): array
    {
        return Cache::remember('category_tree_' . $parentId . '_' . $onlyVisible, function () use ($parentId, $onlyVisible) {
            $query = self::order('sort', 'asc')->order('id', 'asc');
            if ($onlyVisible) {
                $query->where('is_enabled', 1)->where('is_visible', 1);
            }
            $all = $query->select()->toArray();
            return self::buildTree($all, $parentId);
        }, 3600);
    }

    /**
     * 递归构建树
     */
    protected static function buildTree(array $items, int $parentId = 0): array
    {
        $tree = [];
        foreach ($items as $item) {
            if ((int)$item['parent_id'] === $parentId) {
                $children = self::buildTree($items, (int)$item['id']);
                if (!empty($children)) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }

    /**
     * V2.9.26 P-2: 获取分类的祖先链（用于面包屑）
     */
    public static function getBreadcrumbs(int $categoryId): array
    {
        $breadcrumbs = [];
        $current = self::find($categoryId);
        while ($current) {
            array_unshift($breadcrumbs, [
                'id'   => $current->id,
                'name' => $current->name,
                'slug' => $current->slug,
            ]);
            if ($current->parent_id > 0) {
                $current = self::find($current->parent_id);
            } else {
                break;
            }
        }
        return $breadcrumbs;
    }

    /**
     * V2.9.26 P-2: 获取分类的所有子孙ID
     */
    public static function getDescendantIds(int $categoryId): array
    {
        $ids = [$categoryId];
        $children = self::where('parent_id', $categoryId)->column('id');
        foreach ($children as $childId) {
            $ids = array_merge($ids, self::getDescendantIds((int)$childId));
        }
        return $ids;
    }

    /**
     * V2.9.26 P-2: 获取可用于下拉选择的扁平列表（带层级前缀）
     */
    public static function getFlatOptions(int $excludeId = 0): array
    {
        $tree = self::getTree(0, false);
        $options = [['id' => 0, 'name' => '— 顶级分类 —']];
        self::flattenOptions($tree, 0, $excludeId, $options);
        return $options;
    }

    protected static function flattenOptions(array $tree, int $depth, int $excludeId, array &$options): void
    {
        $prefix = str_repeat('　', $depth);
        foreach ($tree as $node) {
            if ((int)$node['id'] === $excludeId) continue;
            $options[] = ['id' => $node['id'], 'name' => $prefix . ($depth > 0 ? '├ ' : '') . $node['name']];
            if (!empty($node['children'])) {
                self::flattenOptions($node['children'], $depth + 1, $excludeId, $options);
            }
        }
    }

    /**
     * V2.9.26 P-2: 获取分类的SEO数据
     */
    public function getSeoData(): array
    {
        return [
            'meta_title'       => $this->meta_title ?: $this->name,
            'meta_description' => $this->meta_description ?: $this->description,
            'meta_keywords'    => $this->meta_keywords,
        ];
    }
}
