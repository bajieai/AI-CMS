<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateStoreCategory;
use think\facade\Cache;

/**
 * 模板分类标签升级 — V2.9.33 T5-4
 * 二级分类 + 标签类型管理
 */
class TemplateCategoryUpgradeService
{
    private const CACHE_TAG = 'template_category';

    /**
     * 获取分类树（含二级分类）
     */
    public function getCategoryTree(): array
    {
        return Cache::remember('category_tree', function () {
            $categories = TemplateStoreCategory::order('sort_order', 'asc')->select()->toArray();

            $tree = [];
            $children = [];

            foreach ($categories as $cat) {
                if ($cat['parent_id'] == 0) {
                    $cat['children'] = [];
                    $tree[$cat['id']] = $cat;
                } else {
                    $children[$cat['parent_id']][] = $cat;
                }
            }

            foreach ($children as $parentId => $childList) {
                if (isset($tree[$parentId])) {
                    $tree[$parentId]['children'] = $childList;
                }
            }

            return array_values($tree);
        }, 3600);
    }

    /**
     * 保存分类（支持二级）
     */
    public function saveCategory(array $data, int $id = 0): array
    {
        $categoryData = [
            'name' => $data['name'] ?? '',
            'parent_id' => (int) ($data['parent_id'] ?? 0),
            'level' => (int) ($data['parent_id'] ?? 0) > 0 ? 2 : 1,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'icon' => $data['icon'] ?? '',
            'description' => $data['description'] ?? '',
        ];

        if ($id > 0) {
            TemplateStoreCategory::where('id', $id)->update($categoryData);
        } else {
            $cat = new TemplateStoreCategory($categoryData);
            $cat->save();
            $id = $cat->id;
        }

        Cache::clear();
        return ['success' => true, 'id' => $id];
    }

    /**
     * 获取分类统计
     */
    public function getCategoryStats(): array
    {
        return Cache::remember('category_stats', function () {
            $categories = TemplateStoreCategory::select()->toArray();
            $stats = [];

            foreach ($categories as $cat) {
                $count = \app\common\model\TemplateStore::where('category_id', $cat['id'])->count();
                $stats[] = [
                    'id' => $cat['id'],
                    'name' => $cat['name'],
                    'level' => $cat['level'] ?? 1,
                    'template_count' => $count,
                ];
            }

            return $stats;
        }, 3600);
    }
}
