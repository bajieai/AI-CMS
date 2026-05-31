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

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\model\Cate;
use app\common\model\Content;
use app\common\service\CateService;
use app\common\service\seo\SchemaMarkupService;

/**
 * 前台分类控制器
 */
class CateController extends FrontBaseController
{
    /**
     * 分类列表页
     * 路由：/product, /news 等（通过append传入type参数）
     */
    public function listing()
    {
        $typeSlug = $this->request->param('type', 'product');
        $typeMap = ['product' => 1, 'case' => 2, 'news' => 3, 'download' => 4, 'job' => 5, 'page' => 6];
        $type = $typeMap[$typeSlug] ?? 1;
        $cateId = (int) $this->request->param('cate_id', 0);

        // 获取分类列表（树形结构）
        $cateService = new CateService();
        $cateList = $cateService->getCatelist($typeSlug, 100, 0);
        $cates = $cateService->getTree($cateList->toArray());

        // 获取内容列表
        $query = Content::where('status', 2)->where('type', $type);
        if ($cateId > 0) {
            $query->where('cate_id', $cateId);
        }
        $list = $query->order('id', 'desc')->paginate(12);

        // 获取当前分类SEO信息
        $currentCate = null;
        if ($cateId > 0) {
            $currentCate = Cate::find($cateId);
        }

        // V2.9.15: 栏目页 Schema.org 结构化标记 (BreadcrumbList + WebPage)
        $schemaService = new SchemaMarkupService();
        $breadcrumbs = [['name' => '首页', 'url' => request()->domain()]];
        if ($currentCate) {
            $breadcrumbs[] = ['name' => $currentCate->name, 'url' => request()->url(true)];
        }
        $breadcrumbSchema = $schemaService->generateBreadcrumb($breadcrumbs);
        $webPageSchema = $schemaService->generateWebPage([
            'title'       => $currentCate ? $currentCate->name : '内容列表',
            'description' => $currentCate ? ($currentCate->description ?: '') : '',
            'url'         => request()->url(true),
        ]);
        $schemaMarkup = $schemaService->toJsonLd([$breadcrumbSchema, $webPageSchema]);

        $this->assign([
            'type' => $type,
            'cate_id' => $cateId,
            'cates' => $cates,
            'cate_tree_html' => $this->renderCateTree($cates, $typeSlug, $cateId),
            'list' => $list,
            'type_slug' => $typeSlug,
            'current_cate' => $currentCate,
            'schema_markup' => $schemaMarkup,
        ]);

        return $this->view('/list');
    }

    /**
     * 递归渲染分类树HTML（避免模板递归include导致编译死循环）
     */
    protected function renderCateTree(array $cates, string $typeSlug, int $cateId): string
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
            $html .= '<a href="/' . $typeSlug . '?cate_id=' . $cate['id'] . '" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center ' . ($isActive ? 'active' : '') . '" style="padding-left:' . $padding . 'rem">';
            $html .= '<span>' . htmlspecialchars((string) $cate['name']) . '</span>';
            if ($hasChildren) {
                $html .= '<i class="bi bi-chevron-' . ($showChildren ? 'down' : 'right') . ' small cate-toggle" data-target="cate-children-' . $cate['id'] . '"></i>';
            }
            $html .= '</a>';
            if ($hasChildren) {
                $html .= '<div class="cate-children" id="cate-children-' . $cate['id'] . '" style="' . (!$showChildren ? 'display:none' : '') . '">';
                $html .= $this->renderCateTree($cate['children'], $typeSlug, $cateId);
                $html .= '</div>';
            }
        }
        return $html;
    }
}
