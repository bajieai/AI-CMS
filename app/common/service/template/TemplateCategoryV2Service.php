<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateCategoryV2;
use think\facade\Cache;

/**
 * 模板分类V2服务 - V2.9.29 Sprint T-4
 */
class TemplateCategoryV2Service
{
    private const CACHE_TAG = 'tpl_category_v2';

    public function getAll(): array
    {
        try {
            return Cache::remember('tpl_cat_v2_all', function () {
                return TemplateCategoryV2::order('sort', 'asc')->select()->toArray();
            }, 3600);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getByDimension(string $dimension): array
    {
        return Cache::remember('tpl_cat_v2_' . $dimension, function () use ($dimension) {
            return TemplateCategoryV2::where('dimension', $dimension)
                ->where('status', 1)
                ->order('sort', 'asc')
                ->select()->toArray();
        }, 3600);
    }

    public function getTree(string $dimension = ''): array
    {
        $list = $dimension ? $this->getByDimension($dimension) : $this->getAll();
        return $this->buildTree($list, 0);
    }

    private function buildTree(array $list, int $parentId): array
    {
        $tree = [];
        foreach ($list as $item) {
            if ((int) $item['parent_id'] === $parentId) {
                $item['children'] = $this->buildTree($list, (int) $item['id']);
                $tree[] = $item;
            }
        }
        return $tree;
    }
}
