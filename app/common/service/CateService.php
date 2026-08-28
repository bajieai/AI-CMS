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

use app\common\model\Cate;
use app\common\support\ContentTypeMap;
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
     * V2.9.50: 渲染分类树 HTML（Bootstrap list-group 结构）
     *
     * 背景：原逻辑在 CateController::renderCateTree（protected），仅列表页可用。
     * V2.9.49 发现 detail_info.html 等模型专属详情模板的侧栏也引用了 {$cate_tree_html|raw}，
     * 但详情页控制器不传该变量，debug 模式下 Undefined variable 直接 500。
     * 故迁移到服务层供列表页/详情页共用。
     */
    public function renderTreeHtml(array $cates, string $typeSlug, int $cateId): string
    {
        $html = '';
        foreach ($cates as $cate) {
            $hasChildren = !empty($cate['children']);
            $isActive = ($cateId == $cate['id']);
            $isParentActive = false;
            if ($hasChildren && !$isActive) {
                foreach ($cate['children'] as $child) {
                    if ($cateId == $child['id']) {
                        $isParentActive = true;
                        break;
                    }
                }
            }
            $showChildren = $isActive || $isParentActive;
            $padding = ($cate['level'] * 1.2 + 1);
            // V2.9.44: 优先使用单段URL /{seo_url}，无 seo_url 时回退到 ?cate_id=X
            $cateUrl = '';
            if (!empty($cate['seo_url'])) {
                $cateUrl = '/' . $cate['seo_url'];
            } else {
                $cateUrl = '/' . $typeSlug . '?cate_id=' . $cate['id'];
            }
            $html .= '<a href="' . $cateUrl . '" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center ' . ($isActive ? 'active' : '') . '" style="padding-left:' . $padding . 'rem">';
            $html .= '<span>' . htmlspecialchars((string) $cate['name']) . '</span>';
            if ($hasChildren) {
                $html .= '<i class="bi bi-chevron-' . ($showChildren ? 'down' : 'right') . ' small cate-toggle" data-target="cate-children-' . $cate['id'] . '"></i>';
            }
            $html .= '</a>';
            if ($hasChildren) {
                $html .= '<div class="cate-children" id="cate-children-' . $cate['id'] . '" style="' . (!$showChildren ? 'display:none' : '') . '">';
                $html .= $this->renderTreeHtml($cate['children'], $typeSlug, $cateId);
                $html .= '</div>';
            }
        }
        return $html;
    }

    /**
     * 获取分类列表（前台模板标签使用）
     */
    public function getCatelist(string $type = '', int $limit = 100, int $parentId = 0)
    {
        $cacheKey = 'cate_list_' . md5($type . '_' . $limit . '_' . $parentId);
        $cacheTag = Config::get('cache.tag.cate', 'cms_cate');

        $result = Cache::get($cacheKey);
        if ($result !== null) {
            return $result;
        }

        $typeMap = ContentTypeMap::typeBySlug();

        $query = Cate::where('status', 1);

        if (!empty($type) && isset($typeMap[$type])) {
            $query->where('type', $typeMap[$type]);
        }

        if ($parentId >= 0) {
            $query->where('parent_id', $parentId);
        }

        $result = $query->order('sort', 'asc')->order('id', 'asc')->limit($limit)->select();
        Cache::set($cacheKey, $result, 3600);
        return $result;
    }
}
