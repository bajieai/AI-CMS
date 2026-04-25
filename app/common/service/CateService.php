<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Cate;
use think\facade\Cache;
use think\facade\Config;

/**
 * 分类服务
 */
class CateService
{
    /**
     * 构建树形结构
     */
    public function getTree(array $list, int $parentId = 0, int $level = 0): array
    {
        $tree = [];
        foreach ($list as $item) {
            if ((int) $item['parent_id'] === $parentId) {
                $item['level'] = $level;
                $item['children'] = $this->getTree($list, (int) $item['id'], $level + 1);
                $tree[] = $item;
            }
        }
        return $tree;
    }

    /**
     * 获取分类列表（前台模板标签使用）
     */
    public function getCatelist(string $type = '', int $limit = 100, int $parentId = 0)
    {
        $cacheKey = 'cate_list_' . md5($type . '_' . $limit . '_' . $parentId);
        $cacheTag = Config::get('cache.tag.cate', 'i8j_cate');

        $result = Cache::tag($cacheTag)->get($cacheKey);
        if ($result !== null) {
            return $result;
        }

        $typeMap = [
            'product' => 1,
            'case' => 2,
            'news' => 3,
            'download' => 4,
            'job' => 5,
            'page' => 6,
        ];

        $query = Cate::where('status', 1);

        if (!empty($type) && isset($typeMap[$type])) {
            $query->where('type', $typeMap[$type]);
        }

        if ($parentId >= 0) {
            $query->where('parent_id', $parentId);
        }

        $result = $query->order('sort', 'asc')->order('id', 'asc')->limit($limit)->select();
        Cache::tag($cacheTag)->set($cacheKey, $result, 3600);
        return $result;
    }
}
